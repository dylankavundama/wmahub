import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  static const primaryColor = Color(0xFFFF6600);
  static const secondaryColor = Color(0xFF1A1A1A);
  static const accentColor = Color(0xFFFF8533);
  static const backgroundColor = Color(0xFF0A0A0C);
  static const cardColor = Color(0xFF1E1E1E);
  static const textColor = Colors.white;
  static const textGrey = Colors.white60;

  static ThemeData darkTheme = ThemeData(
    brightness: Brightness.dark,
    primaryColor: primaryColor,
    scaffoldBackgroundColor: backgroundColor,
    cardColor: cardColor,
    textTheme: GoogleFonts.poppinsTextTheme(
      const TextTheme(
        headlineMedium: TextStyle(color: textColor, fontWeight: FontWeight.bold),
        bodyLarge: TextStyle(color: textColor),
        bodyMedium: TextStyle(color: textGrey),
      ),
    ),
    bottomNavigationBarTheme: const BottomNavigationBarThemeData(
      backgroundColor: secondaryColor,
      selectedItemColor: primaryColor,
      unselectedItemColor: textGrey,
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: primaryColor,
        foregroundColor: Colors.white,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        padding: const EdgeInsets.symmetric(vertical: 16),
      ),
    ),
  );
}
