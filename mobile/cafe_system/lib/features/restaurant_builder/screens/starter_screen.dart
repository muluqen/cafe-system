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
    // 1. EXTRACTED HERE: This prevents the "Cannot use ref after disposed" crash!
    final authNotifier = ref.read(authProvider.notifier);
    
    final authState = ref.watch(authProvider);
    final featured = authState.publicRestaurants;

    final isMobile = MediaQuery.of(context).size.width < 800;

    final themeMode = ref.watch(themeProvider);
    final isDark = themeMode == ThemeMode.dark;

    final bgGradient = isDark
        ? [
            const Color(0xFF071B34),
            const Color(0xFF0E2A4D),
          ]
        : [
            const Color(0xFFF8FAFC),
            const Color(0xFFE2E8F0),
          ];

    final primaryText =
        isDark ? const Color(0xFFEAF2FF) : const Color(0xFF0F172A);

    final secondaryText =
        isDark ? Colors.white70 : Colors.black54;

    return Scaffold(
      body: Container(
        width: double.infinity,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: bgGradient,
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(
              horizontal: 28,
              vertical: 24,
            ),
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  /// TOP BAR
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        "PLATRICK",
                        style: TextStyle(
                          color: isDark
                              ? Colors.orangeAccent
                              : Colors.teal.shade700,
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 3,
                        ),
                      ),
              
                      /// THEME BUTTON
                      Container(
                        decoration: BoxDecoration(
                          color: isDark
                              ? Colors.white10
                              : Colors.black12,
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: IconButton(
                          icon: Icon(
                            isDark
                                ? Icons.light_mode_rounded
                                : Icons.dark_mode_rounded,
                            color: primaryText,
                          ),
                          onPressed: () {
                            ref
                                .read(themeProvider.notifier)
                                .toggleTheme();
                          },
                        ),
                      ),
                    ],
                  ),
              const SizedBox(height: 80),
              
                  /// MAIN TITLE
                  Text(
                    "Find your next\ncafe in minutes.",
                    style: TextStyle(
                      color: primaryText,
                      fontSize: isMobile ? 44 : 64,
                      fontWeight: FontWeight.w800,
                      height: 1.0,
                    ),
                  ),
              
                  const SizedBox(height: 28),
              
                  /// SUBTITLE
                  SizedBox(
                    width: isMobile ? double.infinity : 650,
                    child: Text(
                      "Sign in as a customer to browse nearby restaurants, reserve a table, and order before you arrive.",
                      style: TextStyle(
                        color: secondaryText,
                        fontSize: 18,
                        height: 1.6,
                      ),
                    ),
                  ),
              
                  const SizedBox(height: 40),
              
                  /// FEATURED RESTAURANTS
                  if (authState.loading && featured.isEmpty)
                    Row(
                      children: [
                        const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Text(
                          "Loading cafes...",
                          style: TextStyle(
                            color: secondaryText,
                          ),
                        ),
                      ],
                    )
                  else if (featured.isNotEmpty) ...[
                    Text(
                      "FEATURED NEAR YOU",
                      style: TextStyle(
                        color: secondaryText.withOpacity(0.7),
                        fontSize: 11,
                        letterSpacing: 2,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
              
                    const SizedBox(height: 16),
              
                    Wrap(
                      spacing: 10,
                      runSpacing: 10,
                      children: featured.take(6).map((res) {
                        return Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 10,
                          ),
                          decoration: BoxDecoration(
                            color: isDark
                                ? Colors.white.withOpacity(0.05)
                                : Colors.white.withOpacity(0.7),
                            borderRadius: BorderRadius.circular(30),
                            border: Border.all(
                              color: isDark
                                  ? Colors.white10
                                  : Colors.black12,
                            ),
                          ),
                          child: Text(
                            res['name'] ?? '',
                            style: TextStyle(
                              color: primaryText,
                              fontSize: 13,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        );
                      }).toList(),
                    ),
                  ],
              
                  const SizedBox(height: 60),
              
                  /// CONTINUE BUTTON
                  SizedBox(
                    width: isMobile ? double.infinity : 220,
                    height: 58,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFFF7A45),
                        foregroundColor: Colors.white,
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(18),
                        ),
                      ),
                      onPressed: () {
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (context) => LoginPage(
                              isSignup: false,
                              onToggle: () {},
                              loading: authState.loading,
                              error: authState.error,
                              onSubmit: (data) async {
                                // 2. SAFELY USE authNotifier HERE
                                try {
                                  final type = data["type"];

                                  if (type == "customer_login") {
                                    await authNotifier.login(
                                      data["email"],
                                      data["password"],
                                    );
                                  } else if (type == "customer_signup") {
                                    await authNotifier.register({
                                      "name": data["name"],
                                      "email": data["email"],
                                      "password": data["password"],
                                    });
                                  } else if (type == "staff_login") {
                                    await authNotifier.login(
                                      data["email"],
                                      data["password"],
                                      accessKey: data["access_key"], 
                                    );
                                  } else if (type == "team_signup") {
                                    await authNotifier.register({
                                      "name": data["name"],
                                      "email": data["email"],
                                      "password": data["password"],
                                      "access_key": data["access_key"],
                                    });
                                  } else if (type == "restaurant_signup") {
                                    await authNotifier.registerRestaurant({
                                      "restaurant_name": data["restaurant_name"],
                                      "owner_name": data["owner_name"],
                                      "email": data["email"],
                                      "password": data["password"],
                                      "access_key": data["access_key"],
                                    });
                                  }
                                } catch (e) {
                                  debugPrint("Auth error: $e");
                                }
                              },
                            ),
                          ),
                        );
                      },
                      child: const Text(
                        "Continue",
                        style: TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 60),
              
                  /// FOOTER
                  Center(
                    child: Text(
                      "Discover cafes around you",
                      style: TextStyle(
                        color: secondaryText,
                        fontSize: 14,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}