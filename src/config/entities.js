export const entities = [
  {
    key: "users",
    label: "Users",
    roles: ["restaurant"],
    staffRoles: ["manager"],
    mutateStaffRoles: ["manager"],
    fields: ["name", "email", "password", "staff_role"]
  },
  {
    key: "restaurants",
    label: "Restaurants",
    roles: ["restaurant"],
    staffRoles: ["manager"],
    mutateStaffRoles: ["manager"],
    fields: ["name", "slug", "phone", "email", "address", "is_active"]
  },
  {
    key: "menu_categories",
    label: "Menu Categories",
    roles: ["restaurant"],
    staffRoles: ["manager", "cashier", "barista"],
    mutateStaffRoles: ["manager", "barista"],
    fields: ["name", "display_order", "is_active"]
  },
  {
    key: "menu_items",
    label: "Menu Items",
    roles: ["restaurant"],
    staffRoles: ["manager", "cashier", "barista"],
    mutateStaffRoles: ["manager", "barista"],
    fields: ["menu_category_id", "name", "sku", "description", "price", "is_available", "preparation_time_minutes"]
  },
  {
    key: "orders",
    label: "Orders",
    roles: ["restaurant"],
    staffRoles: ["manager", "cashier", "barista"],
    mutateStaffRoles: ["manager", "cashier", "barista"],
    fields: ["table_id", "order_number", "status", "subtotal", "tax", "total", "notes", "placed_at", "closed_at"]
  },
  {
    key: "order_items",
    label: "Order Items",
    roles: ["restaurant"],
    staffRoles: ["manager", "cashier", "barista"],
    mutateStaffRoles: ["manager", "cashier", "barista"],
    fields: ["order_id", "menu_item_id", "item_name", "quantity", "unit_price", "line_total", "notes"]
  },
  {
    key: "order_status_history",
    label: "Order Status History",
    roles: ["restaurant"],
    staffRoles: ["manager", "cashier", "barista"],
    mutateStaffRoles: ["manager", "cashier", "barista"],
    fields: ["order_id", "status", "changed_by", "changed_at", "note"]
  },
  {
    key: "preferences",
    label: "Preferences",
    roles: ["restaurant"],
    staffRoles: ["manager", "cashier", "barista"],
    mutateStaffRoles: ["manager", "cashier", "barista"],
    fields: ["key", "value"]
  },
  {
    key: "ingredients",
    label: "Ingredients",
    roles: ["restaurant"],
    staffRoles: ["manager"],
    mutateStaffRoles: ["manager"],
    fields: ["name", "unit", "current_stock", "reorder_level", "cost_per_unit", "is_active"]
  },
  {
    key: "inventory_transactions",
    label: "Inventory Transactions",
    roles: ["restaurant"],
    staffRoles: ["manager"],
    mutateStaffRoles: ["manager"],
    fields: ["ingredient_id", "type", "quantity", "balance_after", "reference_type", "reference_id", "note", "transacted_at"]
  },
  {
    key: "tables",
    label: "Tables",
    roles: ["restaurant"],
    staffRoles: ["manager", "cashier", "barista"],
    mutateStaffRoles: ["manager", "cashier"],
    fields: ["name", "capacity", "location", "status", "is_active"]
  },
  {
    key: "table_sessions",
    label: "Table Sessions",
    roles: ["restaurant"],
    staffRoles: ["manager", "cashier", "barista"],
    mutateStaffRoles: ["manager", "cashier"],
    fields: ["table_id", "order_id", "opened_at", "closed_at", "guest_count", "status"]
  },
  {
    key: "shifts",
    label: "Shifts",
    roles: ["restaurant"],
    staffRoles: ["manager"],
    mutateStaffRoles: ["manager"],
    fields: ["name", "starts_at", "ends_at", "status", "notes"]
  },
  {
    key: "staff_shift_assignments",
    label: "Staff Shift Assignments",
    roles: ["restaurant"],
    staffRoles: ["manager"],
    mutateStaffRoles: ["manager"],
    fields: ["shift_id", "user_id", "role", "status", "clock_in_at", "clock_out_at"]
  },
  {
    key: "payment_events",
    label: "Payment Events",
    roles: ["restaurant"],
    staffRoles: ["manager", "cashier"],
    mutateStaffRoles: ["manager", "cashier"],
    fields: ["order_id", "amount", "method", "provider", "provider_reference", "status", "paid_at"]
  }
];
