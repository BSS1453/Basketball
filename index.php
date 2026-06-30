<?php
session_start();

// Lade die sichere Konfiguration
if (!file_exists('config.php')) {
    die("Fehler: Die Datei 'config.php' fehlt! Bitte erstelle sie zuerst.");
}
require_once 'config.php';

// Falls die scores.txt gelöscht wurde, erstelle sie neu mit Standardwerten
if (!file_exists($data_file)) {
    file_put_contents($data_file, "Alba Berlin | 0 | 0\nBerlin Baskets | 0 | 0");
}

$error_msg = "";
$success_msg = "";

// --- 🔐 ADMIN LOGIN LOGIK ---
if (isset($_POST['login'])) {
    $input_user = trim($_POST['username']);
    $input_pass = trim($_POST['password']);
    
    if ($input_user === $admin_user && $input_pass === $admin_pass) {
        $_SESSION['is_admin'] = true;
        // Seite neu laden, um POST-Resubmission zu verhindern
        header("Location: index.php#admin");
        exit;
    } else {
        $error_msg = "Airball! Falscher Username oder Passwort.";
    }
}

// --- 🚪 LOGOUT LOGIK ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// --- 💾 SCORE-SPEICHER LOGIK ---
if (isset($_POST['save_scores']) && isset($_SESSION['is_admin'])) {
    $raw_data = $_POST['score_data'];
    
    // Bereinige die Eingabe ein wenig (entferne unnötige Leerzeilen am Ende)
    $clean_data = trim($raw_data);
    
    if (file_put_contents($data_file, $clean_data) !== false) {
        $success_msg = "Slam Dunk! Die Tabelle wurde erfolgreich aktualisiert.";
    } else {
        $error_msg = "Fehler beim Schreiben auf den Server. Berechtigungen prüfen!";
    }
}

// --- 📊 DATEN AUSLESEN & AUTOMATISCH SORTIEREN ---
$current_scores = file_get_contents($data_file);
$lines = explode("\n", trim($current_scores));
$teams_list = [];

foreach ($lines as $line) {
    if (empty(trim($line))) continue;
    
    $parts = explode("|", $line);
    // Erwarte Format: Teamname | Spiele | Punkte
    if (count($parts) >= 3) {
        $teams_list[] = [
            'name' => trim($parts[0]),
            'matches' => trim($parts[1]),
            'points' => (int)trim($parts[2])
        ];
    }
}

