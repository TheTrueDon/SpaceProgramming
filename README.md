# SPACE//AG Web Edition 1.1

Webspace-Port der aktuellen **SPACE//AG-Serverregeln v0.7** für klassischen PHP-/MySQL-Webspace. Kein dauerhaft laufender Python-/Node-Prozess, kein Docker und keine WebSockets nötig.

## Voraussetzungen

- PHP **8.1 oder neuer**
- PDO-MySQL (`pdo_mysql`)
- MySQL oder MariaDB mit InnoDB
- JavaScript + WebWorker in den Schülerbrowsern
- HTTPS empfohlen

## Installation

1. Im Hosting-Panel eine leere MySQL-/MariaDB-Datenbank anlegen.
2. Den gesamten Ordner per FTP/SFTP hochladen, z. B. nach `space-ag/`.
3. `https://DEINE-DOMAIN/space-ag/install.php` öffnen.
4. Datenbankdaten und ein Lehrerpasswort eintragen.
5. `admin.php` öffnen und **Neues Universum erzeugen**.
6. Das neue Universum startet bewusst **pausiert**. Die Schüler können bereits ihre Teams anlegen.
7. Wenn alle bereit sind, im Lehrerbereich **Start** drücken.

Die Installation erzeugt:

- `space_ag_state` – aktiver Weltzustand + Tickzeit
- `space_ag_team_accounts` – Teamkonten + servergespeicherter Schülercode
- `config.local.php` – lokale Serverkonfiguration

## Lehrer- und Schülerseiten

- `admin.php` – passwortgeschützte Lehreransicht mit kompletter Weltkarte
- `team.php` – Schülereditor + eigene sensorbasierte Flottenansicht
- `index.php` – Weiterleitung zum Schülereditor

### Was bedeutet „Start“?

Auf normalem Webspace läuft der Webserver ohnehin ständig. **Start/Pause steuert nur die Spielsimulation.** Es muss kein Serverprozess manuell gestartet werden.

`Pause` und `Start` verändern oder löschen keine Teams. Nur **Neues Universum erzeugen** ist ein vollständiger Reset und löscht bewusst alle Teams, Flotten und gespeicherten Teamprogramme des alten Universums.

## Dauerhafte Teams

Beim ersten Mal wählt ein Team:

- Teamname
- Teamfarbe
- Team-Passwort (mindestens 4 Zeichen)

Danach bleiben Flotte und Team im aktuellen Universum auf dem Server erhalten.

Auf demselben Browser bleibt zusätzlich die Anmeldung lokal erhalten. Auf einem anderen Rechner wählen die Schüler **Vorhandenes Team** und melden sich mit Teamname + Passwort wieder an.

Der Editorcode wird automatisch lokal **und serverseitig** gespeichert. Damit steht auf einem anderen Rechner wieder der zuletzt gespeicherte Programmcode zur Verfügung. `Strg+S` speichert sofort; beim Tippen wird nach kurzer Pause automatisch gespeichert.

### Wichtig: Was passiert, wenn der Schülerbrowser geschlossen ist?

Der eigentliche Schüler-JavaScript-Code wird aus Sicherheitsgründen weiterhin **nur im Schülerbrowser** ausgeführt. Ist der Browser geschlossen, bleibt die Flotte serverseitig bestehen, aber ihr Programm trifft in dieser Zeit keine neuen Entscheidungen. Bewegte Schiffe fliegen wegen der Trägheit weiter; neue Schub-, Kampf-, Mining- oder Bauentscheidungen gibt es erst wieder nach dem Einloggen und Starten des Bots.

Für eine AG über mehrere Termine ist deshalb sinnvoll, die Simulation am Stundenende im Lehrerbereich zu **pausieren** und beim nächsten Termin wieder zu starten.

## Schüler-Flottenansicht

Der Schülereditor besitzt eine eigene Live-Karte. Sie zeigt:

- eigene Schiffe
- Flugspuren der eigenen Schiffe
- erkannte Planeten
- erkannte fremde Schiffe
- nur Informationen innerhalb der tatsächlichen Sensorreichweiten

Die Schülerkarte ist absichtlich kein vollständiger Weltüberblick. Die Lehrerkarte in `admin.php` bleibt die allwissende Ansicht.

