import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../services/favorites_service.dart';
import '../utils/app_theme.dart';
import 'post_detail_screen.dart';
import 'project_detail_screen.dart';

class FavoritesScreen extends StatefulWidget {
  const FavoritesScreen({super.key});

  @override
  State<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends State<FavoritesScreen>
    with SingleTickerProviderStateMixin {
  final FavoritesService _favService = FavoritesService();
  late TabController _tabController;

  List<dynamic> _favoritePosts = [];
  List<dynamic> _favoriteProjects = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadFavorites();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadFavorites() async {
    setState(() => _isLoading = true);
    try {
      final results = await Future.wait([
        _favService.getFavorites(),
        _favService.getProjectFavorites(),
      ]);
      if (mounted) {
        setState(() {
          _favoritePosts = results[0];
          _favoriteProjects = results[1];
          _isLoading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _removePostFavorite(int postId) async {
    await _favService.removePostFavorite(postId);
    await _loadFavorites();
  }

  Future<void> _removeProjectFavorite(int projectId) async {
    await _favService.removeProjectFavorite(projectId);
    await _loadFavorites();
  }

  @override
  Widget build(BuildContext context) {
    final totalCount = _favoritePosts.length + _favoriteProjects.length;

    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'MES FAVORIS',
              style: TextStyle(
                fontWeight: FontWeight.w900,
                letterSpacing: 1.2,
                fontSize: 18,
              ),
            ),
            if (!_isLoading)
              Text(
                '$totalCount élément${totalCount != 1 ? 's' : ''} sauvegardé${totalCount != 1 ? 's' : ''}',
                style: const TextStyle(
                  color: AppTheme.textGrey,
                  fontSize: 12,
                  fontWeight: FontWeight.normal,
                ),
              ),
          ],
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(52),
          child: _buildTabBar(),
        ),
      ),
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(color: AppTheme.primaryColor),
            )
          : TabBarView(
              controller: _tabController,
              children: [
                _buildPostsTab(),
                _buildProjectsTab(),
              ],
            ),
    );
  }

