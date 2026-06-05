import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../services/wordpress_service.dart';
import '../utils/app_theme.dart';

class AboutScreen extends StatefulWidget {
  const AboutScreen({super.key});

  @override
  State<AboutScreen> createState() => _AboutScreenState();
}

class _AboutScreenState extends State<AboutScreen> {
  final WordPressService _wpService = WordPressService();
  late Future<Map<String, dynamic>> _aboutFuture;

  @override
  void initState() {
    super.initState();
    _aboutFuture = _wpService.fetchAboutInfo();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: FutureBuilder<Map<String, dynamic>>(
        future: _aboutFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(
              child: CircularProgressIndicator(color: AppTheme.primaryColor),
            );
          }

          final data = snapshot.data ?? {};

          return CustomScrollView(
            slivers: [
              SliverAppBar(
                expandedHeight: 220,
                pinned: true,
                flexibleSpace: FlexibleSpaceBar(
                  // title: const Text(
                  //   'À PROPOS',
                  //   style: TextStyle(
                  //     fontWeight: FontWeight.w900,
                  //     letterSpacing: 2,
                  //   ),
                  // ),
                  centerTitle: true,
                  background: Stack(
                    fit: StackFit.expand,
                    children: [
                      CachedNetworkImage(
                        imageUrl: 'https://wmahub.com/asset/off.png',
                        fit: BoxFit.cover,
                        placeholder: (context, url) => Container(
                          color: AppTheme.primaryColor.withValues(alpha: 0.1),
                        ),
                        errorWidget: (context, url, error) => Container(
                          color: AppTheme.primaryColor.withValues(alpha: 0.1),
                          child: const Center(
                            child: Icon(
                              Icons.broken_image_outlined,
                              color: Colors.white24,
                              size: 50,
                            ),
                          ),
                        ),
                      ),
                      Container(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.topCenter,
                            end: Alignment.bottomCenter,
                            colors: [
                              Colors.transparent,
                              Colors.black.withValues(alpha: 0.8),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.all(24.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _buildHeader(data['title'] ?? "QUI SOMMES NOUS ?"),
                      const SizedBox(height: 16),
                      _buildText(
                        data['who_we_are'] ??
                            "WMA Hub est une plateforme internationale de distribution musicale qui accompagne les artistes et les labels dans leur développement.",
                      ),

                      const SizedBox(height: 40),
                      _buildHeader("NOTRE MISSION"),
                      const SizedBox(height: 16),
                      _buildText(
                        data['distribution'] ??
                            "Distribuez votre musique facilement sur plus de 200 plateformes de streaming mondiales.",
                      ),

                      // const SizedBox(height: 40),
                      // _buildHeader("PROJET"),
                      // const SizedBox(height: 16),
                      // // Padding(
                      // //   padding: const EdgeInsets.all(8.0),
                      // //   child: Text('WMAPLUS'),
                      // // ),
                      // Padding(
                      //   padding: const EdgeInsets.all(8.0),
                      //   child: Text('WMAUNITEDAFRICA'),
                      // ),

                      const SizedBox(height: 40),
                      _buildStats(
                        data['stats'] ??
                            [
                              {"label": "Plateformes", "value": "+200"},
                              {"label": "Artistes", "value": "+720"},
                              {"label": "Écoutes / mois", "value": "+80M"},
                              {"label": "Titres", "value": "+100K"},
                            ],
                      ),

                      const SizedBox(height: 40),
                      _buildGlobalPresenceSection(data['global_presence']),

                      const SizedBox(height: 40),
                      _buildHeader("SUIVEZ-NOUS"),
                      const SizedBox(height: 16),
                      ...(data['socials'] as List? ??
                              [
                                {
                                  "platform": "Instagram",
                                  "url":
                                      "https://www.instagram.com/wmaunitedafrica?igsh=aDBoM3c3anIzcXEx",
                                  "handle": "@wmaunitedafrica",
                                },
                                {
                                  "platform": "WhatsApp",
                                  "url": "https://wa.me/256743297668",
                                  "handle": "+256 743 297 668",
                                },
                                {
                                  "platform": "TikTok",
                                  "url":
                                      "https://www.tiktok.com/@wmaplus?_r=1&_t=ZS-95Wi0zXYH5w",
                                  "handle": "@wmaplus",
                                },
                                {
                                  "platform": "Facebook",
                                  "url":
                                      "https://www.facebook.com/share/1FQHLego9Z/",
                                  "handle": "WMA Hub Official",
                                },
                              ])
                          .map(
                            (s) => _buildSocialTile(
                              s['platform'],
                              s['handle'],
                              s['url'],
                            ),
                          )
                          .toList(),

                      const SizedBox(height: 60),
                      Center(
                        child: Column(
                          children: [
                            Text(
                              "Version ${data['version'] ?? '1.0.0'}",
                              style: const TextStyle(
                                color: AppTheme.textGrey,
                                fontSize: 12,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              "Développé par ${data['developed_by'] ?? 'Next Byte Technology'}",
                              style: const TextStyle(
                                color: AppTheme.textGrey,
                                fontSize: 11,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 100),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildHeader(String text) {
    return Text(
      text.toUpperCase(),
      style: const TextStyle(
        color: AppTheme.primaryColor,
        fontWeight: FontWeight.w900,
        fontSize: 16,
        letterSpacing: 2,
      ),
    ).animate().fadeIn(duration: 600.ms).slideX(begin: -0.1);
  }

  Widget _buildText(String text) {
    return Text(
      text,
      style: const TextStyle(color: Colors.white70, fontSize: 16, height: 1.6),
    ).animate().fadeIn(duration: 800.ms, delay: 200.ms);
  }

  Widget _buildStats(List<dynamic> stats) {
    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      mainAxisSpacing: 16,
      crossAxisSpacing: 16,
      childAspectRatio: 1.8,
      children: stats
          .map(
            (s) => Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppTheme.cardColor,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.white10),
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    s['value'],
                    style: const TextStyle(
                      color: AppTheme.primaryColor,
                      fontWeight: FontWeight.bold,
                      fontSize: 20,
                    ),
                  ),
                  Text(
                    s['label'],
                    style: const TextStyle(
                      color: AppTheme.textGrey,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
          )
          .toList(),
    ).animate().fadeIn(duration: 800.ms, delay: 400.ms).scale();
  }

  Widget _buildGlobalPresenceSection(Map<String, dynamic>? data) {
    final subtitle = data?['subtitle'] ?? "IMPACT GLOBAL";
    final title = data?['title'] ?? "NOTRE PRÉSENCE MONDIALE";
    final description =
        data?['description'] ??
        "Interaction temps réel avec nos serveurs mondiaux. Nous accompagnons des talents partout sur le globe.";
    final stats =
        data?['stats'] as List? ??
        [
          {"label": "Artistes actifs", "value": "+720"},
          {"label": "Pays couverts", "value": "150+"},
        ];

    return Column(
      children: [
        const Divider(color: Colors.white10),
        const SizedBox(height: 40),
        Column(
          children: [
            Text(
              subtitle,
              style: const TextStyle(
                color: AppTheme.primaryColor,
                fontWeight: FontWeight.bold,
                fontSize: 10,
                letterSpacing: 3,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w900,
                fontSize: 24,
                letterSpacing: -0.5,
              ),
            ),
          ],
        ).animate().fadeIn().slideY(begin: 0.2),
        const SizedBox(height: 24),
        Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.03),
            borderRadius: BorderRadius.circular(30),
            border: Border.all(color: Colors.white10),
          ),
          child: Column(
            children: [
              Text(
                description,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: AppTheme.textGrey,
                  fontSize: 14,
                  height: 1.5,
                ),
              ),
              const SizedBox(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: stats
                    .map(
                      (s) => Column(
                        children: [
                          Text(
                            s['value'],
                            style: const TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                              fontSize: 20,
                            ),
                          ),
                          Text(
                            s['label'].toString().toUpperCase(),
                            style: const TextStyle(
                              color: AppTheme.primaryColor,
                              fontWeight: FontWeight.bold,
                              fontSize: 10,
                              letterSpacing: 1,
                            ),
                          ),
                        ],
                      ),
                    )
                    .toList(),
              ),
            ],
          ),
        ).animate().fadeIn(delay: 400.ms).slideY(begin: 0.1),
      ],
    );
  }

  Widget _buildSocialTile(String platform, String handle, String url) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: CircleAvatar(
        backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.1),
        child: Icon(_getIcon(platform), color: AppTheme.primaryColor, size: 20),
      ),
      title: Text(
        platform,
        style: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.bold,
        ),
      ),
      subtitle: Text(handle, style: const TextStyle(color: AppTheme.textGrey)),
      trailing: const Icon(
        Icons.arrow_forward_ios,
        color: AppTheme.textGrey,
        size: 14,
      ),
      onTap: () => _launchURL(url),
    ).animate().fadeIn(duration: 400.ms);
  }

  IconData _getIcon(String platform) {
    switch (platform.toLowerCase()) {
      case 'instagram':
        return Icons.camera_alt_outlined;
      case 'whatsapp':
        return Icons.chat_outlined;
      case 'facebook':
        return Icons.facebook_outlined;
      case 'tiktok':
        return Icons.music_note_outlined;
      default:
        return Icons.link;
    }
  }

  Future<void> _launchURL(String urlString) async {
    final Uri url = Uri.parse(urlString);
    if (!await launchUrl(url, mode: LaunchMode.inAppBrowserView)) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Impossible d\'ouvrir $urlString')),
        );
      }
    }
  }
}
