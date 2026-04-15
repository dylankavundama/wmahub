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
  bool _isFetchingMore = false;
  bool _hasMore = true;
  bool _paginationError = false;

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

    return RefreshIndicator(
      onRefresh: _loadInitialData,
      color: AppTheme.primaryColor,
      child: CustomScrollView(
        controller: _scrollController,
        slivers: [
          // Sticky Search/Logo Bar
          SliverAppBar(
            floating: true,
            title: Row(
              children: [
                Image.asset(
                  'assets/logo.png',
                  height: 32,
                  errorBuilder: (c, e, s) => const Icon(
                    Icons.music_note,
                    color: AppTheme.primaryColor,
                  ),
                ),
                const SizedBox(width: 12),
                const Text(
                  'WMA UA',
                  style: TextStyle(
                    fontWeight: FontWeight.w900,
                    letterSpacing: 1,
                  ),
                ),
              ],
            ),
            actions: [
              IconButton(
                icon: const Icon(Icons.notifications_none),
                onPressed: () {},
              ),
            ],
            backgroundColor: AppTheme.backgroundColor.withValues(alpha: 0.8),
          ),

          // Hero Slider
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
            SliverPadding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              sliver: SliverList(
                delegate: SliverChildBuilderDelegate((context, index) {
                  if (index == _posts.length) {
                    if (_paginationError) {
                      return _buildPaginationError();
                    }
                    return _hasMore
                        ? _buildLoadingIndicator()
                        : _buildNoMoreContent();
                  }
                  return _buildPostCard(_posts[index]);
                }, childCount: _posts.length + 1),
              ),
            ),

          const SliverToBoxAdapter(child: SizedBox(height: 100)),
        ],
      ),
    );
  }

  Widget _buildHeroSlider() {
    return CarouselSlider(
      options: CarouselOptions(
        height: 300.0,
        autoPlay: true,
        enlargeCenterPage: true,
        viewportFraction: 0.92,
        autoPlayInterval: const Duration(seconds: 5),
      ),
      items: _slides.map((slide) {
        return Container(
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(24)),
          child: Stack(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(24),
                child: CachedNetworkImage(
                  imageUrl: slide['image_path'] ?? '',
                  fit: BoxFit.cover,
                  width: double.infinity,
                  height: 300,
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
              Container(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(24),
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.transparent,
                      Colors.black.withValues(alpha: 0.8),
                    ],
                  ),
                ),
                padding: const EdgeInsets.all(20),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.end,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      slide['title'] ?? '',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ).animate().fadeIn().slideX(),
                    if (slide['subtitle'] != null)
                      Text(
                        slide['subtitle'],
                        style: const TextStyle(
                          color: Colors.white70,
                          fontSize: 13,
                        ),
                      ).animate().fadeIn(delay: 200.ms),
                  ],
                ),
              ),
            ],
          ),
        );
      }).toList(),
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

  Widget _buildSliderShimmer() {
    return Shimmer.fromColors(
      baseColor: Colors.white.withValues(alpha: 0.1),
      highlightColor: Colors.white.withValues(alpha: 0.2),
      child: Container(
        height: 300,
        margin: const EdgeInsets.symmetric(horizontal: 20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
        ),
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
}
