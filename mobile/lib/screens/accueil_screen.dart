import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:carousel_slider_plus/carousel_slider_plus.dart';
import 'package:shimmer/shimmer.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'dart:async';
import '../services/wordpress_service.dart';
import '../utils/app_theme.dart';
import 'post_detail_screen.dart';
import 'no_internet_screen.dart';
import 'slide_detail_screen.dart';
import 'main_navigation.dart';

class AccueilScreen extends StatefulWidget {
  const AccueilScreen({super.key});

  @override
  State<AccueilScreen> createState() => _AccueilScreenState();
}

class _AccueilScreenState extends State<AccueilScreen> {
  final WordPressService _wpService = WordPressService();
  final ScrollController _scrollController = ScrollController();

  List<dynamic> _posts = [];
  List<dynamic> _slides = [];
  bool _isPostsLoading = true;
  bool _isSlidesLoading = true;
  bool _isError = false;
  bool _isOffline = false;
  StreamSubscription? _connectivitySubscription;
  Timer? _refreshTimer;

  // Pagination
  int _currentPage = 1;
  int _currentSlideIndex = 0;
  bool _isFetchingMore = false;
  bool _hasMore = true;
  bool _paginationError = false;

  // Search
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _checkInitialConnectivity();
    _loadInitialData();
    _scrollController.addListener(_onScroll);
    _connectivitySubscription = Connectivity().onConnectivityChanged.listen((
      results,
    ) {
      if (results.contains(ConnectivityResult.none)) {
        setState(() => _isOffline = true);
      } else {
        if (_isOffline) {
          setState(() => _isOffline = false);
          _loadInitialData();
        }
      }
    });

