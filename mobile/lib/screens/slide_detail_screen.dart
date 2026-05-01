import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import '../utils/app_theme.dart';
import '../services/auth_service.dart';
import 'revenue_screen.dart';
import 'distribution_screen.dart';
import 'notification_screen.dart';
import 'contract_screen.dart';
import 'profile_screen.dart';
import 'services_screen.dart';
import 'about_screen.dart';

class SlideDetailScreen extends StatelessWidget {
  final Map<String, dynamic> slide;

  const SlideDetailScreen({super.key, required this.slide});

  Future<void> _handleNavigation(BuildContext context, String? link) async {
    if (link == null || link.isEmpty || link == '#') {
      debugPrint('Link is empty or #');
      return;
    }

    final String lowerLink = link.toLowerCase();

    // 1. Check for Internal Routes Keywords
    if (lowerLink.contains('revenue') || lowerLink.contains('portefeuille') || lowerLink.contains('argent')) {
      final user = await AuthService().getCurrentUser();
      if (user != null) {
        final intId = user['id'] is int ? user['id'] : int.tryParse(user['id'].toString()) ?? 0;
        if (context.mounted) {
          Navigator.push(context, MaterialPageRoute(builder: (context) => RevenueScreen(userId: intId)));
        }
      } else {
        if (context.mounted) {
          Navigator.push(context, MaterialPageRoute(builder: (context) => const ProfileScreen()));
        }
      }
      return;
    }

    if (lowerLink.contains('distribution') || lowerLink.contains('catalogue') || lowerLink.contains('musique') || lowerLink.contains('sortie')) {
      if (context.mounted) {
        Navigator.push(context, MaterialPageRoute(builder: (context) => const DistributionScreen()));
      }
      return;
    }

    if (lowerLink.contains('notification')) {
      final user = await AuthService().getCurrentUser();
      if (user != null) {
        final intId = user['id'] is int ? user['id'] : int.tryParse(user['id'].toString()) ?? 0;
        if (context.mounted) {
          Navigator.push(context, MaterialPageRoute(builder: (context) => NotificationScreen(userId: intId)));
        }
      } else {
        if (context.mounted) {
          Navigator.push(context, MaterialPageRoute(builder: (context) => const ProfileScreen()));
        }
      }
      return;
    }

    if (lowerLink.contains('contrat') || lowerLink.contains('legal')) {
      final user = await AuthService().getCurrentUser();
      if (user != null) {
        final intId = user['id'] is int ? user['id'] : int.tryParse(user['id'].toString()) ?? 0;
        if (context.mounted) {
          Navigator.push(context, MaterialPageRoute(builder: (context) => ContractScreen(userId: intId, onSigned: () {})));
        }
      } else {
        if (context.mounted) {
          Navigator.push(context, MaterialPageRoute(builder: (context) => const ProfileScreen()));
        }
      }
      return;
    }

    if (lowerLink.contains('service')) {
      if (context.mounted) {
        Navigator.push(context, MaterialPageRoute(builder: (context) => const ServicesScreen()));
      }
      return;
    }

    if (lowerLink.contains('profil') || lowerLink.contains('compte') || lowerLink.contains('dashboard')) {
      if (context.mounted) {
        Navigator.push(context, MaterialPageRoute(builder: (context) => const ProfileScreen()));
      }
      return;
    }

    if (lowerLink.contains('propos') || lowerLink.contains('contact') || lowerLink.contains('about')) {
      if (context.mounted) {
        Navigator.push(context, MaterialPageRoute(builder: (context) => const AboutScreen()));
      }
      return;
    }

    // 2. Fallback to External URL if it looks like a URL
    if (lowerLink.startsWith('http') || lowerLink.startsWith('https') || lowerLink.contains('.')) {
      final Uri url = Uri.parse(link.startsWith('http') ? link : 'https://$link');
      if (!await launchUrl(url, mode: LaunchMode.externalApplication)) {
        debugPrint('Could not launch $url');
      }
      return;
    }
    
    debugPrint('Unrecognized link format: $link');
  }

  @override
  Widget build(BuildContext context) {
    final title = slide['title'] ?? '';
    final subtitle = slide['subtitle'] ?? '';
    final imagePath = slide['image_path'] ?? '';
    final buttonText = slide['button_text'];
    final buttonLink = slide['button_link'];

    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      extendBodyBehindAppBar: true,
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image en grand
            Hero(
              tag: 'slide_$imagePath',
              child: CachedNetworkImage(
                imageUrl: imagePath,
                width: double.infinity,
                height: MediaQuery.of(context).size.height * 0.5,
                fit: BoxFit.cover,
                placeholder: (context, url) => Container(
                  height: MediaQuery.of(context).size.height * 0.5,
                  color: Colors.white.withValues(alpha: 0.05),
                  child: const Center(
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
                ),
                errorWidget: (context, url, error) => Container(
                  height: MediaQuery.of(context).size.height * 0.5,
                  color: AppTheme.cardColor,
                  child: const Center(
                    child: Icon(
                      Icons.image_not_supported_outlined,
                      color: AppTheme.textGrey,
                      size: 60,
                    ),
                  ),
                ),
              ),
            ),
            
            // Textes et bouton
            Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (title.isNotEmpty)
                    Text(
                      title,
                      style: const TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  if (title.isNotEmpty) const SizedBox(height: 16),
                  
                  if (subtitle.isNotEmpty)
                    Text(
                      subtitle,
                      style: const TextStyle(
                        fontSize: 16,
                        color: Colors.white70,
                        height: 1.5,
                      ),
                    ),
                  if (subtitle.isNotEmpty) const SizedBox(height: 32),
                  
                  if (buttonText != null && buttonText.toString().trim().isNotEmpty && 
                      buttonLink != null && buttonLink.toString().trim().isNotEmpty && 
                      buttonLink != '#')
                    SizedBox(
                      width: double.infinity,
                      height: 56,
                      child: ElevatedButton(
                        onPressed: () => _handleNavigation(context, buttonLink),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppTheme.primaryColor,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        child: Text(
                          buttonText,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
