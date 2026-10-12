<?php
declare(strict_types=1);require_once __DIR__.'/../lib/bootstrap.php';
try { $base=space_base_url(); $data=space_with_world_lock(fn(&$w,&$m)=>['tick_seconds'=>$m['tick_seconds']],false); space_json(['client_url'=>$base,'student_url'=>$base.'/team.php','admin_url'=>$base.'/admin.php','tick_seconds'=>$data['tick_seconds']]); } catch(Throwable $e){ error_log('SPACE//AG: '.$e->getMessage());space_error('Interner Serverfehler.',500); }
