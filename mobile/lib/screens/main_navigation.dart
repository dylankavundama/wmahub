import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'accueil_screen.dart';
import 'services_screen.dart';
import 'distributions_screen.dart';
import 'about_screen.dart';
import 'profile_screen.dart';
import 'no_internet_screen.dart';

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
  String _userRole = 'artiste'; // défaut sécurisé
  StreamSubscription? _connectivitySubscription;

  @override
  void initState() {
    super.initState();
    _loadRole();
    _checkInitialConnectivity();
    _connectivitySubscription = Connectivity().onConnectivityChanged.listen(
      (results) => setState(
        () => _isOffline = results.contains(ConnectivityResult.none),
      ),
    );
  }

  Future<void> _loadRole() async {
    final prefs = await SharedPreferences.getInstance();
    final userJson = prefs.getString('auth_user');
    if (userJson != null) {
      final user = json.decode(userJson) as Map<String, dynamic>;
      final role = (user['role'] ?? 'artiste').toString().toLowerCase().trim();
      if (mounted) setState(() => _userRole = role);
    }
  }

  Future<void> _checkInitialConnectivity() async {
    final result = await Connectivity().checkConnectivity();
    if (mounted)
      setState(() => _isOffline = result.contains(ConnectivityResult.none));
  }

  @override
  void dispose() {
    _connectivitySubscription?.cancel();
    super.dispose();
  }

  /// L'onglet Assistant IA n'est visible que pour le rôle 'artiste'
  bool get _isArtiste => _userRole == 'artiste';

  List<Widget> get _screens {
    if (_isArtiste) {
      return [
        const AccueilScreen(),
        const ServicesScreen(),
        const DistributionsScreen(),
        // const WritingAssistantScreen(),
        const AboutScreen(),
        const ProfileScreen(),
      ];
    }
    // Rôle non-artiste (Simple User / Agent) : pas de Services ni de Distributions
    return [
      const AccueilScreen(),
      const AboutScreen(),
      const ProfileScreen(),
    ];
  }

  List<BottomNavigationBarItem> get _navItems {
    if (_isArtiste) {
      return const [
        BottomNavigationBarItem(
          icon: Icon(Icons.home_outlined),
          activeIcon: Icon(Icons.home),
          label: 'Accueil',
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.business_center_outlined),
          activeIcon: Icon(Icons.business_center),
          label: 'Services',
        ),
        // BottomNavigationBarItem(
        //   icon: Icon(Icons.auto_awesome_outlined),
        //   activeIcon: Icon(Icons.auto_awesome),
        //   label: 'Assistant IA',
        // ),
        BottomNavigationBarItem(
          icon: Icon(Icons.music_note_outlined),
          activeIcon: Icon(Icons.music_note),
          label: 'Distributions',
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.info_outline),
          activeIcon: Icon(Icons.info),
          label: 'À propos',
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.person_outline),
          activeIcon: Icon(Icons.person),
          label: 'Profil',
        ),
      ];
    }
    // Rôle non-artiste : 3 onglets (Accueil, À propos, Profil)
    return const [
      BottomNavigationBarItem(
        icon: Icon(Icons.home_outlined),
        activeIcon: Icon(Icons.home),
        label: 'Accueil',
      ),
      BottomNavigationBarItem(
        icon: Icon(Icons.info_outline),
        activeIcon: Icon(Icons.info),
        label: 'À propos',
      ),
      BottomNavigationBarItem(
        icon: Icon(Icons.person_outline),
        activeIcon: Icon(Icons.person),
        label: 'Profil',
      ),
    ];
  }

  void _onTabTap(int index) {
    _loadRole();
    final screens = _screens;
    if (index >= 0 && index < screens.length) {
      setState(() => _selectedIndex = index);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isOffline) {
      return NoInternetScreen(
        onRetry: () async {
          final result = await Connectivity().checkConnectivity();
          setState(() => _isOffline = result.contains(ConnectivityResult.none));
        },
      );
    }

    final screens = _screens;
    final safeIndex = _selectedIndex.clamp(0, screens.length - 1);

    return Scaffold(
      body: IndexedStack(index: safeIndex, children: screens),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: safeIndex,
        type: BottomNavigationBarType.fixed,
        onTap: _onTabTap,
        items: _navItems,
      ),
    );
  }
}
