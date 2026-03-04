<?php
// debug_user_direct.php

require_once __DIR__ . '/app/bootstrap.php';

use App\Models\User;

echo "Direct Property Access Test\n";
echo "==========================\n\n";

// Get user
$users = User::where(['username' => 'admin'])->get();
$user = $users[0] ?? null;

if (!$user) {
    die("User not found\n");
}

echo "1. Accessing via array:\n";
echo "   \$user->attributes['id'] = " . $user->attributes['id'] . "\n";
echo "   \$user->attributes['username'] = " . $user->attributes['username'] . "\n\n";

echo "2. Converting to array:\n";
$asArray = (array)$user;
echo "   Keys: " . implode(', ', array_keys($asArray)) . "\n\n";

echo "3. Using array access if ArrayAccess is implemented:\n";
// This will show if we can use array syntax
if ($user instanceof \ArrayAccess) {
    echo "   User implements ArrayAccess\n";
    echo "   \$user['id'] = " . $user['id'] . "\n";
} else {
    echo "   User does not implement ArrayAccess\n";
}

echo "\n4. Reflection to see properties:\n";
$reflection = new ReflectionClass($user);
$properties = $reflection->getProperties();
foreach ($properties as $prop) {
    $prop->setAccessible(true);
    echo "   {$prop->getName()} = " . print_r($prop->getValue($user), true) . "\n";
}