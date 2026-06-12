import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'onboarding_screen.dart';
import 'main_navigation.dart';
import '../utils/app_theme.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import '../services/logging_service.dart';

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
    if (kIsWeb) return;
    if (defaultTargetPlatform != TargetPlatform.android && defaultTargetPlatform != TargetPlatform.iOS) return;

    try {
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
    } catch (e) {
      LoggingService.warning("Erreur permissions notifications: $e");
    }
  }

  Future<void> _checkFirstLaunch() async {
    try {
      // Demander la permission pendant le splash screen
      await _requestNotificationPermissions();
      
      // Petit délai pour l'animation
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
    } catch (e) {
      LoggingService.critical("Erreur critique au démarrage: $e");
      // En cas d'erreur massive, on tente quand même d'aller à la navigation principale
      if (mounted) {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => const MainNavigation()),
        );
      }
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
              width: 150,
              height: 150,
              decoration: BoxDecoration(
                // color: AppTheme.primaryColor.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Image.asset('assets/logo.png', fit: BoxFit.cover, height: 150, width: 150),
            ).animate().scale(duration: 600.ms).fadeIn(),
            const SizedBox(height: 30),
            // const Text(
            //   'WMA UA',
            //   style: TextStyle(
            //     fontSize: 32,
            //     fontWeight: FontWeight.w900,
            //     letterSpacing: 4,
            //   ),
            // ).animate().slideY(begin: 0.5, end: 0, duration: 600.ms).fadeIn(),
            // const SizedBox(height: 12),
            // const Text(
            //   'We move, WMAFam',
            //   style: TextStyle(
            //     fontSize: 12,
            //     color: AppTheme.textGrey,
            //     letterSpacing: 2,
            //     fontWeight: FontWeight.bold,
            //   ),
            // ).animate().fadeIn(delay: 500.ms),
            const Spacer(),
            Padding(
              padding: const EdgeInsets.only(bottom: 40.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text(
                    'From WMA HUB',
                    style: TextStyle(
                      color: AppTheme.textGrey,
                      fontSize: 14,
                      letterSpacing: 2,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Développé par Next Byte Technology',
                    style: TextStyle(
                      color: AppTheme.textGrey.withValues(alpha: 0.7),
                      fontSize: 11,
                      letterSpacing: 1,
                    ),
                  ),
                ],
              ),
            ).animate().fadeIn(delay: 1.seconds),
          ],
        ),
      ),
    );
  }
}
