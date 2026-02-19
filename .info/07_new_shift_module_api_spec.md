# New Shift Module API Specification
**Status**: Draft
**Version**: 2.1 (Added Press & Machine Metadata)
**Date**: 2026-01-26

This document specifies the architecture, database schema, and API endpoints for the new "Shifts" module. This module replaces the legacy `turni`, `gestione_turni`, and `gestione_turni_presse` system with a clean, English-based, RESTful implementation.

---

## 1. Database Schema (Standardized)

We will use four primary tables. The fields are renamed to standard English.

### 1.1 Table: `shifts` (formerly `turni`)
Defines the standard shift types (e.g., Morning, Afternoon, Night).

| Field Name | Type | Description | Legacy Mapping |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | Primary Key | `id` |
| `name` | `VARCHAR` | Shift Name (e.g., "Morning") | `nome_turno` |
| `start_time` | `TIME` | Shift Start Time | `inizio` |
| `end_time` | `TIME` | Shift End Time | `fine` |
| `company_id` | `INT` | Tenant/Company ID | `azienda` |
| `created_at` | `TIMESTAMP`| | |
| `updated_at` | `TIMESTAMP`| | |

### 1.2 Table: `shift_assignments` (formerly `gestione_turni`)
Stores the daily roster for **Assembly** operations.

| Field Name | Type | Description | Legacy Mapping |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | Primary Key | `id` |
| `supervisor_id` | `BIGINT` | User ID of the Shift Leader | `id_capoturno` |
| `shift_id` | `BIGINT` | ID of the Shift Type | `id_turno` |
| `date` | `DATE` | The specific date of the assignment | `data_turno` |
| `operator_ids` | `JSON` | Array of User IDs (Operators) | `id_operatori` |
| `machine_ids` | `JSON` | Array of Machine IDs | `id_macchinari_associati` |
| `created_at` | `TIMESTAMP`| | |
| `updated_at` | `TIMESTAMP`| | |

### 1.3 Table: `press_shift_assignments` (formerly `gestione_turni_presse`)
Stores the daily roster for **Press/Molding** operations. This table mirrors `shift_assignments` but links to Press machines.

| Field Name | Type | Description | Legacy Mapping |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | Primary Key | `id` |
| `supervisor_id` | `BIGINT` | User ID of the Shift Leader | `id_capoturno` |
| `shift_id` | `BIGINT` | ID of the Shift Type | `id_turno` |
| `date` | `DATE` | The specific date of the assignment | `data_turno` |
| `operator_ids` | `JSON` | Array of User IDs (Operators) | `id_operatori` |
| `machine_ids` | `JSON` | Array of Machine IDs (Presses) | `id_macchinari_associati` |
| `created_at` | `TIMESTAMP`| | |
| `updated_at` | `TIMESTAMP`| | |

### 1.4 Table: `machine_metadata` (formerly `tabella_appoggio_macchine`)
Provides external system IDs (MES, Piovan) for machines, linked by the machine number (`no`).

| Field Name | Type | Description | Legacy Mapping |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | Primary Key | `id` |
| `machine_no` | `VARCHAR` | Machine Number (Foreign Key to `machines.no`) | `no` |
| `mes_id` | `VARCHAR` | ID in the MES system | `id_mes` |
| `piovan_id` | `VARCHAR` | ID in the Piovan system | `id_piovan` |
| `created_at` | `TIMESTAMP`| | |
| `updated_at` | `TIMESTAMP`| | |

---

## 2. API Endpoints

All new APIs should be prefixed with `/api/v1`.

### 2.1 Shift Definitions (`/api/v1/shifts`)
*   `GET /api/v1/shifts` - Returns all available shift types.

### 2.2 Shift Assignments (`/api/v1/shift-assignments`)
Used for **Assembly** rosters.
*   `GET /api/v1/shift-assignments` - Get rosters (filter by `date`, `supervisor_id`).
*   `POST /api/v1/shift-assignments` - Create/Update roster.
*   `DELETE /api/v1/shift-assignments/{id}` - Delete roster.

### 2.3 Press Shift Assignments (`/api/v1/press-shift-assignments`)
Used for **Press** rosters.
*   `GET /api/v1/press-shift-assignments` - Get press rosters.
    *   **Logic**: Fetches machine labels by joining `machines` with `machine_metadata` to show "MES_ID - Piovan_ID (Name)".
*   `POST /api/v1/press-shift-assignments` - Create/Update press roster.
*   `DELETE /api/v1/press-shift-assignments/{id}` - Delete press roster.

---

## 3. Implementation Instructions

### Step 1: Create the Models

**`app/Models/PressShiftAssignment.php`**
```php
class PressShiftAssignment extends Model {
    protected $fillable = ['supervisor_id', 'shift_id', 'date', 'operator_ids', 'machine_ids'];
    
    protected $casts = [
        'date' => 'date',
        'operator_ids' => 'array',
        'machine_ids' => 'array',
    ];

    public function shift() {
        return $this->belongsTo(Shift::class);
    }
}
```

**`app/Models/MachineMetadata.php`**
```php
class MachineMetadata extends Model {
    protected $table = 'machine_metadata';
    // Links to Machine via 'no' column
}
```

### Step 2: Create the Controller (`PressShiftAssignmentController`)

**Key Difference from Assembly**:
When fetching the list of machines for the frontend dropdown, you must join with metadata to create the label:

```php
$machines = DB::table('machines as m')
    ->leftJoin('machine_metadata as mm', 'mm.machine_no', '=', 'm.no')
    ->where('m.type', 'Pressing') // 'GUAMachineCenterType'
    ->select(
        'm.id',
        DB::raw("CONCAT(COALESCE(mm.mes_id, ''), ' - ', COALESCE(mm.piovan_id, ''), ' ( ', m.name, ' )') as label")
    )
    ->get();
```

### Step 3: Frontend Integration
*   **Assembly Grid**: Displays simple Machine Names.
*   **Press Grid**: Displays "MES - Piovan (Name)" format using the enriched data from the API.

---

## 4. Migration Guide

1.  **Rename Tables**:
    ```sql
    RENAME TABLE turni TO shifts;
    RENAME TABLE gestione_turni TO shift_assignments;
    RENAME TABLE gestione_turni_presse TO press_shift_assignments;
    RENAME TABLE tabella_appoggio_macchine TO machine_metadata;
    ```
2.  **Rename Columns** (See tables above).
