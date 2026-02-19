# APP1 Backend Implementation Guide

This guide provides step-by-step instructions to recreate the APP1 monitoring system flow in a new system with your own tables.

---

## Backend Architecture Overview

The APP1 system uses a **3-layer architecture**:

1. **Database Views** - SQL views that join and filter base tables
2. **Controllers** - Laravel controllers that query views and process data
3. **API Endpoints** - Routes that expose data as JSON to the frontend

---

## Step 1: Create Base Tables

### Required Tables

#### 1. Production Orders Table
```sql
CREATE TABLE production_orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    mes_order_no VARCHAR(50) NOT NULL,
    item_no VARCHAR(50),
    item_description VARCHAR(255),
    machine_stamp VARCHAR(50),
    machine_press VARCHAR(50),
    machine_press_desc VARCHAR(255),
    rel_sequence INT,
    quantity DECIMAL(10,2),
    quantita_prodotta DECIMAL(10,2) DEFAULT 0,
    mes_status VARCHAR(50),
    commento TEXT,
    family VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add indexes for performance
CREATE INDEX idx_mes_order_no ON production_orders(mes_order_no);
CREATE INDEX idx_item_no ON production_orders(item_no);
CREATE INDEX idx_machine_press ON production_orders(machine_press);
CREATE INDEX idx_machine_stamp ON production_orders(machine_stamp);
```

#### 2. Machine Center Table
```sql
CREATE TABLE machine_center (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    no VARCHAR(50) UNIQUE NOT NULL,
    gua_position VARCHAR(50),
    name VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add indexes
CREATE INDEX idx_gua_position ON machine_center(gua_position);
```

---

## Step 2: Create Database Views

### Stampaggio (Stamping) View

**Purpose:** Display stamping production orders with machine information

```sql
CREATE VIEW stampaggio_view AS 
SELECT 
    mpo.id,
    mpo.mes_order_no,
    mpo.item_no,
    mpo.item_description,
    mpo.machine_stamp,
    mpo.machine_press,
    mpo.machine_press_desc,
    mpo.rel_sequence,
    mpo.quantity,
    mpo.quantita_prodotta,
    mpo.mes_status,
    mpo.commento,
    mc.gua_position,
    CONCAT(mpo.machine_press, ' ', mpo.machine_press_desc) AS machine_press_full
FROM production_orders AS mpo
LEFT JOIN machine_center AS mc 
    ON mpo.machine_press = mc.no
WHERE mpo.mes_order_no LIKE '%ST%';
```

**Laravel Migration:**
```php
// database/migrations/YYYY_MM_DD_create_stampaggio_view.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS stampaggio_view");
        
        DB::statement("
            CREATE VIEW stampaggio_view AS 
            SELECT 
                mpo.id,
                mpo.mes_order_no,
                mpo.item_no,
                mpo.item_description,
                mpo.machine_stamp,
                mpo.machine_press,
                mpo.machine_press_desc,
                mpo.rel_sequence,
                mpo.quantity,
                mpo.quantita_prodotta,
                mpo.mes_status,
                mpo.commento,
                mc.gua_position,
                CONCAT(mpo.machine_press, ' ', mpo.machine_press_desc) AS machine_press_full
            FROM production_orders AS mpo
            LEFT JOIN machine_center AS mc 
                ON mpo.machine_press = mc.no
            WHERE mpo.mes_order_no LIKE '%ST%'
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS stampaggio_view");
    }
};
```

### Assemblaggio (Assembly) View

**Purpose:** Display assembly production orders with full machine names

```sql
CREATE VIEW assemblaggio_view AS
SELECT 
    o.id,
    o.mes_order_no,
    o.item_no,
    o.item_description,
    o.machine_stamp,
    o.rel_sequence,
    o.quantity,
    o.quantita_prodotta,
    o.mes_status,
    o.commento,
    o.family,
    CONCAT(o.machine_stamp, ' - ', m.name) AS nome_completo_macchina
FROM production_orders o
JOIN machine_center m 
    ON o.machine_stamp = m.gua_position
WHERE o.mes_order_no LIKE '%AS%';
```

