# SOFTWARE REQUIREMENTS SPECIFICATION (SRS)
## Project Title: Advanced Cafe Management & POS System (DineDirect)
**Version:** 1.0  
**Author:** Thesis Project Candidate  
**Date:** May 17, 2026  

---

## 1. Introduction

### 1.1 Purpose
This document specifies the software requirements for **DineDirect**, a highly automated, multi-role Cafe Management and Point of Sale (POS) system tailored for high-volume cafe environments (specifically featuring an Ethiopian theme). It outlines the functional and non-functional requirements, dynamic access policies, and advanced business automation rules that make this platform enterprise-grade and thesis-compliant.

### 1.2 Scope
DineDirect is a web-based, multi-tenant restaurant resource planning and order dispatch platform. The software handles:
- **Interactive POS Terminal:** Custom modifier upcharging and real-time ticket building.
- **Enterprise-Grade Kitchen Display System (KDS):** Multi-station routing (Kitchen vs. Barista) and Expeditor tracking.
- **Smart Ingredient & Recipe Engine:** Automated database transactions that perform real-time inventory deductions upon order completion.
- **Dynamic Role-Based Access Control (RBAC):** Live administrative dashboards to configure field and screen-level entity permissions (Read/Write) per staff role.

### 1.3 Definitions, Acronyms, and Abbreviations
- **POS:** Point of Sale
- **KDS:** Kitchen Display System
- **RBAC:** Role-Based Access Control
- **CRUD:** Create, Read, Update, Delete
- **SKU:** Stock Keeping Unit
- **KPI:** Key Performance Indicator

---

## 2. Overall Description

### 2.1 Product Perspective
DineDirect is an independent, single-page application (SPA) front-end built in Vue 3 communicating with a RESTful Laravel API back-end. The system coordinates operations between the cash register (POS), service staff, kitchen preparation, coffee brewing bar, and store management.

### 2.2 Product Functions
- **Unified Authentication:** Multi-role login supporting Managers, Cashiers, Kitchen Staff, Baristas, and Customers.
- **Customer Self-Ordering Menu:** Discover page supporting live ingredient customization (add/remove) with real-time price updates.
- **Automated Checkouts:** Event-driven checkout logic that handles order placement, transaction logging, and recipe stock deductions atomically.
- **Real-time KDS Ticket Splitting:** Separation of beverage orders and food orders to respective preparing stations.
- **Interactive Permission Matrices:** Manager-level control interface to toggle structural feature accesses per role.

### 2.3 User Classes and Characteristics
1. **Manager:** Admin class with full read/write/edit access. Oversees security dashboards, logs, sales KPIs, inventory, and dynamic staff permissions.
2. **Cashier:** Operational staff. Uses the primary POS layout, enters custom pricing upcharges, handles table checkout flows.
3. **Kitchen Staff / Barista:** Food preparation/coffee brewing team. Operates the KDS screen, receiving only relevant items routed to their designated station.
4. **Customer:** Public user. Self-orders via their table, customizes recipes, and tracks preparation history.

---

## 3. System Features

### 3.1 Interactive POS & Modifier Customization
- **Requirement ID:** FR-POS-001  
- **Description:** Cashiers and customers must be able to customize menu items during order building.
- **Details:**
  - When modifying an item, the interface dynamically pulls contextual ingredients from the database.
  - Adding ingredients adds an upcharge calculated from the raw ingredient's `cost_per_unit`.
  - The ticket subtotal, tax, and grand total automatically update in the DOM.

### 3.2 Automated Recipe & Inventory Engine
- **Requirement ID:** FR-INV-002  
- **Description:** Orders must automatically adjust ingredient stock levels atomically.
- **Details:**
  - Relational mapping of `menu_items` to raw `ingredients` via `recipe_ingredients` database schema.
  - When an order is checked out, the back-end initiates a database transaction (`DB::beginTransaction`).
  - The system deducts the precise quantity of ingredients utilized based on the ordered quantities and modifications.
  - Generates transaction logs in the `inventory_transactions` table under the transaction event.
  - Automatically rolls back the entire ticket creation if any single inventory deduction fails, preventing data mismatch.

### 3.3 Enterprise KDS & Split Assembly Line Routing
- **Requirement ID:** FR-KDS-003  
- **Description:** Orders containing beverages and food must automatically split to their respective workstations.
- **Details:**
  - Backend routing classifies items into `barista` (drinks, coffees, lattes) or `kitchen` (tibs, wot, foods) based on category.
  - Station boards are filtered: Baristas only see drink tickets; Kitchen Staff only see food tickets.
  - **Expeditor Dashboard:** Managers view a consolidated ticket displaying split statuses. The ticket can only be marked as "Completed" once both stations have marked their respective items as "Ready".

### 3.4 Dynamic Role-Based Access Control (RBAC)
- **Requirement ID:** FR-SEC-004  
- **Description:** The system must restrict screen-level features and database mutations based on a live permission engine.
- **Details:**
  - Manager dashboard displays a grid/matrix of entities (Menu, Orders, Inventory, Users) against staff roles.
  - Permissions are saved directly to a database schema (`role_permissions`).
  - Frontend sidebar and views use a dynamic filtering middleware that reads the active user's permissions, dynamically hiding UI buttons, sidebars, and blocking unauthorized API requests.

---

## 4. External Interface Requirements

### 4.1 User Interfaces
- Modern, high-performance responsive web interfaces.
- Uses Ethiopian color themes (Ochre, Emerald, Terracotta, Charcoal) mapped through responsive CSS styling.
- Interactive drawers for modifiers, glassmorphic panel headers, and smooth micro-animations.

### 4.2 Software Interfaces
- **Frontend Framework:** Vue 3 (Vite, Pinia for State Management, Vue Router).
- **Backend API:** Laravel REST API (PHP 8.2+, Eloquent ORM).
- **Database Engine:** PostgreSQL / SQLite (transaction-safe ACID compliance).

---

## 5. Non-functional Requirements

### 5.1 Security
- All api routes must be protected using session/token-based authentication.
- Strict backend role authorization checks on the API controller layer (e.g. `BaseApiController` permission checks).

### 5.2 Reliability & Concurrency
- Uses ACID transactions on crucial endpoints (`CheckoutController`) to prevent database desynchronization under high-traffic checkouts.
- Graceful API failure handling to prevent UI locking.

### 5.3 Performance & Responsiveness
- High-efficiency local network rendering.
- Polling-based auto-refresh intervals of 8 seconds on KDS screens to ensure low latency without server starvation.
