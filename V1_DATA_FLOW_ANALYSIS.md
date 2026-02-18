# V1 Data Flow Analysis (Legacy)

This document explains how data is populated in the `guala-app` (V1), focusing on the "Aligner" scripts (`scripts/db_aligner.php` and `scripts/assemblaggio_aligner.php`).

## Overview

The application relies on external scripts to synchronize data from multiple sources (Business Central, SQL Server, Piovan Machines) into the local MySQL database. These scripts act as an ETL (Extract, Transform, Load) layer.

---

## 1. DB Aligner (`scripts/db_aligner.php`)

This is the main synchronization script. It connects to multiple external databases and APIs to populate the core tables.

### Data Sources
*   **Business Central (API)**: Source for Production Orders and Items.
*   **SQL Server (MDW, DataWarehouse, WMS, Stein, Incas)**: Source for Machine Centers, BOMs, Warehouse Stock, and legacy tracking.
*   **Piovan Machines (SOAP API)**: Source for raw material and lot information directly from machines.

### Key Tables & Flows

| Italian Table Name (Actual) | English Translation / Purpose | Source | Description |
| :--- | :--- | :--- | :--- |
| `table_gua_mes_prod_orders` | **Production Orders** | Business Central (API) | Stores all production orders. Key fields: `mesOrderNo`, `itemNo`, `quantity`. |
| `table_gua_items_in_producion` | **Production Items (BOM)** | Business Central (API) | Details the components required for each production order (Bill of Materials). |
| `machine_center` | **Machine Registry** | SQL Server (DWH) | List of all machines, their companies, and types using `GUAPosition` as a key identifier. |
| `bom_explosion` | **BOM Explosion** | SQL Server (DWH) | Hierarchical breakdown of Bill of Materials. |
| `orderfrommes` | **MES Orders (Romania)** | SQL Server (50.65) | Orders specifically tracking status from the Romania MES system. |
| `bisio_progetti_stain` | **Stain Machine Status** | SQL Server (Stain) | Live status of machines (Running, Stopped, etc.) from the Bisio/Stain system. |
| `ordini_lavoro_lotti` | **Work Order Lots** | SQL Server (Incas) | Links production orders to specific material lots. |
| `qta_guala_pro_rom` | **Warehouse Stock** | SQL Server (WMS) | Current stock quantities in the Warehouse Management System. |
| `table_piovan_import` | **Piovan Material Data** | Piovan Machines (SOAP) | Real-time material and lot info read directly from Piovan drying hoppers. |
| `table_guaprodrouting` | **Production Routing** | Business Central (API) | Steps/routing for production. Status is updated from STAIN. |

### Process Summary
1.  **Extract**: Connects to all sources.
2.  **Transform**:
    *   Calculates produced quantities by querying STAIN (`bm20` database).
    *   Maps families based on Item Numbers.
    *   Aggregates comments and statuses.
3.  **Load**: Inserts data into `_tmp` tables first, then swaps them (`RENAME TABLE`) to ensure zero downtime during updates.

---

## 2. Assemblo Aligner (`scripts/assemblaggio_aligner.php`)

This script is a specialized, high-frequency updater for **Assembly** operations.

### Flow
1.  **Target**: Selects all rows from `table_gua_mes_prod_orders` where `mesOrderNo` contains "AS" (Assembly).
2.  **Source**: Queries the **STAIN** database (`bm20.IndicatorValueEmulation`).
3.  **Action**: Updates the `quantita_prodotta` (Produced Quantity) field in the local `table_gua_mes_prod_orders` table.

---

## 3. Data Presentation (Views)

The application uses SQL Views to join these tables for the frontend.

| View Name | Logic | Usage |
| :--- | :--- | :--- |
| `stampaggio_view` | Joins `table_gua_mes_prod_orders` + `machine_center`. Filters for **Molding** (Stampaggio) orders (`%ST%`). | Used in `stampaggiotableViewController` to display the Molding Monitor. |
| `assemblaggio_view` | Joins `table_gua_mes_prod_orders` + `machine_center`. Filters for **Assembly** orders (`%AS%`). | Used in `assemblaggiotableViewController` to display the Assembly Monitor. |

---

## Glossary (Italian to English)

*   **Stampaggio** = Molding
*   **Assemblaggio** = Assembly
*   **Quantita** = Quantity
*   **Prodotta** = Produced
*   **Macchina** = Machine
*   **Turni** = Shifts
*   **Lotto** = Lot / Batch
*   **Codice** = Code
*   **Descrizione** = Description
