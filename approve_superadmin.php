<?php
require 'vendor/autoload.php';
$db = \Config\Database::connect();
$db->table('users')->where('id', 3)->update(['approval_status' => 'approved']);
echo "User 3 approved\n";
