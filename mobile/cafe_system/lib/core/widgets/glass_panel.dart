import 'package:flutter/material.dart';

class GlassPanel extends StatelessWidget {
  final Widget child;
  final EdgeInsetsGeometry padding;

  const GlassPanel({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(30),
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: padding,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(22),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            Color.fromRGBO(255, 255, 255, 0.95),
            Color.fromRGBO(247, 250, 254, 0.97),
          ],
        ),
        boxShadow: const [
          BoxShadow(
            blurRadius: 40,
            offset: Offset(0, 18),
            color: Color.fromRGBO(18, 23, 34, 0.10),
          )
        ],
      ),
      child: child,
    );
  }
}