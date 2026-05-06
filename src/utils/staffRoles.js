export const staffRoleDefinitions = {
  manager: {
    value: "manager",
    label: "Owner",
    shortLabel: "Owner",
    workspaceLabel: "Owner workspace",
    description: "Full control of the restaurant, team, site setup, menus, tables, payments, and customer feedback.",
    landingLabel: "Site Builder",
    defaultRoute: { name: "restaurant-builder" }
  },
  floor_manager: {
    value: "floor_manager",
    label: "Floor Manager",
    shortLabel: "Floor Manager",
    workspaceLabel: "Floor manager workspace",
    description: "Runs daily service, team movement, live orders, tables, and front-of-house flow.",
    landingLabel: "Orders",
    defaultRoute: { name: "orders" }
  },
  host: {
    value: "host",
    label: "Host / Reception",
    shortLabel: "Host",
    workspaceLabel: "Host workspace",
    description: "Handles guest arrival, seating, bookings, and table availability.",
    landingLabel: "Tables",
    defaultRoute: { name: "tables" }
  },
  server: {
    value: "server",
    label: "Server",
    shortLabel: "Server",
    workspaceLabel: "Server workspace",
    description: "Follows table service, guest orders, and order status during service.",
    landingLabel: "Orders",
    defaultRoute: { name: "orders" }
  },
  cashier: {
    value: "cashier",
    label: "Cashier",
    shortLabel: "Cashier",
    workspaceLabel: "Cashier workspace",
    description: "Manages orders, bills, and payment flow at the counter or register.",
    landingLabel: "Orders",
    defaultRoute: { name: "orders" }
  },
  barista: {
    value: "barista",
    label: "Barista",
    shortLabel: "Barista",
    workspaceLabel: "Barista workspace",
    description: "Handles drinks, cafe menu readiness, and beverage-related order flow.",
    landingLabel: "Menu Items",
    defaultRoute: { name: "menu_items" }
  },
  kitchen: {
    value: "kitchen",
    label: "Kitchen",
    shortLabel: "Kitchen",
    workspaceLabel: "Kitchen workspace",
    description: "Focuses on preparation, ticket details, ingredients, and menu execution.",
    landingLabel: "Orders",
    defaultRoute: { name: "orders" }
  },
  inventory: {
    value: "inventory",
    label: "Inventory",
    shortLabel: "Inventory",
    workspaceLabel: "Inventory workspace",
    description: "Tracks ingredients, stock movement, and reorder visibility.",
    landingLabel: "Ingredients",
    defaultRoute: { name: "ingredients" }
  }
};

export const staffRoleOptions = [
  staffRoleDefinitions.floor_manager,
  staffRoleDefinitions.host,
  staffRoleDefinitions.server,
  staffRoleDefinitions.cashier,
  staffRoleDefinitions.barista,
  staffRoleDefinitions.kitchen,
  staffRoleDefinitions.inventory
];

export function getStaffRoleMeta(role) {
  return staffRoleDefinitions[role] || staffRoleDefinitions.manager;
}
