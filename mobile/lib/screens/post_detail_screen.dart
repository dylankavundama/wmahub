import 'package:flutter/material.dart';
import 'package:flutter_widget_from_html/flutter_widget_from_html.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:wmahub_mobile/screens/login_screen.dart';
import 'package:wmahub_mobile/screens/profile_screen.dart';
import '../utils/app_theme.dart';
import '../widgets/full_screen_image_view.dart';
import '../services/favorites_service.dart';

class PostDetailScreen extends StatefulWidget {
  final dynamic post;
  final List<dynamic> allPosts;

  const PostDetailScreen({
    super.key,
    required this.post,
    this.allPosts = const [],
  });

  @override
  State<PostDetailScreen> createState() => _PostDetailScreenState();
}

class _PostDetailScreenState extends State<PostDetailScreen> {
  final FavoritesService _favService = FavoritesService();
  bool _isFavorite = false;

  @override
  void initState() {
    super.initState();
    _checkFavorite();
  }

  Future<void> _checkFavorite() async {
    final isFav = await _favService.isFavorite(widget.post['id']);
    if (mounted) setState(() => _isFavorite = isFav);
  }

  void _toggleFavorite() async {
    await _favService.toggleFavorite(widget.post);
    _checkFavorite();
  }

  void _sharePost() {
    final String title = widget.post['title']['rendered'];
    final String link = widget.post['link'];
    Share.share('$title\n\nDécouvrez cet article sur WMA UA : $link');
  }

