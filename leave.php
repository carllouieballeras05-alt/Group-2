<?php
/**
 * leave.php — Leave Management module.
 *
 *   GET    leave.php[?name=Jane Doe]   list all, or one person's requests
 *   POST   leave.php                   { name, type, start, end, reason, status }
 *   PATCH  leave.php?id=5              { status: "On Leave" | "Rejected" }  -> approve/reject
 *   DELETE leave.php?id=5              delete a request (HR)
 */

require 'db.php';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $cols = 'id, name, leave_type AS type, start_date AS start, end_date AS end, reason, status';
    $name = $_GET['name'] ?? null;
    if ($name) {
        $stmt = $pdo->prepare("SELECT $cols FROM leave_requests WHERE name = ? ORDER BY id DESC");
        $stmt->execute([$name]);
    } else {
        $stmt = $pdo->query("SELECT $cols FROM leave_requests ORDER BY id DESC");
    }
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $data = input();
    $stmt = $pdo->prepare(
        'INSERT INTO leave_requests (name, leave_type, start_date, end_date, reason, status) VALUES (?,?,?,?,?,?)'
    );
    $stmt->execute([
        trim($data['name'] ?? 'EMPLOYEE'),
        trim($data['type'] ?? 'Vacation'),
        $data['start'] ?? date('Y-m-d'),
        $data['end'] ?? date('Y-m-d'),
        trim($data['reason'] ?? ''),
        $data['status'] ?? 'Pending',
    ]);
    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
    exit;
}

if ($method === 'PATCH') {
    $id = (int) ($_GET['id'] ?? 0);
    $data = input();
    $status = $data['status'] ?? null;
    if (!$id || !in_array($status, ['Pending', 'On Leave', 'Rejected'], true)) {
        fail(400, 'Missing or invalid id/status.');
    }
    $stmt = $pdo->prepare('UPDATE leave_requests SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) fail(400, 'Missing id.');
    $stmt = $pdo->prepare('DELETE FROM leave_requests WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

fail(405, 'Method not allowed.');