    // Auto-refresh every 10 minutes
    _refreshTimer = Timer.periodic(const Duration(minutes: 10), (timer) {
      if (!_isOffline) {
        _loadInitialData();
      }
    });
  }

  Future<void> _checkInitialConnectivity() async {
    final result = await Connectivity().checkConnectivity();
    if (result.contains(ConnectivityResult.none)) {
      setState(() => _isOffline = true);
    }
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _connectivitySubscription?.cancel();
    _refreshTimer?.cancel();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      if (!_isFetchingMore && _hasMore && !_isError) {
        _loadMorePosts();
      }
    }
  }

  Future<void> _loadInitialData() async {
    setState(() {
      _isPostsLoading = true;
      _isSlidesLoading = true;
      _isError = false;
      _currentPage = 1;
      _hasMore = true;
      _paginationError = false;
    });

    try {
      final results = await Future.wait([
        _wpService.fetchHeroSlides(),
        _wpService.fetchPosts(page: 1, perPage: 10),
      ]);

      if (mounted) {
        setState(() {
          _slides = results[0];
          _posts = results[1];
          _isSlidesLoading = false;
          _isPostsLoading = false;
          if (_posts.length < 10) _hasMore = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isError = true;
          _isPostsLoading = false;
          _isSlidesLoading = false;
        });
      }
    }
  }

  Future<void> _loadMorePosts() async {
    setState(() => _isFetchingMore = true);

    try {
      final nextPage = _currentPage + 1;
      final newPosts = await _wpService.fetchPosts(page: nextPage, perPage: 10);

      if (mounted) {
        setState(() {
          if (newPosts.isEmpty) {
            _hasMore = false;
          } else {
            _posts.addAll(newPosts);
            _currentPage = nextPage;
            if (newPosts.length < 10) _hasMore = false;
          }
          _isFetchingMore = false;
          _paginationError = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isFetchingMore = false;
          _paginationError = true;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isOffline && _posts.isEmpty) {
      return NoInternetScreen(onRetry: _loadInitialData);
    }
    if (_isError && _posts.isEmpty) return _buildErrorView();

    return Stack(
      children: [
        RefreshIndicator(
          onRefresh: _loadInitialData,
          color: AppTheme.primaryColor,
          child: CustomScrollView(
            controller: _scrollController,
            slivers: [
              // Hero Slider as the first element
              if (_isSlidesLoading)
                SliverToBoxAdapter(child: _buildSliderShimmer())
              else if (_slides.isNotEmpty)
                SliverToBoxAdapter(child: _buildHeroSlider()),

              // Sections Title
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 30, 20, 15),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      if (_searchQuery.isNotEmpty)
                        Expanded(
                          child: Text(
                            'Résultats pour "$_searchQuery"',
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 1,
                              color: AppTheme.primaryColor,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        )
                      else
                        const Text(
                          'DERNIÈRES ACTUALITÉS',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 1.5,
                            color: AppTheme.primaryColor,
                          ),
                        ),
                      if (_isFetchingMore)
                        const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: AppTheme.primaryColor,
                          ),
                        ),
                    ],
                  ),
                ),
              ),

              // Posts List
              if (_isPostsLoading && _posts.isEmpty)
                SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (context, index) => _buildPostShimmer(),
                    childCount: 5,
                  ),
                )
              else
                Builder(
                  builder: (context) {
                    final filtered = _searchQuery.isEmpty
                        ? _posts
                        : _posts.where((post) {
                            final title = (post['title']?['rendered'] ?? '')
                                .toString()
                                .toLowerCase();
                            return title.contains(_searchQuery.toLowerCase());
                          }).toList();

                    if (filtered.isEmpty && _searchQuery.isNotEmpty) {
                      return SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(
                            vertical: 60,
                            horizontal: 20,
                          ),
                          child: Column(
                            children: [
                              const Icon(
                                Icons.search_off,
                                size: 48,
                                color: AppTheme.textGrey,
                              ),
                              const SizedBox(height: 16),
                              Text(
                                'Aucun résultat pour "$_searchQuery"',
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  color: AppTheme.textGrey,
                                  fontSize: 14,
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }

                    return SliverPadding(
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      sliver: SliverList(
                        delegate: SliverChildBuilderDelegate((context, index) {
                          if (index == filtered.length) {
                            if (_searchQuery.isNotEmpty)
                              return const SizedBox(height: 40);
                            if (_paginationError)
                              return _buildPaginationError();
                            return _hasMore
                                ? _buildLoadingIndicator()
                                : _buildNoMoreContent();
                          }
                          return _buildPostCard(filtered[index]);
                        }, childCount: filtered.length + 1),
                      ),
                    );
                  },
                ),

              const SliverToBoxAdapter(child: SizedBox(height: 100)),
            ],
          ),
        ),

        // Transparent Top Bar
        Positioned(
          top: 0,
          left: 0,
          right: 0,
          child: Container(
            height: MediaQuery.of(context).padding.top + 60,
            padding: EdgeInsets.only(top: MediaQuery.of(context).padding.top),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Colors.black.withValues(alpha: 0.5),
                  Colors.transparent,
                ],
              ),
            ),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(
                children: [
                  Image.asset(
                    'assets/logo.png',
                    height: 36,
                    errorBuilder: (c, e, s) => const Icon(
                      Icons.music_note,
                      color: AppTheme.primaryColor,
                    ),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(
                      Icons.search,
                      color: Colors.white,
                      size: 26,
                    ),
                    onPressed: () => _showSearchPanel(context),
                  ),
                  IconButton(
                    icon: const Icon(
                      Icons.notifications,
                      color: Colors.white,
                      size: 26,
                    ),
                    onPressed: () => _showNotificationsPanel(context),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildHeroSlider() {
    return Stack(
      children: [
        CarouselSlider(
          options: CarouselOptions(
            height: 550.0,
            autoPlay: true,
            viewportFraction: 1.0,
            autoPlayInterval: const Duration(seconds: 8),
            onPageChanged: (index, reason) {
              setState(() => _currentSlideIndex = index);
            },
          ),
          items: _slides.asMap().entries.map((entry) {
            final index = entry.key;
            final slide = entry.value;
            final isEven = index % 2 == 0;

            return GestureDetector(
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => SlideDetailScreen(slide: slide),
                  ),
                );
              },
              child: Stack(
                children: [
                  // Background Image
                  Hero(
                    tag: 'slide_${slide['image_path']}',
                    child: CachedNetworkImage(
                      imageUrl: slide['image_path'] ?? '',
                      fit: BoxFit.cover,
                      width: double.infinity,
                      height: 550,
                      placeholder: (context, url) => _buildSliderShimmer(),
                      errorWidget: (context, url, error) => Container(
                        color: AppTheme.cardColor,
                        child: const Center(
                          child: Icon(
                            Icons.broken_image_outlined,
                            color: Colors.white24,
                            size: 40,
                          ),
                        ),
                      ),
                    ),
                  ),

                  // Gradient Overlay
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        stops: const [0.0, 0.4, 0.7, 1.0],
                        colors: [
                          Colors.black.withValues(alpha: 0.6),
                          Colors.transparent,
                          Colors.black.withValues(alpha: 0.5),
                          AppTheme.backgroundColor,
                        ],
                      ),
                    ),
                  ),

                  // Content
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 40),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        const SizedBox(height: 100),
                        const Spacer(),

                        // Title
                        Text(
                          (slide['title'] ?? '').toUpperCase(),
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 26,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 2,
                            height: 1.1,
                          ),
                        ).animate().fadeIn().scale(
                          begin: const Offset(0.9, 0.9),
                        ),

                        const SizedBox(height: 12),

                        // Tags
                        if (slide['subtitle'] != null)
                          Text(
                            slide['subtitle'].toString().toUpperCase(),
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              color: Colors.white70,
                              fontSize: 14,
                              fontWeight: FontWeight.w500,
                              letterSpacing: 1.2,
                            ),
                          ).animate().fadeIn(delay: 300.ms),

                        const SizedBox(height: 30),

                        // Action Buttons - Alternating
                        if (isEven)
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton(
                              onPressed: () {
                                final state = context.findAncestorStateOfType<MainNavigationState>();
                                state?.jumpToTab(2); // Distributions
                              },
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppTheme.primaryColor,
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 16),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(16),
                                ),
                                elevation: 0,
                              ),
                              child: const Text(
                                'DISTRIBUTIONS',
                                style: TextStyle(fontWeight: FontWeight.w900, letterSpacing: 1),
                              ),
                            ).animate().fadeIn(delay: 400.ms).slideY(begin: 0.2),
                          )
                        else
                          SizedBox(
                            width: double.infinity,
                            child: OutlinedButton(
                              onPressed: () {
                                final state = context.findAncestorStateOfType<MainNavigationState>();
                                state?.jumpToTab(4); // Profil / Rejoindre
                              },
                              style: OutlinedButton.styleFrom(
                                foregroundColor: Colors.white,
                                side: const BorderSide(color: Colors.white24, width: 2),
                                padding: const EdgeInsets.symmetric(vertical: 16),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(16),
                                ),
                              ),
                              child: const Text(
                                'REJOINDRE WMA',
                                style: TextStyle(fontWeight: FontWeight.w900, letterSpacing: 1),
                              ),
                            ).animate().fadeIn(delay: 400.ms).slideY(begin: 0.2),
                          ),

                        const SizedBox(height: 60),
                      ],
                    ),
                  ),
                ],
              ),
            );
          }).toList(),
        ),

        // Dots Indicator
        Positioned(
          bottom: 20,
          left: 0,
          right: 0,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: _slides.asMap().entries.map((entry) {
              return Container(
                width: _currentSlideIndex == entry.key ? 8 : 4,
                height: 4,
                margin: const EdgeInsets.symmetric(horizontal: 4),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(2),
                  color: Colors.white.withValues(
                    alpha: _currentSlideIndex == entry.key ? 0.9 : 0.3,
                  ),
                ),
              );
            }).toList(),
          ),
        ),
      ],
    );
  }

  Widget _buildPostCard(dynamic post) {
    String title = post['title']['rendered'] ?? '';
    String imageUrl = "";
    try {
      imageUrl =
          post['_embedded']?['wp:featuredmedia']?[0]?['source_url'] ?? "";
    } catch (e) {}

    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(builder: (context) => PostDetailScreen(post: post)),
        );
      },
      child: Container(
        margin: const EdgeInsets.only(bottom: 20),
        decoration: BoxDecoration(
          color: AppTheme.cardColor,
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: Colors.white10),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (imageUrl.isNotEmpty)
              ClipRRect(
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(24),
                ),
                child: CachedNetworkImage(
                  imageUrl: imageUrl,
                  height: 200,
                  width: double.infinity,
                  fit: BoxFit.cover,
                  placeholder: (context, url) => Container(
                    height: 200,
                    color: Colors.white.withValues(alpha: 0.05),
                    child: const Center(
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                  ),
                  errorWidget: (context, url, error) => Container(
                    height: 200,
                    color: AppTheme.cardColor,
                    child: const Center(
                      child: Icon(
                        Icons.image_not_supported_outlined,
                        color: AppTheme.textGrey,
                      ),
                    ),
                  ),
                ),
              ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(
                        Icons.calendar_today,
                        size: 12,
                        color: AppTheme.textGrey,
                      ),
                      const SizedBox(width: 5),
                      Text(
                        post['date'].toString().split('T')[0],
                        style: const TextStyle(
                          color: AppTheme.textGrey,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Text(
                    title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ).animate().fadeIn().slideY(begin: 0.1),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.cloud_off, size: 60, color: AppTheme.textGrey),
          const SizedBox(height: 16),
          const Text(
            'Oups ! Erreur de connexion',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          const Text(
            'Vérifiez votre internet et réessayez.',
            style: TextStyle(color: AppTheme.textGrey),
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: _loadInitialData,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.primaryColor,
            ),
            child: const Text('RÉESSAYER'),
          ),
        ],
      ),
    );
  }

  Widget _buildLoadingIndicator() {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 32),
      child: Center(
        child: Column(
          children: [
            const CircularProgressIndicator(color: AppTheme.primaryColor),
            const SizedBox(height: 12),
            Text(
              'Chargement de la suite...',
              style: TextStyle(color: AppTheme.textGrey, fontSize: 12),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildNoMoreContent() {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 32),
      child: Center(
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(
              Icons.check_circle_outline,
              size: 16,
              color: AppTheme.textGrey,
            ),
            const SizedBox(width: 8),
            const Text(
              'Vous avez tout lu !',
              style: TextStyle(
                color: AppTheme.textGrey,
                fontSize: 13,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPaginationError() {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 32),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(
                Icons.error_outline,
                size: 16,
                color: Colors.redAccent,
              ),
              const SizedBox(width: 8),
              const Text(
                'Erreur de chargement !',
                style: TextStyle(
                  color: Colors.redAccent,
                  fontSize: 13,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          TextButton.icon(
            onPressed: _loadMorePosts,
            icon: const Icon(
              Icons.refresh,
              size: 16,
              color: AppTheme.primaryColor,
            ),
            label: const Text(
              'RÉESSAYER',
              style: TextStyle(color: AppTheme.primaryColor, fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPostShimmer() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
      child: Shimmer.fromColors(
        baseColor: Colors.white.withValues(alpha: 0.1),
        highlightColor: Colors.white.withValues(alpha: 0.2),
        child: Container(
          height: 280,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
          ),
        ),
      ),
    );
  }

  Widget _buildSliderShimmer() {
    return Shimmer.fromColors(
      baseColor: Colors.white.withValues(alpha: 0.1),
      highlightColor: Colors.white.withValues(alpha: 0.2),
      child: Container(
        height: 550,
        decoration: BoxDecoration(color: Colors.white),
      ),
    );
  }

  void _applySearch(String query, BuildContext ctx) {
    final q = query.trim();
    if (q.isEmpty) {
      setState(() => _searchQuery = '');
      return;
    }

    // Check if there are any results
    final hasResults = _posts.any((post) {
      final title = (post['title']?['rendered'] ?? '')
          .toString()
          .toLowerCase();
      return title.contains(q.toLowerCase());
    });

    if (hasResults) {
      setState(() => _searchQuery = q);
    } else {
      // Reset search so the list stays visible
      setState(() => _searchQuery = '');
      ScaffoldMessenger.of(ctx).showSnackBar(
        SnackBar(
          content: Text('Aucun résultat pour "$q"'),
          backgroundColor: AppTheme.cardColor,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          action: SnackBarAction(
            label: 'OK',
            textColor: AppTheme.primaryColor,
            onPressed: () {},
          ),
        ),
      );
    }
  }

  void _showSearchPanel(BuildContext context) {
    final controller = TextEditingController(text: _searchQuery);

    showModalBottomSheet(
      context: context,
      backgroundColor: AppTheme.backgroundColor,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) {
        return Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom,
          ),
          child: Container(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Handle
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.white24,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 20),
                // Search field
                TextField(
                  controller: controller,
                  autofocus: true,
                  style: const TextStyle(color: Colors.white),
                  decoration: InputDecoration(
                    hintText: 'Rechercher un article...',
                    hintStyle: const TextStyle(color: Colors.white38),
                    prefixIcon: const Icon(
                      Icons.search,
                      color: AppTheme.primaryColor,
                    ),
                    suffixIcon: IconButton(
                      icon: const Icon(Icons.close, color: Colors.white38),
                      onPressed: () {
                        controller.clear();
                        setState(() => _searchQuery = '');
                        Navigator.pop(context);
                      },
                    ),
                    filled: true,
                    fillColor: AppTheme.cardColor,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: BorderSide.none,
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(16),
                      borderSide: const BorderSide(
                        color: AppTheme.primaryColor,
                        width: 1.5,
                      ),
                    ),
                  ),
                  onChanged: (value) {
                    setState(() => _searchQuery = value);
                  },
                  onSubmitted: (value) {
                    _applySearch(value, context);
                    Navigator.pop(context);
                  },
                ),
                const SizedBox(height: 12),
                // Confirm button
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () {
                      _applySearch(controller.text, context);
                      Navigator.pop(context);
                    },
                    icon: const Icon(Icons.search),
                    label: const Text('Rechercher'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.primaryColor,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  void _showNotificationsPanel(BuildContext context) {
    // Récupérer les 3 derniers articles
    final recentPosts = _posts.take(3).toList();

    // Notifications automatiques du système (Rappels)
    final List<Map<String, String>> autoNotifications = [
      {
        "title": "🎵 Projet en attente",
        "body": "N'oubliez pas de finaliser vos projets pour distribution.",
      },
      {
        "title": "💰 Suivez vos revenus",
        "body": "De nouveaux rapports de streaming sont générés ce mois-ci.",
      },
      {
        "title": "🔥 Restez actif !",
        "body": "Partagez votre nouvelle sortie sur vos réseaux sociaux.",
      },
    ];

    showModalBottomSheet(
      context: context,
      backgroundColor: AppTheme.backgroundColor,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
      ),
      builder: (context) {
        return Container(
          height: MediaQuery.of(context).size.height * 0.7,
          padding: const EdgeInsets.symmetric(vertical: 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header du Drawer
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.white24,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 24),
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 24),
                child: Text(
                  'NOTIFICATIONS',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 2,
                    color: Colors.white,
                  ),
                ),
              ),
              const SizedBox(height: 24),

              Expanded(
                child: SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // SECTION 1 : Articles Récents
                      const Text(
                        'DERNIERS ARTICLES',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.primaryColor,
                          letterSpacing: 1.5,
                        ),
                      ),
                      const SizedBox(height: 16),
                      if (recentPosts.isEmpty)
                        const Text(
                          'Aucun article récent',
                          style: TextStyle(color: Colors.white38),
                        )
                      else
                        ...recentPosts.map((dynamicRawPost) {
                          final post = dynamicRawPost as Map<String, dynamic>;
                          final rawTitle =
                              post['title']?['rendered']?.toString() ?? '';
                          final title = rawTitle.replaceAll(
                            RegExp(r'<[^>]*>'),
                            '',
                          );

                          return ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading: Container(
                              width: 50,
                              height: 50,
                              decoration: BoxDecoration(
                                color: Colors.white10,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: const Icon(
                                Icons.article_outlined,
                                color: Colors.white54,
                              ),
                            ),
                            title: Text(
                              title,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            trailing: const Icon(
                              Icons.chevron_right,
                              color: Colors.white24,
                            ),
                            onTap: () {
                              Navigator.pop(context); // Fermer le panneau
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (context) =>
                                      PostDetailScreen(post: post),
                                ),
                              );
                            },
                          );
                        }).toList(),

                      const SizedBox(height: 32),
                      const Divider(color: Colors.white10),
                      const SizedBox(height: 24),

                      // SECTION 2 : Notifications Automatiques
                      const Text(
                        'RAPPELS AUTOMATIQUES',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.primaryColor,
                          letterSpacing: 1.5,
                        ),
                      ),
                      const SizedBox(height: 16),
                      ...autoNotifications.map((notif) {
                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: AppTheme.cardColor,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: AppTheme.primaryColor.withValues(
                                alpha: 0.1,
                              ),
                            ),
                          ),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: AppTheme.primaryColor.withValues(
                                    alpha: 0.1,
                                  ),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(
                                  Icons.notifications_active,
                                  color: AppTheme.primaryColor,
                                  size: 16,
                                ),
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      notif['title']!,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.bold,
                                        color: Colors.white,
                                        fontSize: 13,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      notif['body']!,
                                      style: const TextStyle(
                                        color: AppTheme.textGrey,
                                        fontSize: 11,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        );
                      }).toList(),
                    ],
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
