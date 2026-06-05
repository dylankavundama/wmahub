import 'dart:ui';
import 'dart:math';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:share_plus/share_plus.dart';
import '../utils/app_theme.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../services/favorites_service.dart';
import '../services/wordpress_service.dart';

class DistributionDetailScreen extends StatefulWidget {
  final dynamic distribution;

  const DistributionDetailScreen({super.key, required this.distribution});

  @override
  State<DistributionDetailScreen> createState() => _DistributionDetailScreenState();
}

class _DistributionDetailScreenState extends State<DistributionDetailScreen> {
  final FavoritesService _favoritesService = FavoritesService();
  bool _isFavorite = false;
  List<dynamic> _randomDistributions = [];

  @override
  void initState() {
    super.initState();
    _checkFavoriteStatus();
    _loadRandomDistributions();
  }

  Future<void> _checkFavoriteStatus() async {
    final int projectId = int.tryParse(widget.distribution['id']?.toString() ?? '0') ?? 0;
    if (projectId != 0) {
      final isFav = await _favoritesService.isProjectFavorite(projectId);
      if (mounted) {
        setState(() {
          _isFavorite = isFav;
        });
      }
    }
  }

  Future<void> _loadRandomDistributions() async {
    final dists = await WordPressService().fetchDistributions();
    if (dists.isNotEmpty && mounted) {
      final currentId = widget.distribution['id']?.toString() ?? '';
      final filteredDists = dists.where((d) => d['id']?.toString() != currentId).toList();
      // Prendre les 6 dernières sorties
      final latestSix = filteredDists.take(6).toList();
      // Les mélanger aléatoirement
      latestSix.shuffle(Random());
      setState(() {
        _randomDistributions = latestSix;
      });
    }
  }

