# Database Migration Report
**Date:** 2026-01-27
**Status:** Completed
**Source:** `guala_db_schema.sql` (Old Italian Schema)
**Destination:** `guala_structure_v2.sql` (New English Schema)

## Overview
This report documents the migration and standardization of the database schema. The goal was to modernize the database structure by:
1.  Preserving all recent user-defined changes in `guala_structure_v2.sql`.
2.  Integrating missing tables from the old `guala_db_schema.sql`.
3.  Translating all Italian table and column names to English.
4.  Standardizing naming conventions (snake_case, plural table names).

## Table Migration & Translation Map

The following table maps the original Italian tables to their new English counterparts.

| Original Table Name (Italian) | New Table Name (English) | Status | Notes |
| :--- | :--- | :--- | :--- |
| `active_app_user` | `active_app_user` | Preserved | Existing V2 table preserved. |
| `active_apps` | `active_apps` | Preserved | Existing V2 table preserved. |
| `activity_log` | `activity_log` | Preserved | Existing V2 table preserved. |
| `aziende` | `companies` | Translated | Renamed for clarity. |
| `bisio_progetti_stain` | `bisio_stain_projects` | Translated | |
| `bom_explosion` | `bom_explosion` | Updated | Field names translated (e.g., `PercScarti` -> `scrap_percentage`). |
| `cache` | `cache` | Preserved | Laravel default. |
| `cache_locks` | `cache_locks` | Preserved | Laravel default. |
| `codici_oggetti` | `object_codes` | Translated | |
| `commento_lavori_guala_fp` | `guala_fp_work_comments` | Translated | |
| `commento_lavori_guala_fp_t2` | `guala_fp_work_comments_t2` | Translated | |
| `dictionary_table` | `dictionary` | Renamed | Removed `_table` suffix. |
| `enpoint_piovan` | `piovan_endpoints` | Translated | Fixed spelling (`enpoint` -> `endpoint`). |
| `ext_infos` | `ext_infos` | Preserved | |
| `failed_jobs` | `failed_jobs` | Preserved | Laravel default. |
| `gestione_turni` | `shift_assignments` | Translated | |
| `gestione_turni_presse` | `press_shift_assignments` | Translated | |
| `job_batches` | `job_batches` | Preserved | Laravel default. |
| `jobs` | `jobs` | Preserved | Laravel default. |
| `macchine` | `machines` | Translated | |
| `machine_center` | `machine_centers` | Renamed | Pluralized. |
| `machine_center_tmp` | - | Dropped | Temporary table removed. |
| `migrations` | `migrations` | Preserved | Laravel default. |
| `note_macchine_operatori` | `machine_operator_notes` | Translated | |
| `orderfrommes` | `order_from_mes` | Normalized | Added underscores. |
| `ordine_note` | `order_notes` | Translated | |
| `ordini_lavoro_lotti` | `work_order_batches` | Translated | |
| `password_reset_tokens` | `password_reset_tokens` | Preserved | Laravel default. |
| `presse` | `presses` | Translated | |
| `qta_guala_pro_rom` | `guala_romania_production_quantities` | Translated | |
| `sessions` | `sessions` | Preserved | Laravel default. |
| `sites` | `sites` | Preserved | Existing V2 table preserved. |
| `tabella_appoggio_macchine` | `machine_support_table` | Translated | |
| `table_commenti_guala_fp` | `guala_fp_comments` | Translated | |
| `table_gestione_ad` | `active_directory_configs` | Translated | |
| `table_gua_items_in_producion` | `guala_items_in_production` | Translated | Fixed typo (`producion` -> `production`). |
| `table_gua_mes_prod_orders` | `guala_mes_production_orders` | Translated | |
| `table_guaprodrouting` | `guala_production_routing` | Translated | |
| `table_piovan_import` | `piovan_imports` | Translated | |
| `turni` | `shifts` | Translated | |
| `users` | `users` | Preserved | Existing V2 table preserved. |

## New Tables (V2 Exclusive)
These tables exist only in the new schema (added by user/Laravel packages):
- `languages`
- `model_has_permissions` (Spatie Permission)
- `model_has_roles` (Spatie Permission)
- `permissions` (Spatie Permission)
- `personal_access_tokens` (Laravel Sanctum)
- `role_has_permissions` (Spatie Permission)
- `roles` (Spatie Permission)
- `settings`
- `translations`

## View Updates
The following database views were updated to reference the new English table and column names:
- `assembly_view` (formerly `assemblaggio_view`)
- `molding_fp_view` (formerly `stampaggio_fp_view` - *Note: Assumed translation based on pattern*)
- `molding_view` (formerly `stampaggio_view` - *Note: Assumed translation based on pattern*)

## Column Translations Examples
Key column translations performed across tables:
- `nome` -> `name`
- `descrizione` -> `description`
- `quantita_prodotta` -> `produced_quantity`
- `PercScarti` -> `scrap_percentage`
- `commento` -> `comment`
- `stato` -> `status`

## Conclusion
The `guala_structure_v2.sql` file now contains a fully localized, consistent, and modernized database schema that supports all legacy features while integrating the new architecture components.
