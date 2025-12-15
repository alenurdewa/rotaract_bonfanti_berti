<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$csvPath = __DIR__ . '/../csv/storia.csv';
if (!file_exists($csvPath)) {
    $headers = ['titolo','data','descrizione'];
    $fp = fopen($csvPath, 'w');
    fputcsv($fp, $headers);
    fclose($fp);
}

// Leggi CSV
$rows = [];
$headers = [];
if (($f = fopen($csvPath, 'r')) !== false) {
    $headers = fgetcsv($f);
    if ($headers === false) $headers = [];
    while (($data = fgetcsv($f)) !== false) $rows[] = $data;
    fclose($f);
}

// AZIONI
$action = $_POST['action'] ?? '';
$row = intval($_POST['row'] ?? -1);

if ($action === 'delete' && isset($rows[$row])) {
    array_splice($rows, $row, 1);
} elseif ($action === 'add' && isset($_POST['col'])) {
    $newRow = array_map('trim', $_POST['col']);
    $rows[] = $newRow;
}

// Salva CSV
$fp = fopen($csvPath, 'w');
fputcsv($fp, $headers);
foreach ($rows as $r) fputcsv($fp, $r);
fclose($fp);

// Messaggio flash
$_SESSION['flash'] = 'Operazione completata con successo.';
header('Location: dashboard.php');
exit;
