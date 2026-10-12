<?php
declare(strict_types=1);require_once __DIR__.'/../../lib/bootstrap.php';space_require_method('POST');space_require_admin_api();
try{$r=space_with_world_lock(function(&$w,&$m){if(empty($w['ready']))space_error('Noch kein Universum erzeugt.',409);$was=!empty($w['running']);$w['running']=true;space_run_tick($w);$w['running']=$was;$m['last_tick_at']=microtime(true);return ['ok'=>true,'tick'=>$w['tick']];},false);space_json($r);}catch(Throwable $e){error_log('SPACE//AG: '.$e->getMessage());space_error('Interner Serverfehler.',500);}
