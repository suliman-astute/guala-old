# Backend Logic & API Analysis

## Architecture Overview

The application follows a **Laravel Model-View-Controller (MVC)** pattern with the following design characteristics:

### Design Patterns Used

1. **Event-Driven Architecture**: Uses Laravel's event system with listeners for cross-cutting concerns
   - `UpdateDictionaryOnLogin` - Refreshes the translation dictionary on admin login
   - `LogSuccessfulLogin` - Logs all user logins to activity log using Spatie Activity Log

2. **Service-Oriented Queries**: Heavy use of Eloquent ORM and raw query builders for data transformation
   - Controllers directly handle database queries
   - Inline helpers and transformation functions within controllers

3. **JSON Data Storage**: Arrays stored as JSON in database columns (e.g., operator/machine assignments)
   - Custom normalization logic converts between array/comma-separated/JSON formats
   - `toIdArray()` helper normalizes different input formats

4. **No Repository Pattern**: Direct database access in controllers (not using Repository pattern)
5. **No Explicit Service Layer**: Business logic embedded in controllers
6. **No Observers**: Uses Listeners instead via EventServiceProvider

---

## API Routes & Features

### Authentication Routes
- **Login**: `POST /login` - User authentication (LDAP-enabled via ldaprecord-laravel)
- **Logout**: `POST /logout` - Logout (fires `LogSuccessfulLogin` listener)
- Registration, password reset, and verification are **disabled** (`register => false`)

### Dashboard & Home
- **Dashboard**: `GET /` or `GET /home` - Main home page (requires auth)
- **App Redirect**: `GET /redirect/{code}` - Routes to specific app by code (checks user permissions)
- **Active Apps Image**: `GET /active_apps/image/{id}` - Retrieve app thumbnail

---

## Feature Groups & Routes

### Admin Panel (middleware: `auth`, `can:ADMIN`)

#### 1. **Site Management** (Prefix: `/sites`)
- `GET /sites/` - List all sites
- `POST /sites/json` - Fetch sites as JSON (for table views)
- `GET /sites/form/{id?}` - Create/edit site form
- `POST /sites/form` - Store/update site
- `POST /sites/delete` - Delete site

#### 2. **User Management** (Prefix: `/users`)
- `GET /users/` - List users
- `POST /users/json` - Fetch users as JSON
- `GET /users/form/{id?}` - User form
- `POST /users/form` - Store/update user
- `POST /users/delete` - Delete user
- `GET /users/form_active_apps/{id?}` - Assign active apps to user
- `POST /users/form_active_apps` - Store active app assignments

#### 3. **Active Apps Management** (Prefix: `/active_apps`)
- `GET /active_apps/` - List active apps
- `POST /active_apps/json` - Fetch as JSON
- `GET /active_apps/form/{id?}` - App form
- `POST /active_apps/form` - Store/update app
- `POST /active_apps/delete` - Delete app

#### 4. **Dictionary/Translations** (Prefix: `/traduzioni`)
- `GET /traduzioni/` - View translations
- `GET /traduzioni/json` - Fetch as JSON
- `GET /traduzioni/form/{id?}` - Create translation
- `POST /traduzioni/store` - Store translation
- `GET /traduzioni/edit/{id}` - Edit form
- `POST /traduzioni/update/{id}` - Update translation
- `POST /traduzioni/delete` - Delete translation

#### 5. **Shift Management** (Prefix: `/turni`)
- `GET /turni/` - List shifts
- `POST /turni/json` - Fetch as JSON
- `GET /turni/form/{id?}` - Create/edit shift
- `POST /turni/form` - Store/update
- `POST /turni/delete` - Delete

#### 6. **Object Codes** (Prefix: `/codice_oggetto`)
- Standard CRUD operations: list, json, form, store, delete

#### 7. **Piovan Management** (Prefix: `/gestione_piovan`)
- Standard CRUD operations for Piovan system integration

#### 8. **Companies/Organizations** (Prefix: `/aziende`)
- Standard CRUD operations: `GET /`, `POST /json`, forms, store, delete

#### 9. **Guala Presses FP** (Prefix: `/presse_guala_fp`)
- `GET /presse_guala_fp/` - List presses
- `POST /presse_guala_fp/json` - Fetch as JSON
- `GET /presse_guala_fp/form/{id?}` - Create/edit form
- `POST /presse_guala_fp/form` - Store/update
- `POST /presse_guala_fp/delete` - Delete

