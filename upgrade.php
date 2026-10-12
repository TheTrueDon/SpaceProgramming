<?php
declare(strict_types=1);
require_once __DIR__.'/lib/bootstrap.php';
if(!space_is_configured()){header('Location: install.php');exit;}
if(!space_admin_logged_in()){
    ?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SPACE//AG Upgrade</title><style>body{background:#05080d;color:#dbe7f1;font-family:system-ui;padding:40px}.card{max-width:650px;margin:auto;border:1px solid #284052;background:#09111a;padding:24px}a{color:#9deeff}</style></head><body><div class="card"><h1>SPACE//AG Upgrade 1.1</h1><p>Bitte zuerst im <a href="admin.php">Lehrerbereich</a> anmelden und anschließend diese Seite erneut öffnen.</p></div></body></html><?php exit;
}
$ok=false;$error='';
try{$pdo=space_pdo();$pdo->exec(space_accounts_schema_sql());$ok=true;}catch(Throwable $e){$error=$e->getMessage();}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SPACE//AG Upgrade</title><style>body{background:#05080d;color:#dbe7f1;font-family:system-ui;padding:40px}.card{max-width:720px;margin:auto;border:1px solid #284052;background:#09111a;padding:24px}a{color:#9deeff}.ok{color:#7ff0ae}.bad{color:#ff9aaa}</style></head><body><div class="card"><h1>SPACE//AG Upgrade 1.1</h1><?php if($ok):?><p class="ok"><b>Datenbankschema ist bereit.</b></p><p>Falls bereits Teams aus Web Edition 1.0 existieren, erzeuge im Lehrerbereich einmal ein <b>neues Universum</b>. Ab dann können Teams mit Teamname + Passwort angelegt und dauerhaft wieder angemeldet werden.</p><p><a href="admin.php">Zurück zum Lehrerbereich</a></p><?php else:?><p class="bad"><b>Fehler:</b> <?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></p><?php endif?></div></body></html>