  Future<void> _toggleFavorite() async {
    await _favoritesService.toggleProjectFavorite(widget.distribution);
    setState(() {
      _isFavorite = !_isFavorite;
    });
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_isFavorite ? 'Ajouté aux favoris' : 'Retiré des favoris'),
          behavior: SnackBarBehavior.floating,
          backgroundColor: AppTheme.primaryColor,
        ),
      );
    }
  }

  Future<void> _shareDistribution() async {
    final title = widget.distribution['title'] ?? 'Sans titre';
    final artist = widget.distribution['artist'] ?? 'Artiste inconnu';
    final link = widget.distribution['link'] ?? '';
    
    final shareText = 'Écoutez "$title" par $artist distribué via WMA UA !\n\n$link';
    await Share.share(shareText);
  }

  Future<void> _launchURL(BuildContext context, String? urlString) async {
    if (urlString == null || urlString.isEmpty) return;
    final Uri url = Uri.parse(urlString);
    if (!await launchUrl(url, mode: LaunchMode.inAppBrowserView)) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Impossible d\'ouvrir le lien')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final imageUrl = widget.distribution['image_url'] ?? '';
    final title = widget.distribution['title'] ?? 'Sans titre';
    final artist = widget.distribution['artist'] ?? 'Artiste inconnu';

    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      body: Stack(
        children: [
          // Arrière-plan flouté (style Apple Music / Spotify)
          if (imageUrl.isNotEmpty)
            Positioned.fill(
              child: CachedNetworkImage(
                imageUrl: imageUrl,
                fit: BoxFit.cover,
              ),
            ),
          Positioned.fill(
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 50.0, sigmaY: 50.0),
              child: Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    stops: const [0.0, 0.4, 1.0],
                    colors: [
                      Colors.black.withOpacity(0.3),
                      AppTheme.backgroundColor.withOpacity(0.9),
                      AppTheme.backgroundColor,
                    ],
                  ),
                ),
              ),
            ),
          ),
          
          // Contenu principal
          SafeArea(
            child: CustomScrollView(
              slivers: [
                SliverAppBar(
                  backgroundColor: Colors.transparent,
                  elevation: 0,
                  leading: IconButton(
                    icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                    onPressed: () => Navigator.pop(context),
                  ),
                ),
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 24.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        const SizedBox(height: 10),
                        
                        // Pochette de l'album / projet
                        Container(
                          width: 260,
                          height: 260,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(24),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.5),
                                blurRadius: 40,
                                offset: const Offset(0, 20),
                              ),
                            ],
                          ),
                          clipBehavior: Clip.antiAlias,
                          child: CachedNetworkImage(
                            imageUrl: imageUrl,
                            fit: BoxFit.cover,
                            placeholder: (context, url) => Container(color: Colors.white10),
                            errorWidget: (context, url, error) => Container(
                              color: Colors.white10,
                              child: const Icon(Icons.music_note, size: 80, color: Colors.white38),
                            ),
                          ),
                        ).animate().scale(duration: 600.ms, curve: Curves.easeOutBack),
                        
                        const SizedBox(height: 40),
                        
                        // Informations Titre / Artiste
                        Text(
                          title,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 26,
                            fontWeight: FontWeight.w900,
                            color: Colors.white,
                            letterSpacing: 1.2,
                          ),
                        ).animate().fadeIn(delay: 200.ms).slideY(begin: 0.2),
                        
                        const SizedBox(height: 12),
                        
                        Text(
                          artist,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 18,
                            color: AppTheme.primaryColor.withOpacity(0.9),
                            fontWeight: FontWeight.w600,
                            letterSpacing: 0.5,
                          ),
                        ).animate().fadeIn(delay: 300.ms).slideY(begin: 0.2),
                        
                        const SizedBox(height: 32),
                        
                        // Boutons d'action rapides (Design UI moderne)
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            _buildActionButton(
                              _isFavorite ? Icons.favorite : Icons.favorite_border,
                              'Favoris',
                              _toggleFavorite,
                              iconColor: _isFavorite ? Colors.redAccent : Colors.white,
                            ),
                            const SizedBox(width: 40),
                            _buildActionButton(
                              Icons.share_outlined, 
                              'Partager', 
                              _shareDistribution,
                            ),
                          ],
                        ).animate().fadeIn(delay: 400.ms),
                        
                        const SizedBox(height: 40),
                        
                        // Bouton d'écoute principal
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: () => _launchURL(context, widget.distribution['link']),
                            icon: const Icon(Icons.play_circle_fill, color: Colors.white, size: 28),
                            label: const Text(
                              'ÉCOUTER MAINTENANT',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 15,
                                fontWeight: FontWeight.bold,
                                letterSpacing: 1.5,
                              ),
                            ),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppTheme.primaryColor,
                              padding: const EdgeInsets.symmetric(vertical: 20),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(20),
                              ),
                              elevation: 10,
                              shadowColor: AppTheme.primaryColor.withOpacity(0.5),
                            ),
                          ),
                        ).animate().fadeIn(delay: 500.ms).scale(begin: const Offset(0.95, 0.95)),
                        
                        const SizedBox(height: 40),
                        const Divider(color: Colors.white10),
                        const SizedBox(height: 30),
                        
                        // Section "À PROPOS"
                        const Align(
                          alignment: Alignment.centerLeft,
                          child: Text(
                            'À PROPOS',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w900,
                              color: Colors.white54,
                              letterSpacing: 2,
                            ),
                          ),
                        ).animate().fadeIn(delay: 600.ms),
                        
                        const SizedBox(height: 16),
                        
                        Text(
                          'Ce projet a été distribué via WMA UA. Cliquez sur "Écouter maintenant" pour découvrir ce titre sur vos plateformes de streaming préférées (Spotify, Apple Music, Boomplay, etc.).\n\nSoutenez les artistes en partageant leurs œuvres avec votre entourage !',
                          textAlign: TextAlign.left,
                          style: TextStyle(
                            color: Colors.white.withOpacity(0.7),
                            fontSize: 15,
                            height: 1.6,
                          ),
                        ).animate().fadeIn(delay: 700.ms),
                        
                        const SizedBox(height: 40),
                        
                        // Section "VOUS AIMEREZ AUSSI"
                        if (_randomDistributions.isNotEmpty) ...[
                          const Align(
                            alignment: Alignment.centerLeft,
                            child: Text(
                              'VOUS AIMEREZ AUSSI',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w900,
                                color: Colors.white54,
                                letterSpacing: 2,
                              ),
                            ),
                          ).animate().fadeIn(delay: 800.ms),
                          const SizedBox(height: 16),
                          SizedBox(
                            height: 180,
                            child: ListView.builder(
                              scrollDirection: Axis.horizontal,
                              itemCount: _randomDistributions.length,
                              itemBuilder: (context, index) {
                                final dist = _randomDistributions[index];
                                return GestureDetector(
                                  onTap: () {
                                    Navigator.pushReplacement(
                                      context,
                                      MaterialPageRoute(
                                        builder: (context) => DistributionDetailScreen(distribution: dist),
                                      ),
                                    );
                                  },
                                  child: Container(
                                    width: 120,
                                    margin: const EdgeInsets.only(right: 16),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Container(
                                          height: 120,
                                          width: 120,
                                          decoration: BoxDecoration(
                                            borderRadius: BorderRadius.circular(12),
                                            color: Colors.white10,
                                          ),
                                          clipBehavior: Clip.antiAlias,
                                          child: CachedNetworkImage(
                                            imageUrl: dist['image_url'] ?? '',
                                            fit: BoxFit.cover,
                                            placeholder: (context, url) => Container(color: Colors.white10),
                                            errorWidget: (context, url, error) => const Icon(Icons.music_note, color: Colors.white38),
                                          ),
                                        ),
                                        const SizedBox(height: 8),
                                        Text(
                                          dist['title'] ?? 'Sans titre',
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: const TextStyle(
                                            color: Colors.white,
                                            fontSize: 12,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          dist['artist'] ?? 'Artiste',
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: const TextStyle(
                                            color: AppTheme.primaryColor,
                                            fontSize: 10,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                );
                              },
                            ),
                          ).animate().fadeIn(delay: 900.ms),
                        ],
                        
                        const SizedBox(height: 60),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionButton(IconData icon, String label, VoidCallback onTap, {Color iconColor = Colors.white}) {
    return GestureDetector(
      onTap: onTap,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.05),
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white10),
            ),
            child: Icon(icon, color: iconColor, size: 24),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            style: const TextStyle(
              color: Colors.white54,
              fontSize: 11,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