**Laravel Migration:**
```php
// database/migrations/YYYY_MM_DD_create_assemblaggio_view.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS assemblaggio_view");
        
        DB::statement("
            CREATE VIEW assemblaggio_view AS
            SELECT 
                o.id,
                o.mes_order_no,
                o.item_no,
                o.item_description,
                o.machine_stamp,
                o.rel_sequence,
                o.quantity,
                o.quantita_prodotta,
                o.mes_status,
                o.commento,
                o.family,
                CONCAT(o.machine_stamp, ' - ', m.name) AS nome_completo_macchina
            FROM production_orders o
            JOIN machine_center m 
                ON o.machine_stamp = m.gua_position
            WHERE o.mes_order_no LIKE '%AS%'
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS assemblaggio_view");
    }
};
```

---

## Step 3: Create Controllers

### Stampaggio Controller

**File:** `app/Http/Controllers/StampaggioViewController.php`

```php
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StampaggioViewController extends Controller
{
    /**
     * Get stampaggio data for the grid
     */
    public function index()
    {
        // Query the stampaggio view
        $rows = DB::table('stampaggio_view')
            ->whereNotNull('mes_order_no')
            ->where('mes_order_no', '!=', '')
            ->whereNotNull('item_no')
            ->where('item_no', '!=', '')
            ->orderBy('machine_press_full')
            ->orderBy('gua_position')
            ->orderBy('rel_sequence')
            ->get();

        // Process each row
        $grouped = [];
        $rows = $rows->sortBy('gua_position');
        
        foreach ($rows as $row) {
            // Check if PDF exists
            $pdfPath = public_path("bolle_lavorazione_pdf/{$row->mes_order_no}.pdf");
            $row->pdf_exists = file_exists($pdfPath);
            
            // Format machine label
            $row->machine_press_full = "Pr " . $row->gua_position . " - " . $row->machine_press_full;
            
            // Group by machine
            $grouped[$row->machine_press_full][] = $row;
        }

        // Flatten the grouped data
        $result = [];
        foreach ($grouped as $pressFull => $items) {
            foreach ($items as $item) {
                $item->is_group = false;
                // Calculate remaining quantity
                $item->quantita_rimanente = $item->quantity - $item->quantita_prodotta;
                $result[] = $item;
            }
        }

        return response()->json(array_values($result));
    }

    /**
     * Update comment for an order
     */
    public function updateCommento(Request $request)
    {
        DB::table('production_orders')
            ->where('id', $request->input('id'))
            ->update(['commento' => $request->input('commento')]);

        return response()->json(['success' => true]);
    }
}
```

### Assemblaggio Controller

**File:** `app/Http/Controllers/AssemblaggioViewController.php`

```php
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssemblaggioViewController extends Controller
{
    /**
     * Get assemblaggio data for the grid
     */
    public function index()
    {
        // Query the assemblaggio view
        $rows = DB::table('assemblaggio_view')
            ->whereNotNull('mes_order_no')
            ->where('mes_order_no', '!=', '')
            ->whereNotNull('item_no')
            ->where('item_no', '!=', '')
            ->orderBy('family')
            ->orderBy('nome_completo_macchina')
            ->orderBy('rel_sequence')
            ->get();

        // Group by family and machine
        $grouped = [];
        foreach ($rows as $row) {
            // Check if PDF exists
            $pdfPath = public_path("bolle_lavorazione_pdf/{$row->mes_order_no}.pdf");
            $row->pdf_exists = file_exists($pdfPath);
            
            $grouped[$row->family][$row->nome_completo_macchina][] = $row;
        }

        // Build hierarchical structure
        $result = [];
        foreach ($grouped as $familyName => $machines) {
            // Add family header
            $result[] = [
                'is_group' => true,
                'group_type' => 'family',
                'value' => $familyName
            ];

            foreach ($machines as $machineFullName => $items) {
                // Skip specific machines if needed
                if ($items[0]->nome_completo_macchina === 'MPACK - PACKAGING') {
                    continue;
                }

                // Add machine header
                $result[] = [
                    'is_group' => true,
                    'group_type' => 'machine',
                    'family' => $familyName,
                    'value' => $items[0]->nome_completo_macchina,
                    'groupLabel' => $items[0]->nome_completo_macchina
                ];

                // Add order items
                foreach ($items as $item) {
                    $item->is_group = false;
                    // Calculate remaining quantity
                    $item->quantita_rimanente = $item->quantity - $item->quantita_prodotta;
                    $result[] = $item;
                }
            }
        }

        return response()->json(array_values($result));
    }

    /**
     * Update comment for an order
     */
    public function updateCommento(Request $request)
    {
        DB::table('production_orders')
            ->where('id', $request->input('id'))
            ->update(['commento' => $request->input('commento')]);

        return response()->json(['success' => true]);
    }
}
```

