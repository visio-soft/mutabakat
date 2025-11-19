# Mutabakat - FilamentPHP 3 Plugin

A FilamentPHP 3 plugin for managing mutabakat (reconciliation) records in Laravel applications.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/visiosoft/mutabakat.svg?style=flat-square)](https://packagist.org/packages/visiosoft/mutabakat)
[![Total Downloads](https://img.shields.io/packagist/dt/visiosoft/mutabakat.svg?style=flat-square)](https://packagist.org/packages/visiosoft/mutabakat)

## Features

- Complete CRUD operations for mutabakat records
- Integration with FilamentPHP 3
- Support for Laravel 11 and 12
- Soft deletes support
- Comprehensive table structure for reconciliation tracking
- Configurable status options
- Multi-currency support

## Requirements

- PHP 8.2 or higher
- Laravel 11.x or 12.x
- FilamentPHP 3.x

## Installation

You can install the package via composer:

```bash
composer require visiosoft/mutabakat
```

Run the migrations:

```bash
php artisan migrate
```

The migrations will be automatically loaded from the package.

## Usage

### Register the Plugin

Add the plugin to your Filament panel provider:

```php
use Visiosoft\Mutabakat\MutabakatPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            MutabakatPlugin::make(),
        ]);
}
```

### Database Structure

The plugin creates a `mutabakat` table with the following fields:

- `id` - Primary key
- `park_id` - Park identifier
- `row_hash` - Unique hash for the row
- `provision_date` - Date of provision
- `company` - Company name
- `parking_name` - Parking facility name
- `transaction_name` - Transaction type name
- `transaction_count` - Number of transactions
- `total_amount` - Total transaction amount
- `commission_amount` - Commission amount
- `net_transfer_amount` - Net transfer amount
- `payment_date` - Payment date
- `status` - Record status (pending, completed, cancelled)
- `deleted_at` - Soft delete timestamp
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### Model Usage

You can use the Mutabakat model in your application:

```php
use Visiosoft\Mutabakat\Models\Mutabakat;

// Create a new record
$mutabakat = Mutabakat::create([
    'park_id' => 1,
    'company' => 'Example Company',
    'parking_name' => 'Main Parking',
    'transaction_name' => 'Daily Settlement',
    'transaction_count' => 150,
    'total_amount' => 15000.00,
    'commission_amount' => 750.00,
    'net_transfer_amount' => 14250.00,
    'status' => 'pending',
]);

// Query records
$pending = Mutabakat::where('status', 'pending')->get();
$completed = Mutabakat::where('status', 'completed')->get();
```

## Configuration

After installation, you can customize the configuration in `config/mutabakat.php`:

```php
return [
    'enabled' => true,
    'currency' => 'TRY',
    'statuses' => [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],
    'navigation' => [
        'icon' => 'heroicon-o-document-text',
        'sort' => 10,
        'group' => null,
    ],
];
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [VisioSoft](https://github.com/visio-soft)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.