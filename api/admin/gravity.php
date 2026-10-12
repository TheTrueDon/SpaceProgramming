<?php
declare(strict_types=1);require_once __DIR__.'/../../lib/bootstrap.php';space_require_method('POST');space_require_admin_api();$b=space_json_body();$enabled=(bool)($b['enabled']??false);
try{$r=space_with_world_lock(function(&$w,&$m)use($enabled){$w['gravity_enabled']=$enabled;$w['logs'][]=$enabled?'Gravitation aktiviert.':'Gravitation deaktiviert.';return ['ok'=>true,'gravity_enabled'=>$enabled];},true);space_json($r);}catch(Throwable $e){error_log('SPACE//AG: '.$e->getMessage());space_error('Interner Serverfehler.',500);}
