import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cafe_system/core/theme/theme_provider.dart';

class SignupPage extends ConsumerStatefulWidget {
  final bool loading;
  final String? error;
final Future<void> Function(Map<String, dynamic> data) onSubmit;

  const SignupPage({
    super.key,
    required this.loading,
    required this.error,
    required this.onSubmit,
  });

  @override
  ConsumerState<SignupPage> createState() =>
      _SignupPageState();
}

class _SignupPageState
    extends ConsumerState<SignupPage> {
  final _nameController =
      TextEditingController();

  final _emailController =
      TextEditingController();

  final _passwordController =
      TextEditingController();

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDark =
        Theme.of(context).brightness ==
            Brightness.dark;

    final backgroundColor =
        isDark
            ? const Color(0xFF0F172A)
            : Colors.white;

    final textColor =
        isDark
            ? Colors.white
            : Colors.black87;

    final labelColor =
        isDark
            ? Colors.white70
            : Colors.black54;

    return Scaffold(
      backgroundColor: backgroundColor,
      body: SafeArea(
        child: SingleChildScrollView(
          padding:
              const EdgeInsets.symmetric(
            horizontal: 40,
            vertical: 20,
          ),
          child: Column(
            crossAxisAlignment:
                CrossAxisAlignment.start,
            children: [

              // TOP HEADER
              Row(
                mainAxisAlignment:
                    MainAxisAlignment
                        .spaceBetween,
                children: [

                  // THEME TOGGLE
                  Container(
                    decoration: BoxDecoration(
                      color: isDark
                          ? Colors.white10
                          : Colors.black12,
                      borderRadius:
                          BorderRadius.circular(
                        14,
                      ),
                    ),
                    child: IconButton(
                      icon: Icon(
                        isDark
                            ? Icons
                                .light_mode_rounded
                            : Icons
                                .dark_mode_rounded,
                        color: textColor,
                      ),
                      onPressed: () {
                        ref
                            .read(
                              themeProvider
                                  .notifier,
                            )
                            .toggleTheme();
                      },
                    ),
                  ),

                  // BACK BUTTON
                  TextButton(
                    onPressed: () {
                      Navigator.pop(
                        context,
                      );
                    },
                    child: const Text(
                      "RESTAURANT TEAM PORTAL",
                      style: TextStyle(
                        color: Color(
                          0xFF14B8A6,
                        ),
                        fontWeight:
                            FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 40),

              // TITLE
              Text(
                "Create customer account",
                style: TextStyle(
                  color: textColor,
                  fontSize: 30,
                  fontWeight:
                      FontWeight.bold,
                ),
              ),

              const SizedBox(height: 12),

              // SUBTITLE
              Text(
                "Set up your account and start exploring restaurants.",
                style: TextStyle(
                  color: labelColor,
                  fontSize: 15,
                  height: 1.5,
                ),
              ),

              const SizedBox(height: 40),

              // FULL NAME
              _buildLabel(
                "Full name",
                labelColor,
              ),

              _buildField(
                _nameController,
                "Your Name",
                isDark,
              ),

              const SizedBox(height: 20),

              // EMAIL
              _buildLabel(
                "Email",
                labelColor,
              ),

              _buildField(
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

              _buildField(
                _passwordController,
                "••••••••",
                isDark,
                obscure: true,
              ),

              // ERROR
              if (widget.error != null)
                Padding(
                  padding:
                      const EdgeInsets.only(
                    top: 10,
                  ),
                  child: Text(
                    widget.error!,
                    style: const TextStyle(
                      color:
                          Colors.redAccent,
                    ),
                  ),
                ),

              const SizedBox(height: 50),

              // CREATE ACCOUNT BUTTON
              SizedBox(
                width: double.infinity,
                height: 56,
                child: ElevatedButton(
                  style:
                      ElevatedButton.styleFrom(
                    backgroundColor:
                        const Color(
                      0xFFC2410C,
                    ),
                    shape:
                        RoundedRectangleBorder(
                      borderRadius:
                          BorderRadius.circular(
                        12,
                      ),
                    ),
                  ),
                  onPressed:
                      widget.loading
                          ? null
                          : () {
widget.onSubmit({
  "name": _nameController.text.trim(),
  "email": _emailController.text.trim(),
  "password": _passwordController.text.trim(),
});
                            },
                  child:
                      widget.loading
                          ? const SizedBox(
                              height: 22,
                              width: 22,
                              child:
                                  CircularProgressIndicator(
                                color:
                                    Colors
                                        .white,
                                strokeWidth:
                                    2,
                              ),
                            )
                          : const Text(
                              "Create account",
                              style:
                                  TextStyle(
                                color:
                                    Colors
                                        .white,
                                fontSize:
                                    16,
                                fontWeight:
                                    FontWeight
                                        .bold,
                              ),
                            ),
                ),
              ),

              const SizedBox(height: 20),

              // FOOTER
              Center(
                child: TextButton(
                  onPressed: () {
                    Navigator.pop(
                      context,
                    );
                  },
                  child: const Text(
                    "Already have an account? Sign in",
                    style: TextStyle(
                      color: Color(
                        0xFF38BDF8,
                      ),
                      fontWeight:
                          FontWeight.w600,
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

  // LABEL
  Widget _buildLabel(
    String text,
    Color color,
  ) {
    return Padding(
      padding:
          const EdgeInsets.only(
        bottom: 8,
      ),
      child: Text(
        text,
        style: TextStyle(
          color: color,
          fontSize: 13,
          fontWeight:
              FontWeight.bold,
        ),
      ),
    );
  }

  // TEXT FIELD
  Widget _buildField(
    TextEditingController controller,
    String hint,
    bool isDark, {
    bool obscure = false,
  }) {
    return TextField(
      controller: controller,
      obscureText: obscure,
      style: TextStyle(
        color:
            isDark
                ? Colors.white
                : Colors.black87,
      ),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: TextStyle(
          color:
              isDark
                  ? Colors.white24
                  : Colors.black26,
        ),
        filled: true,
        fillColor:
            isDark
                ? Colors.white.withOpacity(
                  0.03,
                )
                : Colors.grey[100],
        contentPadding:
            const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 16,
        ),
        border: OutlineInputBorder(
          borderRadius:
              BorderRadius.circular(
            10,
          ),
          borderSide: BorderSide.none,
        ),
      ),
    );
  }
}