import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:shimmer/shimmer.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../utils/app_theme.dart';
import '../services/wordpress_service.dart';
import '../services/cache_service.dart';
import '../services/favorites_service.dart';
import 'project_detail_screen.dart';
import 'package:firebase_auth/firebase_auth.dart';

class AllDistributedProjectsScreen extends StatefulWidget {
  const AllDistributedProjectsScreen({super.key});

  @override
  State<AllDistributedProjectsScreen> createState() => _AllDistributedProjectsScreenState();
}

class _AllDistributedProjectsScreenState extends State<AllDistributedProjectsScreen> {
  final WordPressService _wpService = WordPressService();
  final ScrollController _scrollController = ScrollController();
  
  List<dynamic> _projects = [];
  bool _isLoading = true;
  bool _isFetchingMore = false;
  bool _hasMore = true;
  int _currentPage = 1;
  static const int _limit = 20;

  @override
  void initState() {
    super.initState();
    _loadInitialProjects();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      if (!_isFetchingMore && _hasMore && !_isLoading) {
        _loadMoreProjects();
      }
    }
  }

  Future<void> _loadInitialProjects({bool isBackground = false}) async {
    if (_projects.isEmpty && !isBackground) {
      if (mounted) {
        setState(() {
          _isLoading = true;
          _currentPage = 1;
          _hasMore = true;
        });
      }
    }

    if (_projects.isEmpty) {
      final cached = await CacheService.load('cache_all_distributed_page_1');
      if (cached is List && cached.isNotEmpty && mounted) {
        setState(() {
          _projects = cached;
          _isLoading = false;
        });
      }
    }

    try {
      final data = await _wpService.fetchAllDistributed(page: 1, limit: _limit);
      if (mounted) {
        setState(() {
          _projects = data;
          _isLoading = false;
          _currentPage = 1;
          _hasMore = true;
          if (data.length < _limit) {
            _hasMore = false;
          }
        });
      }
    } catch (e) {
      debugPrint("Error loading initial projects: $e");
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _loadMoreProjects() async {
    if (!mounted) return;
    setState(() => _isFetchingMore = true);

    try {
      final nextPage = _currentPage + 1;
      final data = await _wpService.fetchAllDistributed(page: nextPage, limit: _limit);
      
      if (mounted) {
        setState(() {
          if (data.isEmpty) {
            _hasMore = false;
          } else {
            _projects.addAll(data);
            _currentPage = nextPage;
            if (data.length < _limit) {
              _hasMore = false;
            }
          }
          _isFetchingMore = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isFetchingMore = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      appBar: AppBar(
        title: const Text(
          'TOUTES LES SORTIES',
          style: TextStyle(
            fontWeight: FontWeight.w900,
            letterSpacing: 1.2,
            fontSize: 16,
          ),
        ),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: _isLoading
          ? _buildGridShimmer()
          : RefreshIndicator(
              onRefresh: _loadInitialProjects,
              color: AppTheme.primaryColor,
              child: _projects.isEmpty
                  ? _buildEmptyState()
                  : CustomScrollView(
                      controller: _scrollController,
                      physics: const AlwaysScrollableScrollPhysics(),
                      slivers: [
                        SliverPadding(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                          sliver: SliverGrid(
                            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 2,
                              childAspectRatio: 0.72,
                              crossAxisSpacing: 12,
                              mainAxisSpacing: 12,
                            ),
                            delegate: SliverChildBuilderDelegate(
                              (context, index) {
                                final project = _projects[index];
                                return _GridProjectCardWidget(
                                  key: ValueKey('all_release_${project['id']}'),
                                  project: project,
                                  onTap: () {
                                    Navigator.push(
                                      context,
                                      MaterialPageRoute(
                                        builder: (context) => ProjectDetailScreen(
                                          project: project as Map<String, dynamic>,
                                        ),
                                      ),
                                    );
                                  },
                                );
                              },
                              childCount: _projects.length,
                            ),
                          ),
                        ),
                        if (_isFetchingMore)
                          const SliverToBoxAdapter(
                            child: Padding(
                              padding: EdgeInsets.symmetric(vertical: 24),
                              child: Center(
                                child: CircularProgressIndicator(color: AppTheme.primaryColor),
                              ),
                            ),
                          ),
                        const SliverToBoxAdapter(child: SizedBox(height: 50)),
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
          Icon(Icons.music_note_outlined, size: 64, color: Colors.white.withValues(alpha: 0.1)),
          const SizedBox(height: 16),
          const Text(
            'Aucune sortie distribuée trouvée.',
            style: TextStyle(color: Colors.white54),
          ),
        ],
      ),
    );
  }

  Widget _buildGridShimmer() {
    return GridView.builder(
      padding: const EdgeInsets.all(16),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        childAspectRatio: 0.72,
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
      ),
      itemCount: 8,
      itemBuilder: (context, index) {
        return Shimmer.fromColors(
          baseColor: Colors.white.withValues(alpha: 0.1),
          highlightColor: Colors.white.withValues(alpha: 0.2),
          child: Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
            ),
          ),
        );
      },
    );
  }
}

class _GridProjectCardWidget extends StatefulWidget {
  final dynamic project;
  final VoidCallback onTap;

  const _GridProjectCardWidget({
    super.key,
    required this.project,
    required this.onTap,
  });

  @override
  State<_GridProjectCardWidget> createState() => _GridProjectCardWidgetState();
}

class _GridProjectCardWidgetState extends State<_GridProjectCardWidget> {
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
    final rawId = widget.project['id'];
    final id = rawId is int ? rawId : int.tryParse(rawId.toString()) ?? 0;
    final isFav = await _favService.isProjectFavorite(id);
    if (mounted) setState(() => _isFavorite = isFav);
  }

  Future<void> _toggleFavorite() async {
    HapticFeedback.mediumImpact();
    await _favService.toggleProjectFavorite(widget.project);
    await _checkFavorite();
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_isFavorite ? '❤️ Ajouté aux favoris' : 'Retiré des favoris'),
          backgroundColor: _isFavorite ? AppTheme.primaryColor : AppTheme.cardColor,
          behavior: SnackBarBehavior.floating,
          duration: const Duration(seconds: 1),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final project = widget.project;

    return Stack(
      clipBehavior: Clip.none,
      children: [
        GestureDetector(
          onTap: widget.onTap,
          child: Container(
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
                      project['cover_path'] != null && project['cover_path'] != ''
                          ? CachedNetworkImage(
                              imageUrl: 'https://wmahub.com/dashboards/artiste/uploads/${project['cover_path']}',
                              fit: BoxFit.cover,
                              width: double.infinity,
                              placeholder: (context, url) => Container(color: Colors.white10),
                              errorWidget: (context, url, error) => const Center(
                                child: Icon(Icons.music_note, color: AppTheme.primaryColor, size: 40),
                              ),
                            )
                          : const Center(
                              child: Icon(Icons.music_note, color: AppTheme.primaryColor, size: 40),
                            ),
                      Center(
                        child: Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: Colors.black.withValues(alpha: 0.5),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.play_arrow_rounded, color: Colors.white, size: 24),
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
                        project['title'] ?? 'Sans titre',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        project['artist_name'] ?? 'Artiste',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 10, color: AppTheme.textGrey),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ).animate().fadeIn(delay: 100.ms).scale(begin: const Offset(0.95, 0.95)),
        if (_isLoggedIn)
          Positioned(
            top: 6,
            right: 6,
            child: GestureDetector(
              onTap: _toggleFavorite,
              behavior: HitTestBehavior.opaque,
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 220),
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: _isFavorite
                      ? Colors.redAccent.withValues(alpha: 0.9)
                      : Colors.black.withValues(alpha: 0.55),
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.3),
                      blurRadius: 4,
                    ),
                  ],
                ),
                child: Icon(
                  _isFavorite ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                  color: Colors.white,
                  size: 15,
                ),
              ),
            ),
          ),
      ],
    );
  }
}
