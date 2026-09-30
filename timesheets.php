<?php
/**
 * timesheets.php — Timesheet Management module.
 *
 *   GET    timesheets.php[?name=John Doe]   list all, or one person's timesheets
 *   POST   timesheets.php                   { name, weekEnding, regular, overtime, status }
 *   PATCH  timesheets.php?id=5              { status: "Approved" | "Rejected" }  -> approve/reject
 *   DELETE timesheets.php?id=5              delete a timesheet (HR)
 */

require 'db.php';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $cols = 'id, name, week_ending AS weekEnding, regular_hours AS regular, overtime_hours AS overtime, status';
    $name = $_GET['name'] ?? null;
    if ($name) {
        $stmt = $pdo->prepare("SELECT $cols FROM timesheets WHERE name = ? ORDER BY id DESC");
        $stmt->execute([$name]);
    } else {
        $stmt = $pdo->query("SELECT $cols FROM timesheets ORDER BY id DESC");
    }
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $data = input();
    $stmt = $pdo->prepare(
        'INSERT INTO timesheets (name, week_ending, regular_hours, overtime_hours, status) VALUES (?,?,?,?,?)'
    );
    $stmt->execute([
        trim($data['name'] ?? 'EMPLOYEE'),
        $data['weekEnding'] ?? date('Y-m-d'),
        (float) ($data['regular'] ?? 0),
        (float) ($data['overtime'] ?? 0),
        $data['status'] ?? 'Pending',
    ]);
    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
    exit;
}

if ($method === 'PATCH') {
    $id = (int) ($_GET['id'] ?? 0);
    $data = input();
    $status = $data['status'] ?? null;
    if (!$id || !in_array($status, ['Pending', 'Approved', 'Rejected'], true)) {
        fail(400, 'Missing or invalid id/status.');
    }
    $stmt = $pdo->prepare('UPDATE timesheets SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) fail(400, 'Missing id.');
    $stmt = $pdo->prepare('DELETE FROM timesheets WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

fail(405, 'Method not allowed.');