---

## Step 4: Define Routes

**File:** `routes/web.php`

```php
use App\Http\Controllers\StampaggioViewController;
use App\Http\Controllers\AssemblaggioViewController;

Route::middleware(['auth'])->group(function () {
    
    // Main APP1 page route
    Route::get('/APP1', function () {
        return view('app.APP1.index');
    })->name('app.app1');
    
    // API endpoints for data
    Route::get('/tableview', [StampaggioViewController::class, 'index'])
        ->name('stampaggio.data');
    
    Route::get('/tableviewAssemblaggio', [AssemblaggioViewController::class, 'index'])
        ->name('assemblaggio.data');
    
    // Comment update endpoints
    Route::post('/save-comment', [StampaggioViewController::class, 'updateCommento'])
        ->name('stampaggio.save_comment');
    
    Route::post('/save-comment-assemblaggio', [AssemblaggioViewController::class, 'updateCommento'])
        ->name('assemblaggio.save_comment');
});
```

---

## Step 5: Query Logic Breakdown

### Stampaggio Query Flow

```
1. Query stampaggio_view
   ↓
2. Filter: mes_order_no NOT NULL and not empty
   ↓
3. Filter: item_no NOT NULL and not empty
   ↓
4. Order by: machine_press_full, gua_position, rel_sequence
   ↓
5. For each row:
   - Check if PDF exists
   - Format machine label: "Pr {position} - {machine_name}"
   - Calculate remaining quantity
   ↓
6. Group by machine_press_full
   ↓
7. Return JSON array
```

### Assemblaggio Query Flow

```
1. Query assemblaggio_view
   ↓
2. Filter: mes_order_no NOT NULL and not empty
   ↓
3. Filter: item_no NOT NULL and not empty
   ↓
4. Order by: family, nome_completo_macchina, rel_sequence
   ↓
5. For each row:
   - Check if PDF exists
   - Calculate remaining quantity
   ↓
6. Group by family → machine
   ↓
7. Build hierarchical structure:
   - Family header
   - Machine header
   - Order items
   ↓
8. Return JSON array
```

---

## Step 6: Data Processing Logic

### Common Processing

Both controllers perform these operations:

1. **PDF Existence Check**
   ```php
   $pdfPath = public_path("bolle_lavorazione_pdf/{$row->mes_order_no}.pdf");
   $row->pdf_exists = file_exists($pdfPath);
   ```

2. **Remaining Quantity Calculation**
   ```php
   $item->quantita_rimanente = $item->quantity - $item->quantita_prodotta;
   ```

3. **Group Markers**
   ```php
   $item->is_group = false; // For data rows
   // or
   ['is_group' => true, 'group_type' => 'family'] // For headers
   ```

### Stampaggio-Specific Processing

- **Machine Label Formatting:**
  ```php
  $row->machine_press_full = "Pr " . $row->gua_position . " - " . $row->machine_press_full;
  ```

- **Single-Level Grouping:** By `machine_press_full`

### Assemblaggio-Specific Processing

- **Two-Level Grouping:** By `family` then `nome_completo_macchina`

- **Filtering:** Excludes `'MPACK - PACKAGING'` machines

---

## Step 7: API Response Format

