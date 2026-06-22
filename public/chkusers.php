<?php
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: 3306;
$db   = getenv('DB_DATABASE');
$user = getenv('DB_USERNAME');
$pass = getenv('DB_PASSWORD');
$pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
$users = $pdo->query("SELECT id, name, email, user_type, created_at FROM users LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($users);
