# Localization & Naming Standardization Guide

This document outlines the steps to standardize the application's naming conventions from Italian/Legacy codes to **User-Friendly English**.

The goal is to make the database tables, code models, and URL routes intuitive for developers and users.

---

## 1. Database Standardization

We will rename tables and columns to clear, standard English terms.

### 1.1 Table Renaming Map

| Current Name (Italian) | New Name (Standard English) | Description |
| :--- | :--- | :--- |
| `turni` | **`shifts`** | Defines shift types (Morning, Night). |
| `gestione_turni` | **`shift_assignments`** | Daily assignments for the Assembly department. |
| `gestione_turni_presses` | **`press_shift_assignments`** | Daily assignments for the Press department. |
| `macchine` | **`machines`** | Physical machine definitions. |
| `presse` | **`presses`** | Press machine definitions. |
| `aziende` | **`companies`** | Company/Tenant definitions. |

### 1.2 Column Renaming Map (Key Tables)

**Table: `shifts` (formerly `turni`)**
| Old Column | New Column | Type |
| :--- | :--- | :--- |
| `nome_turno` | `name` | String |
| `inizio` | `start_time` | Time |
| `fine` | `end_time` | Time |
| `azienda` | `company_id` | Foreign Key |

**Table: `shift_assignments` (formerly `gestione_turni`)**
| Old Column | New Column | Type |
| :--- | :--- | :--- |
| `id_capoturno` | `supervisor_id` | Foreign Key (User) |
| `id_turno` | `shift_id` | Foreign Key (Shift) |
| `data_turno` | `date` | Date |
| `id_operatori` | `operator_ids` | JSON Array |
| `id_macchinari_associati` | `machine_ids` | JSON Array |

---

## 2. Route & URL Standardization (User Friendly)

We will replace obscure codes (like `APP2`) with descriptive URLs.

### 2.1 Main Application Routes

| Current URL | New User-Friendly URL | Purpose |
| :--- | :--- | :--- |
| `/APP2` | **`/shift-assignments`** | Manage daily shift rosters (Assembly). |
| `/APP3` | **`/press-schedule`** | Manage daily shift rosters (Presses). |
| `/APP1` | **`/production-monitor`** | View real-time production dashboards. |
| `/turni` | **`/shifts/definitions`** | Admin: Create/Edit shift types. |
| `/monitor_fp` | **`/dashboard/production`** | The main production control tower. |



---

## 3. Code Model Standardization

Create models.
| Model File |
| :--- | :--- |
| **`app/Models/Shift.php`** |
| **`app/Models/ShiftAssignment.php`** |

**Example Model Update (`Shift.php`):**
```php
class Shift extends Model
{
    protected $table = 'shifts'; // Matches new table name
    protected $fillable = ['name', 'start_time', 'end_time', 'company_id'];
}
```

---

## 4. Execution Plan

1.  **Database Migration**: Create a migration to create tables and columns.
2.  **Code Update**:Add Model files and update `protected $table` properties.
3.  **Controller Update**: Create Controller files and update methods.
4.  **Route Update**: Edit `routes/api.php` to use the new friendly URLs.

This plan ensures the system is self-documenting and easier for new developers to understand.
