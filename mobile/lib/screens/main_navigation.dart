import 'package:flutter/material.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'dart:async';
import 'accueil_screen.dart';
import 'services_screen.dart';
import 'about_screen.dart';
import 'profile_screen.dart';
import 'no_internet_screen.dart';

class MainNavigation extends StatefulWidget {
  const MainNavigation({super.key});

  @override
  State<MainNavigation> createState() => _MainNavigationState();
}

class _MainNavigationState extends State<MainNavigation> {
  int _selectedIndex = 0;
  bool _isOffline = false;
  StreamSubscription? _connectivitySubscription;
  @override
  void initState() {
    super.initState();
    _checkInitialConnectivity();
    _connectivitySubscription = Connectivity().onConnectivityChanged.listen((
      results,
    ) {
      setState(() => _isOffline = results.contains(ConnectivityResult.none));
    });
  }

  Future<void> _checkInitialConnectivity() async {
    final result = await Connectivity().checkConnectivity();
    setState(() => _isOffline = result.contains(ConnectivityResult.none));
  }

  @override
  void dispose() {
    _connectivitySubscription?.cancel();
    super.dispose();
  }

  final List<Widget> _screens = [
    const AccueilScreen(),
    const ServicesScreen(),
    const AboutScreen(),
    const ProfileScreen(),
  ];

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

    return Scaffold(
      body: IndexedStack(index: _selectedIndex, children: _screens),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _selectedIndex,
        type: BottomNavigationBarType.fixed,
        onTap: (index) {
          setState(() => _selectedIndex = index);
        },
        items: const [
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
        ],
      ),
    );
  }
}
