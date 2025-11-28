# Phase 3.5: Aircraft Association with Flights
## Implementation Summary

**Requirement**: "Cada vuelo debe estar asociado a una nave"
**Status**: ✅ COMPLETED

---

## 1. Overview

Phase 3.5 ensures that every flight in the system must be associated with an aircraft (nave). This was achieved by:

1. **Backend**: Verified that the `flights` table has a `nave_id` foreign key referencing the `naves` table
2. **Database**: Confirmed foreign key constraint exists to prevent orphaned records
3. **API**: Ensured flight creation/update endpoints require valid `nave_id`
4. **Frontend**: 
   - Replaced hardcoded numeric input for `nave_id` with a dropdown showing aircraft names/models
   - Display aircraft information in all flight views
   - Prevent flight creation without selecting an aircraft

---

## 2. Implementation Details

### 2.1 Backend (Already Implemented)

**File**: `microservicio_vuelos/src/Controllers/FlightController.php`

**Key Validations**:
- Line 52-63 (create method): Validates that `nave_id` is provided and exists
- Line 87-91 (update method): Validates aircraft existence when updating `nave_id`
- Line 75: Checks if aircraft exists using raw query before allowing flight creation

```php
$aircraft = \Illuminate\Database\Capsule\Manager::table('naves')
    ->where('id', $data['nave_id'])
    ->first();

if (!$aircraft) {
    return $this->errorResponse($response, 'La nave especificada no existe', 404);
}
```

**Database Constraint**:
```sql
FOREIGN KEY (nave_id) REFERENCES naves(id)
```

---

### 2.2 Frontend Updates

#### A. Flight Modal - Aircraft Dropdown Selection

**File**: `frontend/index.html` (lines 236-251)

**Changed From**:
```html
<label for="naveId">Nave ID:</label>
<input type="number" id="naveId" required>
```

**Changed To**:
```html
<label for="naveSelect">Nave:</label>
<select id="naveSelect" required style="...">
    <option value="">-- Seleccionar una nave --</option>
</select>
```

**Benefits**:
- User-friendly dropdown instead of numeric ID
- Shows aircraft name, model, and capacity
- Prevents selection of non-existent aircraft
- Clear visual feedback

---

#### B. Flight Creation Modal - Load Aircraft on Open

**File**: `frontend/js/app.js` (lines 680-705)

**New Function**: `openFlightModal()`

```javascript
window.openFlightModal = async function() {
    document.getElementById('flightId').value = '';
    document.getElementById('flightForm').reset();
    document.getElementById('flightModalTitle').textContent = 'Crear Nuevo Vuelo';
    
    // Load list of aircraft
    try {
        const response = await Aircraft.list();
        if (response.success) {
            const aircraft = response.data.data;
            const select = document.getElementById('naveSelect');
            select.innerHTML = '<option value="">-- Seleccionar una nave --</option>';
            
            aircraft.forEach(plane => {
                const option = document.createElement('option');
                option.value = plane.id;
                option.textContent = `${plane.name} (${plane.model}) - Capacidad: ${plane.capacity}`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        alert('Error al cargar naves');
    }
    
    document.getElementById('flightModal').style.display = 'flex';
};
```

**Event Listener**:
```javascript
document.getElementById('btnCreateFlight').addEventListener('click', openFlightModal);
```

---

#### C. Flight Edit Modal - Populate Aircraft Selection

**File**: `frontend/js/app.js` (lines 707-737)

**Updated**: `editFlight()` function

```javascript
window.editFlight = async function(flightId) {
    try {
        const flightResponse = await Flights.getById(flightId);
        const aircraftResponse = await Aircraft.list();
        
        if (flightResponse.success && aircraftResponse.success) {
            const flight = flightResponse.data.data;
            const aircraft = aircraftResponse.data.data;
            
            // ... populate form fields ...
            
            // Load aircraft dropdown with current selection highlighted
            const select = document.getElementById('naveSelect');
            select.innerHTML = '<option value="">-- Seleccionar una nave --</option>';
            
            aircraft.forEach(plane => {
                const option = document.createElement('option');
                option.value = plane.id;
                option.textContent = `${plane.name} (${plane.model}) - Capacidad: ${plane.capacity}`;
                if (plane.id === flight.nave_id) {
                    option.selected = true; // Pre-select current aircraft
                }
                select.appendChild(option);
            });
            
            document.getElementById('flightModalTitle').textContent = 'Editar Vuelo';
            document.getElementById('flightModal').style.display = 'flex';
        }
    } catch (error) {
        alert('Error al cargar vuelo');
    }
};
```

