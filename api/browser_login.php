<?php
declare(strict_types=1);
require_once __DIR__.'/../lib/bootstrap.php';
space_require_method('POST');
$body=space_json_body();$name=trim((string)($body['name']??''));$password=(string)($body['password']??'');
if($name===''||$password==='')space_error('Teamname und Passwort werden benötigt.',422);
try{
    $result=space_with_world_lock(function(&$world,&$meta)use($name,$password){
        if(empty($world['ready']))space_error('Es gibt noch kein Universum.',409);
        $account=space_account_login((int)$world['seed'],$name,$password);
        if(!$account)space_error('Teamname oder Passwort ist falsch.',401);
        $teamId=(string)$account['team_id'];
        if(!isset($world['teams'][$teamId]))space_error('Dieses Team gehört nicht zum aktuellen Universum.',409);
        $world['teams'][$teamId]['last_seen_tick']=$world['tick'];
        $team=$world['teams'][$teamId];
        return ['id'=>$teamId,'name'=>$team['name'],'color'=>$team['color'],'token'=>$account['auth_token'],'code'=>(string)$account['saved_code']];
    },true);
    space_json($result);
}catch(Throwable $e){error_log('SPACE//AG login: '.$e->getMessage());space_error('Anmeldung fehlgeschlagen.',500);}
