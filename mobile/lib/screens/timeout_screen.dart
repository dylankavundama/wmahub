import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:url_launcher/url_launcher.dart';
import '../utils/app_theme.dart';

class TimeoutScreen extends StatelessWidget {
  final VoidCallback onRetry;
  const TimeoutScreen({super.key, required this.onRetry});

  String? _encodeQueryParameters(Map<String, String> params) {
    return params.entries
        .map((MapEntry<String, String> e) =>
            '${Uri.encodeComponent(e.key)}=${Uri.encodeComponent(e.value)}')
        .join('&');
  }

  Future<void> _contactSupport(BuildContext context) async {
    final Uri emailLaunchUri = Uri(
      scheme: 'mailto',
      path: 'nextbytech1@gmail.com',
      query: _encodeQueryParameters(<String, String>{
        'subject': '[WMA Hub] Support Technique - Problème de Connexion (Timeout)',
        'body': 'Bonjour l\'équipe Support,\n\n'
            'Je rencontre un problème de connexion récurrent avec l\'application WMA Hub (Le serveur ne répond pas).\n\n'
            '--- Détails Techniques ---\n'
            '- Erreur : TimeoutException\n'
            '- Écran : Écran de Timeout (Le serveur ne répond pas)\n'
            '- Horodatage : ${DateTime.now().toLocal()}\n'
            '---------------------------\n\n'
            'Pouvez-vous m\'aider s\'il vous plaît ?',
      }),
    );

    try {
      if (await launchUrl(emailLaunchUri)) {
        // Successfully launched
      } else {
        throw 'Launch failed';
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Impossible d\'ouvrir l\'application email. Veuillez écrire à nextbytech1@gmail.com',
            ),
            backgroundColor: Colors.redAccent,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      body: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Animated Icon
            Container(
              padding: const EdgeInsets.all(30),
              decoration: BoxDecoration(
                color: Colors.orange.withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.hourglass_disabled_rounded,
                size: 80,
                color: Colors.orange,
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
              'Le serveur ne répond pas',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w900,
                color: Colors.white,
                letterSpacing: 0.5,
              ),
            ).animate().fadeIn(delay: 300.ms).slideY(begin: 0.2),

            const SizedBox(height: 16),

            // Subtext / Description
            const Text(
              'Les requêtes réseau ont expiré (Timeout). Cela arrive si votre connexion est trop lente, ou si votre fournisseur d\'accès internet bloque le serveur.',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 14,
                color: AppTheme.textGrey,
                height: 1.5,
              ),
            ).animate().fadeIn(delay: 500.ms).slideY(begin: 0.2),

            const SizedBox(height: 24),

            // Helpful Advice Card
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.02),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: Colors.white.withOpacity(0.08)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.lightbulb_outline_rounded, color: AppTheme.primaryColor, size: 18),
                      SizedBox(width: 8),
                      Text(
                        'Conseils de résolution :',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppTheme.primaryColor),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Text(
                    '• Essayez d\'activer un VPN si vous en possédez un.\n'
                    '• Essayez de basculer sur vos données mobiles (4G/5G).\n',
                    style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 12, height: 1.6),
                  ),
                ],
              ),
            ).animate().fadeIn(delay: 700.ms).slideY(begin: 0.2),

            const SizedBox(height: 40),

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
            .fadeIn(delay: 900.ms)
            .scale(begin: const Offset(0.8, 0.8)),

            const SizedBox(height: 16),

            // Contact Support Button
            SizedBox(
              width: double.infinity,
              height: 56,
              child: OutlinedButton.icon(
                onPressed: () => _contactSupport(context),
                icon: const Icon(Icons.mail_outline_rounded, color: AppTheme.primaryColor),
                label: const Text(
                  'CONTACTER LE SUPPORT',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    letterSpacing: 1.2,
                    color: Colors.white,
                  ),
                ),
                style: OutlinedButton.styleFrom(
                  side: const BorderSide(color: AppTheme.primaryColor, width: 2),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
              ),
            )
            .animate()
            .fadeIn(delay: 1100.ms)
            .scale(begin: const Offset(0.8, 0.8)),
          ],
        ),
      ),
    );
  }
}