---

#### D. Flight Save - Validate Aircraft Selection

**File**: `frontend/js/app.js` (lines 739-755)

**Updated**: `saveFlight()` function

```javascript
window.saveFlight = async function(event) {
    event.preventDefault();
    const flightId = document.getElementById('flightId').value;
    const naveSelect = document.getElementById('naveSelect').value;
    
    // Validate that an aircraft is selected
    if (!naveSelect) {
        alert('Debes seleccionar una nave');
        return;
    }
    
    const data = {
        nave_id: parseInt(naveSelect),
        origin: document.getElementById('origin').value,
        destination: document.getElementById('destination').value,
        departure: document.getElementById('departure').value,
        arrival: document.getElementById('arrival').value,
        price: parseFloat(document.getElementById('price').value)
    };
    
    // ... rest of save logic ...
};
```

---

#### E. Flight Management Panel - Display Aircraft Info

**File**: `frontend/js/app.js` (lines 544-599)

**Updated**: `loadFlightsForManagement()` function

```javascript
async function loadFlightsForManagement() {
    // Fetch both flights and aircraft
    const response = await Flights.list();
    const aircraftResponse = await Aircraft.list();
    
    // Create aircraft map for quick lookup
    const aircraftMap = {};
    aircraft.forEach(plane => {
        aircraftMap[plane.id] = plane;
    });
    
    // Display flights with aircraft info
    flightsList.innerHTML = flights.map(flight => {
        const plane = aircraftMap[flight.nave_id];
        const aircraftInfo = plane ? 
            `<br>Nave: <strong>${plane.name}</strong> (${plane.model})` : 
            '<br><em style="color: red;">Nave no disponible</em>';
        
        return `
            <div class="flight-management-item">
                <div>
                    <strong>Vuelo #${flight.id}</strong> - ${flight.origin} → ${flight.destination}${aircraftInfo}<br>
                    Salida: ${flight.departure} | Precio: $${flight.price}
                </div>
                ...
            </div>
        `;
    }).join('');
}
```

---

#### F. Public Flights View - Display Aircraft Info

**File**: `frontend/js/app.js` (lines 144-193)

**Updated**: `loadFlights()` function

```javascript
async function loadFlights() {
    const response = await Flights.list();
    const aircraftResponse = await Aircraft.list();
    
    // Create aircraft map
    const aircraftMap = {};
    aircraft.forEach(plane => {
        aircraftMap[plane.id] = plane;
    });
    
    // Display flights with aircraft info
    flightsList.innerHTML = flights.map(flight => {
        const plane = aircraftMap[flight.nave_id];
        const aircraftInfo = plane ? `
            <div class="flight-info">
                <label>Nave:</label>
                <span><strong>${plane.name}</strong> (${plane.model})</span>
            </div>` : '';
        
        return `
            <div class="flight-card">
                <h3>Vuelo #${flight.id}</h3>
                ...
                ${aircraftInfo}
                ...
            </div>
        `;
    }).join('');
}
```

---

#### G. Flights Search - Display Aircraft Info

**File**: `frontend/js/app.js` (lines 245-303)

**Updated**: `searchFlights()` function

Similar implementation to `loadFlights()`, adding aircraft information to search results.

---

## 3. Key Features

### ✅ Aircraft Selection
- **Before**: Required manual numeric entry for `nave_id`
- **After**: Dropdown with friendly labels showing name, model, and capacity

### ✅ Aircraft Validation
- Cannot create flight without selecting an aircraft
- Backend validates aircraft exists (404 error if not)
- Frontend prevents form submission if no aircraft selected

### ✅ Aircraft Display
- All flight cards now show associated aircraft name and model
- Management panel displays aircraft info
- Public query view displays aircraft info
- Search results include aircraft info

