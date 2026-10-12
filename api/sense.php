<?php
declare(strict_types=1);require_once __DIR__.'/../lib/bootstrap.php';
$teamId=(string)($_GET['team_id']??'');$token=(string)($_GET['token']??'');if($teamId===''||$token==='')space_error('Team-ID oder Token fehlt.',401);
try{$payload=space_with_world_lock(function(&$world,&$meta)use($teamId,$token){if(!space_verify_team($world,$teamId,$token))space_error('Ungültiges Team-Token.',401);$world['teams'][$teamId]['last_seen_tick']=$world['tick'];$p=space_sensor_payload($world,$teamId);$p['tick_seconds']=(float)$meta['tick_seconds'];$p['running']=(bool)$world['running'];return $p;},true);space_json($payload);}catch(Throwable $e){error_log('SPACE//AG: '.$e->getMessage());space_error('Interner Serverfehler.',500);}
