# APP1 System Logic Flow Guide

**Purpose:** Abstract logic flow for implementing a production monitoring system - no specific code or table structures.

---

## System Architecture Pattern

```
Frontend (View) 
    ↓ AJAX Request
API Endpoints (Routes)
    ↓ Call
Controllers (Business Logic)
    ↓ Query
Database Views (Filtered/Joined Data)
    ↓ Based on
Base Tables (Raw Data)
```

---

## Core Concept

**Two-Tab Monitoring Dashboard:**
- Tab 1: Stamping/Manufacturing operations
- Tab 2: Assembly operations

Each tab displays production orders grouped by machines with real-time status.

---

## Database Layer Logic

### Base Data Requirements

**Production Orders Data:**
- Unique order identifier
- Item/product code and description
- Machine assignment information
- Order sequence number
- Planned quantity
- Produced quantity
- Current status (Active, Paused, Stopped, Complete)
- User comments/notes
- Product family/category

**Machine Data:**
- Machine identifier
- Machine position/location
- Machine name/description
- Machine type

### View Layer Logic

**Purpose:** Pre-filter and pre-join data for specific use cases

**View 1 - Stamping Operations:**
1. Join production orders with machine information
2. Filter orders by type (stamping operations only)
3. Combine machine code + description into single field
4. Include machine position for sorting

**View 2 - Assembly Operations:**
1. Join production orders with machine information
2. Filter orders by type (assembly operations only)
3. Combine machine position + name into full machine name
4. Include product family for grouping

**Filtering Strategy:**
- Use order identifier pattern matching to separate operation types
- Example: Orders containing "ST" = Stamping, "AS" = Assembly

---

## Controller Layer Logic

### Data Retrieval Flow

**Step 1: Query the View**
- Fetch from appropriate database view
- Filter out null/empty critical fields (order number, item code)
- Sort by relevant grouping fields

**Step 2: Enrich Data**
- Check for related documents (PDFs, attachments)
- Add document existence flag to each record

**Step 3: Calculate Derived Fields**
- Remaining quantity = Planned - Produced
- Completion percentage (if needed)
- Status indicators

**Step 4: Group Data**
- **Stamping:** Single-level grouping by machine
- **Assembly:** Two-level grouping by family → machine

**Step 5: Format for Frontend**
- Add grouping metadata (is_group, group_type flags)
- Structure hierarchically where needed
- Return as JSON array

### Stamping Controller Logic

```
1. Query stamping view
2. Filter: Remove records with empty order/item numbers
3. Sort by: Machine → Position → Sequence
4. For each record:
   - Check if related document exists
   - Format machine label: "Position X - Machine Name"
   - Calculate remaining quantity
5. Group by machine
6. Flatten into array with group markers
7. Return JSON
```

### Assembly Controller Logic

```
1. Query assembly view
2. Filter: Remove records with empty order/item numbers
3. Sort by: Family → Machine → Sequence
4. For each record:
   - Check if related document exists
   - Calculate remaining quantity
5. Group by family, then by machine
6. Build hierarchy:
   - Family header row
   - Machine header row (under family)
   - Order detail rows (under machine)
7. Optional: Exclude specific machines
8. Return JSON
```

### Comment Update Logic

```
1. Receive: Record ID + New comment text
2. Update: Find record by ID, update comment field
3. Return: Success confirmation
```

---

## API Endpoint Logic

### Endpoint Structure

**Main Page Route:**
- Returns the view template
- No data passed directly

**Data Endpoints:**
- Stamping data endpoint → Calls stamping controller
- Assembly data endpoint → Calls assembly controller

**Update Endpoints:**
- Comment save endpoint → Updates comment in database

### Request/Response Pattern

**Data Request:**
- Method: GET
- Headers: JSON content type, CSRF token
- Response: JSON array of records

**Update Request:**
- Method: POST
- Body: Record ID + Updated field
- Headers: CSRF token
- Response: Success/failure JSON

---

## Data Processing Logic Details

### Document Existence Check

**Logic:**
```
For each order:
  1. Build expected document path using order number
  2. Check if file exists at that path
  3. Add boolean flag to record
  4. Frontend uses flag to:
     - Show green clickable link if exists
     - Show red non-clickable text if missing
```

### Grouping Logic

**Single-Level Grouping (Stamping):**
```
1. Create empty groups object
2. For each record:
   - Use machine name as key
   - Add record to that machine's array
3. Flatten:
   - For each machine group:
     - Add all records with is_group=false flag
```

**Two-Level Grouping (Assembly):**
```
1. Create nested groups object
2. For each record:
   - First level key: Family name
   - Second level key: Machine name
   - Add record to family→machine array
3. Flatten hierarchically:
   - Add family header (is_group=true, type=family)
   - For each machine in family:
     - Add machine header (is_group=true, type=machine)
     - Add all order records (is_group=false)
```

