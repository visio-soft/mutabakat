<?php

namespace Visiosoft\Mutabakat\Commands;

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

        $this->call('vendor:publish', [
            '--tag' => 'mutabakat-migrations',
        ]);

        $this->info('Mutabakat plugin installed successfully!');

        return self::SUCCESS;
    }
}