### Stampaggio Response Structure

```json
[
  {
    "id": 1,
    "mes_order_no": "ST202519003590",
    "item_no": "12345",
    "item_description": "Product Name",
    "machine_stamp": "M01",
    "machine_press": "P01",
    "machine_press_desc": "Press 1",
    "machine_press_full": "Pr 1 - P01 Press 1",
    "rel_sequence": 1,
    "quantity": 1000,
    "quantita_prodotta": 500,
    "quantita_rimanente": 500,
    "mes_status": "Active",
    "commento": "Some comment",
    "gua_position": "1",
    "pdf_exists": true,
    "is_group": false
  }
]
```

### Assemblaggio Response Structure

```json
[
  {
    "is_group": true,
    "group_type": "family",
    "value": "Family A"
  },
  {
    "is_group": true,
    "group_type": "machine",
    "family": "Family A",
    "value": "M01 - Machine 1",
    "groupLabel": "M01 - Machine 1"
  },
  {
    "id": 1,
    "mes_order_no": "AS202519003591",
    "item_no": "67890",
    "item_description": "Assembly Product",
    "machine_stamp": "M01",
    "rel_sequence": 1,
    "quantity": 2000,
    "quantita_prodotta": 1000,
    "quantita_rimanente": 1000,
    "mes_status": "Active",
    "commento": null,
    "family": "Family A",
    "nome_completo_macchina": "M01 - Machine 1",
    "pdf_exists": false,
    "is_group": false
  }
]
```

---

## Step 8: Implementation Checklist

- [ ] Create `production_orders` table with all required fields
- [ ] Create `machine_center` table with machine information
- [ ] Add indexes to both tables for performance
- [ ] Create `stampaggio_view` database view (migration)
- [ ] Create `assemblaggio_view` database view (migration)
- [ ] Run migrations: `php artisan migrate`
- [ ] Create `StampaggioViewController.php` controller
- [ ] Create `AssemblaggioViewController.php` controller
- [ ] Add routes in `routes/web.php`
- [ ] Test `/tableview` endpoint
- [ ] Test `/tableviewAssemblaggio` endpoint
- [ ] Test comment update functionality
- [ ] Verify PDF existence checks work
- [ ] Verify grouping logic works correctly
- [ ] Test with frontend integration

---

## Key Differences to Adapt

When implementing in your new system, adjust these based on your requirements:

| Aspect | Original System | Your System |
|--------|----------------|-------------|
| Table names | `table_gua_mes_prod_orders`, `machine_center` | Your table names |
| Order number filter | `LIKE '%ST%'` or `LIKE '%AS%'` | Your filtering logic |
| Machine joining | `machine_press = mc.no` | Your join conditions |
| PDF location | `public/bolle_lavorazione_pdf/` | Your PDF storage path |
| Status values | `'Active'`, `'Complete'`, `'Stop'`, `'Pause'` | Your status values |
| Grouping logic | By machine/family | Your grouping requirements |

---

## Performance Considerations

1. **Indexes:** Ensure proper indexes on frequently queried columns
2. **View Optimization:** Database views should be optimized for read performance
3. **Caching:** Consider caching the data if it doesn't change frequently
4. **Pagination:** For large datasets, implement pagination
5. **Query Optimization:** Use `select()` to fetch only needed columns

---

## Testing Commands

```bash
# Run migrations
php artisan migrate

# Test the endpoints
curl http://your-domain/tableview
curl http://your-domain/tableviewAssemblaggio

# Check database views
php artisan tinker
>>> DB::table('stampaggio_view')->count();
>>> DB::table('assemblaggio_view')->count();
```

---

## Summary

This backend implementation provides:

✅ **Database Views** for filtered and joined data  
✅ **Controllers** for business logic and data processing  
✅ **API Endpoints** for JSON data delivery  
✅ **Comment Updates** for user annotations  
✅ **PDF Existence Checks** for document availability  
✅ **Hierarchical Grouping** for organized data display  

Follow the steps in order, adapt table/column names to your system, and test each component before moving to the next.
