<?php
/**
 * users.php — registered account management (HR "User Accounts" module)
 * and profile self-editing.
 *
 *   GET    users.php                 list every registered account (password excluded)
 *   PATCH  users.php?id=5            { role } -> change a user's role (HR)
 *                                     { name, phone, country } -> edit own profile
 *   DELETE users.php?id=5            remove an account (HR)
 */

require 'db.php';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query('SELECT id, name, email, username, phone, country, department, role, created_at FROM users ORDER BY id');
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'PATCH') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) fail(400, 'Missing id.');
    $data = input();

    $fields = [];
    $values = [];

    if (isset($data['role']) && in_array($data['role'], ['employee', 'manager', 'hr'], true)) {
        $fields[] = 'role = ?';
        $values[] = $data['role'];
    }
    if (isset($data['name']) && trim($data['name']) !== '') {
        $fields[] = 'name = ?';
        $values[] = trim($data['name']);
    }
    if (isset($data['phone'])) {
        $fields[] = 'phone = ?';
        $values[] = trim($data['phone']);
    }
    if (isset($data['country'])) {
        $fields[] = 'country = ?';
        $values[] = trim($data['country']);
    }

    if (!$fields) fail(400, 'Nothing to update.');

    $values[] = $id;
    $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
    $stmt->execute($values);

    $get = $pdo->prepare('SELECT id, name, email, username, phone, country, department, role FROM users WHERE id = ?');
    $get->execute([$id]);
    echo json_encode(['success' => true, 'user' => $get->fetch()]);
    exit;
}

if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) fail(400, 'Missing id.');
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

fail(405, 'Method not allowed.');
