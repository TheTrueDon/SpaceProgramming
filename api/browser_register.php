<?php
declare(strict_types=1);
require_once __DIR__.'/../lib/bootstrap.php';
space_require_method('POST');
$body=space_json_body();
$name=trim((string)($body['name']??''));
$color=(string)($body['color']??'#44d9ff');
$password=(string)($body['password']??'');
$code=(string)($body['code']??'');
if($name===''||strlen($name)>80)space_error('Teamname fehlt oder ist zu lang.',422);
if(strlen($password)<4)space_error('Das Team-Passwort muss mindestens 4 Zeichen lang sein.',422);
if(!preg_match('/^#[0-9a-fA-F]{6}$/',$color))$color='#44d9ff';
try{
    $result=space_with_world_lock(function(&$world,&$meta)use($name,$color,$password,$code){
        if(empty($world['ready']))space_error('Der Lehrer muss zuerst ein Universum erzeugen.',409);
        $c=space_config();if(count($world['teams'])>=(int)$c['max_teams'])space_error('Maximale Teamzahl erreicht.',409);
        $clean=strtolower(trim($name));foreach($world['teams'] as $team)if(strtolower(trim((string)$team['name']))===$clean)space_error('Dieser Teamname ist bereits vergeben. Nutzt „Vorhandenes Team“, wenn es euer Team ist.',409);
        $team=space_add_team($world,substr($name,0,40),$color,null,space_random_token(18));
        space_account_create((int)$world['seed'],$team['id'],$team['name'],$password,$team['token'],$code);
        return ['id'=>$team['id'],'name'=>$team['name'],'color'=>$team['color'],'token'=>$team['token'],'code'=>$code];
    },true);
    space_json($result);
}catch(Throwable $e){error_log('SPACE//AG register: '.$e->getMessage());space_error('Team konnte nicht angelegt werden.',500);}
