import 'package:flutter/material.dart';
import 'dart:ui';
import 'dart:io';
import 'package:flutter/services.dart';
import '../services/auth_service.dart';
import '../utils/app_theme.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:sign_in_with_apple/sign_in_with_apple.dart';
import 'package:cached_network_image/cached_network_image.dart';

import 'contract_screen.dart';
import 'pending_validation_screen.dart';
import 'agent_tasks_screen.dart';

class LoginScreen extends StatefulWidget {
  final VoidCallback onLoginSuccess;
  const LoginScreen({super.key, required this.onLoginSuccess});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _authService = AuthService();
  bool _isLoading = false;
  bool _termsAccepted = false;

  @override
  void initState() {
    super.initState();
    _checkExistingUserWithoutRole();
  }

  void _checkExistingUserWithoutRole() async {
    final user = await _authService.getCurrentUser();
    if (user != null) {
      final role = user['role']?.toString().toLowerCase().trim();
      if (role == null || role.isEmpty || role == 'null') {
        final userId = int.tryParse(user['id']?.toString() ?? '0') ?? 0;
        if (userId > 0) {
          WidgetsBinding.instance.addPostFrameCallback((_) {
            _showRoleSelectionBottomSheet(userId);
          });
        }
      }
    }
  }

  void _handlePostLoginRouting(Map<String, dynamic> user) async {
    final userId = int.tryParse(user['id']?.toString() ?? '0') ?? 0;
    final role = user['role']?.toString().toLowerCase().trim();
    final isActive = int.tryParse(user['is_active']?.toString() ?? '0') ?? 0;

    // Si pas de rôle (nouvel utilisateur), on affiche le pop-up de sélection de rôle
    if (role == null || role.isEmpty || role == 'null') {
      _showRoleSelectionBottomSheet(userId);
      return;
    }

    if (role == 'admin' || role == 'administrator' || role == 'superadmin') {
      widget.onLoginSuccess();
      return;
    }

    if (role == 'employe') {
      if (isActive == 0) {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (context) => PendingValidationScreen(
              onLogout: () {
                Navigator.pushReplacement(
                  context,
                  MaterialPageRoute(builder: (context) => LoginScreen(onLoginSuccess: widget.onLoginSuccess)),
                );
              },
            ),
          ),
        );
      } else {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (context) => AgentTasksScreen(
              user: user,
              onLogout: () {
                Navigator.pushReplacement(
                  context,
                  MaterialPageRoute(builder: (context) => LoginScreen(onLoginSuccess: widget.onLoginSuccess)),
                );
              },
            ),
          ),
        );
      }
      return;
    }

    if (role == 'artiste') {
      final prefs = await SharedPreferences.getInstance();
      final hasSigned = prefs.getBool('contract_signed_$userId') ?? false;

      if (hasSigned) {
        widget.onLoginSuccess();
      } else {
        if (!mounted) return;
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (context) => ContractScreen(
              userId: userId,
              onSigned: widget.onLoginSuccess,
            ),
          ),
        );
      }
      return;
    }

    // Par défaut (simple_user, distributeur, etc.)
    widget.onLoginSuccess();
  }

  void _showRoleSelectionBottomSheet(int userId) {
    showModalBottomSheet(
      context: context,
      isDismissible: false,
      enableDrag: false,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) {
        return PopScope(
          canPop: false,
          child: Container(
            decoration: BoxDecoration(
              color: const Color(0xFF141418),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
              border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 48,
                  height: 5,
                  decoration: BoxDecoration(
                    color: Colors.white24,
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
                const SizedBox(height: 24),
                const Text(
                  "Choisissez votre profil",
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 0.5,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  "Comment souhaitez-vous utiliser WMA HUB ?",
                  style: TextStyle(color: AppTheme.textGrey, fontSize: 13),
                ),
                const SizedBox(height: 28),
                _buildRoleOptionItem(
                  title: "Artiste",
                  description: "Distribuez votre musique, suivez vos stats et vos revenus.",
                  icon: Icons.music_note_rounded,
                  color: AppTheme.primaryColor,
                  onTap: () async {
                    HapticFeedback.mediumImpact();
                    Navigator.pop(sheetContext);
                    setState(() => _isLoading = true);
                    final updatedUser = await _authService.updateUserRole(userId, 'artiste');
                    setState(() => _isLoading = false);
                    if (updatedUser != null) {
                      _handlePostLoginRouting(updatedUser);
                    }
                  },
                ),
                const SizedBox(height: 16),
                _buildRoleOptionItem(
                  title: "Utilisateur",
                  description: "Découvrez notre catalogue d'artistes et suivez nos actualités.",
                  icon: Icons.person_rounded,
                  color: Colors.tealAccent,
                  onTap: () async {
                    HapticFeedback.mediumImpact();
                    Navigator.pop(sheetContext);
                    setState(() => _isLoading = true);
                    final updatedUser = await _authService.updateUserRole(userId, 'simple_user');
                    setState(() => _isLoading = false);
                    if (updatedUser != null) {
                      _handlePostLoginRouting(updatedUser);
                    }
                  },
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildRoleOptionItem({
    required String title,
    required String description,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.03),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.white.withValues(alpha: 0.05)),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: color, size: 24),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.white),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    description,
                    style: const TextStyle(color: AppTheme.textGrey, fontSize: 11, height: 1.3),
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right_rounded, color: AppTheme.textGrey),
          ],
        ),
      ),
    );
  }

  Future<void> _handleGoogleLogin() async {
    HapticFeedback.mediumImpact();
    setState(() => _isLoading = true);
    try {
      final result = await _authService.loginWithGoogle();
      if (!mounted) return;
      setState(() => _isLoading = false);
      if (result != null && result['success'] == true) {
        _handlePostLoginRouting(result['user']);
      } else if (result != null) {
        _showErrorDialog(result['message'] ?? 'Échec de la connexion Google');
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        _showErrorDialog('Une erreur est survenue: $e');
      }
    }
  }

  void _requireTerms(VoidCallback action) {
    if (_termsAccepted) {
      action();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Vous devez accepter les conditions.'),
          backgroundColor: Colors.redAccent,
        ),
      );
    }
  }

  // Updated Apple login handling: routes user after successful sign-in
  Future<void> _handleAppleLogin() async {
    HapticFeedback.mediumImpact();
    setState(() => _isLoading = true);
    try {
      final result = await _authService.loginWithApple();
      if (mounted) {
        setState(() => _isLoading = false);
        if (result != null && result['success'] == true) {
          _handlePostLoginRouting(result['user']);
        } else {
          final message = result?['message'] ?? 'Échec de la connexion Apple';
          _showErrorDialog(message);
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        if (e is SignInWithAppleAuthorizationException && e.code == AuthorizationErrorCode.canceled) {
          return;
        }
        _showErrorDialog('Une erreur est survenue lors de la connexion Apple: $e');
      }
    }
  }

  void _showErrorDialog(String message) {
    showAdaptiveDialog(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => AlertDialog.adaptive(
        backgroundColor: AppTheme.cardColor,
        title: const Text(
          'Erreur',
          style: TextStyle(
            color: AppTheme.primaryColor,
            fontWeight: FontWeight.bold,
          ),
        ),
        content: Text(message, style: const TextStyle(color: Colors.white70)),
        actions: [
          TextButton(
            onPressed: () async {
              HapticFeedback.lightImpact();
              Navigator.pop(dialogContext);
              await _authService.logout();
              if (mounted) {
                setState(() => _isLoading = false);
              }
            },
            child: const Text(
              'FERMER',
              style: TextStyle(color: AppTheme.textGrey),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        fit: StackFit.expand,
        children: [
          // Background Image
          CachedNetworkImage(
            imageUrl: 'https://wmahub.com/asset/aspi.jpg',
            fit: BoxFit.cover,
            errorWidget: (context, url, error) =>
                Container(color: Colors.black),
          ),

          // Blur / Overlay
          BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 5, sigmaY: 5),
            child: Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Colors.black.withValues(alpha: 0.3),
                    Colors.black.withValues(alpha: 0.8),
                  ],
                ),
              ),
            ),
          ),

          // Content
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 40),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Spacer(),
                  CircleAvatar(
                    backgroundColor: Colors.transparent,
                    radius: 50,
                    backgroundImage: AssetImage('assets/logo.png'),
                  ),
                  const SizedBox(height: 32),
                  const Text(
                    'WMA UA',
                    style: TextStyle(
                      fontSize: 32,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                      letterSpacing: 4,
                    ),
                  ),
                  const SizedBox(height: 12),
                  const Text(
                    'L\'ESPACE DES ARTISTES',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Colors.white70,
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                      letterSpacing: 1.2,
                    ),
                  ),
                  const Spacer(),

                  // Login Box (Glassmorphism)
                  Container(
                    padding: const EdgeInsets.all(32),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.05),
                      borderRadius: BorderRadius.circular(30),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.1),
                      ),
                    ),
                    child: Column(
                      children: [
                        const Text(
                          'Bienvenue',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 16),

                        // Terms Checkbox
                        Theme(
                          data: ThemeData(
                            unselectedWidgetColor: Colors.white54,
                          ),
                          child: CheckboxListTile(
                            value: _termsAccepted,
                            onChanged: (val) {
                              setState(() => _termsAccepted = val ?? false);
                            },
                            title: const Text(
                              "J'accepte les Conditions d'Utilisation et la Politique de Confidentialité",
                              style: TextStyle(
                                color: Colors.white70,
                                fontSize: 11,
                                height: 1.2,
                              ),
                            ),
                            activeColor: AppTheme.primaryColor,
                            checkColor: Colors.white,
                            controlAffinity: ListTileControlAffinity.leading,
                            contentPadding: EdgeInsets.zero,
                            dense: true,
                          ),
                        ),

                        const SizedBox(height: 24),

                        _isLoading
                            ? const CircularProgressIndicator(
                                color: AppTheme.primaryColor,
                              )
                            : Column(
                                children: [
                                  ElevatedButton(
                                    onPressed: () =>
                                        _requireTerms(_handleGoogleLogin),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: Colors.white,
                                      foregroundColor: Colors.black,
                                      minimumSize: const Size(double.infinity, 52),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(16),
                                      ),
                                      elevation: 0,
                                    ),
                                    child: Row(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        Image.network(
                                          'https://www.google.com/favicon.ico',
                                          height: 22,
                                          errorBuilder: (_, __, ___) => const Icon(
                                            Icons.g_mobiledata,
                                            color: Colors.blue,
                                            size: 28,
                                          ),
                                        ),
                                        const SizedBox(width: 12),
                                        const Text(
                                          'Continuer avec Google',
                                          style: TextStyle(
                                            fontWeight: FontWeight.bold,
                                            fontSize: 16,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  if (Platform.isIOS) ...[
                                    const SizedBox(height: 12),
                                    ElevatedButton(
                                      onPressed: () =>
                                          _requireTerms(_handleAppleLogin),
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: Colors.black,
                                        foregroundColor: Colors.white,
                                        minimumSize: const Size(double.infinity, 52),
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(16),
                                          side: const BorderSide(color: Colors.white24),
                                        ),
                                        elevation: 0,
                                      ),
                                      child: const Row(
                                        mainAxisAlignment: MainAxisAlignment.center,
                                        children: [
                                          Icon(Icons.apple, size: 24),
                                          SizedBox(width: 12),
                                          Text(
                                            'Continuer avec Apple',
                                            style: TextStyle(
                                              fontWeight: FontWeight.bold,
                                              fontSize: 16,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
