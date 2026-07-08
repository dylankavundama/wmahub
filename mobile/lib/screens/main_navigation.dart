import 'dart:async';
import 'package:flutter/material.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:audioplayers/audioplayers.dart';
import 'accueil_screen.dart';
import 'distributions_screen.dart';
import 'profile_screen.dart';
import 'project_detail_screen.dart';
import '../services/audio_playback_service.dart';

class MainNavigation extends StatefulWidget {
  const MainNavigation({super.key});

  @override
  State<MainNavigation> createState() => MainNavigationState();
}

class MainNavigationState extends State<MainNavigation> {
  int _selectedIndex = 0;

  void jumpToTab(int index) {
    if (mounted) {
      setState(() => _selectedIndex = index);
    }
  }
  bool _isOffline = false;
  StreamSubscription? _connectivitySubscription;

  @override
  void initState() {
    super.initState();
    _checkInitialConnectivity();
    _connectivitySubscription = Connectivity().onConnectivityChanged.listen(
      (results) => setState(
        () => _isOffline = results.contains(ConnectivityResult.none),
      ),
    );
  }



  Future<void> _checkInitialConnectivity() async {
    final result = await Connectivity().checkConnectivity();
    if (mounted) {
      setState(() => _isOffline = result.contains(ConnectivityResult.none));
    }
  }

  @override
  void dispose() {
    _connectivitySubscription?.cancel();
    super.dispose();
  }



  List<Widget> get _screens => [
    const AccueilScreen(),
    const DistributionsScreen(),
    const ProfileScreen(),
  ];

  List<BottomNavigationBarItem> get _navItems => const [
    BottomNavigationBarItem(
      icon: Icon(Icons.article_outlined),
      activeIcon: Icon(Icons.article),
      label: 'Acceuil',
    ),
    BottomNavigationBarItem(
      icon: Icon(Icons.music_note_outlined),
      activeIcon: Icon(Icons.music_note),
      label: 'Distributions',
    ),
    BottomNavigationBarItem(
      icon: Icon(Icons.person_outline),
      activeIcon: Icon(Icons.person),
      label: 'Profil',
    ),

    
  ];

  void _onTabTap(int index) {
    final screens = _screens;
    if (index >= 0 && index < screens.length) {
      setState(() => _selectedIndex = index);
    }
  }

  @override
  Widget build(BuildContext context) {
    final screens = _screens;
    final safeIndex = _selectedIndex.clamp(0, _navItems.length - 1);
    final int currentIndex = _selectedIndex % _navItems.length;

    return Scaffold(
      body: Stack(
        children: [
          Column(
            children: [
              if (_isOffline)
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 16),
                  color: const Color(0xFFD84315),
                  child: SafeArea(
                    bottom: false,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.wifi_off_rounded, color: Colors.white, size: 14),
                        const SizedBox(width: 8),
                        const Text(
                          'Mode hors ligne — Données en cache',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            letterSpacing: 0.5,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              Expanded(
                child: IndexedStack(index: safeIndex, children: screens),
              ),
            ],
          ),
          Positioned(
            left: 16,
            right: 16,
            bottom: 16,
            child: ValueListenableBuilder<Map<String, dynamic>?>(
              valueListenable: AudioPlaybackService().currentProject,
              builder: (context, currentProject, _) {
                if (currentProject == null) return const SizedBox.shrink();
                return _buildMiniPlayer(currentProject);
              },
            ),
          ),
        ],
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: currentIndex,
        type: BottomNavigationBarType.fixed,
        onTap: _onTabTap,
        items: _navItems,
      ),
    );
  }

  Widget _buildMiniPlayer(Map<String, dynamic> project) {
    final title = project['title'] ?? 'Sans titre';
    final artist = project['artist_name'] ?? 'Artiste inconnu';
    final coverFile = project['cover_path'] ?? project['cover_file'] ?? '';
    final imageUrl = coverFile.isNotEmpty 
        ? (coverFile.startsWith('http') ? coverFile : "https://wmahub.com/dashboards/artiste/uploads/$coverFile") 
        : '';

    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => ProjectDetailScreen(project: project),
          ),
        );
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: const Color(0xEB16161A), // Sleek premium dark background
          borderRadius: BorderRadius.circular(30),
          border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.4),
              blurRadius: 16,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Row(
          children: [
            // Album art
            ClipRRect(
              borderRadius: BorderRadius.circular(20),
              child: SizedBox(
                width: 40,
                height: 40,
                child: imageUrl.isNotEmpty
                    ? CachedNetworkImage(
                        imageUrl: imageUrl,
                        fit: BoxFit.cover,
                        placeholder: (context, url) => Container(color: Colors.white10),
                        errorWidget: (context, url, error) => const Icon(Icons.music_note, color: Colors.white70),
                      )
                    : const Icon(Icons.music_note, color: Colors.white70),
              ),
            ),
            const SizedBox(width: 12),
            // Title & Artist
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    artist,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: Colors.white60,
                      fontSize: 11,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            // Play/Pause button
            ValueListenableBuilder<PlayerState>(
              valueListenable: AudioPlaybackService().playerState,
              builder: (context, state, _) {
                final isPlaying = state == PlayerState.playing;
                return ValueListenableBuilder<bool>(
                  valueListenable: AudioPlaybackService().isLoading,
                  builder: (context, isLoading, _) {
                    if (isLoading) {
                      return const SizedBox(
                        width: 32,
                        height: 32,
                        child: Padding(
                          padding: EdgeInsets.all(6.0),
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Color(0xFFFF6600), // App primary color
                          ),
                        ),
                      );
                    }
                    return IconButton(
                      constraints: const BoxConstraints(),
                      padding: EdgeInsets.zero,
                      icon: Icon(
                        isPlaying ? Icons.pause_circle_filled_rounded : Icons.play_circle_filled_rounded,
                        color: const Color(0xFFFF6600),
                        size: 36,
                      ),
                      onPressed: () {
                        if (isPlaying) {
                          AudioPlaybackService().pause();
                        } else {
                          AudioPlaybackService().resume();
                        }
                      },
                    );
                  },
                );
              },
            ),
            const SizedBox(width: 8),
            // Stop button
            IconButton(
              constraints: const BoxConstraints(),
              padding: EdgeInsets.zero,
              icon: const Icon(
                Icons.close_rounded,
                color: Colors.white70,
                size: 22,
              ),
              onPressed: () {
                AudioPlaybackService().stop();
              },
            ),
          ],
        ),
      ),
    );
  }
}
