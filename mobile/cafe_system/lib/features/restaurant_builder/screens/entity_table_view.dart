import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/entity_provider.dart';

class EntityTableView extends ConsumerStatefulWidget {
  final Map<String, dynamic> entityDef; // Contains 'key', 'label', 'fields'

  const EntityTableView({super.key, required this.entityDef});

  @override
  ConsumerState<EntityTableView> createState() => _EntityTableViewState();
}

class _EntityTableViewState extends ConsumerState<EntityTableView> {
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    Future.microtask(() => _load());
  }

  void _load() {
    ref.read(entityProvider.notifier).fetch(widget.entityDef['key'], search: _searchController.text);
  }

  void _openForm([Map<String, dynamic>? row]) {
    final String key = widget.entityDef['key'];
    final List<String> fields = List<String>.from(widget.entityDef['fields'] ?? []);
    
    // Logic to generate form controllers
    final controllers = { for (var f in fields) f : TextEditingController(text: row?[f]?.toString() ?? "") };

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(row == null ? "Create ${widget.entityDef['label']}" : "Edit Record"),
        content: SingleChildScrollView(
          child: Column(
            children: fields.map((f) => TextField(
              controller: controllers[f],
              decoration: InputDecoration(labelText: f),
            )).toList(),
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text("Cancel")),
          ElevatedButton(
            onPressed: () async {
              final payload = controllers.map((k, v) => MapEntry(k, _normalize(v.text)));
              await ref.read(entityProvider.notifier).save(key, row?['id'], payload);
              if (mounted) Navigator.pop(context);
            },
            child: const Text("Save"),
          )
        ],
      ),
    );
  }

  // Logic to replicate Vue's normalizePayload
  dynamic _normalize(String value) {
    if (value.isEmpty) return null;
    if (value.toLowerCase() == "true") return true;
    if (value.toLowerCase() == "false") return false;
    final numValue = num.tryParse(value);
    if (numValue != null) return numValue;
    return value;
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(entityProvider);
    final rows = state.byEntity[widget.entityDef['key']] ?? [];
    
    // Dynamic Columns: Get keys from first row (Vue columns computed)
    final List<String> columns = rows.isNotEmpty 
        ? (rows.first as Map<String, dynamic>).keys.take(5).toList() 
        : [];

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.entityDef['label']),
        actions: [
          IconButton(icon: const Icon(Icons.add), onPressed: () => _openForm()),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: TextField(
              controller: _searchController,
              decoration: const InputDecoration(hintText: "Search...", suffixIcon: Icon(Icons.search)),
              onSubmitted: (_) => _load(),
            ),
          ),
          if (state.loading) const LinearProgressIndicator(),
          Expanded(
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: SingleChildScrollView(
                child: DataTable(
                  columns: [
                    ...columns.map((c) => DataColumn(label: Text(c.toUpperCase()))),
                    const DataColumn(label: Text("ACTIONS")),
                  ],
                  rows: rows.map((row) => DataRow(cells: [
                    ...columns.map((c) => DataCell(Text(row[c]?.toString() ?? "-"))),
                    DataCell(Row(
                      children: [
                        IconButton(icon: const Icon(Icons.edit), onPressed: () => _openForm(row)),
                        IconButton(icon: const Icon(Icons.delete, color: Colors.red), 
                          onPressed: () => ref.read(entityProvider.notifier).delete(widget.entityDef['key'], row['id'])),
                      ],
                    )),
                  ])).toList(),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}