  Future<void> _launchURL(String urlString) async {
    final Uri url = Uri.parse(urlString);
    if (!await launchUrl(url, mode: LaunchMode.externalApplication)) {
      debugPrint('Could not launch $urlString');
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = widget.post['title']['rendered'];
    final content = widget.post['content']['rendered'];
    final imageUrl =
        widget.post['_embedded']?['wp:featuredmedia']?[0]?['source_url'] ??
        'https://via.placeholder.com/800x400';
    final date = DateTime.parse(widget.post['date']);
    final formattedDate = "${date.day}/${date.month}/${date.year}";
    final String heroTag = 'post_${widget.post['id']}';

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 300,
            pinned: true,
            actions: [
              IconButton(
                icon: Icon(
                  _isFavorite
                      ? Icons.favorite_rounded
                      : Icons.favorite_border_rounded,
                  color: _isFavorite ? Colors.redAccent : Colors.white,
                ),
                onPressed: _toggleFavorite,
                tooltip: 'Favori',
              ),
              IconButton(
                icon: const Icon(Icons.share_rounded),
                onPressed: _sharePost,
                tooltip: 'Partager',
              ),
              const SizedBox(width: 8),
            ],
            flexibleSpace: FlexibleSpaceBar(
              background: GestureDetector(
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) =>
                          FullScreenImageView(imageUrl: imageUrl, tag: heroTag),
                    ),
                  );
                },
                child: Hero(
                  tag: heroTag,
                  child: CachedNetworkImage(
                    imageUrl: imageUrl,
                    fit: BoxFit.cover,
                    placeholder: (context, url) => Container(
                      color: Colors.black.withOpacity(0.1),
                      child: const Center(
                        child: CircularProgressIndicator(strokeWidth: 2),
                      ),
                    ),
                    errorWidget: (context, url, error) => Container(
                      color: AppTheme.cardColor,
                      child: const Center(
                        child: Icon(
                          Icons.image_not_supported_outlined,
                          color: AppTheme.textGrey,
                          size: 50,
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.symmetric(
                horizontal: 24.0,
                vertical: 32.0,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryColor.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      formattedDate,
                      style: const TextStyle(
                        color: AppTheme.primaryColor,
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                        letterSpacing: 1,
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  HtmlWidget(
                    title,
                    textStyle: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 22,
                      height: 1.2,
                    ),
                  ),
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 32.0),
                    child: Divider(color: Colors.white10),
                  ),
                  HtmlWidget(
                    content,
                    textStyle: const TextStyle(
                      color: AppTheme.textGrey,
                      fontSize: 17,
                      height: 1.7,
                    ),
                    onLoadingBuilder: (context, element, loadingProgress) =>
                        const Center(
                          child: CircularProgressIndicator(
                            color: AppTheme.primaryColor,
                          ),
                        ),
                    onTapImage: (imageMetadata) {
                      final url = imageMetadata.sources.first.url;
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) =>
                              FullScreenImageView(imageUrl: url),
                        ),
                      );
                    },
                  ),
                  const SizedBox(height: 60),
                  if (widget.allPosts.isNotEmpty) ...[
                    const Text(
                      "À LIRE AUSSI",
                      style: TextStyle(
                        color: AppTheme.primaryColor,
                        fontWeight: FontWeight.w900,
                        fontSize: 14,
                        letterSpacing: 2,
                      ),
                    ),
                    const SizedBox(height: 24),
                    _buildRelatedPosts(context),
                    const SizedBox(height: 60),
                  ],
                  _buildCTAButton(context),
                  const SizedBox(height: 100),
                ],
              ),
            ),
          ),
        ],
      ),
      floatingActionButton: Row(
        mainAxisAlignment: MainAxisAlignment.end,
        children: [
          FloatingActionButton(
            heroTag: "fav",
            onPressed: _toggleFavorite,
            backgroundColor: _isFavorite
                ? Colors.redAccent
                : AppTheme.cardColor,
            child: Icon(
              _isFavorite
                  ? Icons.favorite_rounded
                  : Icons.favorite_border_rounded,
              color: Colors.white,
            ),
          ),
          const SizedBox(width: 16),
          FloatingActionButton(
            heroTag: "share",
            onPressed: _sharePost,
            backgroundColor: AppTheme.primaryColor,
            child: const Icon(Icons.share_rounded, color: Colors.white),
          ),
        ],
      ),
    );
  }

  Widget _buildCTAButton(BuildContext context) {
    return Container(
      width: double.infinity,
      height: 65,
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppTheme.primaryColor, Color(0xFFFF8800)],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primaryColor.withValues(alpha: 0.3),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: ElevatedButton(
        onPressed:()=> Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => const ProfileScreen()
          ),
        ),
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.transparent,
          shadowColor: Colors.transparent,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
          ),
        ),
        child: const Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.rocket_launch_rounded, color: Colors.white),
            SizedBox(width: 12),
            Text(
              "DISTRIBUER MA MUSIQUE",
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w900,
                fontSize: 16,
                letterSpacing: 1,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRelatedPosts(BuildContext context) {
    final related = widget.allPosts
        .where((p) => p['id'] != widget.post['id'])
        .take(6)
        .toList();

    return SizedBox(
      height: 220,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        itemCount: related.length,
        itemBuilder: (context, index) {
          final rPost = related[index];
          final rTitle = rPost['title']['rendered'];
          final rImageUrl =
              rPost['_embedded']?['wp:featuredmedia']?[0]?['source_url'] ??
              'https://via.placeholder.com/200x120';

          return GestureDetector(
            onTap: () {
              Navigator.pushReplacement(
                context,
                MaterialPageRoute(
                  builder: (context) =>
                      PostDetailScreen(post: rPost, allPosts: widget.allPosts),
                ),
              );
            },
            child: Container(
              width: 220,
              margin: const EdgeInsets.only(right: 20),
              decoration: BoxDecoration(
                color: AppTheme.cardColor,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: Colors.white10),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  ClipRRect(
                    borderRadius: const BorderRadius.vertical(
                      top: Radius.circular(20),
                    ),
                    child: CachedNetworkImage(
                      imageUrl: rImageUrl,
                      height: 120,
                      width: double.infinity,
                      fit: BoxFit.cover,
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(12.0),
                    child: Text(
                      rTitle,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
