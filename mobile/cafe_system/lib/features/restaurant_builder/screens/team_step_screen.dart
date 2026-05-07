import 'package:cafe_system/models/user.dart';
import 'package:flutter/material.dart';


class TeamStepView extends StatefulWidget {
  final List<User> staffMembers;
  final int? ownerId;
  final bool isSaving;
  final String? error;
  final Function(Map<String, dynamic>) onAddStaff;
  final Function(User, String) onUpdateRole;
  final Function(int) onRemoveStaff;
  final VoidCallback onBack;
  final VoidCallback onNext;

  const TeamStepView({
    super.key,
    required this.staffMembers,
    this.ownerId,
    required this.isSaving,
    this.error,
    required this.onAddStaff,
    required this.onUpdateRole,
    required this.onRemoveStaff,
    required this.onBack,
    required this.onNext,
  });

  @override
  State<TeamStepView> createState() => _TeamStepViewState();
}

class _TeamStepViewState extends State<TeamStepView> {
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passController = TextEditingController();
  String _selectedRole = 'cashier';

  void _submit() {
    widget.onAddStaff({
      'name': _nameController.text,
      'email': _emailController.text,
      'password': _passController.text,
      'staff_role': _selectedRole,
    });
    _nameController.clear();
    _emailController.clear();
    _passController.clear();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text("Step 4", style: TextStyle(color: Colors.grey)),
        const Text("Add staff and assign roles", style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
        const SizedBox(height: 20),

        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                Row(
                  children: [
                    Expanded(child: _buildInput("Full name", _nameController)),
                    const SizedBox(width: 10),
                    Expanded(child: _buildInput("Work email", _emailController)),
                  ],
                ),
                const SizedBox(height: 15),
                Row(
                  children: [
                    Expanded(child: _buildInput("Temporary password", _passController)),
                    const SizedBox(width: 10),
                    Expanded(
                      child: DropdownButtonFormField<String>(
                        value: _selectedRole,
                        decoration: const InputDecoration(labelText: "Role", border: OutlineInputBorder()),
                        items: ['cashier', 'barista', 'manager'].map((role) {
                          return DropdownMenuItem(value: role, child: Text(role.toUpperCase()));
                        }).toList(),
                        onChanged: (val) => setState(() => _selectedRole = val!),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                Row(
                  children: [
                    TextButton(onPressed: widget.onBack, child: const Text("Back")),
                    const Spacer(),
                    TextButton(onPressed: widget.onNext, child: const Text("Skip for now")),
                    ElevatedButton(
                      onPressed: widget.isSaving ? null : _submit,
                      child: Text(widget.isSaving ? "Saving..." : "Add staff member"),
                    ),
                  ],
                )
              ],
            ),
          ),
        ),

        if (widget.error != null) Text(widget.error!, style: const TextStyle(color: Colors.red)),

        const SizedBox(height: 20),
        ListView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: widget.staffMembers.length,
          itemBuilder: (context, index) {
            final member = widget.staffMembers[index];
            bool isOwner = member.id == widget.ownerId;

            return ListTile(
              title: Text(member.name ?? ""),
              subtitle: Text("${member.email} • ${member.staffRole ?? 'manager'}"),
              trailing: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  DropdownButton<String>(
                    value: member.staffRole ?? 'manager',
                    disabledHint: Text(member.staffRole ?? 'manager'),
                    onChanged: isOwner ? null : (val) => widget.onUpdateRole(member, val!),
                    items: ['manager', 'cashier', 'barista'].map((r) => DropdownMenuItem(value: r, child: Text(r))).toList(),
                  ),
                  if (!isOwner)
                    IconButton(icon: const Icon(Icons.delete, color: Colors.red), onPressed: () => widget.onRemoveStaff(member.id!)),
                ],
              ),
            );
          },
        )
      ],
    );
  }

  Widget _buildInput(String label, TextEditingController controller) {
    return TextField(controller: controller, decoration: InputDecoration(labelText: label, border: const OutlineInputBorder()));
  }
}