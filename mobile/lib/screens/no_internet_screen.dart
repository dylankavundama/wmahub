import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../utils/app_theme.dart';

class NoInternetScreen extends StatelessWidget {
  final VoidCallback onRetry;
  const NoInternetScreen({super.key, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      body: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 40),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Animated Icon
            Container(
                  padding: const EdgeInsets.all(30),
                  decoration: BoxDecoration(
                    color: AppTheme.primaryColor.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.wifi_off_rounded,
                    size: 80,
                    color: AppTheme.primaryColor,
                  ),
                )
                .animate(
                  onPlay: (controller) => controller.repeat(reverse: true),
                )
                .scale(
                  begin: const Offset(0.9, 0.9),
                  end: const Offset(1.1, 1.1),
                  duration: 2.seconds,
                  curve: Curves.easeInOut,
                )
                .fadeIn(),

            const SizedBox(height: 40),

            // Main Text
            const Text(
              'Oups ! Pas d\'internet',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.w900,
                color: Colors.white,
                letterSpacing: 1,
              ),
            ).animate().fadeIn(delay: 300.ms).slideY(begin: 0.2),

            const SizedBox(height: 16),

            // Subtext
            const Text(
              'Il semble que vous soyez hors ligne. Vérifiez votre connexion pour continuer l\'aventure WMA.',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 15,
                color: AppTheme.textGrey,
                height: 1.5,
              ),
            ).animate().fadeIn(delay: 500.ms).slideY(begin: 0.2),

            const SizedBox(height: 48),

            // Retry Button
            SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: ElevatedButton(
                    onPressed: onRetry,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.primaryColor,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                      elevation: 0,
                    ),
                    child: const Text(
                      'RÉESSAYER',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        letterSpacing: 1.2,
                      ),
                    ),
                  ),
                )
                .animate()
                .fadeIn(delay: 700.ms)
                .scale(begin: const Offset(0.8, 0.8)),

            const SizedBox(height: 20),

            TextButton(
              onPressed: onRetry,
              child: const Text(
                'Paramètres Wi-Fi',
                style: TextStyle(color: AppTheme.textGrey),
              ),
            ).animate().fadeIn(delay: 900.ms),
          ],
        ),
      ),
    );
  }
}
