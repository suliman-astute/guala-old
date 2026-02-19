# Database Schema & Frontend Architecture

## Database Schema Overview

The application uses a **Laravel-managed relational database** (MariaDB/MySQL) with a mix of Eloquent ORM models and raw database tables. The schema reflects a manufacturing management system with multi-tenant site support and role-based access control.

**Update (Jan 2026):** The database schema has been fully modernized and translated from Italian to English. All new development should use the English table and column names.

### Core Tables & Relationships

#### Authentication & Users
```
users (id, name, email, password, admin, surname, personal_role, status, site_id, lang, extra, is_ad_user, superadmin)
  ├─ hasMany: active_app_user
  ├─ hasMany: sessions
  ├─ belongsToMany: active_apps (through active_app_user)
  ├─ belongsTo: sites
  └─ hasMany: shift_assignments (via shift_leader_id)

sites (id, name, deleted_at, created_at, updated_at)
  └─ hasMany: users
  └─ hasMany: active_apps

active_apps (id, code, name_it, name_en, site_id, icon, deleted_at, created_at, updated_at)
  ├─ belongsToMany: users (through active_app_user)
  └─ belongsTo: sites

active_app_user (id, user_id, active_app_id) [junction table]
  ├─ belongsTo: users
  └─ belongsTo: active_apps
```

#### Shift & Operations Management
```
shifts (id, name, start_time, end_time, created_at, updated_at)
  └─ hasMany: shift_assignments

shift_assignments (id, shift_leader_id, shift_id, shift_date, operator_ids [JSON], associated_machine_ids [JSON], created_at, updated_at)
  ├─ belongsTo: users (as shift_leader)
  ├─ belongsTo: shifts
  └─ JSON relations: operator_ids (users), associated_machine_ids (presses)

press_shift_assignments (id, shift_leader_id, shift_id, shift_date, operator_ids [JSON], associated_machine_ids [JSON], created_at, updated_at)
  └─ Similar structure to shift_assignments but for press operations
```

#### Machine & Equipment
```
machine_centers (id, no, name, machine_center_type, company, position, schedule, created_at, updated_at)
  └─ Used for storing all equipment (machines, presses, stamping units)

presses (id, name, created_at, updated_at)
  └─ Legacy press definitions

machines (id, name, created_at, updated_at)
  └─ Machine definitions

machine_support_table (id, no, mes_id, piovan_id, created_at, updated_at)
  └─ Support table mapping machine_centers.no to MES and Piovan IDs

machine_operator_notes (id, created_at, updated_at)
  └─ Notes for machine-operator associations
```

#### Production & Orders
```
guala_items_in_production (id, mesOrderNo, parentitemNo, created_at, updated_at)
  └─ Production items from MES system

guala_mes_production_orders (id, mesOrderNo, itemNo, machineSatmp, machinePress, machinePressDesc, relSequence, quantity, produced_quantity, comment, created_at, updated_at)
  └─ Production orders with progress tracking

guala_production_routing (prodOrderNo, operationNo, total_good_produced_quantity)
  └─ Production routing with total good quantity produced

order_from_mes (order_name, mes_status)
  └─ Orders from MES with status

order_notes (id, created_at, updated_at)
  └─ Notes on orders (batch/lot tracking)

bisio_stain_projects (sap_order_number, name, operation_status)
  └─ SAP order status and operation state
```

#### Catalog & Configuration
```
shifts (shifts/work periods)
companies (id, name, created_at, updated_at) [Companies/Organizations]
object_codes (id, code, description, family, product_type, uom, no)
  └─ Object codes (SKUs, component codes)
```

#### System Tables
```
dictionary (id, IT, EN, table_name, column_name, domain_type)
  └─ Localized field names and enum values

activity_log (id, log_name, description, subject_type, subject_id, causer_type, causer_id, properties, batch_uuid, event, created_at, updated_at)
  └─ Spatie Activity Log for audit trails

active_directory_configs (id, domain, host, base_dn, port, created_at, updated_at)
  └─ Active Directory management

ext_infos (id, created_at, updated_at)
  └─ Extended information storage

jobs (id, queue, payload, attempts, reserved_at, available_at, created_at)
  └─ Queue job storage

cache (key, value, expiration)
  └─ Cache storage
```

#### Views (Database Views)
```
molding_view (formerly stampaggio_view)
  SELECT FROM: guala_mes_production_orders 
  LEFT JOIN: machine_centers ON machinePress = no
  FIELDS: mesOrderNo, itemNo, itemDescription, machinePress, position, quantity, produced_quantity, machinePressFull
  FILTER: WHERE mesOrderNo LIKE '%ST%'

assembly_view (formerly assemblaggio_view)
  Similar to molding_view but for assembly orders (LIKE '%AS%')
```

### Data Types & Key Patterns

1. **JSON Storage**: Complex many-to-many relationships stored as JSON arrays in single columns
   - `shift_assignments.operator_ids` → JSON array of user IDs
   - `shift_assignments.associated_machine_ids` → JSON array of press IDs

2. **Soft Deletes**: Used for non-destructive deletion
   - `users.deleted_at`
   - `sites.deleted_at`
   - `active_apps.deleted_at`

3. **Timestamps**: Standard Laravel created_at/updated_at on most tables

4. **Foreign Keys**: Established for referential integrity
   - `active_app_user.user_id` → `users.id` (cascade delete)
   - `active_app_user.active_app_id` → `active_apps.id` (cascade delete)
   - `active_apps.site_id` → `sites.id` (set null on delete)

### Company/Tenant Filtering

The application uses a **multi-tenant model** via `machine_centers.company` field:
- "Guala Dispensing FP" - Primary company for filtering machines
- Other companies can be configured
- Filters are applied in queries to isolate data by company

---

## Frontend Architecture

### Frontend Stack
- **Template Engine**: Laravel Blade (server-side rendering)
- **CSS Framework**: Bootstrap 5.2.3 + TailwindCSS 4.0
- **CSS Preprocessor**: Sass 1.56.1
- **JavaScript**: Vanilla JavaScript (ES6+) with Axios
- **UI Grid**: ag-Grid Community (^33.3.1)
- **Build Tool**: Vite 6.0.11
- **Admin Template**: AdminLTE 3.15
- **HTTP Client**: Axios 1.7.4

### NO Frontend Framework
- **No Vue.js** - Not installed
- **No React** - Not installed
- **No Angular** - Not installed
- **Pure Server-Side Rendering** - All views are Blade templates compiled on the server

### Asset Compilation Pipeline

#### Vite Configuration
**File**: [vite.config.js](vite.config.js)

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',    // Compiles to public/build/assets/app.css
                'resources/js/app.js',         // Compiles to public/build/assets/app.js
            ],
            refresh: true,                    // Hot reload on file changes
        }),
    ],
});
```

#### Build Process
1. **Development**: `npm run dev`
   - Starts Vite dev server
