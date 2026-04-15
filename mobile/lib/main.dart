import 'package:flutter/material.dart';
import 'screens/splash_screen.dart';
import 'utils/app_theme.dart';

void main() {
  runApp(const WMAHubApp());
}

class WMAHubApp extends StatelessWidget {
  const WMAHubApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'WMA Hub',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.darkTheme,
      home: const SplashScreen(),
    );
  }
}
