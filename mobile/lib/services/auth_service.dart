import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:sign_in_with_apple/sign_in_with_apple.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'wordpress_service.dart';
import 'logging_service.dart';

class AuthService {
  static const String _userKey = 'auth_user';

  /// Client Web OAuth (client_type 3) — requis pour idToken + Firebase Auth sur Android.
  static const String _googleWebClientId =
      '421177402687-pdn6mclmp6en88ccmg4t46e7013bv5r6.apps.googleusercontent.com';

  final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: ['email', 'profile'],
    serverClientId: _googleWebClientId,
  );

  Future<Map<String, dynamic>?> loginWithGoogle() async {
    try {
      final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();
      if (googleUser == null) return null;

      final GoogleSignInAuthentication googleAuth =
          await googleUser.authentication;
      final String? idToken = googleAuth.idToken;

      if (idToken == null) return null;

      try {
        final credential = GoogleAuthProvider.credential(
          accessToken: googleAuth.accessToken,
          idToken: googleAuth.idToken,
        );
        await FirebaseAuth.instance.signInWithCredential(credential);
      } catch (e) {
        LoggingService.error("Erreur Firebase Auth (Google): $e", context: "FirebaseAuth");
      }

      final response = await http
          .post(
            Uri.parse("${WordPressService.apiBaseUrl}/google_auth_mobile.php"),
            body: {'idToken': idToken},
          )
          .timeout(const Duration(seconds: 15));

      final data = json.decode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        final userData = data['user'];
        await _saveUser(userData);
        return {"success": true, "user": userData};
      } else {
        return {
          "success": false,
          "message": data['message'] ?? "Erreur d'authentification",
        };
      }
    } on PlatformException catch (e) {
      LoggingService.error("Google Platform Error: ${e.code} - ${e.message}", context: "GoogleAuth");
      String msg = "Erreur de configuration Google Auth";
      if (e.code == 'channel-error') {
        msg = "Configuration Google manquante (SHA-1 / google-services.json)";
      } else if (e.code == 'network_error') {
        msg = "Problème de connexion réseau.";
      }
      return {"success": false, "message": msg};
    } catch (e) {
      LoggingService.error("Google Login error: $e", context: "GoogleAuth");
      return {"success": false, "message": "Détails: $e"};
    }
  }

  Future<Map<String, dynamic>?> loginWithApple() async {
    try {
      final credential = await SignInWithApple.getAppleIDCredential(
        scopes: [
          AppleIDAuthorizationScopes.email,
          AppleIDAuthorizationScopes.fullName,
        ],
      );

      final response = await http
          .post(
            Uri.parse("${WordPressService.apiBaseUrl}/apple_auth_mobile.php"),
            body: {
              'identityToken': credential.identityToken ?? '',
              'userIdentifier': credential.userIdentifier ?? '',
              'email': credential.email ?? '',
              'name': '${credential.givenName ?? ''} ${credential.familyName ?? ''}'.trim(),
            },
          )
          .timeout(const Duration(seconds: 15));

      final data = json.decode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        final userData = data['user'];
        await _saveUser(userData);
        return {"success": true, "user": userData};
      } else {
        return {
          "success": false,
          "message": data['message'] ?? "Erreur d'authentification Apple",
        };
      }
    } catch (e) {
      LoggingService.error("Apple Login error: $e", context: "AppleAuth");
      return {"success": false, "message": "Erreur: $e"};
    }
  }

  Future<Map<String, dynamic>?> login(String email, String password) async {
    try {
      final response = await http
          .post(
            Uri.parse("${WordPressService.apiBaseUrl}/auth_login.php"),
            body: {'email': email, 'password': password},
          )
          .timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          final userData = data['user'];
          await _saveUser(userData);
          return userData;
        }
      }
    } catch (e) {
      LoggingService.error("Login error: $e", context: "EmailAuth");
    }
    return null;
  }

  Future<Map<String, dynamic>?> updateUserRole(int userId, String role) async {
    try {
      final response = await http
          .post(
            Uri.parse("${WordPressService.apiBaseUrl}/update_user_role.php"),
            body: {'user_id': userId.toString(), 'role': role},
          )
          .timeout(const Duration(seconds: 15));

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
    try {
      await _googleSignIn.signOut();
      await FirebaseAuth.instance.signOut();
    } catch (e) {}
  }

  Future<Map<String, dynamic>?> getCurrentUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userJson = prefs.getString(_userKey);
    if (userJson != null) {
      return json.decode(userJson);
    }
    return null;
  }

  Future<bool> isLoggedIn() async {
    final user = await getCurrentUser();
    return user != null;
  }

  Future<void> _saveUser(Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, json.encode(user));
  }
}
