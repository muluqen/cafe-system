import 'package:flutter_riverpod/flutter_riverpod.dart';

class CartItem {
  final String uid;
  final int menuItemId;
  final String name;
  final int quantity;
  final double unitPrice;
  final List<String> additions;
  final List<String> removals;
  final String note;

  CartItem({
    required this.uid, required this.menuItemId, required this.name,
    this.quantity = 1, required this.unitPrice, 
    this.additions = const [], this.removals = const [], this.note = '',
  });

  CartItem copyWith({int? quantity}) => CartItem(
    uid: uid, menuItemId: menuItemId, name: name, unitPrice: unitPrice,
    additions: additions, removals: removals, note: note,
    quantity: quantity ?? this.quantity,
  );
}

class CartNotifier extends StateNotifier<List<CartItem>> {
  CartNotifier() : super([]);

  void addItem(CartItem item) => state = [...state, item];
  
  void removeLine(String uid) => state = state.where((i) => i.uid != uid).toList();

  void updateQty(String uid, int delta) {
    state = [
      for (final item in state)
        if (item.uid == uid)
          item.copyWith(quantity: (item.quantity + delta).clamp(1, 99))
        else
          item
    ];
  }

  void clear() => state = [];

  double get subtotal => state.fold(0, (sum, item) => sum + (item.unitPrice * item.quantity));
  double get tax => subtotal * 0.1;
  double get total => subtotal + tax;
}

final cartProvider = StateNotifierProvider<CartNotifier, List<CartItem>>((ref) => CartNotifier());