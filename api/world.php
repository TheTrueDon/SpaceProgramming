<?php
declare(strict_types=1);require_once __DIR__.'/../lib/bootstrap.php';
try { space_json(space_with_world_lock(function(&$w,&$m){$p=space_public_world($w);$p['tick_seconds']=(float)$m['tick_seconds'];return $p;},true)); } catch(Throwable $e){ error_log('SPACE//AG: '.$e->getMessage());space_error('Interner Serverfehler.',500); }
