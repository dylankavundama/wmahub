import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:sign_in_with_apple/sign_in_with_apple.dart';
import 'wordpress_service.dart';

class AuthService {
  static const String _userKey = 'auth_user';

  final GoogleSignIn _googleSignIn = GoogleSignIn(
    clientId:
        '547408646820-eedhgi415138ulb823mhh9uhln8i9f60.apps.googleusercontent.com',
    scopes: ['email', 'profile'],
  );

  Future<Map<String, dynamic>?> loginWithGoogle() async {
    try {
      final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();
      if (googleUser == null) return null;

      final GoogleSignInAuthentication googleAuth =
          await googleUser.authentication;
      final String? idToken = googleAuth.idToken;

      if (idToken == null) return null;

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
      debugPrint("Google Platform Error: ${e.code} - ${e.message}");
      String msg = "Erreur de configuration Google Auth";
      if (e.code == 'channel-error') {
        msg = "Configuration Google manquante (SHA-1 / google-services.json)";
      } else if (e.code == 'network_error') {
        msg = "Problème de connexion réseau.";
      }
      return {"success": false, "message": msg};
    } catch (e) {
      debugPrint("Google Login error: $e");
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
      debugPrint("Apple Login error: $e");
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
      debugPrint("Login error: $e");
    }
    return null;
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_userKey);
    try {
      await _googleSignIn.signOut();
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
