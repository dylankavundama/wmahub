import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../utils/app_theme.dart';
import '../services/auth_service.dart';

class PendingValidationScreen extends StatelessWidget {
  final VoidCallback onLogout;
  const PendingValidationScreen({super.key, required this.onLogout});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              AppTheme.backgroundColor,
              Color(0xFF121216),
            ],
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Spacer(),
                
                // Icon pending animation
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: AppTheme.primaryColor.withValues(alpha: 0.1),
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: AppTheme.primaryColor.withValues(alpha: 0.3),
                      width: 2,
                    ),
                  ),
                  child: const Icon(
                    Icons.hourglass_empty_rounded,
                    size: 64,
                    color: AppTheme.primaryColor,
                  ),
                )
                .animate(onPlay: (controller) => controller.repeat())
                .rotate(duration: 3.seconds, curve: Curves.easeInOutCubic),

                const SizedBox(height: 40),

                const Text(
                  'Compte en attente',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 1,
                    color: Colors.white,
                  ),
                ).animate().fadeIn(duration: 600.ms).slideY(begin: 0.2, end: 0),

                const SizedBox(height: 16),

                const Text(
                  "Votre inscription en tant qu'agent/employé a bien été prise en compte.\n\nPour des raisons de sécurité, votre compte doit être validé manuellement par un administrateur avant de pouvoir accéder à vos missions.",
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: AppTheme.textGrey,
                    fontSize: 14,
                    height: 1.6,
                  ),
                ).animate().fadeIn(delay: 300.ms, duration: 600.ms),

                const Spacer(),

                // Option Card Info
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.02),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.05),
                    ),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.info_outline_rounded, color: AppTheme.primaryColor, size: 24),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Text(
                          "Un e-mail vous sera envoyé automatiquement dès que votre compte sera actif.",
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.7),
                            fontSize: 12,
                            height: 1.4,
                          ),
                        ),
                      ),
                    ],
                  ),
                ).animate().fadeIn(delay: 500.ms),

                const SizedBox(height: 24),

                // Button to logout / switch account
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () async {
                      await AuthService().logout();
                      onLogout();
                    },
                    icon: const Icon(Icons.logout_rounded, color: Colors.white),
                    label: const Text(
                      'SE DÉCONNECTER / CHANGER DE COMPTE',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, letterSpacing: 0.5),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.redAccent.withValues(alpha: 0.2),
                      foregroundColor: Colors.redAccent,
                      elevation: 0,
                      side: BorderSide(color: Colors.redAccent.withValues(alpha: 0.3)),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 18),
                    ),
                  ),
                ).animate().fadeIn(delay: 700.ms),
                
                const SizedBox(height: 32),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
