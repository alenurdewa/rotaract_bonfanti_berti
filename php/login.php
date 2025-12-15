<?php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO('sqlite:' . __DIR__ . '/../database.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT * FROM utenti WHERE username = :u LIMIT 1");
        $stmt->execute([
            ':u' => $_POST['username'] ?? ''
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // ✅ CONFRONTO IN CHIARO
        if ($user && ($_POST['password'] ?? '') === $user['password']) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username']
            ];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Credenziali errate';
        }

    } catch (Exception $e) {
        $error = 'Errore DB: ' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Admin Login</title>
  <link rel="stylesheet" href="../css/admin.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>

<nav class="navbar">
  <div class="logo"><h1>Area Admin</h1></div>
</nav>

<div class="container">
  <div class="card" style="max-width:500px;margin:auto;">
    <h2>Login Admin</h2>
    <p>Inserisci le credenziali per accedere alla dashboard.</p>

    <?php if ($error): ?>
      <div class="notice"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="form-row">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button class="button">Entra</button>
    </form>
  </div>
</div>

<footer class="footer">
  <div>Rotaract Admin Panel</div>
</footer>

</body>
</html>
