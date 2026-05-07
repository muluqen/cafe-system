import 'package:flutter/material.dart';

class BrandStepView extends StatelessWidget {
  final Map<String, dynamic> form;
  final String newColor;
  final bool isSaving;
  final String? error;
  final String? message;
  final Function(String, dynamic) onUpdateField;
  final Function(String) onUpdateNewColor;
  final VoidCallback onAddColor;
  final Function(String) onRemoveColor;
  final Function(String) onSetPrimary;
  final VoidCallback onSave;

  const BrandStepView({
    super.key,
    required this.form,
    required this.newColor,
    required this.isSaving,
    this.error,
    this.message,
    required this.onUpdateField,
    required this.onUpdateNewColor,
    required this.onAddColor,
    required this.onRemoveColor,
    required this.onSetPrimary,
    required this.onSave,
  });

  @override
  Widget build(BuildContext context) {
    List<String> colors = List<String>.from(form['brandColors'] ?? []);

    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text("Step 1", style: TextStyle(color: Colors.grey)),
          const Text("Build the first impression", style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
          const SizedBox(height: 20),

          _buildInput("Restaurant name", "name", form['name']),
          _buildInput("Headline", "headline", form['headline'], hint: "Fresh coffee, calm tables..."),
          _buildInput("Customer message", "customerMessage", form['customerMessage'], maxLines: 3),

          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text("New brand color (Hex)"),
                    TextField(
                      onChanged: onUpdateNewColor,
                      decoration: const InputDecoration(hintText: "#14b8a6", border: OutlineInputBorder()),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              ElevatedButton(
                onPressed: colors.length >= 3 ? null : onAddColor,
                child: const Text("+ Add color"),
              ),
            ],
          ),

          const SizedBox(height: 20),
          const Text("Brand colors"),
          Wrap(
            spacing: 10,
            children: colors.map((color) {
              bool isPrimary = form['brandColor'] == color;
              return GestureDetector(
                onTap: () => onSetPrimary(color),
                child: Chip(
                  avatar: CircleAvatar(backgroundColor: _parseColor(color)),
                  label: Text(color),
                  backgroundColor: isPrimary ? Colors.blue.withOpacity(0.2) : null,
                  onDeleted: () => onRemoveColor(color),
                ),
              );
            }).toList(),
          ),

          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(child: _buildInput("Phone", "phone", form['phone'])),
              const SizedBox(width: 10),
              Expanded(child: _buildInput("Email", "email", form['email'])),
            ],
          ),
          _buildInput("Address", "address", form['address']),

          if (message != null) Text(message!, style: const TextStyle(color: Colors.green)),
          if (error != null) Text(error!, style: const TextStyle(color: Colors.red)),

          const SizedBox(height: 30),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: isSaving ? null : onSave,
              style: ElevatedButton.styleFrom(padding: const EdgeInsets.all(16)),
              child: Text(isSaving ? "Saving..." : "Save first page and preview"),
            ),
          )
        ],
      ),
    );
  }

  Widget _buildInput(String label, String field, dynamic value, {int maxLines = 1, String? hint}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 15),
      child: TextField(
        controller: TextEditingController(text: value?.toString())..selection = TextSelection.collapsed(offset: (value?.toString().length ?? 0)),
        maxLines: maxLines,
        onChanged: (val) => onUpdateField(field, val),
        decoration: InputDecoration(labelText: label, hintText: hint, border: const OutlineInputBorder()),
      ),
    );
  }

  Color _parseColor(String hex) {
    try {
      return Color(int.parse(hex.replaceFirst('#', '0xFF')));
    } catch (e) {
      return Colors.grey;
    }
  }
}