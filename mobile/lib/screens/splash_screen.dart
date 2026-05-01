import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'onboarding_screen.dart';
import 'main_navigation.dart';
import '../utils/app_theme.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _checkFirstLaunch();
  }

  Future<void> _requestNotificationPermissions() async {
    final FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin =
        FlutterLocalNotificationsPlugin();
    
    // Pour Android 13+
    await flutterLocalNotificationsPlugin
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.requestNotificationsPermission();
        
    // Pour iOS
    await flutterLocalNotificationsPlugin
        .resolvePlatformSpecificImplementation<IOSFlutterLocalNotificationsPlugin>()
        ?.requestPermissions(alert: true, badge: true, sound: true);
  }

  Future<void> _checkFirstLaunch() async {
    // Demander la permission pendant le splash screen
    await _requestNotificationPermissions();
    
    await Future.delayed(const Duration(seconds: 3));
    final prefs = await SharedPreferences.getInstance();
    final isFirstLaunch = prefs.getBool('first_launch') ?? true;

    if (!mounted) return;

    if (isFirstLaunch) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => const OnboardingScreen()),
      );
    } else {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => const MainNavigation()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            SizedBox(height: MediaQuery.of(context).size.height * 0.35),
            Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                // color: AppTheme.primaryColor.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Image.asset('assets/logo.png'),
            ).animate().scale(duration: 600.ms).fadeIn(),
            const SizedBox(height: 24),
            const Text(
              'WMA UA',
              style: TextStyle(
                fontSize: 32,
                fontWeight: FontWeight.w900,
                letterSpacing: 4,
              ),
            ).animate().slideY(begin: 0.5, end: 0, duration: 600.ms).fadeIn(),
            const SizedBox(height: 12),
            const Text(
              'We move, WMAFam',
              style: TextStyle(
                fontSize: 12,
                color: AppTheme.textGrey,
                letterSpacing: 2,
                fontWeight: FontWeight.bold,
              ),
            ).animate().fadeIn(delay: 500.ms),
            const Spacer(),
            const Padding(
              padding: EdgeInsets.only(bottom: 40.0),
              child: Text(
                'From WMA HUB',
                style: TextStyle(
                  color: AppTheme.textGrey,
                  fontSize: 14,
                  letterSpacing: 2,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ).animate().fadeIn(delay: 1.seconds),
          ],
        ),
      ),
    );
  }
}
