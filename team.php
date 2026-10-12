<?php
declare(strict_types=1);
require_once __DIR__.'/lib/config.php';
if (!space_is_configured()) { header('Location: install.php'); exit; }
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>SPACE//AG – Team Editor</title>
<style>
:root{--bg:#05080d;--panel:#09111a;--panel2:#0c1621;--line:#1c2b39;--text:#dbe7f1;--muted:#71869a;--accent:#44d9ff;--ok:#54ef9b;--bad:#ff6b7d;--warn:#ffc857}
*{box-sizing:border-box}html,body{height:100%;margin:0;background:var(--bg);color:var(--text);font-family:Inter,system-ui,sans-serif}button,input,select,textarea{font:inherit}
#app{height:100%;display:grid;grid-template-rows:56px 1fr;min-height:0}header{display:flex;align-items:center;gap:12px;padding:0 18px;border-bottom:1px solid var(--line);background:#070c12}.brand{font:700 18px ui-monospace,monospace;letter-spacing:.08em}.brand span{color:var(--accent)}.badge{border:1px solid var(--line);padding:5px 8px;font:11px ui-monospace,monospace;color:var(--muted)}.badge strong{color:var(--text)}#conn.ok strong{color:var(--ok)}#conn.bad strong{color:var(--bad)}.spacer{flex:1}.student-url{font:11px ui-monospace,monospace;color:var(--muted)}
main{min-height:0;display:grid;grid-template-columns:minmax(0,1fr) 330px}.workspace{min-width:0;min-height:0;display:grid;grid-template-rows:auto minmax(0,1fr) 190px}.workbody{min-height:0;display:grid;grid-template-columns:minmax(0,1.2fr) minmax(350px,.8fr)}.toolbar{display:flex;align-items:center;gap:8px;padding:10px 12px;border-bottom:1px solid var(--line);background:#070d14}.toolbar button,.side button{border:1px solid #294153;background:#0d1823;color:#cfe0ec;padding:8px 11px;cursor:pointer}.toolbar button:hover,.side button:hover{border-color:#4a718b}.toolbar button.primary{border-color:#208b62;color:#8fffc8}.toolbar button.stop{border-color:#7d3040;color:#ff98a6}.toolbar button:disabled{opacity:.4;cursor:not-allowed}.toolbar select{background:#0b141e;color:#cbdbe7;border:1px solid #263b4c;padding:7px}.hint{color:var(--muted);font-size:11px}.editor-wrap{position:relative;min-height:0;background:#060a10;border-right:1px solid var(--line)}.editor-wrap:before{content:'JAVASCRIPT';position:absolute;right:12px;top:9px;color:#26394a;font:700 11px ui-monospace,monospace;pointer-events:none}#code{width:100%;height:100%;resize:none;border:0;outline:0;background:transparent;color:#d9e7f1;padding:18px 20px;font:14px/1.55 ui-monospace,SFMono-Regular,Consolas,monospace;tab-size:4;white-space:pre;overflow:auto;caret-color:#7ee7ff}.console{border-top:1px solid var(--line);background:#05090e;display:grid;grid-template-rows:30px 1fr;min-height:0}.console-head{display:flex;align-items:center;justify-content:space-between;padding:0 12px;color:#6f8497;font:10px ui-monospace,monospace;letter-spacing:.08em}.console-head button{background:none;border:0;color:#6f8497;cursor:pointer}.console-body{overflow:auto;padding:5px 12px 12px;font:11px/1.45 ui-monospace,monospace}.log{white-space:pre-wrap;color:#9bb0c2}.log.err{color:#ff8392}.log.ok{color:#72e8a8}.log.sys{color:#71869a}
.fleet-view{min-width:0;min-height:0;display:grid;grid-template-rows:34px minmax(0,1fr) 28px;background:#04080d}.fleet-head{display:flex;align-items:center;justify-content:space-between;padding:0 10px;border-bottom:1px solid var(--line);font:10px ui-monospace,monospace;letter-spacing:.08em;color:#7890a4}.fleet-head b{color:#b9d8e9}.fleet-canvas-wrap{position:relative;min-height:0;overflow:hidden}.fleet-canvas-wrap canvas{display:block;width:100%;height:100%;background:#03070b}.fleet-foot{display:flex;align-items:center;justify-content:space-between;padding:0 9px;border-top:1px solid #101d29;color:#61788b;font:9px ui-monospace,monospace}.save-state{font:10px ui-monospace,monospace;color:#6f8497}.save-state.ok{color:#72e8a8}.save-state.bad{color:#ff8392}.mode-tabs{display:grid;grid-template-columns:1fr 1fr;gap:7px;margin:12px 0}.mode-tabs button{margin:0}.mode-tabs button.active{border-color:#44d9ff;color:#9deeff;background:#0b1d29}.create-only.hidden{display:none}.setup-actions{margin-top:8px}.map-legend span{margin-left:8px}.map-legend i{display:inline-block;width:7px;height:7px;border-radius:50%;margin-right:3px;vertical-align:middle}.map-legend .own{background:#54ef9b}.map-legend .enemy{background:#ff6b7d}.map-legend .planet{background:#8aa0b5}
.side{border-left:1px solid var(--line);background:#080e15;overflow:auto;padding:14px}.side h3{margin:18px 0 8px;color:#63798c;font:700 10px ui-monospace,monospace;letter-spacing:.13em}.side h3:first-child{margin-top:0}.card{border:1px solid var(--line);background:#0a121b;padding:11px;margin-bottom:9px}.field{display:grid;gap:5px;margin-bottom:9px}.field label{font:10px ui-monospace,monospace;color:#71869a}.field input{width:100%;background:#060b11;border:1px solid #263746;color:#dce8f1;padding:8px}.row{display:flex;gap:7px}.row>*{flex:1}.team-color{height:36px;padding:2px!important}.statusline{display:flex;justify-content:space-between;gap:8px;border-bottom:1px solid #13212d;padding:5px 0;font:11px ui-monospace,monospace;color:#71869a}.statusline:last-child{border:0}.statusline b{color:#d9e5ef}.api code{display:block;color:#a9c1d3;font:11px/1.65 ui-monospace,monospace}.api em{color:#6b8092;font-style:normal}.notice{color:#8499aa;font-size:11px;line-height:1.5}.notice strong{color:#dbe7f1}.setup{position:fixed;inset:56px 0 0;z-index:5;background:#05080df2;display:flex;align-items:center;justify-content:center;padding:20px}.setup.hidden{display:none}.setup-box{width:min(460px,100%);border:1px solid #284052;background:#09111a;padding:24px;box-shadow:0 30px 80px #0008}.setup-box h1{font:700 20px ui-monospace,monospace;margin:0 0 8px}.setup-box p{color:#8297a8;font-size:13px;line-height:1.55}.setup-box button{width:100%;margin-top:7px;background:#0c1a22;border:1px solid #2d7b69;color:#7bf0b5;padding:10px;cursor:pointer}.dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#40515f;margin-right:6px}.dot.on{background:var(--ok);box-shadow:0 0 7px #54ef9b88}.dot.run{background:var(--accent);box-shadow:0 0 7px #44d9ff88}@media(max-width:1150px){.workbody{grid-template-columns:minmax(0,1fr) 360px}}@media(max-width:900px){main{grid-template-columns:1fr}.side{display:none}.workspace{grid-template-rows:auto minmax(0,1fr) 160px}.workbody{grid-template-columns:minmax(0,1fr) minmax(300px,42%)}.student-url{display:none}}@media(max-width:680px){.workbody{grid-template-columns:1fr}.fleet-view{display:none}}
</style>
</head>
<body>
<div id="app">
<header>
  <div class="brand">SPACE<span>//AG</span> <span style="color:#657b8d">EDITOR</span></div>
  <div id="conn" class="badge bad"><span class="dot"></span><strong>OFFLINE</strong></div>
  <div class="badge">TEAM <strong id="teamBadge">–</strong></div>
  <div class="badge">TICK <strong id="tickBadge">–</strong></div><div id="saveState" class="save-state">CODE: lokal</div>
  <div class="spacer"></div><div class="student-url" id="serverLabel"></div>
</header>
<main>
<section class="workspace">
  <div class="toolbar">
    <button id="runBtn" class="primary" onclick="startBot()">▶ START</button>
    <button id="stopBtn" class="stop" onclick="stopBot()" disabled>■ STOP</button>
    <button onclick="saveCode()">Speichern</button>
    <select id="exampleSelect" onchange="loadExample(this.value)">
      <option value="">Beispiel laden…</option>
      <option value="starter">Minimal</option>
      <option value="colonizer">Kolonisierer</option>
      <option value="hunter">Jäger</option>
    </select>
    <span class="hint">Strg+S: speichern · Tab funktioniert im Editor</span>
  </div>
  <div class="workbody">
    <div class="editor-wrap"><textarea id="code" spellcheck="false"></textarea></div>
    <div class="fleet-view">
      <div class="fleet-head"><span>FLOTTENANSICHT · SENSORBILD</span><b id="viewStatus">warte…</b></div>
      <div class="fleet-canvas-wrap"><canvas id="fleetCanvas"></canvas></div>
      <div class="fleet-foot"><span>Automatischer Zoom auf eure Flotte + erkannte Objekte</span><span class="map-legend"><span><i class="own"></i>eigene</span><span><i class="enemy"></i>Kontakt</span><span><i class="planet"></i>Planet</span></span></div>
    </div>
  </div>
  <div class="console"><div class="console-head"><span>KONSOLE / BOT-LOG</span><button onclick="clearConsole()">LEEREN</button></div><div id="console" class="console-body"></div></div>
</section>
<aside class="side">
  <h3>TEAM</h3>
  <div class="card">
    <div class="statusline"><span>Status</span><b id="botStatus">gestoppt</b></div>
    <div class="statusline"><span>Schiffe</span><b id="statShips">–</b></div>
    <div class="statusline"><span>Fuel-Vorräte</span><b id="statFuel">–</b></div>
    <div class="statusline"><span>Metall-Vorräte</span><b id="statMetal">–</b></div>
    <div class="statusline"><span>Punkte</span><b id="statScore">–</b></div>
  </div>
  <button style="width:100%" onclick="changeTeam()">Team wechseln</button>

  <h3>SCHIFF-BEFEHLE</h3>
  <div class="card api">
    <code>schiff.schub(0.5)</code><code>schiff.drehen(8)</code><code>schiff.landen()</code><code>schiff.starten()</code><code>schiff.tanken()</code><code>schiff.abbauen("beides")</code><code>schiff.kolonisieren()</code><code>schiff.erobern()</code><code>schiff.angreifen(ziel)</code><code>schiff.bauen("fighter")</code><code>schiff.upgrade("waffen")</code><code>schiff.ressourcen_teilen(true)</code>
  </div>
  <h3>UPGRADES (LEVEL 0–3)</h3>
  <div class="card api">
    <code>schiff.upgrade("geschwindigkeit")</code><code>schiff.upgrade("sensoren")</code><code>schiff.upgrade("waffen")</code><code>schiff.upgrade("schilde")</code><code>schiff.upgrade("fracht")</code><code>schiff.upgrade("tank")</code>
    <div class="notice" style="margin-top:7px">Rollen wie <strong>"fighter"</strong> oder <strong>"miner"</strong> sind frei erfundene Labels für eure eigene Programmlogik.</div>
  </div>
  <h3>WICHTIGE WERTE</h3>
  <div class="card api">
    <code>schiff.rolle</code><code>schiff.richtung</code><code>schiff.geschwindigkeit</code><code>schiff.treibstoff</code><code>schiff.fracht_metall</code><code>schiff.fracht_treibstoff</code><code>schiff.schild</code><code>schiff.gelandet</code><code>sensoren.tick</code><code>sensoren.planeten</code><code>sensoren.schiffe</code><code>sensoren.ereignisse</code><code>planet.entfernung</code><code>planet.richtung</code><code>planet.radius</code><code>planet.groesse</code><code>planet.ressourcenprofil</code><code>planet.treibstoff_rate</code><code>planet.metall_rate</code><code>planet.treibstoff_max</code><code>planet.metall_max</code><code>planet.besitzer</code><code>sensoren.tick_dauer</code>
  </div>
  <h3>SICHERHEIT</h3>
  <div class="card notice">Der Code läuft <strong>im Browser</strong> in einem separaten Worker. Eine Endlosschleife wird automatisch abgebrochen. Serverzugriff bekommt nur die feste SPACE//AG-Schnittstelle.</div>
</aside>
</main>
</div>

<div id="setup" class="setup">
  <div class="setup-box">
    <h1>TEAM VERBINDEN</h1>
    <p>Ein Team bleibt auf dem Server gespeichert. Ihr könnt euch später auch an einem anderen Rechner wieder anmelden und mit derselben Flotte und demselben Programmcode weiterarbeiten.</p>
    <div class="mode-tabs"><button id="modeLogin" class="active" onclick="setSetupMode('login')">VORHANDENES TEAM</button><button id="modeCreate" onclick="setSetupMode('create')">NEUES TEAM</button></div>
    <div class="field"><label>TEAMNAME</label><input id="setupName" maxlength="40" autocomplete="username" placeholder="z. B. Nebelkrähen"></div>
    <div class="field"><label>TEAM-PASSWORT</label><input id="setupPassword" type="password" minlength="4" autocomplete="current-password" placeholder="mindestens 4 Zeichen"></div>
    <div id="createColor" class="field create-only hidden"><label>TEAMFARBE</label><input id="setupColor" class="team-color" type="color" value="#44d9ff"></div>
    <button id="setupSubmit" class="setup-actions" onclick="submitSetup()">ANMELDEN & CODE LADEN</button>
    <div id="setupError" class="notice" style="margin-top:10px;color:#ff8392"></div>
    <div class="notice" style="margin-top:10px">Das Passwort wird nicht im Browser gespeichert. Auf diesem Rechner bleibt nur eine Anmeldesitzung erhalten.</div>
  </div>
</div>
<script>
const STORAGE_PREFIX='spaceag.web.v11.';
const SESSION_KEY=STORAGE_PREFIX+'session';
let session=loadJson(SESSION_KEY,null);
let worker=null, botRunning=false, loopTimer=null, lastTick=-1, requestSeq=1, pendingWorker=new Map();
const $=id=>document.getElementById(id);

const EXAMPLES={
starter:`function steuere(schiff, sensoren) {
    // Ohne schub() und drehen() bleibt die Bewegung unveraendert.
    // Beides kostet Treibstoff.
    if (schiff.gelandet) {
        schiff.tanken();
        schiff.starten();
        return;
    }

    if (schiff.treibstoff > 20 && schiff.geschwindigkeit < 5) {
        schiff.schub(0.3);
    }
}`,
colonizer:`function winkelDifferenz(ziel, aktuell) {
    return (ziel - aktuell + 540) % 360 - 180;
}

function fliegeZu(schiff, planet) {
    const oberflaeche = Math.max(0, planet.entfernung - planet.radius);
    const zieltempo = oberflaeche < 100 ? 1.0 : oberflaeche < 320 ? 2.5 : oberflaeche < 900 ? 5.5 : 8.5;
    const rad = planet.richtung * Math.PI / 180;
    const zielVx = Math.cos(rad) * zieltempo;
    const zielVy = Math.sin(rad) * zieltempo;
    const dvx = zielVx - schiff.vx;
    const dvy = zielVy - schiff.vy;
    const korrektur = Math.hypot(dvx, dvy);
    if (korrektur > 0.08) {
        const schubrichtung = (Math.atan2(dvy, dvx) * 180 / Math.PI + 360) % 360;
        const delta = winkelDifferenz(schubrichtung, schiff.richtung);
        if (Math.abs(delta) > 0.5) schiff.drehen(Math.max(-10, Math.min(10, delta)));
        if (Math.abs(delta) < 9) schiff.schub(Math.min(0.7, Math.max(0.15, korrektur / 3)));
    }
    if (oberflaeche < 14 && schiff.geschwindigkeit < 2.2) schiff.landen();
}

function steuere(schiff, sensoren) {
    if (schiff.gelandet) {
        const planet = sensoren.planeten.find(p => p.id === schiff.gelandet_auf);
        schiff.tanken();

        if (planet && planet.besitzer === null) {
            // Fuer eine Kolonie werden 40 Metall aus der Fracht benoetigt.
            if (schiff.fracht_metall >= 40) schiff.kolonisieren();
            else schiff.abbauen("metall");
        } else if (planet && planet.besitzer === schiff.team) {
            // Erst genug Metall fuer die naechste Kolonie laden.
            if (schiff.fracht_metall < 55) {
                schiff.abbauen("metall");
                return;
            }
        } else if (planet && planet.besitzer !== schiff.team) {
            schiff.erobern();
            return;
        }

        schiff.starten();
        return;
    }

    const freie = sensoren.planeten
        .filter(p => p.besitzer === null)
        .sort((a,b) => a.entfernung - b.entfernung);
    if (freie.length) {
        fliegeZu(schiff, freie[0]);
        return;
    }

    const suchrichtung = (Math.floor(sensoren.tick / 120) * 67 + schiff.nummer * 31) % 360;
    const delta = winkelDifferenz(suchrichtung, schiff.richtung);
    if (Math.abs(delta) > 1) schiff.drehen(Math.max(-10, Math.min(10, delta)));
    if (Math.abs(delta) < 12 && schiff.geschwindigkeit < 6) schiff.schub(0.25);
}`,
hunter:`function winkelDifferenz(ziel, aktuell) {
    return (ziel - aktuell + 540) % 360 - 180;
}

function fliegeZu(schiff, planet) {
    const oberflaeche = Math.max(0, planet.entfernung - planet.radius);
    const zieltempo = oberflaeche < 100 ? 1.0 : oberflaeche < 320 ? 2.5 : oberflaeche < 900 ? 5.5 : 8.5;
    const rad = planet.richtung * Math.PI / 180;
    const zielVx = Math.cos(rad) * zieltempo;
    const zielVy = Math.sin(rad) * zieltempo;
    const dvx = zielVx - schiff.vx;
    const dvy = zielVy - schiff.vy;
    const korrektur = Math.hypot(dvx, dvy);
    if (korrektur > 0.08) {
        const schubrichtung = (Math.atan2(dvy, dvx) * 180 / Math.PI + 360) % 360;
        const delta = winkelDifferenz(schubrichtung, schiff.richtung);
        if (Math.abs(delta) > 0.5) schiff.drehen(Math.max(-10, Math.min(10, delta)));
        if (Math.abs(delta) < 9) schiff.schub(Math.min(0.7, Math.max(0.15, korrektur / 3)));
    }
    if (oberflaeche < 14 && schiff.geschwindigkeit < 2.2) schiff.landen();
}

function steuere(schiff, sensoren) {
    // Selbst erfundene Rollen sind nur Labels fuer die eigene Programmlogik.
    if (schiff.rolle === "fighter") {
        const gegner = sensoren.schiffe
            .filter(s => s.team !== schiff.team)
            .sort((a,b) => a.entfernung - b.entfernung);
        if (gegner.length) {
            const ziel = gegner[0];
            const delta = winkelDifferenz(ziel.richtung, schiff.richtung);
            if (Math.abs(delta) > 1) schiff.drehen(Math.max(-10, Math.min(10, delta)));
            if (ziel.entfernung <= schiff.waffenreichweite) schiff.angreifen(ziel);
            else if (Math.abs(delta) < 12 && schiff.geschwindigkeit < 7) schiff.schub(0.35);
            return;
        }
    }

    // Ein Treffer erscheint fuer genau diesen Tick in sensoren.ereignisse.
    if (sensoren.ereignisse.some(e => e.typ === "getroffen")) {
        console.log("Treffer registriert!", sensoren.ereignisse);
    }

    if (schiff.gelandet) {
        const planet = sensoren.planeten.find(p => p.id === schiff.gelandet_auf);
        schiff.tanken();
        schiff.ressourcen_teilen(true);
        if (planet && planet.besitzer === schiff.team) {
            if (schiff.fracht_metall < 70) {
                schiff.abbauen("metall");
                return;
            }
            if (schiff.rolle !== "fighter" && schiff.fracht_metall >= 60) {
                // Der Rollenname ist frei gewaehlt; er veraendert keine Werte.
                schiff.bauen("fighter");
            }
        } else if (planet && planet.besitzer && planet.besitzer !== schiff.team) {
            schiff.erobern();
            return;
        }
        schiff.starten();
        return;
    }

    const gegner = sensoren.schiffe
        .filter(s => s.team !== schiff.team)
        .sort((a,b) => a.entfernung - b.entfernung);
    if (gegner.length) {
        const ziel = gegner[0];
        const delta = winkelDifferenz(ziel.richtung, schiff.richtung);
        if (Math.abs(delta) > 1) schiff.drehen(Math.max(-10, Math.min(10, delta)));
        if (ziel.entfernung <= schiff.waffenreichweite) schiff.angreifen(ziel);
        else if (Math.abs(delta) < 12 && schiff.geschwindigkeit < 7) schiff.schub(0.35);
        return;
    }

    const freie = sensoren.planeten
        .filter(p => p.besitzer === null)
        .sort((a,b) => a.entfernung - b.entfernung);
    if (freie.length) {
        fliegeZu(schiff, freie[0]);
        return;
    }
    if (schiff.treibstoff > 15 && schiff.geschwindigkeit < 5) schiff.schub(0.2);
}`
};

function loadJson(k,d){try{return JSON.parse(localStorage.getItem(k))??d}catch{return d}}
function codeKey(){return STORAGE_PREFIX+'code.'+(session?.teamId||session?.name||'new')}
function log(text,kind=''){const box=$('console'),line=document.createElement('div');line.className='log '+kind;line.textContent=text;box.appendChild(line);while(box.children.length>180)box.firstChild.remove();box.scrollTop=box.scrollHeight}
function clearConsole(){$('console').innerHTML=''}
function setConnection(ok,text){const e=$('conn');e.className='badge '+(ok?'ok':'bad');e.innerHTML=`<span class="dot ${ok?'on':''}"></span><strong>${text}</strong>`}
function setSaveState(text,kind=''){$('saveState').textContent='CODE: '+text;$('saveState').className='save-state '+kind}

let setupMode='login';
function setSetupMode(mode){setupMode=mode;const create=mode==='create';$('modeLogin').classList.toggle('active',!create);$('modeCreate').classList.toggle('active',create);$('createColor').classList.toggle('hidden',!create);$('setupSubmit').textContent=create?'NEUES TEAM ANLEGEN':'ANMELDEN & CODE LADEN';$('setupPassword').autocomplete=create?'new-password':'current-password';$('setupError').textContent=''}
function showSetup(mode='login',message=''){setSetupMode(mode);$('setup').classList.remove('hidden');if(message)$('setupError').textContent=message;$('teamBadge').textContent='–'}
function updateSessionUi(){if(!session){showSetup('login');return}$('setup').classList.add('hidden');$('teamBadge').textContent=session.name;$('serverLabel').textContent=location.href.replace(/team\.php(?:[?#].*)?$/,'');const local=localStorage.getItem(codeKey());if(local!==null)$('code').value=local;else if(!$('code').value)$('code').value=EXAMPLES.colonizer;loadServerCode().catch(()=>{})}
function storeSession(data){session={teamId:data.id,token:data.token,name:data.name,color:data.color};localStorage.setItem(SESSION_KEY,JSON.stringify(session));$('teamBadge').textContent=session.name;if(typeof data.code==='string'){ $('code').value=data.code||EXAMPLES.colonizer;localStorage.setItem(codeKey(),$('code').value)}updateSessionUi()}
async function jsonRequest(url,options={}){const r=await fetch(url,options);let data={};try{data=await r.json()}catch{}if(!r.ok)throw new Error(data.detail||'Serverfehler.');return data}
async function submitSetup(){const name=$('setupName').value.trim(),password=$('setupPassword').value;if(!name){$('setupError').textContent='Bitte einen Teamnamen eingeben.';return}if(password.length<4){$('setupError').textContent='Das Team-Passwort muss mindestens 4 Zeichen lang sein.';return}$('setupError').textContent='verbinde…';try{let data;if(setupMode==='create'){data=await jsonRequest('api/browser_register.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({name,color:$('setupColor').value,password,code:EXAMPLES.colonizer})})}else{data=await jsonRequest('api/browser_login.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({name,password})})}storeSession(data);$('setupPassword').value='';setConnection(false,'GESTOPPT');setSaveState('servergespeichert','ok');log(setupMode==='create'?'Team angelegt. Eure Flotte bleibt auf dem Server erhalten.':'Team geladen. Flotte und Programmcode sind wieder da.','ok')}catch(e){$('setupError').textContent=e.message}}
function changeTeam(){stopBot();if(!confirm('Auf diesem Browser abmelden? Das Team und der gespeicherte Code bleiben auf dem Server erhalten.'))return;localStorage.removeItem(SESSION_KEY);session=null;$('code').value=EXAMPLES.colonizer;setSaveState('nicht angemeldet');showSetup('login')}
async function loadServerCode(){if(!session?.teamId||!session?.token)return;try{const data=await jsonRequest(`api/team_code.php?team_id=${encodeURIComponent(session.teamId)}&token=${encodeURIComponent(session.token)}`,{cache:'no-store'});if(typeof data.code==='string'&&data.code!==''){$('code').value=data.code;localStorage.setItem(codeKey(),data.code)}setSaveState('servergespeichert','ok')}catch(e){if(/Token|Teamkonto|gefunden/i.test(e.message)){localStorage.removeItem(SESSION_KEY);session=null;showSetup('login','Die gespeicherte Anmeldung gehört nicht mehr zum aktuellen Universum. Bitte neu anmelden.')}else setSaveState('Server nicht erreichbar','bad')}}
let saveTimer=null,saveBusy=false,saveAgain=false;
async function saveCode(showLog=true){if(!session)return;const code=$('code').value;localStorage.setItem(codeKey(),code);setSaveState('speichert…');if(!session.teamId||!session.token){setSaveState('nur lokal');return}if(saveBusy){saveAgain=true;return}saveBusy=true;try{await jsonRequest(`api/team_code.php?team_id=${encodeURIComponent(session.teamId)}&token=${encodeURIComponent(session.token)}`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({code})});setSaveState('servergespeichert','ok');if(showLog)log('Code auf dem Server gespeichert.','ok')}catch(e){setSaveState('nur lokal','bad');if(showLog)log('Server-Speichern fehlgeschlagen: '+e.message,'err')}finally{saveBusy=false;if(saveAgain){saveAgain=false;saveCode(false)}}}
function scheduleSave(){localStorage.setItem(codeKey(),$('code').value);setSaveState('ungespeichert');clearTimeout(saveTimer);saveTimer=setTimeout(()=>saveCode(false),1200)}
function loadExample(name){if(!name)return;if($('code').value.trim()&&!confirm('Aktuellen Editorinhalt durch das Beispiel ersetzen?')){$('exampleSelect').value='';return}$('code').value=EXAMPLES[name];saveCode(false);$('exampleSelect').value=''}

async function sense(){if(!session?.teamId||!session?.token)throw new Error('Nicht bei einem Team angemeldet.');return jsonRequest(`api/sense.php?team_id=${encodeURIComponent(session.teamId)}&token=${encodeURIComponent(session.token)}`,{cache:'no-store'})}
async function sendActions(actions){return jsonRequest(`api/act.php?team_id=${encodeURIComponent(session.teamId)}&token=${encodeURIComponent(session.token)}`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({actions})})}

function newWorker(){if(worker)worker.terminate();worker=new Worker('static/student_worker.js');worker.onmessage=e=>{const m=e.data||{},p=pendingWorker.get(m.id);if(!p)return;pendingWorker.delete(m.id);clearTimeout(p.timer);if(m.type==='error')p.reject(new Error(m.message));else p.resolve(m)};worker.onerror=e=>log('Worker-Fehler: '+e.message,'err')}
function workerCall(type,data,timeout=350){return new Promise((resolve,reject)=>{const id=requestSeq++,timer=setTimeout(()=>{pendingWorker.delete(id);if(worker)worker.terminate();worker=null;reject(new Error('Euer Code hat zu lange gebraucht und wurde gestoppt (mögliche Endlosschleife).'))},timeout);pendingWorker.set(id,{resolve,reject,timer});worker.postMessage({type,id,...data})})}

async function startBot(){if(botRunning||!session)return;await saveCode(false);clearConsole();$('botStatus').textContent='verbinde…';setConnection(false,'VERBINDE');try{const first=await sense();renderFleet(first);newWorker();await workerCall('load',{code:$('code').value},700);botRunning=true;$('runBtn').disabled=true;$('stopBtn').disabled=false;$('botStatus').textContent='läuft';setConnection(true,'ONLINE');log('Bot gestartet. Änderungen am Code werden nach STOP → START aktiv.','ok');lastTick=-1;botLoop(first)}catch(e){setConnection(false,'FEHLER');$('botStatus').textContent='Fehler';log(e.message,'err')}}
function stopBot(){botRunning=false;clearTimeout(loopTimer);loopTimer=null;for(const p of pendingWorker.values()){clearTimeout(p.timer);p.reject(new Error('Bot gestoppt.'))}pendingWorker.clear();if(worker)worker.terminate();worker=null;$('runBtn').disabled=false;$('stopBtn').disabled=true;$('botStatus').textContent='gestoppt';setConnection(false,'GESTOPPT')}
function applySenseUi(data){renderFleet(data);$('tickBadge').textContent=data.tick;$('statShips').textContent=data.ships.length;$('statFuel').textContent=data.team.fuel;$('statMetal').textContent=data.team.metal;$('statScore').textContent=data.team.score;$('viewStatus').textContent=(data.running?'RUNNING':'PAUSE')+' · '+Number(data.tick_seconds||1.5).toFixed(2)+' s/Tick'}
async function botLoop(prefetched=null){if(!botRunning)return;let data=prefetched;try{data=data||await sense();setConnection(true,'ONLINE');applySenseUi(data);if(data.tick!==lastTick){lastTick=data.tick;const result=await workerCall('tick',{payload:data},400);for(const l of result.logs||[])log(`[${data.tick}] ${l}`);await sendActions(result.actions||[])}}catch(e){log(e.message,'err');if(String(e.message).includes('lange gebraucht')){stopBot();return}setConnection(false,'WARTET')}const wait=Math.max(90,Math.min(350,Number(data?.tick_seconds||1.5)*300));loopTimer=setTimeout(()=>botLoop(),wait)}
let idleViewTimer=null;async function idleViewLoop(){if(session&&!botRunning){try{const data=await sense();applySenseUi(data);setConnection(true,'BEREIT')}catch(e){setConnection(false,'OFFLINE')}}idleViewTimer=setTimeout(idleViewLoop,800)}

const fleetCanvas=$('fleetCanvas'),fctx=fleetCanvas.getContext('2d');let fleetSense=null,fleetTrails=new Map(),fleetLastTrailTick=-1;
function resizeFleet(){const r=fleetCanvas.getBoundingClientRect(),dpr=Math.max(1,window.devicePixelRatio||1),w=Math.max(1,Math.round(r.width*dpr)),h=Math.max(1,Math.round(r.height*dpr));if(fleetCanvas.width!==w||fleetCanvas.height!==h){fleetCanvas.width=w;fleetCanvas.height=h;fctx.setTransform(dpr,0,0,dpr,0,0)}drawFleet()}
function fleetObjects(data){const planets=new Map(),contacts=new Map();for(const ship of data?.ships||[]){for(const p of ship.planets||[])planets.set(p.id,p);for(const c of ship.contacts||[])if(c.team!==data.team.id)contacts.set(c.id,c)}return {ships:data?.ships||[],planets:[...planets.values()],contacts:[...contacts.values()]}}
function renderFleet(data){fleetSense=data;if(data.tick!==fleetLastTrailTick){fleetLastTrailTick=data.tick;for(const s of data.ships||[]){const a=fleetTrails.get(s.id)||[];a.push({x:s.x,y:s.y});if(a.length>70)a.shift();fleetTrails.set(s.id,a)}const alive=new Set((data.ships||[]).map(s=>s.id));for(const id of fleetTrails.keys())if(!alive.has(id))fleetTrails.delete(id)}drawFleet()}
function drawFleet(){const cssW=fleetCanvas.clientWidth,cssH=fleetCanvas.clientHeight;if(!cssW||!cssH)return;fctx.clearRect(0,0,cssW,cssH);fctx.fillStyle='#03070b';fctx.fillRect(0,0,cssW,cssH);if(!fleetSense?.ships?.length){fctx.fillStyle='#63788a';fctx.font='12px ui-monospace,monospace';fctx.fillText('Keine eigenen Schiffe vorhanden.',16,28);return}const o=fleetObjects(fleetSense),pts=[...o.ships,...o.planets,...o.contacts];let minX=Infinity,maxX=-Infinity,minY=Infinity,maxY=-Infinity;for(const q of pts){if(!Number.isFinite(q.x)||!Number.isFinite(q.y))continue;minX=Math.min(minX,q.x);maxX=Math.max(maxX,q.x);minY=Math.min(minY,q.y);maxY=Math.max(maxY,q.y)}if(!Number.isFinite(minX)){minX=0;maxX=fleetSense.world_size||14000;minY=0;maxY=fleetSense.world_size||14000}const padWorld=Math.max(320,Math.max(maxX-minX,maxY-minY)*.12),spanX=Math.max(900,maxX-minX+padWorld*2),spanY=Math.max(900,maxY-minY+padWorld*2),cx=(minX+maxX)/2,cy=(minY+maxY)/2,zoom=Math.min((cssW-28)/spanX,(cssH-28)/spanY,.42),sx=x=>cssW/2+(x-cx)*zoom,sy=y=>cssH/2+(y-cy)*zoom;
  fctx.strokeStyle='#0c1822';fctx.lineWidth=1;const grid=1000;for(let gx=Math.floor((cx-spanX/2)/grid)*grid;gx<cx+spanX/2;gx+=grid){const x=sx(gx);fctx.beginPath();fctx.moveTo(x,0);fctx.lineTo(x,cssH);fctx.stroke()}for(let gy=Math.floor((cy-spanY/2)/grid)*grid;gy<cy+spanY/2;gy+=grid){const y=sy(gy);fctx.beginPath();fctx.moveTo(0,y);fctx.lineTo(cssW,y);fctx.stroke()}
  fctx.strokeStyle=session?.color||'#54ef9b';fctx.globalAlpha=.32;for(const a of fleetTrails.values()){if(a.length<2)continue;fctx.beginPath();a.forEach((q,i)=>i?fctx.lineTo(sx(q.x),sy(q.y)):fctx.moveTo(sx(q.x),sy(q.y)));fctx.stroke()}fctx.globalAlpha=1;
  for(const p of o.planets){const x=sx(p.x),y=sy(p.y),r=Math.max(3,Math.min(18,(p.radius||50)*zoom));fctx.beginPath();fctx.arc(x,y,r,0,Math.PI*2);fctx.strokeStyle=p.owner===fleetSense.team.id?(session?.color||'#54ef9b'):'#72879a';fctx.lineWidth=p.owner===fleetSense.team.id?2:1;fctx.fillStyle='#0a1118';fctx.fill();fctx.stroke();if(r>5){fctx.fillStyle='#73899a';fctx.font='9px ui-monospace,monospace';fctx.fillText(p.name||p.id,x+r+3,y+3)}}
  for(const c of o.contacts){const x=sx(c.x),y=sy(c.y);fctx.save();fctx.translate(x,y);fctx.rotate(Math.PI/4);fctx.fillStyle='#ff6b7d';fctx.fillRect(-4,-4,8,8);fctx.restore();fctx.fillStyle='#b98991';fctx.font='9px ui-monospace,monospace';fctx.fillText(c.id,x+7,y-6)}
  for(const sh of o.ships){const x=sx(sh.x),y=sy(sh.y),a=Number(sh.heading||0)*Math.PI/180;fctx.save();fctx.translate(x,y);fctx.rotate(a);fctx.beginPath();fctx.moveTo(8,0);fctx.lineTo(-6,-5);fctx.lineTo(-3,0);fctx.lineTo(-6,5);fctx.closePath();fctx.fillStyle=session?.color||'#54ef9b';fctx.fill();fctx.restore();fctx.fillStyle='#d2e7f2';fctx.font='9px ui-monospace,monospace';fctx.fillText(sh.id,x+8,y+11)}
}
window.addEventListener('resize',resizeFleet);if(window.ResizeObserver)new ResizeObserver(resizeFleet).observe(fleetCanvas.parentElement);setTimeout(resizeFleet,0);

$('code').addEventListener('input',scheduleSave);
$('code').addEventListener('keydown',e=>{if(e.key==='Tab'){e.preventDefault();const t=e.target,s=t.selectionStart,n=t.selectionEnd;t.setRangeText('    ',s,n,'end')}if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){e.preventDefault();saveCode(true)}});
window.addEventListener('beforeunload',()=>{if(session)localStorage.setItem(codeKey(),$('code').value)});
setSetupMode('login');updateSessionUi();idleViewLoop();
</script>
</body>
</html>
