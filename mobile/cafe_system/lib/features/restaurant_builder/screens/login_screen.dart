import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cafe_system/core/theme/theme_provider.dart'; // Ensure path is correct
import 'package:cafe_system/features/auth/providers/auth_provider.dart'; // Ensure path is correct

class LoginPage extends ConsumerStatefulWidget {
  final bool isSignup;
  final VoidCallback onToggle;
  final bool loading;
  final String? error;
  final Function(String email, String password, String? name) onSubmit;

  const LoginPage({
    super.key,
    required this.isSignup,
    required this.onToggle,
    required this.loading,
    required this.error,
    required this.onSubmit,
  });

  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _nameController = TextEditingController();
  final _accessKeyController = TextEditingController();
  
  String activeTab = "Sign in";

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _nameController.dispose();
    _accessKeyController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    // Detect current theme mode
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final backgroundColor = isDark ? const Color(0xFF0F172A) : Colors.white;
    final textColor = isDark ? Colors.white : Colors.black87;
    final labelColor = isDark ? Colors.white70 : Colors.black54;

    return Scaffold(
      backgroundColor: backgroundColor,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // 1. TOP ACTIONS (Toggle Theme & Customer Login)
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  IconButton(
                    icon: Icon(isDark ? Icons.light_mode : Icons.dark_mode, 
                      color: isDark ? Colors.tealAccent : Colors.teal),
                    onPressed: () => ref.read(themeProvider.notifier).toggleTheme(),
                  ),
                  TextButton(
                    onPressed: widget.onToggle,
                    child: Text(
                      widget.isSignup ? "BACK TO LOGIN" : "CUSTOMER LOGIN",
                      style: const TextStyle(
                        color: Color(0xFF14B8A6), 
                        fontWeight: FontWeight.bold, 
                        fontSize: 12
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 30),

              // 2. PILL TABS
              Container(
                decoration: BoxDecoration(
                  color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey[200],
                  borderRadius: BorderRadius.circular(30),
                ),
                padding: const EdgeInsets.all(4),
                child: Row(
                  children: [
                    _buildTab("Sign in", isDark),
                    _buildTab("Team account", isDark),
                    _buildTab("New restaurant", isDark),
                  ],
                ),
              ),

              const SizedBox(height: 40),

              // 3. TEXT HEADERS
              Text(
                activeTab == "New restaurant" ? "Join Platrick" : "Restaurant team sign in",
                style: TextStyle(
                  color: textColor, 
                  fontSize: 28, 
                  fontWeight: FontWeight.bold,
                  letterSpacing: -0.5
                ),
              ),
              const SizedBox(height: 12),
              Text(
                "Use your work account and restaurant access key to enter your portal.",
                style: TextStyle(color: labelColor, fontSize: 15, height: 1.5),
              ),

              const SizedBox(height: 40),

              // 4. FORM FIELDS
              if (activeTab == "New restaurant") ...[
                _buildLabel("Restaurant Name", labelColor),
                _buildTextField(_nameController, "Cafe Name", isDark),
                const SizedBox(height: 20),
              ],

              _buildLabel("Work email", labelColor),
              _buildTextField(_emailController, "email@restaurant.com", isDark),
              const SizedBox(height: 20),

              _buildLabel("Password", labelColor),
              _buildTextField(_passwordController, "••••••••", isDark, isPassword: true),
              const SizedBox(height: 20),

              _buildLabel("Restaurant access key", labelColor),
              _buildTextField(_accessKeyController, "Enter key", isDark),

              // ERROR MESSAGE
              if (widget.error != null)
                Padding(
                  padding: const EdgeInsets.only(top: 20),
                  child: Text(
                    widget.error!, 
                    style: const TextStyle(color: Colors.redAccent, fontSize: 13)
                  ),
                ),

              const SizedBox(height: 50),

              // 5. SIGN IN BUTTON
              SizedBox(
                width: double.infinity,
                height: 56,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFC2410C), // Orange-Red from design
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 0,
                  ),
                  // lib/features/restaurant_builder/screens/login_screen.dart
// Inside the ElevatedButton...
onPressed: widget.loading 
  ? null 
  : () {
      print("Login Attempt: ${_emailController.text}"); // Check your console!
      widget.onSubmit(
        _emailController.text.trim(), 
        _passwordController.text.trim(), 
        null
      );
    },
                  child: widget.loading 
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text(
                        "Sign in", 
                        style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)
                      ),
                ),
              ),
              const SizedBox(height: 40),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTab(String label, bool isDark) {
    bool isActive = activeTab == label;
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => activeTab = label),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: isActive ? (isDark ? Colors.white.withOpacity(0.1) : Colors.white) : Colors.transparent,
            borderRadius: BorderRadius.circular(25),
            boxShadow: isActive && !isDark ? [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 4)] : null,
          ),
          child: Center(
            child: Text(
              label, 
              style: TextStyle(
                color: isActive ? (isDark ? Colors.white : Colors.black) : Colors.grey, 
                fontSize: 12, 
                fontWeight: FontWeight.bold
              )
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLabel(String text, Color color) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(text, style: TextStyle(color: color, fontSize: 13, fontWeight: FontWeight.bold)),
    );
  }

  Widget _buildTextField(TextEditingController controller, String hint, bool isDark, {bool isPassword = false}) {
    return TextField(
      controller: controller,
      obscureText: isPassword,
      style: TextStyle(color: isDark ? Colors.white : Colors.black87),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: TextStyle(color: isDark ? Colors.white12 : Colors.black26),
        filled: true,
        fillColor: isDark ? Colors.white.withOpacity(0.03) : Colors.grey[100],
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8), 
          borderSide: isDark ? BorderSide.none : BorderSide(color: Colors.grey[300]!)
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8), 
          borderSide: isDark ? BorderSide.none : BorderSide(color: Colors.grey[300]!)
        ),
      ),
    );
  }
}