### ✅ Aircraft Change Support
- Can change aircraft when editing a flight
- Current aircraft is pre-selected in dropdown
- All existing flights show their associated aircraft

### ✅ Error Handling
- Backend returns 404 if specified `nave_id` doesn't exist
- Frontend shows error message if aircraft not available
- Graceful handling of missing aircraft in display

---

## 4. Data Flow Diagram

```
User Creates Flight
        ↓
Frontend: openFlightModal()
        ↓
Load Aircraft List from API
        ↓
Populate naveSelect Dropdown
        ↓
User Selects Aircraft & Fills Other Fields
        ↓
User Clicks "Guardar Vuelo"
        ↓
saveFlight() Validates Aircraft Selection
        ↓
API: POST /api/flights { nave_id, origin, destination, ... }
        ↓
Backend: FlightController::create()
        ↓
Validates nave_id Exists in Database
        ↓
Creates Flight with Foreign Key Reference
        ↓
Returns Flight with nave_id
        ↓
Frontend: Display Success & Refresh List
        ↓
loadFlightsForManagement()
        ↓
Fetch Flights + Aircraft
        ↓
Display Flight + Aircraft Name/Model
```

---

## 5. Files Modified

| File | Changes |
|------|---------|
| `frontend/index.html` | Replaced numeric input with select dropdown for aircraft |
| `frontend/js/app.js` | Added aircraft loading, updated flight display functions |

---

## 6. Database Structure

**Current State** (No changes needed):

```sql
CREATE TABLE flights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nave_id INT NOT NULL,  -- ✓ Already required
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    departure DATETIME NOT NULL,
    arrival DATETIME NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nave_id) REFERENCES naves(id)  -- ✓ Constraint exists
);
```

---

## 7. Testing Procedures

### Test 1: Create Flight with Aircraft Selection
```
1. Navigate to Flight Management
2. Click "+ Crear Nuevo Vuelo"
3. Verify dropdown is populated with aircraft
4. Select an aircraft from dropdown
5. Fill remaining fields
6. Click "Guardar Vuelo"
7. Verify: Flight created with aircraft info displayed
```

### Test 2: Edit Flight Aircraft Assignment
```
1. In Flight Management, click "✏️ Editar" on a flight
2. Verify: Current aircraft is pre-selected in dropdown
3. Select different aircraft
4. Save changes
5. Verify: Flight updated with new aircraft
```

### Test 3: Prevent Flight Without Aircraft
```
1. Open flight creation modal
2. Don't select any aircraft
3. Click "Guardar Vuelo"
4. Verify: Alert shows "Debes seleccionar una nave"
5. Form submission blocked
```

### Test 4: Verify Aircraft Display in Public View
```
1. Navigate to "Vuelos Disponibles"
2. Verify: Each flight shows aircraft name and model
3. Use search/filter
4. Verify: Aircraft info still displayed in results
```

### Test 5: Backend Validation
```
1. Try to create flight with invalid nave_id via API
2. Verify: 404 error "La nave especificada no existe"
3. Try to update flight with invalid nave_id
4. Verify: 404 error returned
```

---

## 8. User Experience Improvements

### Before Phase 3.5
- Admin had to remember numeric IDs for aircraft
- Risk of creating flights with non-existent aircraft IDs
- Public users couldn't see what aircraft would be used
- No validation at frontend level

### After Phase 3.5
- Aircraft selection via intuitive dropdown
- Aircraft details visible during selection
- Frontend validation prevents invalid submissions
- Public users see complete flight information including aircraft
- Consistent aircraft info display across all views

---

## 9. API Behavior

### Create Flight
**Endpoint**: `POST /api/flights`

**Before**:
```json
{
    "nave_id": 1,
    "origin": "Bogotá",
    "destination": "Medellín",
    "departure": "2025-12-01 08:00",
    "arrival": "2025-12-01 09:00",
    "price": 200000
}
```

**Validation**: 
- If `nave_id` not provided → 400 Bad Request
- If `nave_id` doesn't exist → 404 Not Found
- If `nave_id` invalid → 500 Error

