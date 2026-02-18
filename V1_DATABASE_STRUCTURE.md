# V1 Database Structure (Legacy)

This document outlines the database structure of the legacy `guala-app`.

## Core System Tables

### `active_apps`
Registry of available applications within the system.
- `id`: PK
- `name`: string
- `code`: string
- `created_at`, `updated_at`, `deleted_at`: timestamps

### `sites`
Physical sites/locations.
- `id`: PK
- `name`: string
- `created_at`, `updated_at`, `deleted_at`: timestamps

### `ext_infos`
Extended information/dummy table for testing or legacy data.
- `id`: PK
- `val1`, `val2`, `val3`: char
- `stampe`: char (index)
- `seq`: char (index)
- `code`: char (index)
- `n_order`: char
- `qta_ric`, `qta_prod`: integer
- `timestamps`

---

## User & Authentication

### `users`
Standard Laravel users table extended with custom fields.
- `id`: PK
- `name`: string
- `email`: string (unique)
- `password`: string
- `site_id`: FK to `sites`
- `lang`: string (2 chars)
- `user_id`: string (Legacy ID)
- `cognome`: string (Surname)
- `matricola`: string (Employee ID)
- `valido`: integer
- `is_ad_user`: integer (Active Directory flag)
- `superadmin`: boolean
- `timestamps`, `softDeletes`

### `active_app_user`
Pivot table linking users to active apps.
- `id`: PK
- `user_id`: FK to `users`
- `active_app_id`: FK to `active_apps`

---

## Production & MES (Legacy)

### `table_gua_mes_prod_orders`
Stores production orders imported from MES.
- `id`: PK
- `mesOrderNo`: string
- `mesStatus`: string
- `itemNo`: string
- `itemDescription`: text
- `machineSatmp`: string
- `machinePress`: string
- `machinePressDesc`: string
- `guaCustomerNO`: string
- `guaCustomName`: string
- `guaCustomerOrder`: string
- `quantity`: integer
- `relSequence`: integer
- `quantita_prodotta`: integer

### `table_gua_items_in_producion`
Components/BOM items related to production orders.
- `id`: PK
- `entryNo`: integer
- `componentNo`: string
- `parentitemNo`: string
- `compDescription`: text
- `levelCode`: integer
- `qty`: integer
- `unitOfMeasure`: string
- `prodorderno`: string
- `mesOrderNo`: string
- `commento`: text

### `bom_explosion`
Hierarchical Bill of Materials data.
- `id`: PK
- `xLevel`: integer
- `productionBOMNo`: string
- `No`, `ReplSystem`, `InvPostGr`: string
- `UoM`: string
- `QtyPer`, `PercScarti`: double
- `Company`: string

### `orderfrommes`
Simplified MES order tracking.
- `id`: PK
- `ordernane`: string
- `messtatus`: string

---

## Machine & Shift Management

### `machine_center`
Registry of machine centers.
- `id`: PK
- `name`: string
- `GUAPosition`: string
- `GUAMachineCenterType`: string
- `Company`: string
- `GUASchedule`: string

### `turni`
Shift definitions.
- `id`: PK
- `nome_turno`: string (Shift Name)
- `inizio`: integer (Start hour/value)
- `fine`: integer (End hour/value)

### `macchine`
Simple machine list (separate from machine_center in legacy).
- `id`: PK
- `nome`: string

### `gestione_turni`
Shift assignments header.
- `id`: PK
- `id_capoturno`: FK (Supervisor)
- `id_turno`: FK (Shift)
- `id_operatori`: FK (Operator group/ID?)
- `id_macchinari_associati`: FK
- `data_turno`: date

### `gestione_turni_presse`
Shift assignments specifically for presses.
- Same structure as `gestione_turni`.

### `note_macchine_operatori`
Daily notes entered by operators for machines.
- `id`: PK
- `id_macchina`: FK to `machine_center`
- `id_operatore`: FK to `users`
- `data`: date
- `nota`: text
- Unique Constraint: `['id_macchina', 'id_operatore', 'data']`

---

## Database Views

### `stampaggio_view`
Aggregates molding data (likely queries `table_gua_mes_prod_orders` joined with others).

### `assemblaggio_view`
Aggregates assembly data.
