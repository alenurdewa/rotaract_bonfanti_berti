<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$bollettiniDir = __DIR__ . '/../bollettini';
if (!is_dir($bollettiniDir)) { @mkdir($bollettiniDir, 0755, true); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf'])) {
    $file = $_FILES['pdf'];
    $descr = trim($_POST['descr'] ?? '');
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($ext !== 'pdf') {
        $_SESSION['flash'] = 'Errore: solo file PDF consentiti.';
    } else {
        $target = $bollettiniDir . '/' . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $_SESSION['flash'] = 'PDF caricato con successo!';
        } else {
            $_SESSION['flash'] = 'Errore durante il caricamento.';
        }
    }
} else {
    $_SESSION['flash'] = 'Nessun file caricato.';
}

header('Location: dashboard.php');
exit;
