import 'package:cafe_system/models/restaurant_table.dart';
import 'package:flutter/material.dart';


class TablesStepView extends StatefulWidget {
  final List<RestaurantTable> tables;
  final bool isSaving;
  final String? error;
  final Function(Map<String, dynamic>) onSaveTable;
  final Function(int) onRemoveTable;
  final VoidCallback onBack;
  final VoidCallback onFinish;

  const TablesStepView({
    super.key, required this.tables, required this.isSaving, 
    this.error, required this.onSaveTable, required this.onRemoveTable, 
    required this.onBack, required this.onFinish
  });

  @override
  State<TablesStepView> createState() => _TablesStepViewState();
}

class _TablesStepViewState extends State<TablesStepView> {
  final _nameController = TextEditingController();
  final _capacityController = TextEditingController(text: "2");
  final _locationController = TextEditingController();

  void _submit() {
    widget.onSaveTable({
      'name': _nameController.text,
      'capacity': _capacityController.text,
      'location': _locationController.text,
    });
    _nameController.clear();
    _locationController.clear();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text("Step 5", style: TextStyle(color: Colors.grey)),
        const Text("Add tables", style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
        const SizedBox(height: 20),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                Row(
                  children: [
                    Expanded(child: TextField(controller: _nameController, decoration: const InputDecoration(labelText: "Table Name"))),
                    const SizedBox(width: 10),
                    Expanded(child: TextField(controller: _capacityController, decoration: const InputDecoration(labelText: "Capacity"), keyboardType: TextInputType.number)),
                  ],
                ),
                TextField(controller: _locationController, decoration: const InputDecoration(labelText: "Location (Patio, Upstairs...)")),
                const SizedBox(height: 20),
                Row(
                  children: [
                    TextButton(onPressed: widget.onBack, child: const Text("Back")),
                    const Spacer(),
                    TextButton(onPressed: widget.onFinish, child: const Text("Finish Setup")),
                    ElevatedButton(onPressed: widget.isSaving ? null : _submit, child: Text(widget.isSaving ? "Saving..." : "Add Table")),
                  ],
                )
              ],
            ),
          ),
        ),
        const SizedBox(height: 20),
        ListView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: widget.tables.length,
          itemBuilder: (context, index) {
            final table = widget.tables[index];
            return ListTile(
              title: Text(table.name),
              subtitle: Text("${table.capacity} seats • ${table.location ?? 'Main Floor'}"),
              trailing: IconButton(icon: const Icon(Icons.delete, color: Colors.red), onPressed: () => widget.onRemoveTable(table.id)),
            );
          },
        )
      ],
    );
  }
}