<?php
/**
 * shifts.php — Shift / Schedule Management module.
 *
 *   GET    shifts.php[?name=John Doe]   list all, or one person's shifts
 *   POST   shifts.php                   { name, shift, date, time, location, status }
 *   DELETE shifts.php?id=5              delete a shift (Manager / HR)
 */

require 'db.php';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $cols = 'id, name, shift_name AS shift, shift_date AS date, time_range AS time, location, status';
    $name = $_GET['name'] ?? null;
    if ($name) {
        $stmt = $pdo->prepare("SELECT $cols FROM shifts WHERE name = ? ORDER BY shift_date, id");
        $stmt->execute([$name]);
    } else {
        $stmt = $pdo->query("SELECT $cols FROM shifts ORDER BY shift_date, id");
    }
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $data = input();
    $stmt = $pdo->prepare(
        'INSERT INTO shifts (name, shift_name, shift_date, time_range, location, status) VALUES (?,?,?,?,?,?)'
    );
    $stmt->execute([
        trim($data['name'] ?? 'EMPLOYEE'),
        trim($data['shift'] ?? 'Morning'),
        $data['date'] ?? date('Y-m-d'),
        trim($data['time'] ?? ''),
        trim($data['location'] ?? ''),
        $data['status'] ?? 'Scheduled',
    ]);
    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
    exit;
}

if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) fail(400, 'Missing id.');
    $stmt = $pdo->prepare('DELETE FROM shifts WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

fail(405, 'Method not allowed.');
