import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../auth/providers/auth_provider.dart';

class AppLayout extends ConsumerWidget {
  final Widget child; // This is like <RouterView />

  const AppLayout({super.key, required this.child});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final user = authState.user;

    // Logic for Branding (replicates computed restaurantBrand)
    String restaurantBrand = "platrick";
    if (user?.isRestaurant ?? false) {
      final match = authState.publicRestaurants.firstWhere(
        (r) => r['id'].toString() == user?.restaurantId.toString(),
        orElse: () => null,
      );
      restaurantBrand = match?['name'] ?? "platrick";
    }

    return Scaffold(
      // Mobile Header (Replicates the Mobile FAB trigger)
      appBar: AppBar(
        title: Text(user?.isCustomer ?? true ? "platrick" : restaurantBrand),
        backgroundColor: user?.isCustomer ?? true ? Colors.blueGrey[50] : null,
      ),
      
      // Sidebar (Replicates <aside class="sidebar">)
      drawer: Drawer(
        child: Column(
          children: [
            _buildSidebarHead(user, restaurantBrand),
            
            // Restaurant Selector for Customers
            if (user?.isCustomer ?? true) _buildRestaurantSelector(ref, authState),

            _buildSidebarIntro(user),

            // Navigation Links
            Expanded(
              child: ListView(
                padding: EdgeInsets.zero,
                children: _getNavLinks(user, ref).map((link) {
                  return ListTile(
                    title: Text(link['label']!),
                    onTap: () {
                      Navigator.pop(context); // Close drawer
                      // Replicates router.push
                      // Navigator.pushNamed(context, link['name']!); 
                    },
                  );
                }).toList(),
              ),
            ),

            // Logout Button
            Padding(
              padding: const EdgeInsets.all(20.0),
              child: SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: () => ref.read(authProvider.notifier).logout(),
                  child: const Text("Logout"),
                ),
              ),
            ),
          ],
        ),
      ),
      
      // Main Content Area
      body: child,
    );
  }

  Widget _buildSidebarHead(dynamic user, String brand) {
    return DrawerHeader(
      decoration: BoxDecoration(color: Colors.blueGrey[900]),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(brand, style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold)),
          if (user?.isRestaurant ?? false)
            Text("${user.staffRole} portal", style: const TextStyle(color: Colors.teal, fontSize: 12)),
        ],
      ),
    );
  }

  Widget _buildRestaurantSelector(WidgetRef ref, AuthState state) {
    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text("Selected Restaurant", style: TextStyle(fontSize: 10, color: Colors.grey)),
          DropdownButton<String>(
            isExpanded: true,
            value: state.selectedRestaurantId.isEmpty ? null : state.selectedRestaurantId,
            hint: const Text("Choose one"),
            items: state.publicRestaurants.map((r) {
              return DropdownMenuItem(value: r['id'].toString(), child: Text(r['name']));
            }).toList(),
            onChanged: (val) => ref.read(authProvider.notifier).setSelectedRestaurant(val!),
          ),
        ],
      ),
    );
  }

  Widget _buildSidebarIntro(dynamic user) {
    bool isCustomer = user?.isCustomer ?? true;
    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(isCustomer ? "Customer Space" : "Service Map", style: const TextStyle(fontWeight: FontWeight.bold)),
          const SizedBox(height: 5),
          Text(
            isCustomer 
              ? "Browse menus, customize your order, and keep reorders one tap away."
              : "Private restaurant workspace for operations, staff, and order flow.",
            style: const TextStyle(fontSize: 12, color: Colors.grey),
          ),
        ],
      ),
    );
  }

  List<Map<String, String>> _getNavLinks(dynamic user, WidgetRef ref) {
    if (user?.isCustomer ?? true) {
      return [
        {'name': 'customer-discover', 'label': 'Discover'},
        {'name': 'customer-orders', 'label': 'My Orders'},
        {'name': 'customer-feedback', 'label': 'Feedback'},
        {'name': 'customer-rewards', 'label': 'Perks'},
        {'name': 'customer-account', 'label': 'Account'},
      ];
    } else {
      bool isManager = user?.staffRole == 'manager';
      return [
        if (isManager) {'name': 'restaurant-builder', 'label': 'Site Builder'},
        if (isManager) {'name': 'restaurant-pulse', 'label': 'Customer Voice'},
        {'name': 'dashboard', 'label': isManager ? 'Owner Home' : 'Dashboard'},
      ];
    }
  }
}