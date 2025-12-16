<?php

namespace Visio\mutabakat\Commands;

use Illuminate\Console\Command;

class MutabakatCommand extends Command
{
    public $signature = 'mutabakat:install';

    public $description = 'Install Mutabakat plugin';

    public function handle(): int
    {
        $this->info('Installing Mutabakat plugin...');

        $this->call('vendor:publish', [
            '--tag' => 'mutabakat-config',
        ]);

        $this->info('Mutabakat plugin installed successfully!');
        $this->info('Run "php artisan migrate" to create the database tables.');

        return self::SUCCESS;
    }
}
