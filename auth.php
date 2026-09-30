<?php
/**
 * auth.php — account creation & sign-in.
 *
 *   POST auth.php?action=signup   { name, email, username, password, phone, country, role }
 *   POST auth.php?action=login    { identifier, password }
 */

require 'db.php';

$action = $_GET['action'] ?? '';
$data = input();

if ($action === 'signup') {
    $name     = trim($data['name'] ?? '');
    $email    = trim($data['email'] ?? '');
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $phone    = trim($data['phone'] ?? '');
    $country  = trim($data['country'] ?? '');
    $role     = in_array($data['role'] ?? '', ['employee', 'manager', 'hr'], true) ? $data['role'] : 'employee';

    if (!$name || !$email || !$username || !$password) {
        fail(400, 'Please fill in all required fields.');
    }
    if (strlen($password) < 8) {
        fail(400, 'Password must be at least 8 characters long.');
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
    $check->execute([$email, $username]);
    if ($existing = $check->fetch()) {
        fail(409, 'An account with this email or username already exists.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, username, password, phone, country, role) VALUES (?,?,?,?,?,?,?)'
    );
    $stmt->execute([$name, $email, $username, $hash, $phone, $country, $role]);

    $user = [
        'id' => (int) $pdo->lastInsertId(),
        'name' => $name,
        'email' => $email,
        'username' => $username,
        'phone' => $phone,
        'country' => $country,
        'role' => $role,
    ];

    echo json_encode(['success' => true, 'user' => $user]);
    exit;
}

if ($action === 'login') {
    $identifier = trim($data['identifier'] ?? '');
    $password   = $data['password'] ?? '';

    if (!$identifier || !$password) {
        fail(400, 'Please enter both email/username and password.');
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR username = ?');
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        fail(401, 'Incorrect email/username or password.');
    }

    unset($user['password']);
    echo json_encode(['success' => true, 'user' => $user]);
    exit;
}

fail(400, 'Unknown action. Use ?action=signup or ?action=login.');
