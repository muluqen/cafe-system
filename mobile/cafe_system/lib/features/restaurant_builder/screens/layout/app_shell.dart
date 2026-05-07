import 'package:cafe_system/features/restaurant_builder/screens/DashboardScreen.dart';
import 'package:flutter/material.dart';
import 'sidebar.dart';


class AppShell extends StatelessWidget {
  const AppShell({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Row(
        children: [
          /// SIDEBAR
          const SizedBox(
            width: 260,
            child: Sidebar(),
          ),

          /// MAIN CONTENT
          Expanded(
            child: DashboardScreen(),
          ),
        ],
      ),
    );
  }
}