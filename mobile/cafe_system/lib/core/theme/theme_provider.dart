import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

final themeProvider = StateNotifierProvider<ThemeNotifier, ThemeMode>((ref) => ThemeNotifier());

class ThemeNotifier extends StateNotifier<ThemeMode> {
  ThemeNotifier() : super(ThemeMode.dark); // Default to dark like your Vue app

  void toggleTheme() {
    state = (state == ThemeMode.light) ? ThemeMode.dark : ThemeMode.light;
  }
}

class AppTheme {
  // LIGHT THEME CONFIG
  static ThemeData light = ThemeData(
    brightness: Brightness.light,
    scaffoldBackgroundColor: Colors.white,
    primaryColor: const Color(0xFFC2410C),
    colorScheme: const ColorScheme.light(
      primary: Color(0xFFC2410C),
      surface: Color(0xFFF8FAFC),
      onSurface: Colors.black87,
    ),
  );

  // DARK THEME CONFIG
  static ThemeData dark = ThemeData(
    brightness: Brightness.dark,
    scaffoldBackgroundColor: const Color(0xFF0F172A),
    primaryColor: const Color(0xFFC2410C),
    colorScheme: const ColorScheme.dark(
      primary: Color(0xFFC2410C),
      surface: Color(0xFF111827),
      onSurface: Colors.white,
    ),
  );
}