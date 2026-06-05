import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/wordpress_service.dart';
import '../utils/app_theme.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'distribution_detail_screen.dart';

class DistributionsScreen extends StatefulWidget {
  const DistributionsScreen({super.key});

  @override
  State<DistributionsScreen> createState() => _DistributionsScreenState();
}

class _DistributionsScreenState extends State<DistributionsScreen> {
  final WordPressService _wpService = WordPressService();
  List<dynamic> _distributions = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadDistributions();
  }

  Future<void> _loadDistributions() async {
    setState(() => _isLoading = true);
    final data = await _wpService.fetchDistributions();
    if (!mounted) return;
    setState(() {
      _distributions = data;
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      appBar: AppBar(
        title: const Text(
          'DISTRIBUTIONS',
          style: TextStyle(fontWeight: FontWeight.w900, letterSpacing: 1.2),
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppTheme.primaryColor))
          : RefreshIndicator(
              onRefresh: _loadDistributions,
              color: AppTheme.primaryColor,
              child: _distributions.isEmpty
                  ? _buildEmptyState()
                  : CustomScrollView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      slivers: [
                        SliverPadding(
                          padding: const EdgeInsets.all(20),
                          sliver: SliverGrid(
                            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 2,
                              childAspectRatio: 0.75,
                              crossAxisSpacing: 15,
                              mainAxisSpacing: 15,
                            ),
                            delegate: SliverChildBuilderDelegate(
                              (context, index) => _buildDistCard(_distributions[index]),
                              childCount: _distributions.length,
                            ),
                          ),
                        ),
                        SliverToBoxAdapter(
                          child: Padding(
                            padding: const EdgeInsets.symmetric(vertical: 40, horizontal: 20),
                            child: Column(
                              children: [
                                const Divider(color: Colors.white10),
                                const SizedBox(height: 20),
                                const Text(
                                  'Ce sont là nos plus gros projets déjà distribués',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 16,
                                    color: Colors.white,
                                  ),
                                ),
                                const SizedBox(height: 10),
                                Text(
                                  'WMA Hub - Propulseur de talents',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.white.withOpacity(0.3),
                                    fontStyle: FontStyle.italic,
                                  ),
                                ),
                                const SizedBox(height: 60),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
            ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.music_note_outlined, size: 64, color: Colors.white.withOpacity(0.1)),
          const SizedBox(height: 16),
          const Text(
            'Aucune distribution publiée pour le moment.',
            style: TextStyle(color: Colors.white54),
          ),
        ],
      ),
    );
  }

  Widget _buildDistCard(dynamic dist) {
    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => DistributionDetailScreen(distribution: dist),
          ),
        );
      },
      child: Container(
        decoration: BoxDecoration(
          color: AppTheme.cardColor,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white.withOpacity(0.05)),
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: CachedNetworkImage(
                imageUrl: dist['image_url'] ?? '',
                fit: BoxFit.cover,
                width: double.infinity,
                placeholder: (context, url) => Container(color: Colors.white10),
                errorWidget: (context, url, error) => const Icon(Icons.broken_image),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    dist['title'] ?? 'Sans titre',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    dist['artist'] ?? 'Artiste inconnu',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(color: AppTheme.primaryColor, fontSize: 12),
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
