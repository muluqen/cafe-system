import 'package:flutter/material.dart';
import '../theme/app_colors.dart';

class AppBackground extends StatelessWidget {
  final Widget child;

  const AppBackground({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                AppColors.bgMain,
                AppColors.bgAccent,
              ],
            ),
          ),
        ),

        Positioned(
          top: -120,
          right: -90,
          child: _glow(const Color.fromRGBO(52, 211, 153, 0.20)),
        ),

        Positioned(
          bottom: -170,
          left: -120,
          child: _glow(const Color.fromRGBO(56, 189, 248, 0.20)),
        ),

        child,
      ],
    );
  }

  Widget _glow(Color color) {
    return Container(
      width: 460,
      height: 460,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: color,
      ),
    );
  }
}