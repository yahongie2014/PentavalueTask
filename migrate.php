#!/usr/bin/env php
<?php

/**
 * Database Migration CLI Tool
 *
 * Usage:
 *   php migrate.php             # Run pending migrations
 *   php migrate.php status      # Show migration status
 *   php migrate.php rollback    # Rollback last migration
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Connectivity\DB;
use Connectivity\Migrator;

// Load environment
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize database
DB::init();
$pdo = (new DB())->connect();

// Create migrator
$migrator = new Migrator($pdo);

// Parse command
$command = $argv[1] ?? 'migrate';

try {
    switch ($command) {
        case 'migrate':
            echo "=== Running Migrations ===\n";
            $migrator->migrate();
            echo "\n✓ All migrations completed successfully!\n";
            break;

        case 'status':
            echo "=== Migration Status ===\n";
            $status = $migrator->status();

            if (empty($status)) {
                echo "No migrations defined.\n";
            } else {
                foreach ($status as $item) {
                    $icon = $item['status'] === 'executed' ? '✓' : '○';
                    $color = $item['status'] === 'executed' ? "\033[32m" : "\033[33m";
                    echo "{$color}{$icon}\033[0m {$item['name']} - {$item['status']}\n";
                }
            }
            break;

        case 'rollback':
            echo "=== Rolling Back Last Migration ===\n";
            $migrator->rollback();
            break;

        default:
            echo "Unknown command: {$command}\n";
            echo "\nAvailable commands:\n";
            echo "  migrate   - Run pending migrations\n";
            echo "  status    - Show migration status\n";
            echo "  rollback  - Rollback last migration\n";
            exit(1);
    }
} catch (Exception $e) {
    echo "\n✗ Error: {$e->getMessage()}\n";
    exit(1);
}