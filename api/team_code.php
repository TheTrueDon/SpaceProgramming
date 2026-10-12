<?php
declare(strict_types=1);
require_once __DIR__.'/../lib/bootstrap.php';
$teamId=(string)($_GET['team_id']??'');$token=(string)($_GET['token']??'');
if($teamId===''||$token==='')space_error('Team-ID oder Token fehlt.',401);
try{
    $seed=space_with_world_lock(function(&$world,&$meta)use($teamId,$token){
        if(empty($world['ready'])||!space_verify_team($world,$teamId,$token))space_error('Ungültiges Team-Token.',401);
        return (int)$world['seed'];
    },false);
    $account=space_account_verify($seed,$teamId,$token);if(!$account)space_error('Teamkonto nicht gefunden.',401);
    if(($_SERVER['REQUEST_METHOD']??'GET')==='GET')space_json(['code'=>(string)$account['saved_code']]);
    space_require_method('POST');$body=space_json_body();$code=(string)($body['code']??'');
    space_account_save_code($seed,$teamId,$token,$code);space_json(['ok'=>true]);
}catch(InvalidArgumentException $e){space_error($e->getMessage(),422);}catch(Throwable $e){error_log('SPACE//AG code: '.$e->getMessage());space_error('Code konnte nicht gespeichert werden.',500);}
