import 'package:flutter/material.dart';
import '../../../core/widgets/glass_panel.dart';

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        children: [
          /// HERO SECTION
          GlassPanel(
            child: Row(
              children: [
                /// LEFT
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: const [
                      Text("Your Restaurant Space",
                          style: TextStyle(fontSize: 12)),

                      SizedBox(height: 10),

                      Text(
                        "Your restaurant runs on Platrick like it owns the place.",
                        style: TextStyle(
                          fontSize: 28,
                          fontWeight: FontWeight.bold,
                        ),
                      ),

                      SizedBox(height: 10),

                      Text(
                        "This is your operational home.",
                      ),
                    ],
                  ),
                ),

                /// RIGHT BADGE
                Expanded(
                  child: Column(
                    children: const [
                      Text("Signed in as"),
                      SizedBox(height: 6),
                      Text(
                        "Manager",
                        style: TextStyle(fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 20),

          /// CONTROL ROOMS
          GlassPanel(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: const [
                Text(
                  "Run the parts of your restaurant",
                  style: TextStyle(fontSize: 22),
                ),

                SizedBox(height: 10),

                Text("Tools available: dynamic"),
              ],
            ),
          ),
        ],
      ),
    );
  }
}