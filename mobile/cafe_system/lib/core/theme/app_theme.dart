import 'package:flutter/material.dart';
import 'app_colors.dart';

class AppTheme {
  static ThemeData light = ThemeData(
    scaffoldBackgroundColor: AppColors.bgMain,
    fontFamily: 'PlusJakartaSans',
    useMaterial3: true,

    textTheme: const TextTheme(
      headlineLarge: TextStyle(
        fontSize: 42,
        height: 1.0,
        fontWeight: FontWeight.w700,
        color: AppColors.textMain,
      ),
      titleLarge: TextStyle(
        fontSize: 28,
        fontWeight: FontWeight.w700,
        color: AppColors.textMain,
      ),
      bodyMedium: TextStyle(
        fontSize: 14,
        height: 1.7,
        color: AppColors.textSoft,
      ),
    ),
  );
}