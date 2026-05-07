import 'package:cafe_system/core/theme/app_colors.dart';
import 'package:flutter/material.dart';


class Sidebar extends StatelessWidget {
  const Sidebar({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.sidebar,
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            "Platrick",
            style: TextStyle(
              color: Colors.white,
              fontSize: 22,
              fontWeight: FontWeight.bold,
            ),
          ),

          const SizedBox(height: 20),

          _navItem("Dashboard"),
          _navItem("Orders"),
          _navItem("Menu"),
          _navItem("Tables"),
        ],
      ),
    );
  }

  Widget _navItem(String title) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Text(
        title,
        style: const TextStyle(color: Colors.white70),
      ),
    );
  }
}