# Database V2 Migration Guide

**Status**: Proposal / Draft
**Date**: 2026-01-26
**Objective**: Standardization, Normalization, and English Translation of the Database Schema.

This document outlines the changes from the legacy `guala_db_schema.sql` (v1) to the new `guala_db_v2.sql` (v2).

---

## 1. Migration Strategy
The goal is to move from Italian, non-standard naming conventions to English, standard Laravel conventions (snake_case, plural tables, singular models).

### Key Principles:
1.  **Renaming**: All tables and columns translated to English.
2.  **Normalization**: Foreign keys strictly defined.
3.  **JSON Usage**: Where appropriate (e.g., operator lists), explicit JSON columns are used.
4.  **Legacy Filtering**: Temporary (`_tmp`) and backup tables (`_t2`) are excluded.
5.  **External Sync Isolation**: Tables that are synced from external sources (MES, ERP) are clearly marked or kept separate to avoid data loss.

---

## 2. Table Mappings (Old -> New)

| Old Table Name | New Table Name | Status / Notes |
| :--- | :--- | :--- |
| `active_app_user` | `active_app_user` | Kept as pivot. Standardized. |
| `active_apps` | `active_apps` | Updated. `azienda` -> `company_id`. |
| `aziende` | `companies` | Renamed. `nome` -> `name`. |
| `gestione_turni` | `shift_assignments` | Renamed. `id_capoturno` -> `shift_leader_id`, `id_turno` -> `shift_id`. |
| `gestione_turni_presse`| `press_shift_assignments`| Renamed. Same structure as above. |
| `macchine` | `machines` | Renamed. Simple local list. |
| `machine_center` | `external_machine_source`| **SYNC TABLE**. Renamed to indicate external source nature. |
| `tabella_appoggio_macchine`| `machine_metadata` | Renamed. Stores persistent local data for machines. |
| `turni` | `shifts` | Renamed. `nome_turno` -> `name`. |
| `users` | `users` | Standardized. |
| `note_macchine_operatori`| `machine_operator_notes` | Renamed. |
| `presse` | `presses` | Renamed. |
| `sites` | `sites` | Kept. |
| `activity_log` | `activity_log` | Kept (Spatie package). |
| `failed_jobs` | `failed_jobs` | Kept (Laravel). |
| `migrations` | `migrations` | Kept (Laravel). |
| `password_reset_tokens`| `password_reset_tokens` | Kept (Laravel). |
| `sessions` | `sessions` | Kept (Laravel). |

### Excluded / Deprecated Tables
The following tables are **NOT** included in the V2 schema (Legacy/Temp/Garbage):
*   `machine_center_tmp`
*   `commento_lavori_guala_fp`
*   `commento_lavori_guala_fp_t2`
*   `table_commenti_guala_fp`
*   `assemblaggio_view` (Views should be recreated via migration, not raw SQL dump)
*   `stampaggio_fp` (View)
*   `stampaggio_view` (View)
*   `bisio_progetti_stain` (Specific integration, likely needs refactoring or dedicated migration)

---

## 3. Detailed Column Changes

### `active_apps`
*   `azienda` (int) -> `company_id` (bigint, FK -> companies.id)
*   `name_it`/`name_en` -> `name` (varchar) - English by default.

### `shift_assignments` (was `gestione_turni`)
*   `id_capoturno` -> `shift_leader_id`
*   `id_turno` -> `shift_id`
*   `id_operatori` -> `operator_ids` (JSON)
*   `id_macchinari_associati` -> `machine_ids` (JSON)
*   `data_turno` -> `shift_date`
*   `nota` -> `note`

### `shifts` (was `turni`)
*   `nome_turno` -> `name`
*   `inizio` -> `start_time` (int) - Kept as int if minutes from midnight, or changed to TIME type. *Decision: Kept as INT for compatibility, rename to `start_minutes`? No, `start_time` is clearer.*
*   `fine` -> `end_time` (int)
*   `azienda` -> `company_id`

### `machine_metadata` (was `tabella_appoggio_macchine`)
*   `no` -> `machine_code` (The stable link to external source)
*   `id_piovan` -> `piovan_id`
*   `id_mes` -> `mes_id`
*   `azienda` -> `company_id`

---

## 4. Next Steps
1.  **Run Migrations**: Create Laravel migrations based on `guala_db_v2.sql` to apply these changes incrementally if possible, or use the SQL dump for a fresh start.
2.  **Update Models**: All Eloquent models must be updated to map to the new table names (using `protected $table = 'new_name'`).
3.  **Update Sync Scripts**: The `db_aligner.php` must be updated to write to `external_machine_source` (or `machines` if we decide to use that as the sync target).
