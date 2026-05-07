import 'package:flutter/widgets.dart';

Widget _infoCard(String title, String text) {
  return Container(
    width: double.infinity,
    margin: const EdgeInsets.only(bottom: 16),
    padding: const EdgeInsets.all(20),
    decoration: BoxDecoration(
      borderRadius: BorderRadius.circular(20),
      color: const Color(0xFF0F1C2D),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          title,
          style: const TextStyle(
            fontSize: 12,
            letterSpacing: 2,
            fontWeight: FontWeight.bold,
            color: Color(0xFFFF7A59),
          ),
        ),

        const SizedBox(height: 10),

        Text(
          text,
          style: const TextStyle(
            height: 1.5,
            color: Color(0xFF94A3B8),
          ),
        ),
      ],
    ),
  );
}