import 'dart:async';
import 'package:flutter/material.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'accueil_screen.dart';
import 'distributions_screen.dart';
import 'profile_screen.dart';

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
      body: Column(
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
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: currentIndex,
        type: BottomNavigationBarType.fixed,
        onTap: _onTabTap,
        items: _navItems,
      ),
    );
  }
}
