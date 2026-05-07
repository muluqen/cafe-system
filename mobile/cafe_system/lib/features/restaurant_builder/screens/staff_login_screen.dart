import '../../auth/providers/auth_provider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

enum StaffMode { login, team, restaurant }

class StaffLoginView extends ConsumerStatefulWidget {
  const StaffLoginView({super.key});

  @override
  ConsumerState<StaffLoginView> createState() => _StaffLoginViewState();
}

class _StaffLoginViewState extends ConsumerState<StaffLoginView> {
  StaffMode mode = StaffMode.login;

  // Controllers for all 3 forms (Simplified for brevity)
  final _email = TextEditingController();
  final _pass = TextEditingController();
  final _accessKey = TextEditingController();
  final _restName = TextEditingController();
  String? selectedRestaurantId;

  Future<void> _submit() async {
    final auth = ref.read(authProvider.notifier);
    try {
      if (mode == StaffMode.login) {
        await auth.login(_email.text, _pass.text); // Note: add accessKey to your login if backend requires it
      } else if (mode == StaffMode.restaurant) {
        await auth.registerRestaurant({
          'restaurant_name': _restName.text,
          'email': _email.text,
          'password': _pass.text,
          'access_key': _accessKey.text,
        });
      }
      // Navigate to dashboard on success
    } catch (e) {
      // Error handled by authProvider.error
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    return Scaffold(
      body: Row(
        children: [
          // ASIDE: Showcase
          Expanded(
            child: Container(
              color: Colors.blueGrey[900],
              padding: const EdgeInsets.all(60),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text("Platrick Team", style: TextStyle(color: Colors.teal, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 20),
                  const Text("Open your restaurant workspace the right way.", 
                    style: TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 40),
                  _buildNote("Private by default", "Every restaurant gets its own isolated workspace."),
                  _buildNote("Owner-first setup", "Joining creates the restaurant and manager account."),
                ],
              ),
            ),
          ),

          // MAIN: Login Card
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(60),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Mode Switcher
                  ToggleButtons(
                    isSelected: [mode == StaffMode.login, mode == StaffMode.team, mode == StaffMode.restaurant],
                    onPressed: (index) => setState(() => mode = StaffMode.values[index]),
                    children: const [Text("Sign in"), Text("Team account"), Text("New restaurant")],
                  ),
                  const SizedBox(height: 30),
                  
                  // Dynamic Form fields
                  if (mode == StaffMode.restaurant)
                    TextField(controller: _restName, decoration: const InputDecoration(labelText: "Restaurant Name")),
                  
                  TextField(controller: _email, decoration: const InputDecoration(labelText: "Work Email")),
                  TextField(controller: _pass, decoration: const InputDecoration(labelText: "Password"), obscureText: true),
                  TextField(controller: _accessKey, decoration: const InputDecoration(labelText: "Access Key"), obscureText: true),
                  
                  if (authState.error.isNotEmpty) 
                    Text(authState.error, style: const TextStyle(color: Colors.red)),

                  const SizedBox(height: 20),
                  ElevatedButton(
                    onPressed: authState.loading ? null : _submit,
                    child: Text(authState.loading ? "Loading..." : "Enter Portal"),
                  )
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNote(String title, String body) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(color: Colors.teal, fontWeight: FontWeight.bold)),
          Text(body, style: const TextStyle(color: Colors.grey)),
        ],
      ),
    );
  }
}