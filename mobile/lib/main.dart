import 'dart:ui';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:workmanager/workmanager.dart';
import 'package:firebase_core/firebase_core.dart';
import 'screens/splash_screen.dart';
import 'utils/app_theme.dart';
import 'services/notification_service.dart';
import 'services/logging_service.dart';

void main() async {
  // 1. Initialisation vitale des widgets
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();

  // 2. Lancement immédiat de l'UI pour quitter le splash screen natif
  runApp(const WMAHubApp());

  // 3. Configuration des logs (en arrière-plan)
  FlutterError.onError = (FlutterErrorDetails details) {
    FlutterError.presentError(details);
    LoggingService.logException(
      details.exception,
      details.stack,
      context: details.library ?? 'Flutter',
    );
  };

  PlatformDispatcher.instance.onError = (error, stack) {
    LoggingService.logException(error, stack, context: 'PlatformDispatcher');
    return true;
  };

  // 4. Initialisations secondaires (ne bloquent pas l'UI)
  _initServices();
}

/// Initialise les services de manière asynchrone sans bloquer le démarrage de l'UI
Future<void> _initServices() async {
  try {
    if (!kIsWeb && (defaultTargetPlatform == TargetPlatform.android || defaultTargetPlatform == TargetPlatform.iOS)) {
      await NotificationService.init();
    }
  } catch (e) {
    LoggingService.error("Erreur NotificationService: $e");
  }

  try {
    if (!kIsWeb && (defaultTargetPlatform == TargetPlatform.android || defaultTargetPlatform == TargetPlatform.iOS)) {
      await Workmanager().initialize(
        callbackDispatcher,
        isInDebugMode: false,
      );
      NotificationService.scheduleReminder();
    }
  } catch (e) {
    LoggingService.error("Erreur Workmanager: $e");
  }
}

class WMAHubApp extends StatelessWidget {
  const WMAHubApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'WMA UA',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.darkTheme,
      home: const SplashScreen(),
    );
  }
}
