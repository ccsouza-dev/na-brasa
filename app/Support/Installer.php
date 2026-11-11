<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use Throwable;

class Installer
{
    private static bool $running = false;

    public static function ensure(): void
    {
        if (self::$running || app()->runningUnitTests()) {
            return;
        }

        self::$running = true;

        try {
            $marker = storage_path('app/installed');
            if (is_file($marker)) {
                return;
            }

            self::createDatabaseIfMissing();

            if (! Schema::hasTable('products')) {
                Artisan::call('migrate', ['--force' => true]);
            }

            if (Schema::hasTable('products') && DB::table('products')->count() === 0) {
                Artisan::call('db:seed', [
                    '--force' => true,
                    '--class' => 'Database\\Seeders\\CatalogSeeder',
                ]);
            }

            if (Schema::hasTable('products') && DB::table('products')->count() > 0) {
                file_put_contents($marker, 'ok');
            }
        } finally {
            self::$running = false;
        }
    }

    private static function createDatabaseIfMissing(): void
    {
        if (config('database.default') !== 'mysql') {
            return;
        }

        $database = (string) config('database.connections.mysql.database');
        if (! preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            return;
        }

        try {
            DB::connection()->getPdo();

            return;
        } catch (Throwable $e) {
            $message = $e->getMessage();
            if (! str_contains($message, '1049') && ! str_contains($message, 'Unknown database')) {
                return;
            }
        }

        $connection = config('database.connections.mysql');
        $dsn = sprintf('mysql:host=%s;port=%s', $connection['host'], $connection['port']);
        $pdo = new PDO($dsn, (string) $connection['username'], (string) ($connection['password'] ?? ''));
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        DB::purge('mysql');
    }
};