// Sortiere das Array: Höchste Punktzahl nach oben
usort($teams_list, function($a, $b) {
    return $b['points'] - $a['points'];
});
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berlin Basketball Meisterschaft 2026</title>
    <style>
        :root {
            --bball-orange: #ff5500;
            --bball-glow: rgba(255, 85, 0, 0.4);
            --court-dark: #0d0d11;
            --court-grey: #1a1a24;
            --court-light: #252533;
            --text-light: #ffffff;
            --text-muted: #8e8e9f;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--court-dark);
            color: var(--text-light);
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 50% 30%, #1c1c28 0%, var(--court-dark) 70%),
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 100% 100%, 40px 40px, 40px 40px;
            padding-bottom: 60px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Hero Header mit absolutem Basketball-Feeling */
        header {
            text-align: center;
            padding: 60px 0 40px 0;
            position: relative;
        }

        header h1 {
            font-family: 'Impact', 'Arial Black', sans-serif;
            font-size: 3.5rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: var(--text-light);
            text-shadow: 0 0 15px var(--bball-glow), 3px 3px 0px var(--bball-orange);
            margin-bottom: 10px;
        }

        header .badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--bball-orange), #ff2200);
            color: white;
            padding: 6px 16px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
            border-radius: 50px;
            box-shadow: 0 4px 15px var(--bball-glow);
            letter-spacing: 1px;
        }

        /* Anzeigetafel (Scoreboard Table) */
        .scoreboard-wrapper {
            background-color: var(--court-grey);
            border: 2px solid #2d2d3f;
            border-top: 4px solid var(--bball-orange);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5), 0 0 20px rgba(255, 85, 0, 0.05);
            margin-bottom: 40px;
        }

        .scoreboard-title {
            font-family: 'Impact', sans-serif;
            font-size: 1.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            color: var(--bball-orange);
            border-bottom: 1px solid #2d2d3f;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            color: var(--text-muted);
            padding: 12px 16px;
            border-bottom: 2px solid #2d2d3f;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #2d2d3f;
            font-size: 1.1rem;
            font-weight: 500;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: rgba(255,255,255,0.02);
        }

        .col-rank { width: 80px; text-align: center; font-family: 'Impact', sans-serif; font-size: 1.3rem; color: var(--text-muted); }
        tr:nth-child(1) .col-rank { color: #ffd700; font-size: 1.6rem; text-shadow: 0 0 10px rgba(255,215,0,0.3); }
        tr:nth-child(2) .col-rank { color: #c0c0c0; }
        tr:nth-child(3) .col-rank { color: #cd7f32; }

        .col-team { font-weight: 600; }
        .col-matches { width: 100px; text-align: center; color: var(--text-muted); }
        .col-points { width: 100px; text-align: center; font-family: 'Impact', sans-serif; font-size: 1.4rem; color: var(--bball-orange); }

        /* Admin & Login Panels */
        .admin-zone {
            background-color: #11111a;
            border: 1px solid #222230;
            border-radius: 12px;
            padding: 30px;
            margin-top: 40px;
        }

        .panel-heading {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .inputs-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        input[type="text"], input[type="password"], textarea {
            width: 100%;
            background-color: var(--court-light);
            border: 1px solid #3d3d52;
            color: white;
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--bball-orange);
            box-shadow: 0 0 8px var(--bball-glow);
        }

        textarea {
            height: 160px;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            line-height: 1.5;
            font-size: 1.1rem;
        }

        .btn {
            background: linear-gradient(135deg, var(--bball-orange), #e04a00);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 6px;
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: transform 0.1s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 12px rgba(255, 85, 0, 0.2);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px var(--bball-glow);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .btn-logout {
            background: transparent;
            color: var(--text-muted);
            border: 1px solid #3d3d52;
            padding: 8px 16px;
            font-size: 0.85rem;
            text-decoration: none;
            border-radius: 4px;
            float: right;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            color: white;
            border-color: #ff3333;
            background-color: rgba(255, 51, 51, 0.05);
        }

        /* Hinweise & Alerts */
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .alert-error { background-color: rgba(255, 51, 51, 0.1); border-left: 4px solid #ff3333; color: #ff6666; }
        .alert-success { background-color: rgba(46, 204, 113, 0.1); border-left: 4px solid #2ecc71; color: #2ecc71; }

        .info-text {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 5px;
            line-height: 1.4;
        }

        footer {
            text-align: center;
            margin-top: 60px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        footer a {
            color: var(--bball-orange);
            text-decoration: none;
        }
        footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div class="badge">Berlin Tournaments</div>
        <h1>Championship 2026</h1>
    </header>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>
    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success">🏀 <?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>

    <div class="scoreboard-wrapper">
        <div class="scoreboard-title">Aktuelle Tabelle</div>
        <table>
            <thead>
                <tr>
                    <th class="col-rank">Platz</th>
                    <th class="col-team">Team</th>
                    <th class="col-matches">Spiele</th>
                    <th class="col-points">Punkte</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teams_list)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;">
                            Noch keine Teams eingetragen. Admin-Panel nutzen!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $rank = 1; ?>
                    <?php foreach ($teams_list as $team): ?>
                        <tr>
                            <td class="col-rank"><?php echo $rank++; ?></td>
                            <td class="col-team"><?php echo htmlspecialchars($team['name']); ?></td>
                            <td class="col-matches"><?php echo htmlspecialchars($team['matches']); ?></td>
                            <td class="col-points"><?php echo htmlspecialchars($team['points']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="admin-zone" id="admin">
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
            <div style="margin-bottom: 20px; overflow: hidden;">
                <a href="?logout=true" class="btn-logout">Abmelden</a>
                <h2 class="panel-heading" style="color: var(--bball-orange);">🛠 Trainer-Clipboard (Admin-Panel)</h2>
            </div>
            
            <form method="POST" action="index.php#admin">
                <div class="inputs-group">
                    <label for="score_data">Tabellendaten bearbeiten</label>
                    <textarea id="score_data" name="score_data" spellcheck="false"><?php echo htmlspecialchars($current_scores); ?></textarea>
                    <p class="info-text">
                        <strong>Regel:</strong> Jedes Team in eine eigene Zeile schreiben. <br>
                        <strong>Format:</strong> <code>Teamname | Spiele | Punkte</code> (getrennt durch das Pipe-Symbol "|").<br>
                        Die Tabelle sortiert die Teams nach dem Speichern automatisch nach Punkten!
                    </p>
                </div>
                <button type="submit" name="save_scores" class="btn">Tabelle Speichern</button>
            </form>

        <?php else: ?>
            <h2 class="panel-heading">🔐 Admin Login</h2>
            <form method="POST" action="index.php#admin">
                <div class="inputs-group">
                    <label for="username">Benutzername</label>
                    <input type="text" id="username" name="username" placeholder="z.B. berlin_admin" required>
                </div>
                <div class="inputs-group">
                    <label for="password">Passwort</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="btn">Einloggen</button>
            </form>
        <?php endif; ?>
    </div>

    <footer>
        &copy; 2026 Berlin Basketball Meisterschaft | Entwickelt mit echtem Court-Feeling 🏀
    </footer>
</div>

</body>
</html>
