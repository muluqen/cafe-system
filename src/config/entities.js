const ownerOnly = ["manager"];
const leadership = ["manager", "floor_manager"];
const frontOfHouse = ["manager", "floor_manager", "host", "server", "cashier"];
const drinksAndKitchen = ["manager", "floor_manager", "barista", "kitchen"];
const stockTeam = ["manager", "inventory", "kitchen"];
const paymentsTeam = ["manager", "cashier"];
const serviceTeam = ["manager", "floor_manager", "host", "server", "cashier", "barista", "kitchen"];

export const entities = [
  {
    key: "users",
    label: "Team",
    roles: ["restaurant"],
    staffRoles: ownerOnly,
    mutateStaffRoles: ownerOnly,
    fields: ["name", "email", "password", "staff_role"]
  },
  {
    key: "restaurants",
    label: "Restaurant Profile",
    roles: ["restaurant"],
    staffRoles: ownerOnly,
    mutateStaffRoles: ownerOnly,
    fields: ["name", "slug", "phone", "email", "address", "is_active"]
  },
  {
    key: "menu_categories",
    label: "Menu Categories",
    roles: ["restaurant"],
    staffRoles: [...ownerOnly, ...drinksAndKitchen.filter((role) => role !== "manager")],
    mutateStaffRoles: ["manager", "barista", "kitchen"],
    fields: ["name", "display_order", "is_active"]
  },
  {
    key: "menu_items",
    label: "Menu Items",
    roles: ["restaurant"],
    staffRoles: [...ownerOnly, ...drinksAndKitchen.filter((role) => role !== "manager"), "cashier"],
    mutateStaffRoles: ["manager", "barista", "kitchen"],
    fields: ["menu_category_id", "name", "sku", "description", "price", "is_available", "preparation_time_minutes"]
  },
  {
    key: "orders",
    label: "Orders",
    roles: ["restaurant"],
    staffRoles: serviceTeam,
    mutateStaffRoles: ["manager", "floor_manager", "host", "server", "cashier", "barista", "kitchen"],
    fields: ["table_id", "order_number", "status", "subtotal", "tax", "total", "notes", "placed_at", "closed_at"]
  },
  {
    key: "order_items",
    label: "Order Items",
    roles: ["restaurant"],
    staffRoles: serviceTeam,
    mutateStaffRoles: ["manager", "floor_manager", "host", "server", "cashier", "barista", "kitchen"],
    fields: ["order_id", "menu_item_id", "item_name", "quantity", "unit_price", "line_total", "notes"]
  },
  {
    key: "order_status_history",
    label: "Order Status History",
    roles: ["restaurant"],
    staffRoles: serviceTeam,
    mutateStaffRoles: ["manager", "floor_manager", "host", "server", "cashier", "barista", "kitchen"],
    fields: ["order_id", "status", "changed_by", "changed_at", "note"]
  },
  {
    key: "preferences",
    label: "Preferences",
    roles: ["restaurant"],
    staffRoles: leadership,
    mutateStaffRoles: leadership,
    fields: ["key", "value"]
  },
  {
    key: "ingredients",
    label: "Ingredients",
    roles: ["restaurant"],
    staffRoles: [...ownerOnly, "inventory", "kitchen"],
    mutateStaffRoles: ["manager", "inventory", "kitchen"],
    fields: ["name", "unit", "current_stock", "reorder_level", "cost_per_unit", "is_active"]
  },
  {
    key: "inventory_transactions",
    label: "Inventory Transactions",
    roles: ["restaurant"],
    staffRoles: stockTeam,
    mutateStaffRoles: ["manager", "inventory"],
    fields: ["ingredient_id", "type", "quantity", "balance_after", "reference_type", "reference_id", "note", "transacted_at"]
  },
  {
    key: "tables",
    label: "Tables",
    roles: ["restaurant"],
    staffRoles: frontOfHouse,
    mutateStaffRoles: ["manager", "floor_manager", "host", "cashier"],
    fields: ["name", "capacity", "location", "status", "is_active"]
  },
  {
    key: "table_sessions",
    label: "Table Sessions",
    roles: ["restaurant"],
    staffRoles: frontOfHouse,
    mutateStaffRoles: ["manager", "floor_manager", "host", "server", "cashier"],
    fields: ["table_id", "order_id", "opened_at", "closed_at", "guest_count", "status"]
  },
  {
    key: "shifts",
    label: "Shifts",
    roles: ["restaurant"],
    staffRoles: leadership,
    mutateStaffRoles: leadership,
    fields: ["name", "starts_at", "ends_at", "status", "notes"]
  },
  {
    key: "staff_shift_assignments",
    label: "Staff Shift Assignments",
    roles: ["restaurant"],
    staffRoles: leadership,
    mutateStaffRoles: leadership,
    fields: ["shift_id", "user_id", "role", "status", "clock_in_at", "clock_out_at"]
  },
  {
    key: "payment_events",
    label: "Payment Events",
    roles: ["restaurant"],
    staffRoles: [...ownerOnly, "floor_manager", ...paymentsTeam.filter((role) => role !== "manager")],
    mutateStaffRoles: ["manager", "floor_manager", "cashier"],
    fields: ["order_id", "amount", "method", "provider", "provider_reference", "status", "paid_at"]
  }
];