#### 10. **Machine Presses** (Prefix: `/presse`)
- Standard CRUD: list, json, form, store, delete

#### 11. **Stamping** (Prefix: `/stamping`)
- Standard CRUD operations

#### 12. **Machines** (Prefix: `/macchine`)
- `GET /macchine/` - List machines
- `POST /macchine/json` - Fetch as JSON
- `GET /macchine/form/{id?}` - Create/edit
- `POST /macchine/form` - Store/update
- `POST /macchine/delete` - Delete
- `GET /macchine/form_dati/{id?}` - Extended data form
- `POST /macchine/form_dati` - Store extended machine data

#### 13. **Active Directory** (Prefix: `/ad`)
- Standard CRUD: Manage LDAP/AD sync
- `GET /ad/`, `POST /ad/json`, forms, store, delete

#### 14. **APP1** (Assemblaggio Dashboard)
- `GET /APP1` - View assemblaggio production dashboard

#### 15. **Shift Management APP2** (Prefix: `/APP2`)
- `GET /APP2/` - Index
- `GET /APP2/json` - Fetch shifts as JSON for today (or custom date)
- `GET /APP2/form/{id?}` - Create/edit shift assignment
- `POST /APP2/form` - Store/update shift with operators & machines
- `POST /APP2/delete` - Delete shift

#### 16. **Presses Shift Management APP3** (Prefix: `/APP3`)
- `GET /APP3/` - Index
- `GET /APP3/json` - Fetch press shifts as JSON
- `GET /APP3/form/{id?}` - Create/edit
- `POST /APP3/form` - Store/update
- `POST /APP3/delete` - Delete

---

### Authenticated User Routes (All Users)

#### 1. **APP1 - Stampaggio Table View** (Production Stamping)
- `GET /tableview` - Show stampaggio production table
- `POST /save-comment` - Update comment on stamping order

#### 2. **APP1 - Assemblaggio Table View**
- `GET /tableviewAssemblaggio` - Show assemblaggio production table
- `POST /save-comment` - Update assemblaggio comment

#### 3. **Monitor FP** (Production Monitoring)
- `GET /monitor_fp` - Production FP monitoring dashboard
- `GET /monitor_fp/data` - Fetch production data as JSON (supports date range filtering: `?from=YYYY-MM-DD&to=YYYY-MM-DD`)
- `POST /save-comment` - Update production comment

#### 4. **Ordini/Orders** (Prefix: `/ordini`)
- `GET /ordini/` - Orders index
- `GET /ordini/json` - Fetch orders as JSON (supports `?data=YYYY-MM-DD`)
- `GET /ordini/dettaglio` - Order details (querystring: `?ordine=CM2501389/01`)
- `GET /ordini/note/list` - List notes (querystring: `?ordine=XYZ`)
- `POST /ordini/note` - Save note (`{ ordine, lotto, nota }`)
- `GET /ordini/piovan` - Piovan orders
- `POST /ordini/piovan/lotto` - Save Piovan batch

#### 5. **Machine-Operator Association** (Prefix: `/associazione_macchine`)
- `GET /associazione_macchine/` - Association index
- `GET /associazione_macchine/json` - Fetch associations (querystring: `?data=YYYY-MM-DD`)
- `POST /associazione_macchine/nota` - Save note (`{ id, nota }`)

#### 6. **BOM Details** (Bill of Materials)
- `GET /dettagli-ordine/{id}/{parentitemNo}` - Show BOM details for order

#### 7. **Barcode Generation**
- `GET /barcode/{code}` - Generate Code128 barcode image

#### 8. **PDF Exports**
- `GET /APP1/PDF/stampa` - Export assemblaggio PDF
- `GET /APP1/PDF_Stampaggio/stampa` - Export stampaggio PDF
- `GET /Monitor_Fp/PDF_Stampaggio_Fp/stampa` - Export production FP PDF

---

## Complex Controller Analysis

### 1. **GestioneTurniController** (Shift Management)
**File**: [app/Http/Controllers/GestioneTurniController.php](app/Http/Controllers/GestioneTurniController.php)

**Purpose**: Manages shift assignments for assembly operations, connecting operators with machines and shifts.

**Key Methods**:

