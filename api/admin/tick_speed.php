<?php
declare(strict_types=1);require_once __DIR__.'/../../lib/bootstrap.php';space_require_method('POST');space_require_admin_api();$b=space_json_body();$seconds=max(0.15,min(6.0,(float)($b['tick_seconds']??1.5)));
try{$r=space_with_world_lock(function(&$w,&$m)use($seconds){$m['tick_seconds']=$seconds;$m['last_tick_at']=microtime(true);$w['logs'][]='Tickdauer auf '.number_format($seconds,2,',','').' s gesetzt.';return ['ok'=>true,'tick_seconds'=>$seconds];},true);space_json($r);}catch(Throwable $e){error_log('SPACE//AG: '.$e->getMessage());space_error('Interner Serverfehler.',500);}
