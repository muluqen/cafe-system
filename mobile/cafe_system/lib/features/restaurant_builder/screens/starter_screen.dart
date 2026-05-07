import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cafe_system/features/auth/providers/auth_provider.dart';
import 'package:cafe_system/core/theme/theme_provider.dart';
import 'package:cafe_system/features/restaurant_builder/screens/login_screen.dart';

class StarterPage extends ConsumerStatefulWidget {
  const StarterPage({super.key});

  @override
  ConsumerState<StarterPage> createState() => _StarterPageState();
}

class _StarterPageState extends ConsumerState<StarterPage> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      ref.read(authProvider.notifier).loadPublicRestaurants();
    });
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final featured = authState.publicRestaurants;
    final isMobile = MediaQuery.of(context).size.width < 800;
    final themeState = ref.watch(themeProvider);
    final isDark = themeState == ThemeMode.dark;

    return Scaffold( // Added Scaffold back to allow the Theme Toggle in the header/body
       backgroundColor: Theme.of(context).colorScheme.surface,
      body: Stack(
        children: [
          // 1. THEME TOGGLE (Top Right)
          Positioned(
            top: 40,
            right: 20,
            child: IconButton(
            icon: Icon(isDark ? Icons.light_mode : Icons.dark_mode),
            onPressed: () => ref.read(themeProvider.notifier).toggleTheme(),
          ),
          ),

          // 2. MAIN CONTENT
          Padding(
            padding: const EdgeInsets.all(40.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  "Platrick",
 style: TextStyle(
              color: isDark ? Colors.tealAccent : Colors.teal[700], // Dynamic
              fontSize: 14, 
              fontWeight: FontWeight.bold, 
              letterSpacing: 2
            ),
                ),
                const SizedBox(height: 20),
                const Text(
                  "Find your next\ncafe in minutes.",
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 38,
                    fontWeight: FontWeight.bold,
                    height: 1.1,
                  ),
                ),
                const SizedBox(height: 20),
                const Text(
                  "Sign in as a customer to browse nearby restaurants, reserve a table, and order before you arrive.",
                  style: TextStyle(
                    color: Colors.white60,
                    fontSize: 16,
                    height: 1.5,
                  ),
                ),
                const SizedBox(height: 40),

                // Featured Chips
                if (authState.loading && featured.isEmpty)
                  const CircularProgressIndicator(color: Colors.tealAccent)
                else ...[
                  const Text("FEATURED NEAR YOU",
                      style: TextStyle(color: Colors.white24, fontSize: 10)),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: featured.take(6).map((res) {
                      return Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.05),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.white10),
                        ),
                        child: Text(
                          res['name'] ?? '',
                          style: const TextStyle(color: Colors.white, fontSize: 12),
                        ),
                      );
                    }).toList(),
                  ),
                ],

                const SizedBox(height: 60),

                // 3. GET STARTED CLICKABLE TEXT
                InkWell(
                  onTap: () {
                    // Logic: If mobile, navigate. If desktop, do nothing (form is already there)
                    if (isMobile) {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => LoginPage(
                            isSignup: false,
                            onToggle: () {}, // You might want to handle this differently on mobile
                            loading: authState.loading,
                            error: authState.error,
                            onSubmit: (email, password, name) {
                              ref.read(authProvider.notifier).login(email, password);
                            },
                          ),
                        ),
                      );
                    }
                  },
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        isMobile ? "Get started →" : "Join the community",
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}