- **`json(Request $request)`** - Complex data aggregation
  - Filters shifts by date (default: today)
  - Only shows shifts for non-admin users (restricted to shifts they manage)
  - Performs nested joins to resolve operator names and machine names
  - Converts stored JSON arrays to comma-separated display strings
  - Handles flexible ID formats: array, JSON string, comma-separated, or single value

- **`create($id = null)`** - Pre-loads associable resources
  - Fetches available operators (active "Operatore Assemblaggio" users)
  - Fetches available machines for "Guala Dispensing FP" company
  - Passes as dropdown options to Blade view

- **`store(Request $request)`** - Normalizes and validates data
  - Calls `toIdArray()` to normalize operator/machine IDs into consistent array format
  - Validates shift data with Eloquent validation rules
  - Stores arrays as JSON in database columns
  - Returns JSON response for AJAX submission

**Design Pattern**: Direct database query with inline transformation (no repository)

**Business Logic**: 
- Time-based filtering (shifts for specific dates)
- Role-based filtering (admins see all, others see own shifts)
- Data normalization (flexible input formats)

---

### 2. **ProductionFPController** (Production Monitoring)
**File**: [app/Http/Controllers/ProductionFPController.php](app/Http/Controllers/ProductionFPController.php) (657 lines)

**Purpose**: Retrieves and enriches production data from multiple tables for real-time monitoring dashboard.

**Key Methods**:

- **`index(Request $request)`** - Complex multi-source data enrichment
  - Fetches production records from `ProductionFP` model
  - Filters by date range (querystring: `?from=YYYY-MM-DD&to=YYYY-MM-DD`)
  - **Enriches machine data** with names and positions from `Macchine` table
  - **Batch-loads production quantities** from `table_guaprodrouting`
  - **Batch-loads operation status** from `bisio_progetti_stain`
  - Filters out machines not in the "Guala Dispensing FP" company
  - Returns fully enriched JSON response

- **`updateCommento(Request $request)`** - Quick comment update
  - Saves user comments to `table_gua_mes_prod_orders`

**Design Pattern**: Heavy use of batch query optimization, in-memory mapping

**Business Logic**:
- Multi-database source aggregation (ProductionFP, Macchine, routing tables)
- Position-based machine mapping
- Batch query optimization to prevent N+1 problem
- Company-based filtering for data isolation

---

### 3. **OrdiniController** (Orders Management)
**File**: [app/Http/Controllers/OrdiniController.php](app/Http/Controllers/OrdiniController.php) (272 lines)

**Purpose**: Manages work orders and batch tracking, including operator notes and Piovan integration.

**Key Methods**:

- **`json(Request $request)`** - Complex shift-operator-order mapping
  - Gets current user's shifts for today (or custom date via `?data=YYYY-MM-DD`)
  - For non-admin users: filters to only their assigned shifts
  - Extracts machines assigned to those shifts
  - Joins with order data from `bisio_progetti_stain`
  - Returns hierarchical structure: shifts → machines → orders (SAP order numbers)
  - Access control: non-admins only see their own shifts' orders

- **`dettaglio(Request $request)`** - Order detail retrieval
  - Querystring: `?ordine=CM2501389/01`
  - Fetches full order BOM and details

- **`listNote(Request $request)`** - Notes management
  - Retrieves all notes for an order
  - Querystring: `?ordine=XYZ`

- **`saveNota(Request $request)`** - Saves batch-specific notes
  - Request payload: `{ ordine, lotto, nota }`
  - Updates or creates notes in tracking table

- **`piovan()` & `salvaLotto(Request $request)`** - Piovan ERP integration
  - Syncs batch data with Piovan accounting system
  - `POST /ordini/piovan/lotto` - Saves batch to Piovan

**Design Pattern**: Access control (user-based filtering), hierarchical data aggregation