**Response**: Flight object with all fields including `nave_id`

### Read Flights
**Endpoint**: `GET /api/flights`

**Response**: Array of flight objects with `nave_id` field

Each flight includes:
```json
{
    "id": 1,
    "nave_id": 1,
    "origin": "Bogotá",
    "destination": "Medellín",
    "departure": "2025-12-01 08:00:00",
    "arrival": "2025-12-01 09:00:00",
    "price": "200000.00",
    "created_at": "2025-11-27T...",
    "updated_at": "2025-11-27T..."
}
```

### Update Flight
**Endpoint**: `PUT /api/flights/{id}`

Can include `nave_id` to reassign aircraft:
```json
{
    "nave_id": 2
}
```

**Validation**: Same as create

---

## 10. Architecture Decisions

### 1. Dropdown Over Text Input
- **Reason**: Prevents typos, ensures only valid IDs are submitted
- **Alternative Considered**: Autocomplete input (more complex, less performant)
- **Decision**: Dropdown is simplest, most intuitive

### 2. Load Aircraft on Modal Open
- **Reason**: Ensures dropdown always has latest aircraft list
- **Alternative**: Load once at page load (stale if new aircraft added)
- **Decision**: On-demand loading better for dynamic content

### 3. Pre-select Current Aircraft on Edit
- **Reason**: User immediately sees what aircraft is assigned
- **Alternative**: Force re-selection (poor UX)
- **Decision**: Pre-selection reduces cognitive load

### 4. Aircraft Map for Display
- **Reason**: Efficient O(1) lookup instead of O(n) search for each flight
- **Alternative**: Loop through aircraft array (slower)
- **Decision**: Map provides better performance with many aircraft

---

## 11. Backward Compatibility

✅ **Fully Compatible**

- Existing flights in database continue to work (already have `nave_id`)
- API endpoint behavior unchanged (still accepts `nave_id` in JSON)
- Database schema unchanged
- Only frontend interaction improved

---

## 12. Future Enhancements

### Potential Improvements
1. **Aircraft Capacity Validation**: Block flight creation if selected aircraft capacity is too small
2. **Aircraft Availability**: Show which aircraft are available for each time period
3. **Cost Calculation**: Auto-calculate price based on aircraft type and route
4. **Aircraft Maintenance**: Track and display aircraft maintenance schedules
5. **Advanced Filtering**: Filter flights by aircraft type, capacity, etc.

---

## 13. Completion Checklist

- ✅ Backend validation already in place
- ✅ Aircraft dropdown added to flight modal
- ✅ Aircraft list loaded on modal open
- ✅ Aircraft selection validation in save function
- ✅ Aircraft info displayed in management view
- ✅ Aircraft info displayed in public flight view
- ✅ Aircraft info displayed in search results
- ✅ Pre-selection working in edit modal
- ✅ Error handling for missing aircraft
- ✅ Frontend form submission prevented without aircraft
- ✅ Database constraints intact
- ✅ API behavior verified
- ✅ User experience improved

---

## 14. Testing Artifacts

**Test File**: `tools/test_phase_3_5.html`
- Interactive test page for verifying aircraft-flight associations
- Tests aircraft list retrieval
- Tests flight creation with aircraft
- Tests flight-aircraft association verification
- Can be accessed at: `http://localhost/Gestion_vuelos_reservas/tools/test_phase_3_5.html`

---

## 15. Summary

Phase 3.5 successfully ensures that **every flight must be associated with an aircraft**. The implementation provides:

1. **Enforced Association**: Backend validates `nave_id` on every flight operation
2. **User-Friendly Selection**: Dropdown interface for aircraft selection instead of numeric IDs
3. **Complete Visibility**: Aircraft information displayed in all flight views
4. **Frontend Validation**: Prevents submission without aircraft selection
5. **Backward Compatible**: All existing flights continue to work
6. **Scalable**: Efficient data lookups using maps

The system now guarantees data integrity while providing a superior user experience for managing flight-aircraft associations.

---

**Status**: ✅ PHASE 3.5 COMPLETED
**Date**: November 27, 2025
**Requirement Fully Met**: "Cada vuelo debe estar asociado a una nave"
