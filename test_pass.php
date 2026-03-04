<?php
// set_password.php - Set a new password

require_once __DIR__ . '/app/bootstrap.php';

$db = \App\Config\Database::getConnection();

// Choose your new password
$newPassword = 'Admin@123'; // Change this if you want

// Generate fresh hash
$newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

echo "New password: {$newPassword}\n";
echo "New hash: {$newHash}\n\n";

// Update the database
$sql = "UPDATE users SET password_hash = :hash WHERE username = 'Admin' RETURNING id";
$stmt = $db->prepare($sql);
$stmt->execute([':hash' => $newHash]);

$userId = $stmt->fetchColumn();

if ($userId) {
    echo "✅ Password updated successfully!\n\n";
    
    // Verify the new hash works
    $verifyStmt = $db->prepare("SELECT password_hash FROM users WHERE username = 'Admin'");
    $verifyStmt->execute();
    $newHashInDB = $verifyStmt->fetchColumn();
    
    echo "Verifying new hash:\n";
    echo "  Hash in DB: {$newHashInDB}\n";
    echo "  password_verify('{$newPassword}', hash): " . 
         (password_verify($newPassword, $newHashInDB) ? '✅ WORKS' : '❌ FAILED') . "\n";
    
    echo "\nYou can now login with:\n";
    echo "  Username: admin\n";
    echo "  Password: {$newPassword}\n";
} else {
    echo "❌ Failed to update password\n";
}