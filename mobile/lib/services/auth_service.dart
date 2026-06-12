import 'dart:convert';
import 'dart:math';

import 'package:crypto/crypto.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:sign_in_with_apple/sign_in_with_apple.dart';
import 'logging_service.dart';
import 'wordpress_service.dart';

/// Authentification mobile via Firebase Auth (Google, Apple) + sync WordPress.
class AuthService {
  static const String _userKey = 'auth_user';
  static const String _firebaseAuthApi = 'firebase_auth_mobile.php';

  static void _authDebug(String step, [Map<String, Object?>? details]) {
    if (!kDebugMode) return;
    final buffer = StringBuffer('[Auth/Firebase] $step');
    if (details != null) {
      for (final entry in details.entries) {
        buffer.write(' | ${entry.key}=${entry.value}');
      }
    }
    debugPrint(buffer.toString());
    LoggingService.info(buffer.toString(), context: 'FirebaseAuth');
  }

  static String _tokenPreview(String? token) {
    if (token == null || token.isEmpty) return '(vide)';
    if (token.length <= 24) return '${token.length} car.';
    return '${token.substring(0, 12)}…${token.substring(token.length - 8)} (${token.length} car.)';
  }

  String _generateNonce([int length = 32]) {
    const charset =
        '0123456789ABCDEFGHIJKLMNOPQRSTUVXYZabcdefghijklmnopqrstuvwxyz-._';
    final random = Random.secure();
    return List.generate(length, (_) => charset[random.nextInt(charset.length)])
        .join();
  }

  /// Restaure la session Firebase + profil WordPress (au démarrage).
  Future<Map<String, dynamic>?> restoreSession() async {
    final firebaseUser = FirebaseAuth.instance.currentUser;
    if (firebaseUser == null) return null;

    try {
      final idToken = await firebaseUser.getIdToken(true);
      if (idToken == null) return null;
      return _syncFirebaseToken(idToken);
    } catch (e) {
      _authDebug('restoreSession échec', {'erreur': e.toString()});
      return null;
    }
  }

  Future<Map<String, dynamic>?> loginWithGoogle() async {
    _authDebug('Connexion Google → Firebase Auth (signInWithProvider)');
    try {
      final googleProvider = GoogleAuthProvider();
      final userCredential =
          await FirebaseAuth.instance.signInWithProvider(googleProvider);

      return _completeFirebaseSignIn(
        userCredential,
        displayName: userCredential.user?.displayName,
      );
    } on FirebaseAuthException catch (e) {
      return _firebaseError(e);
    } catch (e) {
      final msg = e.toString();
      LoggingService.error('Google/Firebase: $e', context: 'FirebaseAuth');
      if (msg.contains('ApiException: 10') || msg.contains('sign_in_failed')) {
        return {
          'success': false,
          'message':
              'Configuration Google incorrecte (empreinte SHA ou client OAuth Firebase).',
        };
      }
      if (msg.contains('channel-error')) {
        return {
          'success': false,
          'message':
              'Relancez l\'app complètement (arrêt + flutter run), pas un simple hot reload.',
        };
      }
      return {'success': false, 'message': 'Erreur Google: $e'};
    }
  }

  Future<Map<String, dynamic>?> loginWithApple() async {
    _authDebug('Connexion Apple → Firebase Auth');
    try {
      final rawNonce = _generateNonce();
      final nonce = sha256.convert(utf8.encode(rawNonce)).toString();

      final appleCredential = await SignInWithApple.getAppleIDCredential(
        scopes: [
          AppleIDAuthorizationScopes.email,
          AppleIDAuthorizationScopes.fullName,
        ],
        nonce: nonce,
      );

      final oauthCredential = OAuthProvider('apple.com').credential(
        idToken: appleCredential.identityToken,
        rawNonce: rawNonce,
      );

      final userCredential =
          await FirebaseAuth.instance.signInWithCredential(oauthCredential);

      final appleName =
          '${appleCredential.givenName ?? ''} ${appleCredential.familyName ?? ''}'
              .trim();

      return _completeFirebaseSignIn(
        userCredential,
        displayName: appleName.isNotEmpty ? appleName : null,
      );
    } on SignInWithAppleAuthorizationException catch (e) {
      if (e.code == AuthorizationErrorCode.canceled) return null;
      return {'success': false, 'message': 'Connexion Apple annulée ou refusée'};
    } on FirebaseAuthException catch (e) {
      return _firebaseError(e);
    } catch (e) {
      LoggingService.error('Apple/Firebase: $e', context: 'FirebaseAuth');
      return {'success': false, 'message': 'Erreur Apple: $e'};
    }
  }

