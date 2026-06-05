import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:shimmer/shimmer.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../utils/app_theme.dart';
import '../services/auth_service.dart';
import '../services/wordpress_service.dart';
import '../services/favorites_service.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'revenue_screen.dart';
import 'distribution_screen.dart';
import 'notification_screen.dart';
import 'contract_screen.dart';
import 'profile_screen.dart';
import 'services_screen.dart';
import 'about_screen.dart';
import 'project_detail_screen.dart';

class SlideDetailScreen extends StatefulWidget {
  final Map<String, dynamic> slide;

  const SlideDetailScreen({super.key, required this.slide});

  @override
  State<SlideDetailScreen> createState() => _SlideDetailScreenState();
}

class _SlideDetailScreenState extends State<SlideDetailScreen> {
  final WordPressService _wpService = WordPressService();
  List<dynamic> _latestReleases = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadLatestReleases();
  }

  Future<void> _loadLatestReleases() async {
    try {
      final releases = await _wpService.fetchLatestDistributed();
      if (mounted) {
        setState(() {
          _latestReleases = releases;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

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
      if (!await launchUrl(url, mode: LaunchMode.inAppBrowserView)) {
        debugPrint('Could not launch $url');
      }
      return;
    }
    
    debugPrint('Unrecognized link format: $link');
  }

  @override
  Widget build(BuildContext context) {
    final title = (widget.slide['title'] ?? '')
        .replaceAll(RegExp(r'<br\s*/?>', caseSensitive: false), '\n');
    final subtitle = (widget.slide['subtitle'] ?? '')
        .replaceAll(RegExp(r'<br\s*/?>', caseSensitive: false), '\n');
    final imagePath = widget.slide['image_path'] ?? '';
    final buttonText = widget.slide['button_text'];
    final buttonLink = widget.slide['button_link'];

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
            
            // Section "Dernières sorties" (6 derniers projets distribués)
            _buildRelatedReleasesSection(),
            const SizedBox(height: 50),
          ],
        ),
      ),
    );
  }

  Widget _buildRelatedReleasesSection() {
    if (_isLoading) {
      return _buildReleasesShimmer();
    }
    if (_latestReleases.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          child: Text(
            'DERNIÈRES SORTIES',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w900,
              letterSpacing: 1.5,
              color: AppTheme.primaryColor,
            ),
          ),
        ),
        SizedBox(
          height: 220,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 18),
            itemCount: _latestReleases.length,
            itemBuilder: (context, index) {
              final release = _latestReleases[index];
              return _buildReleaseCard(release);
            },
          ),
        ),
      ],
    );
  }

  Widget _buildReleaseCard(dynamic release) {
    return _SlideReleaseCardWidget(
      key: ValueKey('slide_release_${release['id']}'),
      release: release,
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) =>
                ProjectDetailScreen(project: release as Map<String, dynamic>),
          ),
        );
      },
    );
  }

  Widget _buildReleasesShimmer() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          child: Text(
            'DERNIÈRES SORTIES',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w900,
              letterSpacing: 1.5,
              color: AppTheme.primaryColor,
            ),
          ),
        ),
        SizedBox(
          height: 220,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 18),
            itemCount: 6,
            itemBuilder: (context, index) {
              return Shimmer.fromColors(
                baseColor: Colors.white.withValues(alpha: 0.1),
                highlightColor: Colors.white.withValues(alpha: 0.2),
                child: Container(
                  width: 140,
                  margin: const EdgeInsets.symmetric(horizontal: 6),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Carte de sortie musicale avec favori pour SlideDetailScreen
// ─────────────────────────────────────────────────────────────────────────────

class _SlideReleaseCardWidget extends StatefulWidget {
  final dynamic release;
  final VoidCallback onTap;

  const _SlideReleaseCardWidget({
    super.key,
    required this.release,
    required this.onTap,
  });

  @override
  State<_SlideReleaseCardWidget> createState() =>
      _SlideReleaseCardWidgetState();
}

class _SlideReleaseCardWidgetState extends State<_SlideReleaseCardWidget> {
  final FavoritesService _favService = FavoritesService();
  bool _isFavorite = false;
  bool _isLoggedIn = false;

  @override
  void initState() {
    super.initState();
    _isLoggedIn = FirebaseAuth.instance.currentUser != null;
    _checkFavorite();
  }

  Future<void> _checkFavorite() async {
    final rawId = widget.release['id'];
    final id = rawId is int ? rawId : int.tryParse(rawId.toString()) ?? 0;
    final isFav = await _favService.isProjectFavorite(id);
    if (mounted) setState(() => _isFavorite = isFav);
  }

  Future<void> _toggleFavorite() async {
    HapticFeedback.mediumImpact();
    await _favService.toggleProjectFavorite(widget.release);
    await _checkFavorite();
  }

  @override
  Widget build(BuildContext context) {
    final release = widget.release;

    return GestureDetector(
      onTap: widget.onTap,
      child: Container(
        width: 140,
        margin: const EdgeInsets.symmetric(horizontal: 6),
        decoration: BoxDecoration(
          color: AppTheme.cardColor,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.white10),
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Stack(
                fit: StackFit.expand,
                children: [
                  release['cover_path'] != null && release['cover_path'] != ''
                      ? CachedNetworkImage(
                          imageUrl:
                              'https://wmahub.com/dashboards/artiste/uploads/${release['cover_path']}',
                          fit: BoxFit.cover,
                          placeholder: (context, url) =>
                              Container(color: Colors.white10),
                          errorWidget: (context, url, error) => const Center(
                            child: Icon(Icons.music_note,
                                color: AppTheme.primaryColor, size: 40),
                          ),
                        )
                      : const Center(
                          child: Icon(Icons.music_note,
                              color: AppTheme.primaryColor, size: 40),
                        ),
                  // Bouton play
                  Center(
                    child: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.5),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.play_arrow_rounded,
                          color: Colors.white, size: 24),
                    ),
                  ),
                  // Bouton favori
                  if (_isLoggedIn)
                    Positioned(
                      top: 6,
                      right: 6,
                      child: GestureDetector(
                        onTap: _toggleFavorite,
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 250),
                          padding: const EdgeInsets.all(5),
                          decoration: BoxDecoration(
                            color: _isFavorite
                                ? Colors.redAccent.withValues(alpha: 0.9)
                                : Colors.black.withValues(alpha: 0.5),
                            shape: BoxShape.circle,
                          ),
                          child: Icon(
                            _isFavorite
                                ? Icons.favorite_rounded
                                : Icons.favorite_border_rounded,
                            color: Colors.white,
                            size: 14,
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(10.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    release['title'] ?? 'Sans titre',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                        color: Colors.white),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    release['artist_name'] ?? 'Artiste',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        fontSize: 10, color: AppTheme.textGrey),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    ).animate().fadeIn(delay: 100.ms).scale(begin: const Offset(0.95, 0.95));
  }
}
