# Installation Guide

This guide will walk you through installing and configuring the Mutabakat FilamentPHP plugin.

## Requirements

Before installing, ensure your system meets the following requirements:

- PHP 8.2 or higher
- Laravel 11.x or 12.x
- FilamentPHP 3.x
- MySQL 5.7+ or PostgreSQL 9.6+ (or any database supporting decimal types)

## Step 1: Install via Composer

Install the package using Composer:

```bash
composer require visiosoft/mutabakat
```

## Step 2: Run Installation Command

Run the installation command to publish the configuration and migration files:

```bash
php artisan mutabakat:install
```

This command will:
- Publish the configuration file to `config/mutabakat.php`
- Publish the migration file to your `database/migrations` directory

## Step 3: Run Migrations

Execute the migrations to create the `mutabakat` table:

```bash
php artisan migrate
```

## Step 4: Register the Plugin

Open your Filament Panel Provider (typically `app/Providers/Filament/AdminPanelProvider.php`) and register the plugin:

```php
use Visiosoft\Mutabakat\MutabakatPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... other configuration
        ->plugins([
            MutabakatPlugin::make(),
        ]);
}
```

## Step 5: Configure (Optional)

You can customize the plugin by editing the `config/mutabakat.php` file:

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

## Verification

After installation, you should see a "Mutabakat" menu item in your Filament admin panel. Click on it to start managing mutabakat records.

## Troubleshooting

### Package not found
If you get a "Package not found" error, make sure:
- You have the correct package name: `visiosoft/mutabakat`
- Your Composer is up to date: `composer self-update`
- Your minimum-stability is set correctly in your project's `composer.json`

### Migration fails
If the migration fails:
- Check your database connection in `.env`
- Ensure your database user has CREATE TABLE permissions
- Check for existing tables with the name `mutabakat`

### Plugin not appearing in Filament
If the plugin doesn't appear:
- Clear your application cache: `php artisan cache:clear`
- Clear the view cache: `php artisan view:clear`
- Ensure you've registered the plugin in your Panel Provider

## Next Steps

- Read the [Usage Guide](USAGE.md) to learn how to use the plugin
- Check out the [API Documentation](API.md) for advanced usage
- Join our community for support and discussions