### Sorting Logic

**Stamping Sort Priority:**
1. Machine full name
2. Machine position number
3. Order sequence number

**Assembly Sort Priority:**
1. Product family
2. Machine full name
3. Order sequence number

---

## Frontend Integration Logic

### Initial Load

```
1. Page loads with empty grids
2. JavaScript initializes two grid components
3. On grid ready event:
   - Make AJAX call to data endpoint
   - Receive JSON array
   - Populate grid with data
```

### Auto-Refresh

```
1. Set interval timer (e.g., 5 minutes)
2. On timer trigger:
   - Re-fetch data from both endpoints
   - Update grids with new data
   - Reset timer
```

### User Interactions

**Comment Editing:**
```
1. User types in comment field
2. On blur/change event:
   - Get record ID from field
   - Get new comment text
   - POST to update endpoint
   - Show success/error message
```

**Document Viewing:**
```
1. If document exists (green link):
   - Click opens document in new tab
2. If document missing (red text):
   - Click disabled
```

---

## Data Flow Diagram (Abstract)

```
┌─────────────────────────────────────────────────┐
│ Base Tables                                     │
│ • Production Orders (all types)                 │
│ • Machine Information                           │
└─────────────────┬───────────────────────────────┘
                  │
                  ├─────────────────┬─────────────────┐
                  ▼                 ▼                 ▼
         ┌────────────────┐  ┌────────────────┐
         │ Stamping View  │  │ Assembly View  │
         │ • Join tables  │  │ • Join tables  │
         │ • Filter ST    │  │ • Filter AS    │
         │ • Add fields   │  │ • Add fields   │
         └────────┬───────┘  └────────┬───────┘
                  │                   │
                  ▼                   ▼
         ┌────────────────┐  ┌────────────────┐
         │ Stamping Ctrl  │  │ Assembly Ctrl  │
         │ • Query view   │  │ • Query view   │
         │ • Enrich data  │  │ • Enrich data  │
         │ • Group by     │  │ • Group by     │
         │   machine      │  │   family+mach  │
         │ • Return JSON  │  │ • Return JSON  │
         └────────┬───────┘  └────────┬───────┘
                  │                   │
                  ▼                   ▼
         ┌────────────────┐  ┌────────────────┐
         │ /tableview     │  │ /tableviewAsm  │
         │ API Endpoint   │  │ API Endpoint   │
         └────────┬───────┘  └────────┬───────┘
                  │                   │
                  └─────────┬─────────┘
                            ▼
                  ┌──────────────────┐
                  │ Frontend Grid    │
                  │ • Display data   │
                  │ • Auto-refresh   │
                  │ • Edit comments  │
                  └──────────────────┘
```

---

## Key Logic Patterns

### 1. Separation of Concerns
- **Views:** Data filtering and joining
- **Controllers:** Business logic and formatting
- **Frontend:** Display and user interaction

### 2. Type-Based Filtering
- Use order identifier patterns to separate operation types
- Each type gets its own view and controller

### 3. Hierarchical Grouping
- Simple operations: Single-level grouping
- Complex operations: Multi-level grouping
- Use metadata flags to indicate group vs data rows

### 4. Progressive Enhancement
- Base data from database
- Enrich with calculated fields
- Add external checks (file existence)
- Format for display

### 5. Real-Time Updates
- Periodic auto-refresh
- Immediate feedback on user actions
- Optimistic UI updates

---

## Implementation Decision Points

When adapting this logic to your system, decide:

1. **Order Type Identification**
   - How to distinguish stamping vs assembly orders?
   - Pattern matching? Status field? Type field?

2. **Machine Relationship**
   - How are machines linked to orders?
   - Direct ID? Position code? Multiple fields?

3. **Grouping Strategy**
   - What grouping makes sense for your operations?
   - Single or multi-level?
   - What are the grouping keys?

4. **Document Management**
   - What documents need to be linked?
   - Where are they stored?
   - How to identify them?

5. **Status Tracking**
   - What statuses exist in your system?
   - How to display them?
   - What actions trigger status changes?

6. **Calculated Fields**
   - What needs to be calculated?
   - Where to calculate (DB view vs controller)?
   - What formulas to use?

---

## Summary

**Core Logic Flow:**
1. Store raw data in base tables
2. Create filtered views for each operation type
3. Controllers query views and enrich data
4. API endpoints expose JSON data
5. Frontend fetches and displays with auto-refresh

**Key Principles:**
- ✅ Use database views for filtering/joining
- ✅ Controllers handle business logic
- ✅ Group data hierarchically for display
- ✅ Enrich with calculated and external data
- ✅ Return structured JSON for frontend
- ✅ Auto-refresh for real-time monitoring

Adapt field names, table structures, and grouping logic to match your specific system requirements while maintaining this overall pattern.