  Future<Map<String, dynamic>?> _completeFirebaseSignIn(
    UserCredential userCredential, {
    String? displayName,
  }) async {
    final firebaseUser = userCredential.user;
    if (firebaseUser == null) {
      return {'success': false, 'message': 'Utilisateur Firebase introuvable'};
    }

    final idToken = await firebaseUser.getIdToken();
    if (idToken == null) {
      return {'success': false, 'message': 'Token Firebase introuvable'};
    }

    _authDebug('Firebase UID', {
      'uid': firebaseUser.uid,
      'email': firebaseUser.email ?? '(null)',
      'idToken': _tokenPreview(idToken),
    });

    return _syncFirebaseToken(idToken, displayName: displayName);
  }

  Future<Map<String, dynamic>?> _syncFirebaseToken(
    String firebaseIdToken, {
    String? displayName,
  }) async {
    final apiUrl = '${WordPressService.apiBaseUrl}/$_firebaseAuthApi';
    _authDebug('Sync backend WordPress', {'url': apiUrl});

    final body = <String, String>{'firebaseIdToken': firebaseIdToken};
    if (displayName != null && displayName.isNotEmpty) {
      body['displayName'] = displayName;
    }

    final response = await http
        .post(Uri.parse(apiUrl), body: body)
        .timeout(const Duration(seconds: 30));

    Map<String, dynamic> data;
    try {
      data = json.decode(response.body) as Map<String, dynamic>;
    } catch (_) {
      return {
        'success': false,
        'message': 'Réponse serveur invalide (pas du JSON)',
      };
    }

    if (response.statusCode == 200 && data['success'] == true) {
      final userData = data['user'] as Map<String, dynamic>;
      await _saveUser(userData);
      _authDebug('Sync OK', {'userId': userData['id']});
      return {'success': true, 'user': userData};
    }

    await FirebaseAuth.instance.signOut();
    return {
      'success': false,
      'message': data['message'] ?? 'Erreur de synchronisation serveur',
    };
  }

  Map<String, dynamic> _firebaseError(FirebaseAuthException e) {
    _authDebug('FirebaseAuthException', {'code': e.code, 'message': e.message});
    String msg = e.message ?? e.code;
    switch (e.code) {
      case 'invalid-credential':
        msg = 'Identifiants invalides.';
        break;
      case 'account-exists-with-different-credential':
        msg = 'Un compte existe déjà avec un autre mode de connexion.';
        break;
      case 'operation-not-allowed':
        msg =
            'Ce mode de connexion n\'est pas activé dans Firebase Console (Authentication → Sign-in method).';
        break;
    }
    return {'success': false, 'message': msg};
  }

  Future<Map<String, dynamic>?> updateUserRole(int userId, String role) async {
    try {
      final response = await http
          .post(
            Uri.parse("${WordPressService.apiBaseUrl}/update_user_role.php"),
            body: {'user_id': userId.toString(), 'role': role},
          )
          .timeout(const Duration(seconds: 30));

      final data = json.decode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        final userData = data['user'];
        await _saveUser(userData);
        return userData;
      }
    } catch (e) {
      debugPrint("Update role error: $e");
    }
    return null;
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_userKey);
    await FirebaseAuth.instance.signOut();
  }

  Future<Map<String, dynamic>?> getCurrentUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userJson = prefs.getString(_userKey);
    if (userJson != null) {
      return json.decode(userJson) as Map<String, dynamic>;
    }
    return null;
  }

  Future<bool> isLoggedIn() async {
    if (FirebaseAuth.instance.currentUser != null) {
      final local = await getCurrentUser();
      if (local != null) return true;
      final restored = await restoreSession();
      return restored?['success'] == true;
    }
    return (await getCurrentUser()) != null;
  }

  User? get firebaseUser => FirebaseAuth.instance.currentUser;

  Future<void> _saveUser(Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, json.encode(user));
  }
}
