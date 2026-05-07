class EntityDefinition {
  final String key;
  final String label;
  final List<String> roles;
  final List<String>? staffRoles;
  final List<String> fields;

  const EntityDefinition({
    required this.key,
    required this.label,
    required this.roles,
    this.staffRoles,
    required this.fields,
  });
}

class EntitiesConfig {
  static const List<EntityDefinition> all = [
    EntityDefinition(
      key: "users",
      label: "Users",
      roles: ["restaurant"],
      staffRoles: ["manager"],
      fields: ["name", "email", "password", "staff_role"],
    ),
    EntityDefinition(
      key: "menu_items",
      label: "Menu Items",
      roles: ["restaurant"],
      staffRoles: ["manager", "cashier", "barista"],
      fields: ["name", "description", "price", "is_available"],
    ),
    EntityDefinition(
      key: "orders",
      label: "Orders",
      roles: ["restaurant"],
      fields: ["order_number", "status", "total", "placed_at"],
    ),
    // ... Add the rest of your entities from the JS file here
  ];
}