<?php
/**
 * attendance.php — Time & Attendance module.
 *
 *   GET    attendance.php[?name=John Doe]     list all, or one person's rows
 *   POST   attendance.php                     { action:"clock", name, department }  -> clock in/out toggle
 *   POST   attendance.php                     { name, department, clockIn, clockOut, status } -> manual add (HR)
 *   DELETE attendance.php?id=5                delete a row (HR)
 */

require 'db.php';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $cols = 'id, name, department AS dept, clock_in AS clockIn, clock_out AS clockOut, status, log_date AS logDate';
    $name = $_GET['name'] ?? null;
    if ($name) {
        $stmt = $pdo->prepare("SELECT $cols FROM attendance WHERE name = ? ORDER BY id DESC");
        $stmt->execute([$name]);
    } else {
        $stmt = $pdo->query("SELECT $cols FROM attendance ORDER BY id DESC");
    }
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $data = input();

    if (($data['action'] ?? '') === 'clock') {
        $name = trim($data['name'] ?? '');
        $dept = trim($data['department'] ?? '');
        if (!$name) fail(400, 'Missing name.');

        $time = date('h:i A');

        $stmt = $pdo->prepare('SELECT * FROM attendance WHERE name = ? AND log_date = CURDATE() ORDER BY id DESC LIMIT 1');
        $stmt->execute([$name]);
        $entry = $stmt->fetch();

        if (!$entry) {
            $ins = $pdo->prepare('INSERT INTO attendance (name, department, clock_in, clock_out, status, log_date) VALUES (?,?,?,?,?,CURDATE())');
            $ins->execute([$name, $dept, $time, '--:--', 'Present']);
            echo json_encode(['success' => true, 'action' => 'clock_in', 'time' => $time]);
        } elseif ($entry['clock_out'] === '--:--') {
            $upd = $pdo->prepare('UPDATE attendance SET clock_out = ? WHERE id = ?');
            $upd->execute([$time, $entry['id']]);
            echo json_encode(['success' => true, 'action' => 'clock_out', 'time' => $time]);
        } else {
            $upd = $pdo->prepare('UPDATE attendance SET clock_in = ?, clock_out = "--:--", status = "Present" WHERE id = ?');
            $upd->execute([$time, $entry['id']]);
            echo json_encode(['success' => true, 'action' => 'clock_in', 'time' => $time]);
        }
        exit;
    }

    // Manual entry (HR "Add Entry")
    $stmt = $pdo->prepare('INSERT INTO attendance (name, department, clock_in, clock_out, status) VALUES (?,?,?,?,?)');
    $stmt->execute([
        trim($data['name'] ?? 'EMPLOYEE'),
        trim($data['department'] ?? ''),
        $data['clockIn'] ?? '--:--',
        $data['clockOut'] ?? '--:--',
        $data['status'] ?? 'Present',
    ]);
    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
    exit;
}

if ($method === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) fail(400, 'Missing id.');
    $stmt = $pdo->prepare('DELETE FROM attendance WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

fail(405, 'Method not allowed.');
