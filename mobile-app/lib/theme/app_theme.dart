import 'package:flutter/material.dart';

class AppTheme {
  AppTheme._();

  // Palet warna ramah lingkungan (selaras dengan web-backend/DESIGN.md, lulus WCAG AA)
  static const Color forestDark = Color(0xFF0D261B);
  static const Color forestPrimary = Color(0xFF146C38);
  static const Color forestAccent = Color(0xFF198746);
  static const Color canvasBg = Color(0xFFF4F7F4);
  static const Color surfaceWhite = Color(0xFFFFFFFF);
  static const Color borderSubtle = Color(0xFFD5E0D8);
  static const Color borderInput = Color(0xFFB8C9BD);
  static const Color inkPrimary = Color(0xFF111C16);
  static const Color inkMuted = Color(0xFF43544A);
  static const Color dangerRed = Color(0xFF991B1B);
  static const Color dangerSurface = Color(0xFFFEE2E2);

  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      scaffoldBackgroundColor: canvasBg,
      colorScheme: ColorScheme.fromSeed(
        seedColor: forestPrimary,
        primary: forestPrimary,
        onPrimary: Colors.white,
        surface: surfaceWhite,
        onSurface: inkPrimary,
        error: dangerRed,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: forestDark,
        foregroundColor: Colors.white,
        elevation: 0,
        centerTitle: false,
      ),
      cardTheme: CardThemeData(
        color: surfaceWhite,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: const BorderSide(color: borderSubtle),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surfaceWhite,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        labelStyle: const TextStyle(color: inkMuted, fontSize: 14),
        hintStyle: const TextStyle(color: inkMuted, fontSize: 14),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: borderInput),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: borderInput),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: forestPrimary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: dangerRed),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: forestPrimary,
          foregroundColor: Colors.white,
          minimumSize: const Size.fromHeight(48),
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(10),
          ),
          textStyle: const TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: forestPrimary,
          minimumSize: const Size.fromHeight(48),
          side: const BorderSide(color: forestPrimary, width: 1.5),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(10),
          ),
          textStyle: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
    );
  }
}
