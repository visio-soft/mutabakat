# Usage Guide

This guide covers the basic and advanced usage of the Mutabakat FilamentPHP plugin.

## Basic Usage

### Creating a Mutabakat Record

Through the Filament UI:
1. Navigate to the "Mutabakat" section in your admin panel
2. Click the "New Mutabakat" button
3. Fill in the required fields
4. Click "Create"

Programmatically:
```php
use Visiosoft\Mutabakat\Models\Mutabakat;

$mutabakat = Mutabakat::create([
    'park_id' => 1,
    'row_hash' => 'unique-hash-123',
    'provision_date' => '2024-01-15',
    'company' => 'ABC Company',
    'parking_name' => 'Main Parking Lot',
    'transaction_name' => 'Daily Settlement',
    'transaction_count' => 150,
    'total_amount' => 15000.00,
    'commission_amount' => 750.00,
    'net_transfer_amount' => 14250.00,
    'payment_date' => '2024-01-20',
    'status' => 'pending',
]);
```

### Updating a Record

Through the Filament UI:
1. Navigate to the "Mutabakat" section
2. Click on a record to edit
3. Update the fields
4. Click "Save changes"

Programmatically:
```php
$mutabakat = Mutabakat::find(1);
$mutabakat->update([
    'status' => 'completed',
    'payment_date' => now(),
]);
```

### Deleting a Record

The plugin supports soft deletes, so deleted records are not permanently removed from the database.

Through the Filament UI:
1. Navigate to the record
2. Click the delete button
3. Confirm the deletion

Programmatically:
```php
// Soft delete
$mutabakat = Mutabakat::find(1);
$mutabakat->delete();

// Restore a soft-deleted record
$mutabakat->restore();

// Permanently delete
$mutabakat->forceDelete();
```

## Querying Records

### Basic Queries

```php
use Visiosoft\Mutabakat\Models\Mutabakat;

// Get all records
$all = Mutabakat::all();

// Get pending records
$pending = Mutabakat::where('status', 'pending')->get();

// Get records by park
$parkRecords = Mutabakat::where('park_id', 1)->get();

// Get records within a date range
$records = Mutabakat::whereBetween('provision_date', ['2024-01-01', '2024-01-31'])->get();
```

### Including Soft-Deleted Records

```php
// Include soft-deleted records
$all = Mutabakat::withTrashed()->get();

// Only soft-deleted records
$deleted = Mutabakat::onlyTrashed()->get();
```

### Aggregations

```php
// Sum of total amounts
$total = Mutabakat::sum('total_amount');

// Average commission
$avgCommission = Mutabakat::avg('commission_amount');

// Count by status
$pendingCount = Mutabakat::where('status', 'pending')->count();
```

## Filtering in Filament

The plugin includes built-in filters:

### Status Filter
Filter records by their status (pending, completed, cancelled)

### Trashed Filter
Show only active records, only deleted records, or both

## Bulk Actions

You can perform bulk actions on multiple records:

1. Select multiple records using checkboxes
2. Choose an action from the bulk actions dropdown:
   - Delete selected
   - Force delete selected
   - Restore selected

## Field Descriptions

### park_id
The identifier for the parking facility

### row_hash
A unique hash for the record, useful for preventing duplicates

### provision_date
The date when the provision was made

### company
The name of the company associated with this record

### parking_name
The name of the parking facility

### transaction_name
The type of transaction (e.g., "Daily Settlement", "Weekly Settlement")

### transaction_count
The number of transactions included in this record

### total_amount
The total transaction amount (in TRY by default)

### commission_amount
The commission amount deducted from the total

### net_transfer_amount
The net amount after commission (usually: total_amount - commission_amount)

### payment_date
The date when payment was made or scheduled

### status
Current status of the record:
- `pending`: Awaiting processing
- `completed`: Successfully processed
- `cancelled`: Cancelled transaction

## Advanced Usage

### Using the Facade

```php
use Visiosoft\Mutabakat\Facades\Mutabakat;

// Get package version
$version = Mutabakat::version();
```

### Custom Status Values

You can customize status options in `config/mutabakat.php`:

```php
'statuses' => [
    'draft' => 'Draft',
    'pending' => 'Pending',
    'approved' => 'Approved',
    'completed' => 'Completed',
    'rejected' => 'Rejected',
],
```

### Changing Currency

Update the currency in the configuration file:

```php
'currency' => 'EUR', // or 'USD', 'GBP', etc.
```

## Best Practices

1. **Always use row_hash**: Generate unique hashes for each record to prevent duplicates
2. **Validate amounts**: Ensure total_amount = commission_amount + net_transfer_amount
3. **Set proper dates**: Use provision_date for the transaction date and payment_date for when it was paid
4. **Use status wisely**: Update status as records progress through your workflow
5. **Regular cleanup**: Archive old completed records periodically
6. **Soft deletes**: Prefer soft deletes over hard deletes to maintain data integrity

## Examples

### Daily Settlement Import

```php
use Visiosoft\Mutabakat\Models\Mutabakat;
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    $settlements = [
        // Your settlement data
    ];
    
    foreach ($settlements as $settlement) {
        Mutabakat::create([
            'row_hash' => md5(json_encode($settlement)),
            'park_id' => $settlement['park_id'],
            'provision_date' => $settlement['date'],
            'company' => $settlement['company'],
            'transaction_count' => $settlement['count'],
            'total_amount' => $settlement['total'],
            'commission_amount' => $settlement['commission'],
            'net_transfer_amount' => $settlement['total'] - $settlement['commission'],
            'status' => 'pending',
        ]);
    }
});
```

### Generate Report

```php
use Visiosoft\Mutabakat\Models\Mutabakat;

$report = Mutabakat::selectRaw('
    company,
    COUNT(*) as record_count,
    SUM(transaction_count) as total_transactions,
    SUM(total_amount) as total_amount,
    SUM(commission_amount) as total_commission,
    SUM(net_transfer_amount) as total_net
')
->where('status', 'completed')
->whereBetween('provision_date', ['2024-01-01', '2024-01-31'])
->groupBy('company')
->get();
```

## Support

For issues, questions, or contributions, please visit:
- GitHub Issues: [https://github.com/visio-soft/mutabakat/issues](https://github.com/visio-soft/mutabakat/issues)
- Documentation: [https://github.com/visio-soft/mutabakat](https://github.com/visio-soft/mutabakat)
