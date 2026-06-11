import 'package:flutter/material.dart';

class PrimaryButton extends StatelessWidget {
  final String text;
  final VoidCallback? onPressed;
  final bool isLoading;
  final bool isSecondary;
  final IconData? icon;

  const PrimaryButton({
    super.key,
    required this.text,
    this.onPressed,
    this.isLoading = false,
    this.isSecondary = false,
    this.icon,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    final baseColor = isSecondary 
        ? (isDark ? Colors.tealAccent.withOpacity(0.1) : Colors.teal.shade50)
        : (isDark ? Colors.tealAccent : Colors.teal.shade700);

    final textColor = isSecondary 
        ? (isDark ? Colors.tealAccent : Colors.teal.shade800)
        : (isDark ? Colors.black87 : Colors.white);

    return SizedBox(
      height: 52,
      child: ElevatedButton(
        onPressed: isLoading ? null : onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: baseColor,
          foregroundColor: textColor,
          disabledBackgroundColor: baseColor.withOpacity(0.5),
          disabledForegroundColor: textColor.withOpacity(0.5),
          elevation: isSecondary ? 0 : 2,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
            side: isSecondary 
                ? BorderSide(color: isDark ? Colors.tealAccent.withOpacity(0.3) : Colors.teal.shade200)
                : BorderSide.none,
          ),
          padding: const EdgeInsets.symmetric(horizontal: 24),
        ),
        child: isLoading
            ? SizedBox(
                width: 24,
                height: 24,
                child: CircularProgressIndicator(
                  strokeWidth: 2.5,
                  valueColor: AlwaysStoppedAnimation<Color>(textColor),
                ),
              )
            : Row(
                mainAxisAlignment: MainAxisAlignment.center,
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (icon != null) ...[
                    Icon(icon, size: 20, color: textColor),
                    const SizedBox(width: 10),
                  ],
                  Text(
                    text,
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      letterSpacing: 0.5,
                      color: textColor,
                    ),
                  ),
                ],
              ),
      ),
    );
  }
}
