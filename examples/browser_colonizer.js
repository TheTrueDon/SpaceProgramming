function winkelDifferenz(ziel, aktuell) {
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
            if (schiff.fracht_metall >= 40) schiff.kolonisieren();
            else schiff.abbauen("metall");
        } else if (planet && planet.besitzer === schiff.team) {
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

    const freie = sensoren.planeten.filter(p => p.besitzer === null).sort((a,b) => a.entfernung - b.entfernung);
    if (freie.length) return fliegeZu(schiff, freie[0]);

    const suchrichtung = (Math.floor(sensoren.tick / 120) * 67 + schiff.nummer * 31) % 360;
    const delta = winkelDifferenz(suchrichtung, schiff.richtung);
    if (Math.abs(delta) > 1) schiff.drehen(Math.max(-10, Math.min(10, delta)));
    if (Math.abs(delta) < 12 && schiff.geschwindigkeit < 6) schiff.schub(0.25);
}