**Business Logic**:
- Multi-level authorization (admin vs. operator)
- Date-aware filtering (today's shifts)
- Order-to-operator mapping via shift assignments
- ERP integration (Piovan batch tracking)

---

### 4. **stampaggiotableViewController** (Stamping Table)
**File**: [app/Http/Controllers/stampaggiotableViewController.php](app/Http/Controllers/stampaggiotableViewController.php)

**Purpose**: Real-time stamping/production monitoring table.

**Key Methods**:

- **`index()`** - Fetches stamping production data
  - Queries `stampaggio_view` (likely a database view aggregating multiple tables)
  - Filters valid orders (non-null `mesOrderNo` and `itemNo`)
  - Groups by machine press and position
  - Checks for PDF file existence on disk
  - Returns JSON for ag-Grid frontend table

- **`updateCommento(Request $request)`** - Comment update
  - Updates `table_gua_mes_prod_orders.commento`

- **`stampa(Request $request)`** - PDF export
  - Same logic as `index()` but formatted for PDF generation
  - Includes file existence check for PDF generation

**Design Pattern**: View-based data retrieval, file system checks

---

## Event Listeners

### LogSuccessfulLogin (Spatie Activity Log)
- **Event**: `Illuminate\Auth\Events\Login`
- **Action**: Logs login activity with user, IP, and user agent
- **Usage**: Audit trail for security

### UpdateDictionaryOnLogin
- **Event**: `Illuminate\Auth\Events\Login`
- **Action**: Syncs dictionary (translations) from config when admin logs in
- **Logic**: Only runs for admin users; populates `Dictionary` table from `config/dizionario.php`

---

## Data Flow Patterns

### 1. **CRUD Pattern** (Standard Admin Operations)
```
List View (GET /)
    ↓
Table Grid (POST /json) [ag-Grid via AJAX]
    ↓
Form Modal (GET /form/{id?})
    ↓
Submit (POST /form)
    ↓
DB Update / Validation Response
```

### 2. **Hierarchical Query Pattern** (Shifts → Operators → Machines)
```
User Login
    ↓
Dashboard (today's shifts for user)
    ↓
Shift Detail (GET /APP2/form/{id})
    ↓
Assign Operators + Machines (POST /APP2/form)
    ↓
Store as JSON arrays in GestioneTurni
```

### 3. **Production Monitoring Pattern** (Multi-Source Enrichment)
```
Request /monitor_fp/data (with optional date range)
    ↓
Fetch ProductionFP records
    ↓
Batch-load Machine names/positions
    ↓
Batch-load Routing quantities
    ↓
Batch-load Operation status
    ↓
Filter by company
    ↓
Return enriched JSON
```

---

## Database Integration Points

### Eloquent Models Used
- `User` - Authenticated users
- `GestioneTurni` - Shift assignments
- `Macchine` - Production machines
- `ProductionFP` - Production records
- `Dictionary` - Translated field names
- `ActiveApp` - Available applications
- Others: `Aziende`, `Presse`, `Stamping`, `Turno`, `CodiciOggetto`, etc.

### Raw Query Tables (Direct DB Access)
- `users` - User lookup
- `turni` - Shift definitions
- `machine_center` - Machine catalog
- `table_guaprodrouting` - Production routing/quantities
- `bisio_progetti_stain` - SAP order status
- `gestione_turni_presses` - Press shift assignments
- `stampaggio_view` - Database view for production monitoring
- `table_gua_mes_prod_orders` - Production order comments
- `tabella_appoggio_macchine` - Machine support table (MES ID mapping)

### Cross-System Integration
- **Business Central / Piovan**: SAP order sync
- **MES System**: Production orders (`mesOrderNo`, `itemNo`)
- **LDAP/Active Directory**: User authentication via ldaprecord-laravel

---

## Key Architectural Observations

1. **No Separation of Concerns**: Business logic directly in controllers
2. **Database-Driven Views**: Heavy reliance on pre-built database views
3. **Batch Query Optimization**: Uses array mapping to avoid N+1 queries
4. **JSON as Storage**: Complex data (arrays) stored as JSON in columns
5. **Role-Based Access**: User `admin` flag and role-based view filtering
6. **Event System**: Limited use (only for login/audit)
7. **Flexible Input Handling**: Multiple input format normalization (toIdArray pattern)
8. **Direct SQL**: Mix of Eloquent ORM and raw query builder
9. **Activity Logging**: Spatie Activity Log integrated for audit trail
10. **No API Versioning**: Single API surface shared with views

---

## Summary

The application is a **manufacturing production management system** with:
- **Shift-based operations** for factory floor management
- **Multi-source data aggregation** for real-time production monitoring
- **Order-to-operator mapping** via shift assignments
- **ERP integration** (Piovan, Business Central, MES)
- **Role-based access control** for different operator types
- **Activity auditing** via Spatie Activity Log
- **LDAP authentication** for enterprise integration

The codebase favors **pragmatic, direct database access** over architectural patterns like repositories or services, making it suitable for rapid development but potentially challenging for large-scale refactoring.
