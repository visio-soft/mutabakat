<?php

namespace Visiosoft\Reconciliation\Commands;

use Illuminate\Console\Command;

class ReconciliationCommand extends Command
{
    public $signature = 'reconciliation:install';

    public $description = 'Install Reconciliation plugin';

    public function handle(): int
    {
        $this->info('Installing Reconciliation plugin...');

        $this->call('vendor:publish', [
            '--tag' => 'reconciliation-config',
        ]);

        $this->info('Reconciliation plugin installed successfully!');
        $this->info('Run "php artisan migrate" to create the database tables.');

        return self::SUCCESS;
    }
}
