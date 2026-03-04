<?php
// install.php

require_once __DIR__ . '/app/bootstrap.php';

use App\Database\Seeder;

// Check if already installed
if (file_exists(__DIR__ . '/.installed')) {
    die('CMS is already installed. Delete .installed file to reinstall.');
}

echo "CMS Installation\n";
echo "================\n\n";

try {
    // Run database migrations (you'd need to create the schema first)
    echo "Running database migrations...\n";
    // You should run your SQL schema here
    
    // Seed initial data
    echo "Seeding database...\n";
    $seeder = new Seeder();
    $seeder->seed();
    
    // Create installed flag
    file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
    
    echo "\n✓ Installation completed successfully!\n";
    echo "You can now login with:\n";
    echo "Username: admin\n";
    echo "Password: Admin@123\n";
    echo "\nIMPORTANT: Change the default password immediately!\n";
    
} catch (Exception $e) {
    echo "✗ Installation failed: " . $e->getMessage() . "\n";
    exit(1);
}