  Widget _buildTabBar() {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 0, 20, 12),
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(14),
      ),
      child: TabBar(
        controller: _tabController,
        indicator: BoxDecoration(
          color: AppTheme.primaryColor,
          borderRadius: BorderRadius.circular(10),
        ),
        indicatorSize: TabBarIndicatorSize.tab,
        labelColor: Colors.white,
        unselectedLabelColor: AppTheme.textGrey,
        labelStyle: const TextStyle(
          fontWeight: FontWeight.bold,
          fontSize: 13,
          letterSpacing: 0.5,
        ),
        dividerColor: Colors.transparent,
        tabs: [
          Tab(
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.article_outlined, size: 16),
                const SizedBox(width: 6),
                Text('Actualités (${_favoritePosts.length})'),
              ],
            ),
          ),
          Tab(
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.music_note_outlined, size: 16),
                const SizedBox(width: 6),
                Text('Projets (${_favoriteProjects.length})'),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ─────────────────────────────────────────────
  // ONGLET ACTUALITÉS
  // ─────────────────────────────────────────────

  Widget _buildPostsTab() {
    if (_favoritePosts.isEmpty) {
      return _buildEmptyState(
        icon: Icons.article_outlined,
        title: 'Aucune actualité aimée',
        subtitle:
            'Aimez des articles depuis le blog pour les retrouver ici rapidement.',
        onDiscover: () {
          Navigator.pop(context);
          _tabController.animateTo(0);
        },
        buttonLabel: 'DÉCOUVRIR LES ACTUALITÉS',
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 100),
      itemCount: _favoritePosts.length,
      itemBuilder: (context, index) {
        final post = _favoritePosts[index];
        return _buildPostFavoriteCard(post, index);
      },
    );
  }

  Widget _buildPostFavoriteCard(dynamic post, int index) {
    String title = post['title']?['rendered'] ?? 'Sans titre';
    String imageUrl = '';
    try {
      imageUrl =
          post['_embedded']?['wp:featuredmedia']?[0]?['source_url'] ?? '';
    } catch (_) {}
    final int id = post['id'] as int? ?? 0;
    final int viewCount = FavoritesService.getViewCount(id);
    final String date = post['date']?.toString().split('T')[0] ?? '';

    return Dismissible(
      key: ValueKey('fav_post_$id'),
      direction: DismissDirection.endToStart,
      background: Container(
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 24),
        margin: const EdgeInsets.only(bottom: 16),
        decoration: BoxDecoration(
          color: Colors.redAccent.withValues(alpha: 0.15),
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: Colors.redAccent.withValues(alpha: 0.3)),
        ),
        child: const Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.delete_outline_rounded, color: Colors.redAccent, size: 28),
            SizedBox(height: 4),
            Text(
              'RETIRER',
              style: TextStyle(
                color: Colors.redAccent,
                fontSize: 10,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ),
      onDismissed: (_) => _removePostFavorite(id),
      child: GestureDetector(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => PostDetailScreen(post: post),
            ),
          ).then((_) => _loadFavorites());
        },
        child: Container(
          margin: const EdgeInsets.only(bottom: 16),
          decoration: BoxDecoration(
            color: AppTheme.cardColor,
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: Colors.white10),
          ),
          child: Row(
            children: [
              // Image miniature
              if (imageUrl.isNotEmpty)
                ClipRRect(
                  borderRadius: const BorderRadius.horizontal(
                    left: Radius.circular(24),
                  ),
                  child: CachedNetworkImage(
                    imageUrl: imageUrl,
                    width: 110,
                    height: 110,
                    fit: BoxFit.cover,
                    placeholder: (context, url) => Container(
                      width: 110,
                      height: 110,
                      color: Colors.white.withValues(alpha: 0.05),
                    ),
                    errorWidget: (context, url, error) => Container(
                      width: 110,
                      height: 110,
                      color: AppTheme.cardColor,
                      child: const Icon(Icons.article_outlined,
                          color: AppTheme.textGrey),
                    ),
                  ),
                )
              else
                Container(
                  width: 110,
                  height: 110,
                  decoration: BoxDecoration(
                    color: AppTheme.primaryColor.withValues(alpha: 0.08),
                    borderRadius: const BorderRadius.horizontal(
                      left: Radius.circular(24),
                    ),
                  ),
                  child: const Icon(Icons.article_outlined,
                      color: AppTheme.primaryColor, size: 32),
                ),
              // Contenu
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                          height: 1.3,
                        ),
                      ),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          const Icon(Icons.calendar_today,
                              size: 11, color: AppTheme.textGrey),
                          const SizedBox(width: 4),
                          Text(
                            date,
                            style: const TextStyle(
                                color: AppTheme.textGrey, fontSize: 11),
                          ),
                          const SizedBox(width: 10),
                          const Icon(Icons.visibility_outlined,
                              size: 11, color: AppTheme.textGrey),
                          const SizedBox(width: 4),
                          FutureBuilder<int>(
                            future: _favService.getPostViewCount(id),
                            initialData: viewCount,
                            builder: (context, snapshot) {
                              final currentViews = snapshot.data ?? viewCount;
                              return Text(
                                '$currentViews vues',
                                style: const TextStyle(
                                    color: AppTheme.textGrey, fontSize: 11),
                              );
                            },
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          const Icon(Icons.favorite_rounded,
                              size: 12, color: Colors.redAccent),
                          const SizedBox(width: 4),
                          const Text(
                            'Aimé',
                            style: TextStyle(
                              color: Colors.redAccent,
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const Spacer(),
                          const Icon(Icons.chevron_right,
                              color: AppTheme.textGrey, size: 18),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ).animate(delay: Duration(milliseconds: index * 60)).fadeIn().slideX(begin: -0.05),
    );
  }

  // ─────────────────────────────────────────────
  // ONGLET PROJETS MUSICAUX
  // ─────────────────────────────────────────────

  Widget _buildProjectsTab() {
    if (_favoriteProjects.isEmpty) {
      return _buildEmptyState(
        icon: Icons.music_note_outlined,
        title: 'Aucun projet aimé',
        subtitle:
            'Aimez des sorties musicales depuis les sections "Dernières Sorties" pour les retrouver ici.',
        onDiscover: () => Navigator.pop(context),
        buttonLabel: 'EXPLORER LES SORTIES',
      );
    }

    return GridView.builder(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 100),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 14,
        mainAxisSpacing: 14,
        childAspectRatio: 0.72,
      ),
      itemCount: _favoriteProjects.length,
      itemBuilder: (context, index) {
        final project = _favoriteProjects[index];
        return _buildProjectFavoriteCard(project, index);
      },
    );
  }

  Widget _buildProjectFavoriteCard(dynamic project, int index) {
    final rawId = project['id'];
    final int id = rawId is int ? rawId : int.tryParse(rawId.toString()) ?? 0;
    final int viewCount = FavoritesService.getViewCount(id);
    final String title = project['title'] ?? 'Sans titre';
    final String artist = project['artist_name'] ?? 'Artiste';
    final String? coverPath = project['cover_path'];

    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => ProjectDetailScreen(
              project: project as Map<String, dynamic>,
            ),
          ),
        ).then((_) => _loadFavorites());
      },
      child: Container(
        decoration: BoxDecoration(
          color: AppTheme.cardColor,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.white10),
        ),
        clipBehavior: Clip.antiAlias,
        child: Stack(
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Cover
                Expanded(
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      coverPath != null && coverPath.isNotEmpty
                          ? CachedNetworkImage(
                              imageUrl:
                                  'https://wmahub.com/dashboards/artiste/uploads/$coverPath',
                              fit: BoxFit.cover,
                              placeholder: (context, url) =>
                                  Container(color: Colors.white10),
                              errorWidget: (context, url, error) =>
                                  Container(
                                color: AppTheme.cardColor,
                                child: const Icon(Icons.music_note,
                                    color: AppTheme.primaryColor, size: 40),
                              ),
                            )
                          : Container(
                              color: AppTheme.primaryColor.withValues(alpha: 0.08),
                              child: const Icon(Icons.music_note,
                                  color: AppTheme.primaryColor, size: 40),
                            ),
                      // Gradient bas
                      Positioned(
                        bottom: 0,
                        left: 0,
                        right: 0,
                        child: Container(
                          height: 60,
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                              colors: [
                                Colors.transparent,
                                Colors.black.withValues(alpha: 0.7),
                              ],
                            ),
                          ),
                        ),
                      ),
                      // Bouton play
                      Center(
                        child: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: Colors.black.withValues(alpha: 0.45),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.play_arrow_rounded,
                              color: Colors.white, size: 26),
                        ),
                      ),
                    ],
                  ),
                ),
                // Infos
                Padding(
                  padding: const EdgeInsets.all(10),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        artist,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 11,
                          color: AppTheme.primaryColor,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          const Icon(Icons.visibility_outlined,
                              size: 11, color: AppTheme.textGrey),
                          const SizedBox(width: 3),
                          Text(
                            '$viewCount',
                            style: const TextStyle(
                                color: AppTheme.textGrey, fontSize: 10),
                          ),
                          const Spacer(),
                          const Icon(Icons.favorite_rounded,
                              size: 12, color: Colors.redAccent),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
            // Bouton supprimer en haut à droite
            Positioned(
              top: 8,
              right: 8,
              child: GestureDetector(
                onTap: () => _removeProjectFavorite(id),
                child: Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: 0.6),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.favorite_rounded,
                    color: Colors.redAccent,
                    size: 16,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    ).animate(delay: Duration(milliseconds: index * 70)).fadeIn().scale(
          begin: const Offset(0.95, 0.95),
        );
  }

  // ─────────────────────────────────────────────
  // EMPTY STATE
  // ─────────────────────────────────────────────

  Widget _buildEmptyState({
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onDiscover,
    required String buttonLabel,
  }) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 40),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(28),
              decoration: BoxDecoration(
                gradient: RadialGradient(
                  colors: [
                    AppTheme.primaryColor.withValues(alpha: 0.12),
                    AppTheme.primaryColor.withValues(alpha: 0.0),
                  ],
                ),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 64, color: AppTheme.primaryColor),
            ),
            const SizedBox(height: 24),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 12),
            Text(
              subtitle,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: AppTheme.textGrey,
                fontSize: 13,
                height: 1.5,
              ),
            ),
            const SizedBox(height: 36),
            SizedBox(
              width: double.infinity,
              height: 54,
              child: ElevatedButton.icon(
                onPressed: onDiscover,
                icon: const Icon(Icons.explore_outlined, color: Colors.white),
                label: Text(
                  buttonLabel,
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    letterSpacing: 0.5,
                    color: Colors.white,
                  ),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppTheme.primaryColor,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    ).animate().fadeIn(duration: 450.ms).scale(begin: const Offset(0.95, 0.95));
  }
}
