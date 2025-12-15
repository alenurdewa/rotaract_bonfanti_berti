<?php
session_start();

// Se non loggato, torna al login
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Percorsi CSV e PDF
$csvPath = __DIR__ . '/../csv/storia.csv';
$bollettiniDir = __DIR__ . '/../bollettini';

// Crea cartelle se non esistono
if (!is_dir(dirname($csvPath))) { @mkdir(dirname($csvPath), 0755, true); }
if (!is_dir($bollettiniDir)) { @mkdir($bollettiniDir, 0755, true); }

// Leggi CSV
$rows = [];
$headers = [];
if (file_exists($csvPath)) {
    if (($f = fopen($csvPath, 'r')) !== false) {
        $headers = fgetcsv($f);
        if ($headers === false) $headers = [];
        while (($data = fgetcsv($f)) !== false) $rows[] = $data;
        fclose($f);
    }
} else {
    $headers = ['titolo','data','descrizione'];
    $fp = fopen($csvPath, 'w');
    fputcsv($fp, $headers);
    fclose($fp);
}

$msg = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Dashboard Admin</title>
  <link rel="stylesheet" href="../css/admin.css">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
</head>
<body>
  <nav class="navbar">
    <div class="logo"><h1>Admin — Dashboard</h1></div>
    <div class="nav-links">
      <a href="logout.php">Logout</a>
      <a href="../" target="_blank">Sito pubblico</a>
    </div>
  </nav>

  <div class="container">
    <div class="card">
      <h2 class="h1">Benvenuto, <?= htmlspecialchars($_SESSION['user']['username']); ?>.</h2>
      <p class="lead">Qui puoi modificare la storia (CSV) e caricare i bollettini in PDF. Semplice e veloce.</p>
      <?php if ($msg): ?><div class="notice"><?= htmlspecialchars($msg); ?></div><?php endif; ?>
    </div>

    <div class="card">
      <h3>Storia (../csv/storia.csv)</h3>
      <p class="lead">Visualizza, modifica o elimina righe. Aggiungi nuove voci con il form sotto.</p>
      <table>
        <thead>
          <tr>
            <?php foreach ($headers as $h): ?><th><?= htmlspecialchars($h); ?></th><?php endforeach; ?>
            <th>Azioni</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <?php foreach ($r as $c): ?><td><?= htmlspecialchars($c); ?></td><?php endforeach; ?>
              <td class="actions">
                <!-- Modifica e Elimina per test -->
                <form style="display:inline" method="post" action="csv_action.php">
                  <input type="hidden" name="action" value="edit_form">
                  <input type="hidden" name="row" value="<?= $i; ?>">
                  <button class="button secondary" type="submit">Modifica</button>
                </form>
                <form style="display:inline" method="post" action="csv_action.php" onsubmit="return confirm('Sei sicuro?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="row" value="<?= $i; ?>">
                  <button class="button" type="submit">Elimina</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <hr style="margin:16px 0;">

      <h4>Aggiungi nuova riga</h4>
      <form method="post" action="csv_action.php" class="form-row">
        <input type="hidden" name="action" value="add">
        <?php foreach ($headers as $h): ?>
            <input name="col[]" placeholder="<?= htmlspecialchars($h); ?>" required />
        <?php endforeach; ?>
        <button class="button" type="submit">Aggiungi</button>
      </form>
    </div>

    <div class="card">
      <h3>Carica bollettino (../bollettini)</h3>
      <form method="post" action="upload.php" enctype="multipart/form-data" class="form-row">
        <input type="file" name="pdf" accept="application/pdf" required />
        <input type="text" name="descr" placeholder="Descrizione breve (opzionale)" />
        <button class="button" type="submit">Carica PDF</button>
      </form>

      <hr style="margin:16px 0;">
      <h4>Bollettini caricati</h4>
      <ul>
        <?php
          $files = glob($bollettiniDir . '/*.pdf');
          if (!$files) echo '<li>Nessun bollettino</li>';
          else foreach ($files as $f) echo '<li><a href="../bollettini/' . rawurlencode(basename($f)) . '" target="_blank">' . htmlspecialchars(basename($f)) . '</a></li>';
        ?>
      </ul>
    </div>

  </div>

  <footer class="footer">
    <div>Dashboard</div>
  </footer>
</body>
</html>
