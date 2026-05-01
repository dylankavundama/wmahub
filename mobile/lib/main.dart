import 'package:flutter/material.dart';
import 'package:workmanager/workmanager.dart';
import 'screens/splash_screen.dart';
import 'utils/app_theme.dart';
import 'services/notification_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialisation des notifications locales
  await NotificationService.init();
  
  // Initialisation du gestionnaire de tâches en arrière-plan
  await Workmanager().initialize(
    callbackDispatcher,
    isInDebugMode: false,
  );

  // Planification du rappel tous les 48h
  NotificationService.scheduleReminder();

  runApp(const WMAHubApp());
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