Die Karte aktualisiert sich auch bei gestopptem Bot. Dadurch kann man Verhalten beobachten, den Bot stoppen, Code ändern und danach erneut starten.

## Tick-Speed

Basisgeschwindigkeit ist **1,5 s pro Tick**. Der Lehrerbereich bietet folgende Stufen:

| Geschwindigkeit | Sekunden pro Tick |
| ---: | ---: |
| 0,25× | 6,00 s |
| 0,5× | 3,00 s |
| 1× | 1,50 s |
| 2× | 0,75 s |
| 3× | 0,50 s |
| 5× | 0,30 s |
| 10× | 0,15 s |

**5×** ist ein guter schneller Beobachtungsmodus: Das Verhalten wird sehr schnell sichtbar, während die Browser noch bequem jeden Tick verarbeiten können. **10×** ist als kurzer Testmodus gedacht. Mit vielen gleichzeitig aktiven Teams verursacht er auf Shared Hosting deutlich mehr HTTP-/PHP-/MySQL-Last.

## Spielregeln aus v0.7

Enthalten sind unter anderem:

- Trägheit ohne künstlichen Luftwiderstand
- Schub, Drehen und Treibstoffverbrauch
- optionale Planetengravitation
- Landen und Starten
- Kolonisieren und mehrtickiges Erobern
- Kampf, Waffen-Cooldown, Schilde und Trefferereignisse
- Ressourcenabbau, explizites Tanken und Ressourcenteilung
- Schiffsbau und frei benennbare Rollen
- Engine-/Tank-/Scanner-/Cargo-/Weapon-/Shield-Upgrades
- stark unterschiedliche Planetengrößen von Zwerg- bis Riesenplaneten
- unterschiedliche Rohstoffprofile: treibstoffreich, metallreich, gemischt, reich und arm
- größere Planeten haben tendenziell höhere Produktionsraten und Lagerkapazitäten
- Sensoren melden Planetengröße, Durchmesser, Ressourcenprofil, Produktion, Vorräte und Kapazitäten

Im Schülercode stehen dadurch z. B. zur Verfügung:

```js
planet.groesse
planet.radius
planet.durchmesser
planet.ressourcenprofil
planet.treibstoff_rate
planet.metall_rate
planet.treibstoff_max
planet.metall_max
sensoren.tick_dauer
```

## Ticks auf Shared Hosting

PHP beendet normalerweise jeden Request. Die Welt wird deshalb requestgetrieben weitergeschaltet:

1. Lehrer- oder Schülerbrowser ruft die API auf.
2. PHP sperrt den Weltzustand mit einer MySQL-Transaktion (`SELECT ... FOR UPDATE`).
3. Fällige Ticks werden exakt einmal berechnet.
4. Der Zustand wird atomar gespeichert.
5. Parallel eintreffende Requests können denselben Tick nicht doppelt ausführen.

Wenn nachts niemand die Anwendung aufruft, wird kein riesiger Rückstand nachgerechnet. Alte Catch-up-Ticks werden bewusst begrenzt.

## Sicherheit

- Lehrerzugriff über PHP-Session + CSRF-Schutz
- Lehrerpasswort nur als Hash gespeichert
- Team-Passwörter nur als Hash gespeichert
- Schülercode wird nicht auf dem PHP-Server ausgeführt
- Browser-Worker erhält keine Datenbankzugänge
- Aktionen werden serverseitig nur für Schiffe des authentifizierten Teams angenommen
- Schülerkarte erhält nur Sensordaten des eigenen Teams

## Upgrade von Web Edition 1.0

Wer bereits Web Edition 1.0 installiert hat:

1. Dateien durch 1.1 ersetzen.
2. Im Lehrerbereich anmelden.
3. `upgrade.php` einmal aufrufen.
4. Danach ein neues Universum erzeugen.

Das neue Universum verwendet dann die dauerhaften Teamkonten. Bei einer frischen Installation ist `upgrade.php` nicht nötig.

## Tests

Beim Erstellen der Version wurden alle PHP-Dateien syntaktisch geprüft und der reine PHP-Simulationskern ausgeführt. Zusätzlich wurden die v0.7-Planeteneigenschaften (Größenklassen, Ressourcenprofile und Kapazitäten) sowie die erweiterten Sensordaten kontrolliert. Der Browser-JavaScript-Code und der WebWorker wurden mit Node syntaktisch geprüft.
