import 'package:flutter/material.dart';
import 'dart:ui';
import 'package:url_launcher/url_launcher.dart';
import '../services/auth_service.dart';
import '../utils/app_theme.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'contract_screen.dart';

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

  Future<void> _handleGoogleLogin() async {
    setState(() => _isLoading = true);
    try {
      final result = await _authService.loginWithGoogle();
      if (mounted) {
        setState(() => _isLoading = false);
        if (result != null && result['success'] == true) {
          final userId = result['user']?['id'] ?? 0;
          final prefs = await SharedPreferences.getInstance();
          final hasSigned = prefs.getBool('contract_signed_$userId') ?? false;

          if (hasSigned) {
            widget.onLoginSuccess();
          } else {
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
        } else {
          final message = result?['message'] ?? 'Échec de la connexion';
          _showErrorDialog(message);
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        _showErrorDialog('Une erreur est survenue: $e');
      }
    }
  }

  void _showErrorDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: AppTheme.cardColor,
        title: const Text(
          'Accès Refusé',
          style: TextStyle(
            color: AppTheme.primaryColor,
            fontWeight: FontWeight.bold,
          ),
        ),
        content: Text(message, style: const TextStyle(color: Colors.white70)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text(
              'FERMER',
              style: TextStyle(color: AppTheme.textGrey),
            ),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              _launchURL('https://wmahub.com/auth/login.php');
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryColor,
            ),
            child: const Text('DEVENIR ARTISTE'),
          ),
        ],
      ),
    );
  }

  Future<void> _launchURL(String url) async {
    if (!await launchUrl(
      Uri.parse(url),
      mode: LaunchMode.externalApplication,
    )) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Impossible d\'ouvrir le lien')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        fit: StackFit.expand,
        children: [
          // Background Image
          Image.network(
            'https://wmahub.com/asset/aspi.jpg',
            fit: BoxFit.cover,
            errorBuilder: (context, error, stackTrace) =>
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
                            : ElevatedButton(
                                onPressed: _termsAccepted
                                    ? _handleGoogleLogin
                                    : () {
                                        ScaffoldMessenger.of(
                                          context,
                                        ).showSnackBar(
                                          const SnackBar(
                                            content: Text(
                                              "Vous devez accepter les conditions pour continuer.",
                                            ),
                                            backgroundColor: Colors.redAccent,
                                          ),
                                        );
                                      },
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.white,
                                  foregroundColor: Colors.black,
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 24,
                                    vertical: 16,
                                  ),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(16),
                                  ),
                                  elevation: 0,
                                ),
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Image.network(
                                      'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Google_\"G\"_Logo.svg/1200px-Google_\"G\"_Logo.svg.png',
                                      height: 24,
                                      errorBuilder: (c, e, s) => const Icon(
                                        Icons.g_mobiledata,
                                        color: Colors.blue,
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
