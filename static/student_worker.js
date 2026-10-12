/* SPACE//AG student-code worker.
   The team token never enters this worker. The page handles networking; this
   worker only receives sensor snapshots and returns action intents. */

const send = self.postMessage.bind(self);
const RealFunction = Function;
let steuereFn = null;
let currentLogs = [];

for (const name of ["fetch", "XMLHttpRequest", "WebSocket", "EventSource", "Worker", "SharedWorker", "importScripts"]) {
  try { self[name] = undefined; } catch (_) {}
}

function show(value) {
  if (typeof value === "string") return value;
  try { return JSON.stringify(value); } catch (_) { return String(value); }
}

const safeConsole = {
  log: (...xs) => currentLogs.push(xs.map(show).join(" ")),
  info: (...xs) => currentLogs.push("INFO  " + xs.map(show).join(" ")),
  warn: (...xs) => currentLogs.push("WARN  " + xs.map(show).join(" ")),
  error: (...xs) => currentLogs.push("ERROR " + xs.map(show).join(" ")),
};
try { self.console = safeConsole; } catch (_) {}

function shipNumber(id) {
  const match = String(id || "").match(/S(\d+)$/);
  return match ? Number(match[1]) : 1;
}

function planetDeutsch(p) {
  return Object.freeze({
    id: p.id,
    name: p.name,
    x: p.x,
    y: p.y,
    entfernung: p.distance,
    richtung: p.direction,
    radius: p.radius,
    durchmesser: p.diameter,
    groesse: p.size_class,
    ressourcenprofil: p.resource_profile,
    treibstoff_rate: p.fuel_rate,
    metall_rate: p.metal_rate,
    treibstoff_vorrat: p.fuel_stock,
    metall_vorrat: p.metal_stock,
    treibstoff_max: p.fuel_capacity,
    metall_max: p.metal_capacity,
    besitzer: p.owner,
  });
}

function kontaktDeutsch(s) {
  return Object.freeze({
    id: s.id,
    team: s.team,
    rolle: s.role,
    x: s.x,
    y: s.y,
    entfernung: s.distance,
    richtung: s.direction,
    geschwindigkeit: s.speed,
    leben: s.hp,
    schild: s.shield,
    fracht_treibstoff: s.cargo_fuel,
    fracht_metall: s.cargo_metal,
    ressourcen_freigegeben: s.resource_share,
  });
}

function ereignisDeutsch(e) {
  return Object.freeze({
    typ: e.type === "hit" ? "getroffen" : e.type,
    tick: e.tick,
    angreifer: e.attacker,
    angreifer_team: e.attacker_team,
    richtung: e.direction,
    schaden: e.damage,
    schild_absorbiert: e.shield_absorbed,
    leben_danach: e.hp_after,
    schild_danach: e.shield_after,
  });
}

function makeShip(raw, action) {
  const ship = {
    id: raw.id,
    team: raw.team,
    rolle: raw.role,
    nummer: shipNumber(raw.id),
    x: raw.x,
    y: raw.y,
    vx: raw.vx,
    vy: raw.vy,
    geschwindigkeit: raw.speed,
    richtung: raw.heading,
    treibstoff: raw.fuel,
    treibstoff_max: raw.fuel_capacity,
    fracht_treibstoff: raw.cargo_fuel,
    fracht_metall: raw.cargo_metal,
    fracht_max: raw.cargo_capacity,
    leben: raw.hp,
    schild: raw.shield,
    schild_max: raw.shield_capacity,
    gelandet: Boolean(raw.landed_on),
    gelandet_auf: raw.landed_on,
    sensorreichweite: raw.sensor_range,
    waffenreichweite: raw.weapon_range,
    cooldown: raw.cooldown,
    ressourcen_freigegeben: raw.resource_share,
    eroberung: raw.capture_progress,
    eroberung_noetig: raw.capture_required,

    schub(staerke = 1) { action.thrust = Number(staerke); },
    drehen(grad) { action.turn = Number(grad); },
    landen() { action.land = true; },
    starten() { action.takeoff = true; },
    abbauen(rohstoff = "beides") { action.mine = String(rohstoff); },
    kolonisieren() { action.colonize = true; },
    erobern() { action.capture = true; },
    tanken(menge = true) { action.refuel = menge === true ? true : Number(menge); },
    angreifen(ziel) { action.fire = typeof ziel === "string" ? ziel : ziel?.id; },
    bauen(rolle = "standard") { action.build = String(rolle); },
    upgrade(system) { action.upgrade = String(system); },
    ressourcen_teilen(erlaubt = true) { action.share_resources = Boolean(erlaubt); },
  };
  return Object.freeze(ship);
}

function compile(code) {
  const factory = RealFunction("console", `"use strict";\n${code}\n\nif (typeof steuere !== "function") { throw new Error("Es fehlt: function steuere(schiff, sensoren) { ... }"); }\nreturn steuere;`);
  const candidate = factory(safeConsole);
  if (typeof candidate !== "function") throw new Error("steuere ist keine Funktion.");
  steuereFn = candidate;
}

async function runSnapshot(payload) {
  if (!steuereFn) throw new Error("Code wurde noch nicht gestartet.");
  currentLogs = [];
  const actions = [];

  for (const raw of payload.ships || []) {
    const action = { ship_id: raw.id };
    const ship = makeShip(raw, action);
    const sensors = Object.freeze({
      tick: payload.tick,
      weltgroesse: payload.world_size,
      gravitation: Boolean(payload.gravity_enabled),
      tick_dauer: payload.tick_seconds,
      team: Object.freeze({
        id: payload.team?.id,
        name: payload.team?.name,
        treibstoff_gesamt: payload.team?.fuel,
        metall_gesamt: payload.team?.metal,
        // Alte Namen bleiben als reine Anzeige kompatibel.
        treibstoff: payload.team?.fuel,
        metall: payload.team?.metal,
        punkte: payload.team?.score,
      }),
      planeten: Object.freeze((raw.planets || []).map(planetDeutsch)),
      schiffe: Object.freeze((raw.contacts || []).map(kontaktDeutsch)),
      ereignisse: Object.freeze((raw.events || []).map(ereignisDeutsch)),
    });

    await steuereFn(ship, sensors);
    actions.push(action);
  }

  return { actions, logs: currentLogs.slice(-30) };
}

self.onmessage = async (event) => {
  const msg = event.data || {};
  try {
    if (msg.type === "load") {
      currentLogs = [];
      compile(String(msg.code || ""));
      send({ type: "loaded", id: msg.id, logs: currentLogs });
      return;
    }
    if (msg.type === "tick") {
      const result = await runSnapshot(msg.payload || {});
      send({ type: "result", id: msg.id, ...result });
    }
  } catch (err) {
    send({
      type: "error",
      id: msg.id,
      message: err?.message || String(err),
      stack: err?.stack || "",
      logs: currentLogs.slice(-30),
    });
  }
};
