import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'package:cafe_system/features/auth/providers/auth_provider.dart';
import 'package:cafe_system/features/restaurant_builder/screens/login_screen.dart';
import 'package:cafe_system/features/restaurant_builder/screens/starter_screen.dart';

class AuthWrapper extends ConsumerStatefulWidget {
  const AuthWrapper({super.key});

  @override
  ConsumerState<AuthWrapper> createState() => _AuthWrapperState();
}

class _AuthWrapperState extends ConsumerState<AuthWrapper> {
  bool isSignup = false;

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final authNotifier = ref.read(authProvider.notifier);

    return Scaffold(
      resizeToAvoidBottomInset: true,
      body: LayoutBuilder(
        builder: (context, constraints) {
          return Row(
            children: [
              if (constraints.maxWidth > 900)
                const Expanded(
                  flex: 5,
                  child: StarterPage(),
                ),

              Expanded(
                flex: constraints.maxWidth > 900 ? 5 : 10,
                child: LoginPage(
                  isSignup: isSignup,
                  onToggle: () {
                    setState(() {
                      isSignup = !isSignup;
                    });
                  },
                  loading: authState.loading,
                  error: authState.error,

                  onSubmit: (data) async {
                    try {
                      final type = data["type"];

                      // =========================
                      // CUSTOMER LOGIN
                      // =========================
                      if (type == "customer_login") {
                        await authNotifier.login(
                          data["email"],
                          data["password"],
                        );
                      }

                      // =========================
                      // CUSTOMER REGISTER
                      // =========================
                      else if (type == "customer_signup") {
                        await authNotifier.register({
                          "name": data["name"],
                          "email": data["email"],
                          "password": data["password"],
                        });
                      }

                      // =========================
                      // STAFF LOGIN
                      // =========================
                      else if (type == "staff_login") {
                        await authNotifier.login(
                          data["email"],
                          data["password"],
                          accessKey: data["access_key"], // FIXED: passes access key to notifier
                        );
                      }

                      // =========================
                      // TEAM REGISTER
                      // =========================
                      else if (type == "team_signup") {
                        await authNotifier.register({
                          "name": data["name"],
                          "email": data["email"],
                          "password": data["password"],
                          "access_key": data["access_key"],
                        });
                      }

                      // =========================
                      // RESTAURANT REGISTER
                      // =========================
                      else if (type == "restaurant_signup") {
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
            ],
          );
        },
      ),
    );
  }
}