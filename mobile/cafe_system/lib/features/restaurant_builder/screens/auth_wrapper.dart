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
      // This prevents the keyboard from causing overflow
      resizeToAvoidBottomInset: true, 
      body: LayoutBuilder(
        builder: (context, constraints) {
          return Row(
            children: [
              // LEFT SIDE (Showcase) - Hidden on small screens if you want
              if (constraints.maxWidth > 800)
                const Expanded(
                  flex: 5,
                  child: StarterPage(),
                ),
              
              // RIGHT SIDE (Form)
              Expanded(
                flex: constraints.maxWidth > 800 ? 5 : 10,
                child: LoginPage(
                  isSignup: isSignup,
                  onToggle: () => setState(() => isSignup = !isSignup),
                  loading: authState.loading,
                  error: authState.error,
                  onSubmit: (email, password, name) {
                    if (isSignup) {
                      authNotifier.register({'name': name, 'email': email, 'password': password});
                    } else {
                      authNotifier.login(email, password);
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