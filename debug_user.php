<?php
// debug_user.php - Check what the application sees

require_once __DIR__ . '/app/bootstrap.php';

use App\Models\User;

echo "GIS CMS - User Debug\n";
echo "===================\n\n";

try {
    // Check database connection
    $db = \App\Config\Database::getConnection();
    echo "✅ Database connected\n\n";
    
    // Direct SQL query
    $stmt = $db->query("SELECT id, username, email, is_active FROM users WHERE username = 'admin'");
    $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    echo "Direct SQL query result:\n";
    print_r($userData);
    echo "\n";
    
    // Using User model
    $users = User::where(['username' => 'admin'])->get();
    
    if (empty($users)) {
        echo "❌ User not found via model\n";
    } else {
        $user = $users[0];
        echo "User model result:\n";
        echo "  ID: " . $user->id . "\n";
        echo "  Username: " . $user->username . "\n";
        echo "  Email: " . $user->email . "\n";
        echo "  is_active (raw): " . var_export($user->is_active, true) . "\n";
        echo "  is_active (boolean): " . ($user->is_active ? 'true' : 'false') . "\n";
        echo "  Role ID: " . $user->role_id . "\n";
        
        // Check the actual attribute value
        echo "  Raw attributes: ";
        print_r($user->toArray());
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}