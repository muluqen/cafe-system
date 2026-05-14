import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'package:cafe_system/core/theme/theme_provider.dart';

class LoginPage extends ConsumerStatefulWidget {
  final bool isSignup;
  final VoidCallback onToggle;
  final bool loading;
  final String? error;

  final Future<void> Function(
    Map<String, dynamic> data,
  ) onSubmit;

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

  bool isStaffMode = false;
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
    final isDark = Theme.of(context).brightness == Brightness.dark;

    final backgroundColor = isDark ? const Color(0xFF0F172A) : Colors.white;
    final textColor = isDark ? Colors.white : Colors.black87;
    final labelColor = isDark ? Colors.white70 : Colors.black54;

    return Scaffold(
      backgroundColor: backgroundColor,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(
            horizontal: 40,
            vertical: 20,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // TOP BAR
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  // THEME BUTTON
                  Container(
                    decoration: BoxDecoration(
                      color: isDark ? Colors.white10 : Colors.black12,
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: IconButton(
                      icon: Icon(
                        isDark ? Icons.light_mode_rounded : Icons.dark_mode_rounded,
                        color: textColor,
                      ),
                      onPressed: () {
                        ref.read(themeProvider.notifier).toggleTheme();
                      },
                    ),
                  ),

                  // STAFF / CUSTOMER SWITCH
                  TextButton(
                    onPressed: () {
                      setState(() {
                        isStaffMode = !isStaffMode;
                        activeTab = "Sign in";
                      });
                    },
                    child: Text(
                      isStaffMode ? "CUSTOMER LOGIN" : "RESTAURANT TEAM PORTAL",
                      style: const TextStyle(
                        color: Color(0xFF14B8A6),
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 30),

              // STAFF TABS
              if (isStaffMode)
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

              if (isStaffMode) const SizedBox(height: 40),

              // TITLE
              Text(
                isStaffMode
                    ? (activeTab == "Sign in"
                        ? "Restaurant team sign in"
                        : activeTab == "Team account"
                            ? "Create team account"
                            : "Join as a new restaurant")
                    : (widget.isSignup ? "Create customer account" : "Welcome back"),
                style: TextStyle(
                  color: textColor,
                  fontSize: 30,
                  fontWeight: FontWeight.bold,
                ),
              ),

              const SizedBox(height: 12),

              // SUBTITLE
              Text(
                isStaffMode
                    ? (activeTab == "Sign in"
                        ? "Use your work account and restaurant access key to enter your portal."
                        : activeTab == "Team account"
                            ? "Create a staff account for an existing restaurant."
                            : "Create your restaurant and owner account together.")
                    : (widget.isSignup
                        ? "Set up your account and start exploring restaurants."
                        : "Log in to continue your orders and bookings."),
                style: TextStyle(
                  color: labelColor,
                  fontSize: 15,
                  height: 1.5,
                ),
              ),

              const SizedBox(height: 40),

              // NAME FIELD
              if ((!isStaffMode && widget.isSignup) ||
                  (isStaffMode && activeTab != "Sign in")) ...[
                _buildLabel(
                  activeTab == "New restaurant" ? "Restaurant / Owner name" : "Full name",
                  labelColor,
                ),
                _buildTextField(
                  _nameController,
                  activeTab == "New restaurant" ? "Restaurant name" : "Your name",
                  isDark,
                ),
                const SizedBox(height: 20),
              ],

              // EMAIL
              _buildLabel(
                isStaffMode ? "Work email" : "Email",
                labelColor,
              ),
              _buildTextField(
                _emailController,
                "email@example.com",
                isDark,
              ),
              const SizedBox(height: 20),

              // PASSWORD
              _buildLabel(
                "Password",
                labelColor,
              ),
              _buildTextField(
                _passwordController,
                "••••••••",
                isDark,
                isPassword: true,
              ),
              const SizedBox(height: 20),

              // ACCESS KEY
              if (isStaffMode) ...[
                _buildLabel(
                  "Restaurant access key",
                  labelColor,
                ),
                _buildTextField(
                  _accessKeyController,
                  "Enter access key",
                  isDark,
                ),
                const SizedBox(height: 20),
              ],

              // ERROR
              if (widget.error != null && widget.error!.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(bottom: 16),
                  child: Text(
                    widget.error!,
                    style: const TextStyle(
                      color: Colors.redAccent,
                      fontSize: 14,
                    ),
                  ),
                ),

              const SizedBox(height: 20),

              // SUBMIT BUTTON
              SizedBox(
                width: double.infinity,
                height: 56,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFC2410C),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  onPressed: widget.loading
                      ? null
                      : () async {
                          // FIXED LOGIC: Safely trim and map fields 
                          final email = _emailController.text.trim();
                          final password = _passwordController.text.trim();
                          final name = _nameController.text.trim();
                          final accessKey = _accessKeyController.text.trim();

                          // CUSTOMER LOGIN
                          if (!isStaffMode && !widget.isSignup) {
                            await widget.onSubmit({
                              "type": "customer_login",
                              "email": email,
                              "password": password,
                            });
                          }
                          // CUSTOMER SIGNUP
                          else if (!isStaffMode && widget.isSignup) {
                            await widget.onSubmit({
                              "type": "customer_signup",
                              "name": name,
                              "email": email,
                              "password": password,
                            });
                          }
                          // STAFF LOGIN
                          else if (isStaffMode && activeTab == "Sign in") {
                            await widget.onSubmit({
                              "type": "staff_login",
                              "email": email,
                              "password": password,
                              "access_key": accessKey,
                            });
                          }
                          // TEAM ACCOUNT
                          else if (isStaffMode && activeTab == "Team account") {
                            await widget.onSubmit({
                              "type": "team_signup",
                              "name": name,
                              "email": email,
                              "password": password,
                              "access_key": accessKey,
                            });
                          }
                          // NEW RESTAURANT
                          else if (isStaffMode && activeTab == "New restaurant") {
                            await widget.onSubmit({
                              "type": "restaurant_signup",
                              "restaurant_name": name,
                              "owner_name": name, 
                              "email": email,
                              "password": password,
                              "access_key": accessKey,
                            });
                          }
                        },
                  child: widget.loading
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : Text(
                          isStaffMode
                              ? (activeTab == "Sign in"
                                  ? "Sign in"
                                  : activeTab == "Team account"
                                      ? "Create team account"
                                      : "Join Platrick")
                              : (widget.isSignup ? "Create account" : "Sign in"),
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                ),
              ),

              const SizedBox(height: 20),

              // CUSTOMER TOGGLE
              if (!isStaffMode)
                Center(
                  child: TextButton(
                    onPressed: widget.onToggle,
                    child: Text(
                      widget.isSignup
                          ? "Already have an account? Sign in"
                          : "New to Platrick? Create account",
                      style: const TextStyle(
                        color: Color(0xFF38BDF8),
                        fontWeight: FontWeight.w600,
                        fontSize: 15,
                      ),
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

  // TAB
  Widget _buildTab(String label, bool isDark) {
    final isActive = activeTab == label;

    return Expanded(
      child: GestureDetector(
        onTap: () {
          setState(() {
            activeTab = label;
          });
        },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: isActive
                ? (isDark ? Colors.white.withOpacity(0.1) : Colors.white)
                : Colors.transparent,
            borderRadius: BorderRadius.circular(25),
          ),
          child: Center(
            child: Text(
              label,
              style: TextStyle(
                color: isActive ? (isDark ? Colors.white : Colors.black) : Colors.grey,
                fontWeight: FontWeight.bold,
                fontSize: 12,
              ),
            ),
          ),
        ),
      ),
    );
  }

  // LABEL
  Widget _buildLabel(String text, Color color) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(
        text,
        style: TextStyle(
          color: color,
          fontSize: 13,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }

  // INPUT
  Widget _buildTextField(
    TextEditingController controller,
    String hint,
    bool isDark, {
    bool isPassword = false,
  }) {
    return TextField(
      controller: controller,
      obscureText: isPassword,
      style: TextStyle(
        color: isDark ? Colors.white : Colors.black87,
      ),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: TextStyle(
          color: isDark ? Colors.white24 : Colors.black26,
        ),
        filled: true,
        fillColor: isDark ? Colors.white.withOpacity(0.03) : Colors.grey[100],
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 16,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide.none,
        ),
      ),
    );
  }
}