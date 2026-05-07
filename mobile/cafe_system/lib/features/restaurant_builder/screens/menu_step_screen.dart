import 'package:cafe_system/models/menu_item.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';


class MenuStepView extends ConsumerStatefulWidget {
  final List<MenuItem> menuItems;
  final Function(Map<String, dynamic>) onSave;
  final Function(int) onRemove;
  final VoidCallback onBack;
  final VoidCallback onNext;
  final bool isSaving;

  const MenuStepView({
    super.key,
    required this.menuItems,
    required this.onSave,
    required this.onRemove,
    required this.onBack,
    required this.onNext,
    this.isSaving = false,
  });

  @override
  ConsumerState<MenuStepView> createState() => _MenuStepViewState();
}

class _MenuStepViewState extends ConsumerState<MenuStepView> {
  // Form Controllers
  final _categoryController = TextEditingController();
  final _nameController = TextEditingController();
  final _priceController = TextEditingController();
  final _timeController = TextEditingController();
  final _descController = TextEditingController();

  void _submit() {
    widget.onSave({
      'categoryName': _categoryController.text,
      'name': _nameController.text,
      'price': _priceController.text,
      'preparation_time_minutes': _timeController.text,
      'description': _descController.text,
    });
    // Clear form after adding (replicates Vue's Object.assign)
    _nameController.clear();
    _priceController.clear();
    _timeController.clear();
    _descController.clear();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        const Text("Step 3", style: TextStyle(color: Colors.grey, fontSize: 12)),
        const Text("Build the menu", style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
        const SizedBox(height: 20),

        // Form Section (The Card)
        Card(
          elevation: 0,
          shape: RoundedRectangleBorder(side: BorderSide(color: Colors.grey.shade300), borderRadius: BorderRadius.circular(8)),
          child: Padding(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              children: [
                // Builder Split 1
                Row(
                  children: [
                    Expanded(child: _buildTextField("Category", _categoryController, "Coffee...")),
                    const SizedBox(width: 10),
                    Expanded(child: _buildTextField("Item name", _nameController, "Latte...")),
                  ],
                ),
                const SizedBox(height: 15),
                // Builder Split 2
                Row(
                  children: [
                    Expanded(child: _buildTextField("Price", _priceController, "0.00", keyboard: TextInputType.number)),
                    const SizedBox(width: 10),
                    Expanded(child: _buildTextField("Prep time (min)", _timeController, "10", keyboard: TextInputType.number)),
                  ],
                ),
                const SizedBox(height: 15),
                _buildTextField("Description", _descController, "What makes this item good?", maxLines: 3),
                
                const SizedBox(height: 20),
                
                // Action Buttons
                Row(
                  children: [
                    TextButton(onPressed: widget.onBack, child: const Text("Back")),
                    const Spacer(),
                    TextButton(onPressed: widget.onNext, child: const Text("Skip for now")),
                    const SizedBox(width: 10),
                    ElevatedButton(
                      onPressed: widget.isSaving ? null : _submit,
                      child: Text(widget.isSaving ? "Saving..." : "Add menu item"),
                    ),
                  ],
                )
              ],
            ),
          ),
        ),

        const SizedBox(height: 30),

        // Record List (The List of items)
        const Text("Menu Items", style: TextStyle(fontWeight: FontWeight.bold)),
        const SizedBox(height: 10),
        if (widget.menuItems.isEmpty)
          const Center(child: Text("Start by adding at least one menu item.", style: TextStyle(color: Colors.grey))),
        
        ListView.separated(
          shrinkWrap: true, // Needed inside a Column
          physics: const NeverScrollableScrollPhysics(),
          itemCount: widget.menuItems.length,
          separatorBuilder: (context, index) => const Divider(),
          itemBuilder: (context, index) {
            final item = widget.menuItems[index];
            return ListTile(
              contentPadding: EdgeInsets.zero,
              title: Text(item.name, style: const TextStyle(fontWeight: FontWeight.bold)),
              subtitle: Text("${item.categoryName ?? 'Uncategorized'} • \$${item.price.toStringAsFixed(2)}"),
              trailing: TextButton(
                onPressed: () => widget.onRemove(item.id),
                child: const Text("Remove", style: TextStyle(color: Colors.red)),
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildTextField(String label, TextEditingController controller, String hint, {int maxLines = 1, TextInputType keyboard = TextInputType.text}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500)),
        const SizedBox(height: 5),
        TextField(
          controller: controller,
          maxLines: maxLines,
          keyboardType: keyboard,
          decoration: InputDecoration(
            hintText: hint,
            isDense: true,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(6)),
          ),
        ),
      ],
    );
  }
}