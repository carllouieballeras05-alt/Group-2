<?php
/**
 * departments.php — Workforce Analytics module (department summary table).
 *
 *   GET    departments.php
 *   POST   departments.php   { dept, headcount, rate, overtime, status }
 *   DELETE departments.php?id=5   (HR)
 */

require 'db.php';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query('SELECT id, dept_name AS dept, headcount, attendance_rate AS rate, overtime_hours AS overtime, status FROM departments ORDER BY id');
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $data = input();
    $stmt = $pdo->prepare(
        'INSERT INTO departments (dept_name, headcount, attendance_rate, overtime_hours, status) VALUES (?,?,?,?,?)'
    );
    $stmt->execute([
        trim($data['dept'] ?? 'DEPARTMENT'),
        (int) ($data['headcount'] ?? 0),
        (float) ($data['rate'] ?? 0),
        (float) ($data['overtime'] ?? 0),
        $data['status'] ?? 'Active',
    ]);
    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
    exit;
}

if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) fail(400, 'Missing id.');
    $stmt = $pdo->prepare('DELETE FROM departments WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

fail(405, 'Method not allowed.');
