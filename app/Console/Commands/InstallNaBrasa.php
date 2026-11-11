<?php

namespace App\Console\Commands;

use App\Support\Installer;
use Illuminate\Console\Command;

class InstallNaBrasa extends Command
{
    protected $signature = 'na-brasa:install';

    protected $description = 'Cria o banco, as tabelas e o cardápio inicial';

    public function handle(): int
    {
        Installer::ensure();
        $this->info('Banco, tabelas e cardápio prontos.');

        return self::SUCCESS;
    }
};
