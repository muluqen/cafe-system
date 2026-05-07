import 'package:cafe_system/features/auth/providers/auth_provider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../providers/cart_provider.dart';

class CustomerDiscoverView extends ConsumerStatefulWidget {
  const CustomerDiscoverView({super.key});

  @override
  ConsumerState<CustomerDiscoverView> createState() => _CustomerDiscoverViewState();
}

class _CustomerDiscoverViewState extends ConsumerState<CustomerDiscoverView> {
  int? activeCategoryId;

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authProvider);
    final cart = ref.watch(cartProvider);
    final cartNotifier = ref.read(cartProvider.notifier);

    // Replicate Vue's heroStyle and branding
    final brandColor = _parseColor(auth.selectedRestaurantId.isNotEmpty ? "#ff7a59" : "#607d8b");

    return Scaffold(
      body: Row(
        children: [
          // Main Menu Area
          Expanded(
            flex: 2,
            child: CustomScrollView(
              slivers: [
                // Branded Hero (Replicates customer-hero)
                SliverToBoxAdapter(
                  child: Container(
                    padding: const EdgeInsets.all(40),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(colors: [brandColor, brandColor.withOpacity(0.7)]),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text("Platrick Discover", style: TextStyle(color: Colors.white70)),
                        const SizedBox(height: 10),
                        const Text(
                          "Pick your spot, customize your meal, send it to the kitchen.",
                          style: TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 20),
                        ElevatedButton(onPressed: () {}, child: const Text("Select Table")),
                      ],
                    ),
                  ),
                ),

                // Category Pills
                SliverPersistentHeader(
                  pinned: true,
                  delegate: _CategoryHeaderDelegate(
                    child: Container(
                      color: Colors.white,
                      height: 60,
                      child: ListView(
                        scrollDirection: Axis.horizontal,
                        children: [
                          _buildPill("All", activeCategoryId == null, () => setState(() => activeCategoryId = null)),
                          _buildPill("Coffee", activeCategoryId == 1, () => setState(() => activeCategoryId = 1)),
                          _buildPill("Food", activeCategoryId == 2, () => setState(() => activeCategoryId = 2)),
                        ],
                      ),
                    ),
                  ),
                ),

                // Menu Grid
                SliverPadding(
                  padding: const EdgeInsets.all(16),
                  sliver: SliverGrid(
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2, mainAxisSpacing: 10, crossAxisSpacing: 10, childAspectRatio: 1.5,
                    ),
                    delegate: SliverChildBuilderDelegate(
                      (context, index) => _buildMenuCard(cartNotifier),
                      childCount: 6,
                    ),
                  ),
                ),
              ],
            ),
          ),

          // Cart Sidebar (Replicates cart-panel)
          Container(
            width: 350,
            color: Colors.grey[50],
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text("Your Order", style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                const Divider(),
                Expanded(
                  child: cart.isEmpty 
                    ? const Center(child: Text("Nothing in your order yet."))
                    : ListView.builder(
                        itemCount: cart.length,
                        itemBuilder: (context, index) {
                          final item = cart[index];
                          return ListTile(
                            title: Text(item.name),
                            subtitle: Text("\$${item.unitPrice}"),
                            trailing: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                IconButton(icon: const Icon(Icons.remove), onPressed: () => cartNotifier.updateQty(item.uid, -1)),
                                Text("${item.quantity}"),
                                IconButton(icon: const Icon(Icons.add), onPressed: () => cartNotifier.updateQty(item.uid, 1)),
                              ],
                            ),
                          );
                        },
                      ),
                ),
                const Divider(),
                _buildTotals(cartNotifier),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: cart.isEmpty ? null : () {},
                    style: ElevatedButton.styleFrom(backgroundColor: brandColor, foregroundColor: Colors.white),
                    child: const Text("Place Order"),
                  ),
                )
              ],
            ),
          )
        ],
      ),
    );
  }

  Widget _buildPill(String label, bool active, VoidCallback onTap) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8.0, vertical: 10),
      child: ActionChip(
        label: Text(label),
        backgroundColor: active ? Colors.teal : Colors.grey[200],
        onPressed: onTap,
      ),
    );
  }

  Widget _buildMenuCard(CartNotifier notifier) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text("Latte", style: TextStyle(fontWeight: FontWeight.bold)),
            const Text("Chef-crafted and made fresh.", style: TextStyle(fontSize: 12, color: Colors.grey)),
            const Spacer(),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text("\$4.50", style: TextStyle(fontWeight: FontWeight.bold)),
                ElevatedButton(
                  onPressed: () => notifier.addItem(CartItem(uid: DateTime.now().toString(), menuItemId: 1, name: "Latte", unitPrice: 4.50)),
                  child: const Text("Order"),
                )
              ],
            )
          ],
        ),
      ),
    );
  }

  Widget _buildTotals(CartNotifier n) {
    return Column(
      children: [
        _row("Subtotal", "\$${n.subtotal.toStringAsFixed(2)}"),
        _row("Tax (10%)", "\$${n.tax.toStringAsFixed(2)}"),
        const Divider(),
        _row("Total", "\$${n.total.toStringAsFixed(2)}", bold: true),
      ],
    );
  }

  Widget _row(String label, String val, {bool bold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [Text(label), Text(val, style: TextStyle(fontWeight: bold ? FontWeight.bold : null))],
      ),
    );
  }

  Color _parseColor(String hex) => Color(int.parse(hex.replaceFirst('#', '0xFF')));
}

// Helper for pinning the categories at the top while scrolling
class _CategoryHeaderDelegate extends SliverPersistentHeaderDelegate {
  final Widget child;
  _CategoryHeaderDelegate({required this.child});
  @override
  double get minExtent => 60;
  @override
  double get maxExtent => 60;
  @override
  Widget build(context, shrink, overlaps) => child;
  @override
  bool shouldRebuild(covariant SliverPersistentHeaderDelegate oldDelegate) => false;
}