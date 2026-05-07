import 'package:cafe_system/core/theme/app_theme.dart';
import 'package:cafe_system/core/theme/theme_provider.dart' hide AppTheme;
import 'package:cafe_system/features/auth/providers/auth_provider.dart';
import 'package:cafe_system/features/restaurant_builder/screens/layout/app_shell.dart';
import 'package:cafe_system/features/restaurant_builder/screens/starter_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

void main() {
  runApp(const ProviderScope(child: MyApp()));
}

class MyApp extends ConsumerWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final themeMode = ref.watch(themeProvider);

    return MaterialApp(
      title: 'Cafe App',
      debugShowCheckedModeBanner: false,

      themeMode: themeMode,
      theme: AppTheme.light,
      darkTheme: ThemeData.dark(),


      home: authState.isAuthenticated
          ? const AppShell()
          : const StarterPage(),
    );
  }
}