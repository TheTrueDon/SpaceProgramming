<?php
declare(strict_types=1);
require_once __DIR__.'/lib/config.php';
require_once __DIR__.'/lib/db.php';
require_once __DIR__.'/lib/engine.php';

$configFile = __DIR__.'/config.local.php';
$already = is_file($configFile) && space_is_configured();
$error = '';
$success = false;
$manualConfig = '';

if (!$already && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $host = trim((string)($_POST['db_host'] ?? 'localhost')) ?: 'localhost';
    $port = max(1,(int)($_POST['db_port'] ?? 3306));
    $name = trim((string)($_POST['db_name'] ?? ''));
    $user = trim((string)($_POST['db_user'] ?? ''));
    $pass = (string)($_POST['db_pass'] ?? '');
    $adminPassword = (string)($_POST['admin_password'] ?? '');
    $tick = max(0.15,min(6.0,(float)($_POST['tick_seconds'] ?? 1.5)));
    $maxTeams = max(2,min(100,(int)($_POST['max_teams'] ?? 30)));
    try {
        if ($name==='' || $user==='') throw new RuntimeException('Datenbankname und Datenbankbenutzer fehlen.');
        if (strlen($adminPassword) < 8) throw new RuntimeException('Das Lehrer-Passwort muss mindestens 8 Zeichen lang sein.');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',$host,$port,$name);
        $pdo = new PDO($dsn,$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
        $pdo->exec(space_schema_sql());
        require_once __DIR__.'/lib/accounts.php';
        $pdo->exec(space_accounts_schema_sql());
        $world = space_empty_world();
        $stmt = $pdo->prepare('INSERT INTO space_ag_state (id,state_json,last_tick_at,tick_seconds) VALUES (1,?,?,?) ON DUPLICATE KEY UPDATE state_json=VALUES(state_json),last_tick_at=VALUES(last_tick_at),tick_seconds=VALUES(tick_seconds)');
        $stmt->execute([json_encode($world,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),microtime(true),$tick]);
        $config = [
            'db_host'=>$host,'db_port'=>$port,'db_name'=>$name,'db_user'=>$user,'db_pass'=>$pass,
            'admin_password_hash'=>password_hash($adminPassword,PASSWORD_DEFAULT),
            'max_teams'=>$maxTeams,'max_catchup_ticks'=>8,'default_tick_seconds'=>$tick,'session_name'=>'SPACEAGADMIN',
        ];
        $manualConfig = "<?php\ndeclare(strict_types=1);\nreturn " . var_export($config,true) . ";\n";
        if (@file_put_contents($configFile,$manualConfig,LOCK_EX) === false) {
            throw new RuntimeException('Die Datenbank wurde eingerichtet, aber config.local.php konnte nicht geschrieben werden. Speichere den unten angezeigten Inhalt manuell als config.local.php im Hauptordner.');
        }
        @chmod($configFile,0600);
        $success = true;
    } catch (Throwable $e) { $error = $e->getMessage(); }
}
?>
<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SPACE//AG Installation</title>
<style>body{margin:0;background:#05080d;color:#dbe7f1;font-family:system-ui,sans-serif}.wrap{max-width:760px;margin:45px auto;padding:0 18px}.card{background:#09111a;border:1px solid #1c2b39;padding:22px;margin:14px 0}h1{font-family:ui-monospace,monospace;letter-spacing:.08em}h1 span{color:#44d9ff}label{display:block;color:#91a5b8;font-size:13px;margin:12px 0 5px}input{width:100%;padding:10px;background:#050a10;border:1px solid #294052;color:#e4eef7;box-sizing:border-box}.grid{display:grid;grid-template-columns:2fr 1fr;gap:10px}.btn{display:inline-block;padding:11px 15px;background:#0a1b24;border:1px solid #34788a;color:#9deeff;font-weight:700;text-decoration:none;cursor:pointer;margin-top:16px}.bad{border-color:#743b49;color:#ffb5c0}.ok{border-color:#285f46;color:#7ff0ae}code,pre{font-family:ui-monospace,monospace}pre{white-space:pre-wrap;word-break:break-all;background:#04070b;padding:12px;border:1px solid #283746;font-size:11px}</style></head><body><div class="wrap"><h1>SPACE<span>//AG</span> WEB INSTALLATION</h1>
<?php if($already): ?><div class="card ok"><b>Bereits eingerichtet.</b><p>Die Datei <code>config.local.php</code> existiert. Aus Sicherheitsgründen kann der Web-Installer jetzt nichts mehr überschreiben.</p><a class="btn" href="admin.php">Lehrerbereich öffnen</a> <a class="btn" href="team.php">Team-Editor öffnen</a></div>
<?php elseif($success): ?><div class="card ok"><b>Installation abgeschlossen.</b><p>Datenbank und Konfiguration sind eingerichtet.</p><a class="btn" href="admin.php">Lehrerbereich öffnen</a></div>
<?php else: ?>
<div class="card"><p>Lege im Hosting-Panel zuerst eine leere MySQL/MariaDB-Datenbank an. Trage anschließend deren Zugangsdaten hier ein. SPACE//AG erzeugt darin zwei eigene Tabellen: Spielzustand und Teamkonten/gespeicherter Code.</p>
<?php if($error): ?><div class="card bad"><b>Fehler:</b> <?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?><?php if($manualConfig): ?><p>Manuelle Konfiguration:</p><pre><?=htmlspecialchars($manualConfig,ENT_QUOTES,'UTF-8')?></pre><?php endif ?></div><?php endif ?>
<form method="post"><label>Datenbank-Host</label><div class="grid"><input name="db_host" value="<?=htmlspecialchars($_POST['db_host']??'localhost')?>"><input name="db_port" type="number" value="<?=htmlspecialchars($_POST['db_port']??'3306')?>"></div><label>Datenbankname</label><input name="db_name" required value="<?=htmlspecialchars($_POST['db_name']??'')?>"><label>Datenbankbenutzer</label><input name="db_user" required value="<?=htmlspecialchars($_POST['db_user']??'')?>"><label>Datenbankpasswort</label><input name="db_pass" type="password" value=""><label>Lehrer/Admin-Passwort (mindestens 8 Zeichen)</label><input name="admin_password" type="password" minlength="8" required><div class="grid"><div><label>Tickdauer in Sekunden</label><input name="tick_seconds" type="number" min="0.15" max="6" step="0.05" value="<?=htmlspecialchars($_POST['tick_seconds']??'1.5')?>"></div><div><label>Max. Teams</label><input name="max_teams" type="number" min="2" max="100" value="<?=htmlspecialchars($_POST['max_teams']??'30')?>"></div></div><button class="btn" type="submit">INSTALLIEREN</button></form></div><?php endif ?>
</div></body></html>
