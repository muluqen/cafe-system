import 'package:cafe_system/features/auth/providers/auth_provider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';


class CustomerAccountView extends ConsumerWidget {
  const CustomerAccountView({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authProvider).user;

    return Scaffold(
      appBar: AppBar(title: const Text("Profile Settings")),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            TextField(decoration: const InputDecoration(labelText: "Full Name"), controller: TextEditingController(text: user?.name)),
            TextField(decoration: const InputDecoration(labelText: "Email"), controller: TextEditingController(text: user?.email)),
            const SizedBox(height: 30),
            ElevatedButton(onPressed: () {}, child: const Text("Save Changes")),
          ],
        ),
      ),
    );
  }
}