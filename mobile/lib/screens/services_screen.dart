import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../utils/app_theme.dart';
import 'writing_assistant_screen.dart';

class ServicesScreen extends StatelessWidget {
  const ServicesScreen({super.key});

  Future<void> _launchURL(String url) async {
    final Uri uri = Uri.parse(url);
    if (!await launchUrl(uri, mode: LaunchMode.inAppBrowserView)) {
      throw Exception('Could not launch $url');
    }
  }

  @override
  Widget build(BuildContext context) {
    final List<ServiceItem> services = [
      ServiceItem(
        title: "Distribution Mondiale",
        description:
            "Diffusez votre musique sur Spotify, Apple Music, TikTok et +200 autres plateformes en 48h.",
        icon: Icons.public,
        url: "https://wmahub.com/auth/login.php",
        color: AppTheme.primaryColor,
      ),
      ServiceItem(
        title: "Marketing & Promo",
        description:
            "Boostez votre visibilité avec nos outils de promotion ciblée, pitch playlists et campagnes ads.",
        icon: Icons.campaign,
        url: "https://wmahub.com/auth/login.php",
        color: AppTheme.primaryColor,
      ),
      ServiceItem(
        title: "Gestion des Droits",
        description:
            "Sécurisez vos œuvres (Copyright) et récupérez 100% de vos royalties sans intermédiaire.",
        icon: Icons.shield_outlined,
        url: "https://wmahub.com/auth/login.php",
        color: AppTheme.primaryColor,
      ),
      ServiceItem(
        title: "TikTok For Artiste",
        description:
            "Optimisez votre présence sur TikTok avec un compte certifié Artiste pour booster votre visibilité.",
        icon: Icons
            .music_note, // Replacement for TikTok icon if FontAwesome isn't here, or generic icon
        url:
            "https://wa.me/243994717485?text=Bonjour%20WMA%20Hub,%20je%20souhaite%20demander%20un%20compte%20TikTok%20For%20Artiste.",
        color: const Color(0xFFFE2C55),
      ),
      ServiceItem(
        title: "Certification Plateformes",
        description:
            "Obtenez le badge de vérification sur toutes les plateformes de streaming audio mondiales.",
        icon: Icons.verified_user_outlined,
        url:
            "https://wa.me/243994717485?text=Bonjour%20WMA%20Hub,%20je%20souhaite%20la%20certification%20sur%20toutes%20les%20plateformes%20audio.",
        color: Colors.blue,
      ),
      ServiceItem(
        title: "YouTube Certification",
        description:
            "Passez au niveau supérieur avec une chaîne officielle d'artiste et la monétisation complète.",
        icon: Icons.video_library,
        url:
            "https://wa.me/243994717485?text=Bonjour%20WMA%20Hub,%20je%20souhaite%20la%20certification%20et%20monétisation%20YouTube.",
        color: Colors.red,
      ),
      ServiceItem(
        title: "Portfolio Pro",
        description:
            "Demandez un portfolio professionnel et personnalisé présenté sous forme de mini site web.",
        icon: Icons.badge_outlined,
        url:
            "https://wa.me/243977734735?text=Bonjour%20WMA%20Hub,%20je%20souhaite%20demander%20un%20portfolio%20artistique%20professionnel.",
        color: AppTheme.primaryColor,
      ),
      ServiceItem(
        title: "Assistant Écriture",
        description:
            "Utilisez la puissance de l'IA pour générer des paroles de chansons basées sur vos thèmes.",
        icon: Icons.gesture,
        url: "https://wmahub.com/auth/login.php",
        color: AppTheme.primaryColor,
        nativeTabIndex: 0,
      ),
      ServiceItem(
        title: "Générateur de Refrain",
        description:
            "Créez un refrain mémorable intégré à vos couplets. L'IA sublime votre morceau.",
        icon: Icons.mic_external_on,
        url: "https://wmahub.com/auth/login.php",
        color: Colors.purple,
        nativeTabIndex: 1,
      ),
      ServiceItem(
        title: "Correction Texte",
        description:
            "Optimisez vos écrits. Notre IA corrige les fautes et améliore le style.",
        icon: Icons.auto_fix_high,
        url: "https://wmahub.com/auth/login.php",
        color: Colors.green,
        nativeTabIndex: 1,
      ),
      ServiceItem(
        title: "Bloc-Note Cloud",
        description:
            "Gardez une trace de toutes vos idées, mélodies et textes. Accessible partout.",
        icon: Icons.cloud_done_outlined,
        url: "https://wmahub.com/auth/login.php",
        color: Colors.blueAccent,
        nativeTabIndex: 2,
      ),
    ];

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'SERVICES ARTISTE',
          style: TextStyle(fontWeight: FontWeight.w900, letterSpacing: 1.5),
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
      ),
      body: ListView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
        itemCount: services.length,
        itemBuilder: (context, index) {
          final service = services[index];
          return Container(
            margin: const EdgeInsets.only(bottom: 20),
            decoration: BoxDecoration(
              color: AppTheme.cardColor,
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: Colors.white10),
              boxShadow: [
                BoxShadow(
                  color: service.color.withValues(alpha: 0.05),
                  blurRadius: 20,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            child: InkWell(
              onTap: () {
                if (service.nativeTabIndex != null) {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => WritingAssistantScreen(
                        initialTabIndex: service.nativeTabIndex!,
                      ),
                    ),
                  );
                } else {
                  _launchURL(service.url);
                }
              },
              borderRadius: BorderRadius.circular(24),
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: service.color.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Icon(
                            service.icon,
                            color: service.color,
                            size: 28,
                          ),
                        ),
                        const Spacer(),
                        Icon(
                          service.nativeTabIndex != null ? Icons.arrow_forward : Icons.arrow_outward,
                          color: AppTheme.textGrey,
                          size: 20,
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    Text(
                      service.title,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        letterSpacing: -0.5,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      service.description,
                      style: const TextStyle(
                        color: AppTheme.textGrey,
                        fontSize: 14,
                        height: 1.6,
                      ),
                    ),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        Text(
                          service.nativeTabIndex != null
                              ? "OUVRIR L'OUTIL"
                              : (service.url.contains('wa.me') ? "DEMANDER" : "EN SAVOIR PLUS"),
                          style: TextStyle(
                            color: service.color,
                            fontSize: 11,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 1.2,
                          ),
                        ),
                        const SizedBox(width: 8),
                        if (service.url.contains('wa.me'))
                          Icon(
                            Icons.chat_bubble_outline,
                            color: service.color,
                            size: 14,
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class ServiceItem {
  final String title;
  final String description;
  final IconData icon;
  final String url;
  final Color color;
  final int? nativeTabIndex;

  ServiceItem({
    required this.title,
    required this.description,
    required this.icon,
    required this.url,
    required this.color,
    this.nativeTabIndex,
  });
}
