# Machine Module Specification & Standardization
**Status**: Draft
**Version**: 1.0
**Date**: 2026-01-26

This document specifies the architecture for the **Machine Module**, dealing with the legacy `machine_center` table and its associated metadata.

---

## 1. System Context & Synchronization (CRITICAL)

The `machine_center` table is **NOT** a standard local table. It is a **Cached Mirror** of an external Data Warehouse (SQL Server).

*   **Source**: `shir.stg_p.MachineCenter` (External DB)
*   **Sync Mechanism**: `scripts/db_aligner.php`
*   **Behavior**: The sync script performs a full `DELETE FROM machine_center` followed by `INSERT`.
*   **Implication**: Any local changes made directly to columns in `machine_center` (like `name` or `GUAPosition`) **WILL BE LOST** the next time the sync runs.

**Architectural Decision**:
*   The `machines` table must be treated as **Read-Only** by the API.
*   Any persistent local data must be stored in `machine_metadata` (formerly `tabella_appoggio_macchine`), which is preserved during sync.

---

## 2. Database Schema (Standardized)

### 2.1 Table: `machines` (formerly `machine_center`)
Stores the raw machine list synced from the warehouse.

| Field Name | Type | Description | Legacy Mapping | Notes |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | Primary Key | `id` | Auto-increment, but unstable across syncs? |
| `code` | `VARCHAR` | Unique Machine Number | `no` | **Stable Key** for linking. |
| `name` | `VARCHAR` | Machine Name | `name` | Overwritten by sync. |
| `type` | `VARCHAR` | Machine Type | `GUAMachineCenterType` | Values: 'Machine' (Assembly), 'Pressing' (Press). |
| `company` | `VARCHAR` | Owner Company | `Company` | e.g., 'Guala Dispensing FP'. |
| `position` | `VARCHAR` | Physical Location/Dept | `GUAPosition` | |
| `schedule_code`| `VARCHAR` | Scheduling Code | `GUA_schedule` | |

### 2.2 Table: `machine_metadata` (formerly `tabella_appoggio_macchine`)
Stores local, persistent configuration for machines, linked by the stable `code`.

| Field Name | Type | Description | Legacy Mapping |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | Primary Key | `id` |
| `machine_code` | `VARCHAR` | Foreign Key to `machines.code` | `no` |
| `mes_id` | `VARCHAR` | ID in the MES system | `id_mes` |
| `piovan_id` | `VARCHAR` | ID in the Piovan system | `id_piovan` |
| `custom_company`| `VARCHAR` | Local company override | `azienda` |
| `created_at` | `TIMESTAMP`| | |
| `updated_at` | `TIMESTAMP`| | |

---

## 3. Models & Logic

### 3.1 Base Model: `Machine`
Maps to `machines` table. Should be marked as immutable for create/update/delete.

```php
class Machine extends Model {
    protected $table = 'machines'; // formerly machine_center
    
    // Prevent local timestamps as table is synced
    public $timestamps = false;

    // Relationship to Metadata
    public function metadata() {
        return $this->hasOne(MachineMetadata::class, 'machine_code', 'code');
    }

    // Scopes for Types
    public function scopeAssembly($query) {
        return $query->where('type', 'Machine');
    }

    public function scopePresses($query) {
        return $query->where('type', 'Pressing');
    }
}
```

### 3.2 Metadata Model: `MachineMetadata`
Maps to `machine_metadata` table. Fully writable.

```php
class MachineMetadata extends Model {
    protected $table = 'machine_metadata'; // formerly tabella_appoggio_macchine
    protected $fillable = ['machine_code', 'mes_id', 'piovan_id', 'custom_company'];
}
```

---

## 4. API Endpoints (`/api/v1/machines`)

### 4.1 List Machines (`GET /api/v1/machines`)
Returns the list of machines, enriched with metadata.

*   **Query Params**:
    *   `type`: `assembly` | `press` (Maps to internal `Machine` / `Pressing`)
    *   `company`: Filter by company name.
*   **Logic**:
    *   Join `machines` with `machine_metadata`.
    *   Select `machines.*`, `metadata.mes_id`, `metadata.piovan_id`.

### 4.2 Update Machine Metadata (`PATCH /api/v1/machines/{code}/metadata`)
Allows updating the local IDs (MES, Piovan) for a machine.

*   **URL Param**: `code` (The stable `no` column, NOT the `id`).
*   **Body**:
    ```json
    {
        "mes_id": "M123",
        "piovan_id": "P456"
    }
    ```
*   **Logic**:
    ```php
    MachineMetadata::updateOrCreate(
        ['machine_code' => $code],
        $request->only(['mes_id', 'piovan_id'])
    );
    ```

### 4.3 Sync Status (`GET /api/v1/machines/sync-status`)
(Optional) Returns the last time the `db_aligner.php` script ran (if logged) or simple count stats.

---

## 5. Migration Strategy from Legacy

1.  **Rename Tables**:
    ```sql
    RENAME TABLE machine_center TO machines;
    RENAME TABLE tabella_appoggio_macchine TO machine_metadata;
    ```

2.  **Update `db_aligner.php`**:
    *   The sync script **MUST** be updated to target the new table name `machines`.
    *   It must map the source columns to the new English column names (e.g., `GUAMachineCenterType` -> `type`).

3.  **Codebase Refactor**:
    *   Update `MacchineController` to strictly separate "Read Machine" from "Write Metadata".
    *   Remove any logic that attempts to update `machine_center` columns directly, as they are volatile.

This specification ensures that the application respects the external "Source of Truth" while allowing necessary local configurations.
