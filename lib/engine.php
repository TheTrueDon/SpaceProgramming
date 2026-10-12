<?php
declare(strict_types=1);

/* SPACE//AG Web engine.
 * Pure PHP port of the v0.7 Python simulation. State is represented as arrays
 * so it can be serialized directly to JSON and stored atomically in MySQL.
 */

const SPACE_WORLD_SIZE = 14000.0;
const SPACE_DEFAULT_PLANET_COUNT = 44;
const SPACE_MIN_PLANET_GAP = 430.0;

const SPACE_MAX_SPEED = 16.0;
const SPACE_BASE_ACCEL = 0.65;
const SPACE_TURN_LIMIT = 10.0;
const SPACE_LAND_MARGIN = 12.0;
const SPACE_LAND_SPEED = 2.4;
const SPACE_TAKEOFF_SPEED = 1.2;
const SPACE_THRUST_FUEL_USE = 0.08;
const SPACE_TURN_FUEL_PER_DEG = 0.006;

const SPACE_GRAVITY_RANGE = 1200.0;
const SPACE_GRAVITY_STRENGTH = 0.018;
const SPACE_GRAVITY_ACCEL_CAP = 0.04;

const SPACE_SHOT_DAMAGE = 24.0;
const SPACE_SHOT_COOLDOWN = 4;
const SPACE_CAPTURE_TICKS = 8;
const SPACE_TRADE_RANGE = 260.0;

const SPACE_COLONIZE_METAL_COST = 40.0;
const SPACE_BUILD_METAL_COST = 80.0;
const SPACE_BUILD_FUEL_COST = 40.0;

const SPACE_PLANET_PRODUCTION_FACTOR = 0.25;
const SPACE_MINE_FACTOR = 1.5;

const SPACE_PLANET_SIZE_CLASSES = [
    ['zwerg', 34.0, 50.0, 0.45, 10],
    ['klein', 50.0, 75.0, 0.72, 25],
    ['mittel', 75.0, 105.0, 1.00, 35],
    ['gross', 105.0, 145.0, 1.48, 22],
    ['riesig', 145.0, 190.0, 2.15, 8],
];
const SPACE_RESOURCE_PROFILES = [
    ['treibstoffreich', 1.55, 0.0, 22],
    ['metallreich', 0.0, 1.65, 22],
    ['gemischt', 1.0, 1.0, 39],
    ['reich', 1.65, 1.65, 13],
    ['arm', 0.18, 0.18, 4],
];

const SPACE_TEAM_COLORS = ["#44d9ff", "#ff4f81", "#ffc857", "#70e000", "#a78bfa", "#ff8c42"];

function space_clamp(float $value, float $lo, float $hi): float {
    return max($lo, min($hi, $value));
}

function space_mod(float $value, float $mod): float {
    $r = fmod($value, $mod);
    return $r < 0 ? $r + $mod : $r;
}

function space_distance(float $ax, float $ay, float $bx, float $by): float {
    return hypot($bx - $ax, $by - $ay);
}

function space_angle_to(float $ax, float $ay, float $bx, float $by): float {
    return space_mod(rad2deg(atan2($by - $ay, $bx - $ax)), 360.0);
}

function space_angle_delta(float $target, float $current): float {
    return space_mod($target - $current + 180.0, 360.0) - 180.0;
}

function space_fuel_capacity(array $ship): float {
    return 120.0 + ((int)($ship['tank_level'] ?? 0)) * 45.0;
}

function space_cargo_capacity(array $ship): float {
    return 90.0 + ((int)($ship['cargo_level'] ?? 0)) * 60.0;
}

function space_shield_capacity(array $ship): float {
    return ((int)($ship['shield_level'] ?? 0)) * 30.0;
}

function space_effective_max_speed(array $ship): float {
    return SPACE_MAX_SPEED * (1.0 + 0.10 * (int)($ship['engine_level'] ?? 0));
}

function space_effective_accel(array $ship): float {
    return SPACE_BASE_ACCEL * (1.0 + 0.18 * (int)($ship['engine_level'] ?? 0));
}

function space_effective_sensor_range(array $ship): float {
    return (float)($ship['sensor_range'] ?? 1900.0) * (1.0 + 0.25 * (int)($ship['scanner_level'] ?? 0));
}

function space_effective_weapon_range(array $ship): float {
    return (float)($ship['weapon_range'] ?? 320.0) * (1.0 + 0.15 * (int)($ship['weapon_level'] ?? 0));
}

function space_effective_weapon_damage(array $ship): float {
    return SPACE_SHOT_DAMAGE * (1.0 + 0.15 * (int)($ship['weapon_level'] ?? 0));
}

function space_empty_world(): array {
    return [
        'seed' => 0,
        'tick' => 0,
        'ready' => false,
        'running' => false,
        'gravity_enabled' => false,
        'planets' => [],
        'ships' => [],
        'teams' => [],
        'pending_actions' => [],
        'logs' => [],
    ];
}

function space_random_float(float $lo, float $hi): float {
    return $lo + (mt_rand() / mt_getrandmax()) * ($hi - $lo);
}

function space_random_token(int $bytes = 24): string {
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

function space_weighted_choice(array $entries): array {
    $total=0;foreach($entries as $e)$total+=(int)$e[count($e)-1];
    $r=mt_rand(1,max(1,$total));$sum=0;foreach($entries as $e){$sum+=(int)$e[count($e)-1];if($r<=$sum)return $e;}
    return $entries[array_key_last($entries)];
}

function space_random_planet(int $index): array {
    [$sizeName,$rMin,$rMax,$sizeScale] = space_weighted_choice(SPACE_PLANET_SIZE_CLASSES);
    [$profile,$fuelMult,$metalMult] = space_weighted_choice(SPACE_RESOURCE_PROFILES);
    $radius=space_random_float((float)$rMin,(float)$rMax);
    $fuelRate=space_random_float(1.6,4.8)*(float)$sizeScale*(float)$fuelMult;
    $metalRate=space_random_float(0.9,3.2)*(float)$sizeScale*(float)$metalMult;
    $fuelCapacity=space_random_float(420.0,720.0)*(float)$sizeScale*max((float)$fuelMult,0.0);
    $metalCapacity=space_random_float(300.0,560.0)*(float)$sizeScale*max((float)$metalMult,0.0);
    if((float)$fuelMult===0.0){$fuelCapacity=0.0;$fuelRate=0.0;}
    if((float)$metalMult===0.0){$metalCapacity=0.0;$metalRate=0.0;}
    return [
        'id'=>sprintf('P%02d',$index),'name'=>'PX-'.mt_rand(100,998),'x'=>0.0,'y'=>0.0,
        'radius'=>round($radius,1),'fuel_rate'=>round($fuelRate,2),'metal_rate'=>round($metalRate,2),
        'fuel_stock'=>$fuelCapacity?round($fuelCapacity*space_random_float(0.35,0.78),1):0.0,
        'metal_stock'=>$metalCapacity?round($metalCapacity*space_random_float(0.35,0.78),1):0.0,
        'fuel_capacity'=>round($fuelCapacity,1),'metal_capacity'=>round($metalCapacity,1),
        'size_class'=>$sizeName,'resource_profile'=>$profile,'owner'=>null,
    ];
}

function space_create_world(?int $seed = null, int $planetCount = SPACE_DEFAULT_PLANET_COUNT): array {
    $seed ??= random_int(0, 1_999_999_999);
    mt_srand($seed, MT_RAND_MT19937);
    $world = space_empty_world();
    $world['seed'] = $seed;
    $world['ready'] = true;
    $world['running'] = true;

    $planetCount=max(15,min(90,$planetCount));$placed=[];$attempts=0;
    while(count($placed)<$planetCount && $attempts<$planetCount*500){
        $attempts++;$planet=space_random_planet(count($placed));$margin=520.0+$planet['radius'];
        $planet['x']=space_random_float($margin,SPACE_WORLD_SIZE-$margin);$planet['y']=space_random_float($margin,SPACE_WORLD_SIZE-$margin);
        $tooClose=false;foreach($placed as $q){if(space_distance($planet['x'],$planet['y'],$q['x'],$q['y'])<SPACE_MIN_PLANET_GAP+$planet['radius']+$q['radius']){$tooClose=true;break;}}
        if(!$tooClose)$placed[]=$planet;
    }
    while(count($placed)<$planetCount){
        $planet=space_random_planet(count($placed));$margin=420.0+$planet['radius'];
        for($i=0;$i<1000;$i++){
            $planet['x']=space_random_float($margin,SPACE_WORLD_SIZE-$margin);$planet['y']=space_random_float($margin,SPACE_WORLD_SIZE-$margin);
            $ok=true;foreach($placed as $q){if(space_distance($planet['x'],$planet['y'],$q['x'],$q['y'])<=$planet['radius']+$q['radius']+80.0){$ok=false;break;}}
            if($ok)break;
        }
        $placed[]=$planet;
    }
    foreach($placed as $planet)$world['planets'][$planet['id']]=$planet;
    $world['logs'][]="Universe generated: seed {$seed}, ".count($placed)." planets.";
    return $world;
}

function space_nearest_free_neighbour(array $planet, array $free): float {
    $best = SPACE_WORLD_SIZE;
    foreach ($free as $q) {
        if ($q['id'] === $planet['id']) continue;
        $best = min($best, space_distance($planet['x'], $planet['y'], $q['x'], $q['y']));
    }
    return $best;
}

function space_start_planet_id(array $world): string {
    $free = array_values(array_filter($world['planets'], fn($p) => $p['owner'] === null));
    if (!$free) {
        $ids = array_keys($world['planets']);
        sort($ids, SORT_STRING);
        return $ids[0];
    }
    $friendly = array_values(array_filter($free, fn($p) => space_nearest_free_neighbour($p, $free) <= 1900.0));
    $candidates = $friendly ?: $free;
    $occupied = array_values(array_filter($world['planets'], fn($p) => $p['owner'] !== null));
    if (!$occupied) {
        usort($candidates, fn($a, $b) => abs(space_nearest_free_neighbour($a, $free) - 1150.0) <=> abs(space_nearest_free_neighbour($b, $free) - 1150.0));
        return $candidates[0]['id'];
    }
    $bestId = $candidates[0]['id'];
    $bestDistance = -INF;
    foreach ($candidates as $p) {
        $nearestOccupied = INF;
        foreach ($occupied as $q) {
            $nearestOccupied = min($nearestOccupied, space_distance($p['x'], $p['y'], $q['x'], $q['y']));
        }
        if ($nearestOccupied > $bestDistance) {
            $bestDistance = $nearestOccupied;
            $bestId = $p['id'];
        }
    }
    return $bestId;
}

function space_next_ship_id(array $world, string $teamId): string {
    $max = 0;
    $prefix = $teamId . '-S';
    foreach (array_keys($world['ships']) as $id) {
        if (str_starts_with($id, $prefix)) {
            $n = substr($id, strlen($prefix));
            if (ctype_digit($n)) $max = max($max, (int)$n);
        }
    }
    return $prefix . ($max + 1);
}

function space_add_team(array &$world, string $name, ?string $color = null, ?string $token = null, ?string $clientKey = null): array {
    $idx = count($world['teams']) + 1;
    $teamId = 'T' . $idx;
    $team = [
        'id' => $teamId,
        'name' => $name,
        'color' => $color ?: SPACE_TEAM_COLORS[($idx - 1) % count(SPACE_TEAM_COLORS)],
        'token' => $token ?: space_random_token(18),
        'client_key' => $clientKey ?: space_random_token(18),
        'score' => 0,
        'last_seen_tick' => (int)$world['tick'],
    ];
    $world['teams'][$teamId] = $team;

    $planetId = space_start_planet_id($world);
    $world['planets'][$planetId]['owner'] = $teamId;
    $world['planets'][$planetId]['resource_profile'] = 'heimat';
    $world['planets'][$planetId]['fuel_rate'] = max((float)$world['planets'][$planetId]['fuel_rate'], 3.2);
    $world['planets'][$planetId]['metal_rate'] = max((float)$world['planets'][$planetId]['metal_rate'], 2.1);
    $world['planets'][$planetId]['fuel_capacity'] = max((float)($world['planets'][$planetId]['fuel_capacity']??0), 760.0);
    $world['planets'][$planetId]['metal_capacity'] = max((float)($world['planets'][$planetId]['metal_capacity']??0), 520.0);
    $world['planets'][$planetId]['fuel_stock'] = max((float)$world['planets'][$planetId]['fuel_stock'], 420.0);
    $world['planets'][$planetId]['metal_stock'] = max((float)$world['planets'][$planetId]['metal_stock'], 260.0);
    $planet = $world['planets'][$planetId];
    $landingAngle = space_mod($idx * 137.5, 360.0);
    $rad = deg2rad($landingAngle);
    $shipId = $teamId . '-S1';
    $world['ships'][$shipId] = space_new_ship([
        'id' => $shipId,
        'team' => $teamId,
        'x' => $planet['x'] + cos($rad) * ($planet['radius'] + 3.0),
        'y' => $planet['y'] + sin($rad) * ($planet['radius'] + 3.0),
        'heading' => $landingAngle,
        'role' => 'standard',
        'landed_on' => $planetId,
        'landed_angle' => $landingAngle,
    ]);
    $world['logs'][] = "{$name} joined at {$planet['name']} with {$shipId}.";
    return $world['teams'][$teamId];
}

function space_new_ship(array $overrides): array {
    return array_replace([
        'id' => '', 'team' => '', 'x' => 0.0, 'y' => 0.0,
        'vx' => 0.0, 'vy' => 0.0, 'heading' => 0.0,
        'fuel' => 120.0, 'cargo_fuel' => 0.0, 'cargo_metal' => 0.0,
        'hp' => 100.0, 'shield' => 0.0,
        'landed_on' => null, 'landed_angle' => null,
        'sensor_range' => 1900.0, 'weapon_range' => 320.0,
        'cooldown' => 0, 'role' => 'standard',
        'engine_level' => 0, 'tank_level' => 0, 'scanner_level' => 0,
        'cargo_level' => 0, 'weapon_level' => 0, 'shield_level' => 0,
        'resource_share' => false,
        'capture_planet' => null, 'capture_progress' => 0,
        'last_hit_tick' => -10000,
        'events' => [],
    ], $overrides);
}

function space_verify_team(array $world, string $teamId, string $token): bool {
    return isset($world['teams'][$teamId]) && hash_equals((string)$world['teams'][$teamId]['token'], $token);
}

function space_team_resource_totals(array $world, string $teamId): array {
    $fuel = 0.0; $metal = 0.0;
    foreach ($world['planets'] as $p) {
        if ($p['owner'] === $teamId) { $fuel += (float)$p['fuel_stock']; $metal += (float)$p['metal_stock']; }
    }
    foreach ($world['ships'] as $s) {
        if ($s['team'] === $teamId) { $fuel += (float)$s['cargo_fuel']; $metal += (float)$s['cargo_metal']; }
    }
    return [$fuel, $metal];
}

function space_sensor_payload(array $world, string $teamId): array {
    $resultShips = [];
    foreach ($world['ships'] as $ship) {
        if ($ship['team'] !== $teamId) continue;
        $planets = [];
        $contacts = [];
        $sensorRange = space_effective_sensor_range($ship);
        foreach ($world['planets'] as $p) {
            $d = space_distance($ship['x'], $ship['y'], $p['x'], $p['y']);
            if ($d <= $sensorRange) {
                $planets[] = [
                    'id'=>$p['id'], 'name'=>$p['name'], 'x'=>round($p['x'],2), 'y'=>round($p['y'],2),
                    'distance'=>round($d,2), 'direction'=>round(space_angle_to($ship['x'],$ship['y'],$p['x'],$p['y']),2),
                    'radius'=>round($p['radius'],1), 'diameter'=>round($p['radius']*2,1),
                    'size_class'=>$p['size_class']??'mittel', 'resource_profile'=>$p['resource_profile']??'gemischt',
                    'fuel_rate'=>$p['fuel_rate'], 'metal_rate'=>$p['metal_rate'],
                    'fuel_stock'=>round($p['fuel_stock'],1), 'metal_stock'=>round($p['metal_stock'],1),
                    'fuel_capacity'=>round((float)($p['fuel_capacity']??1600.0),1), 'metal_capacity'=>round((float)($p['metal_capacity']??1100.0),1), 'owner'=>$p['owner'],
                ];
            }
        }
        foreach ($world['ships'] as $other) {
            if ($other['id'] === $ship['id']) continue;
            $d = space_distance($ship['x'],$ship['y'],$other['x'],$other['y']);
            if ($d <= $sensorRange) {
                $contact = [
                    'id'=>$other['id'], 'team'=>$other['team'], 'role'=>$other['role'],
                    'x'=>round($other['x'],2), 'y'=>round($other['y'],2), 'distance'=>round($d,2),
                    'direction'=>round(space_angle_to($ship['x'],$ship['y'],$other['x'],$other['y']),2),
                    'speed'=>round(hypot($other['vx'],$other['vy']),2), 'hp'=>round($other['hp'],1), 'shield'=>round($other['shield'],1),
                ];
                if ($other['team'] === $teamId) {
                    $contact['cargo_fuel'] = round($other['cargo_fuel'],1);
                    $contact['cargo_metal'] = round($other['cargo_metal'],1);
                    $contact['resource_share'] = (bool)$other['resource_share'];
                }
                $contacts[] = $contact;
            }
        }
        usort($planets, fn($a,$b)=>$a['distance'] <=> $b['distance']);
        usort($contacts, fn($a,$b)=>$a['distance'] <=> $b['distance']);
        $resultShips[] = [
            'id'=>$ship['id'],'team'=>$ship['team'],'role'=>$ship['role'],
            'x'=>round($ship['x'],2),'y'=>round($ship['y'],2),'vx'=>round($ship['vx'],2),'vy'=>round($ship['vy'],2),
            'speed'=>round(hypot($ship['vx'],$ship['vy']),2),'heading'=>round($ship['heading'],2),
            'fuel'=>round($ship['fuel'],1),'fuel_capacity'=>round(space_fuel_capacity($ship),1),
            'cargo_fuel'=>round($ship['cargo_fuel'],1),'cargo_metal'=>round($ship['cargo_metal'],1),
            'cargo_capacity'=>round(space_cargo_capacity($ship),1),
            'hp'=>round($ship['hp'],1),'shield'=>round($ship['shield'],1),'shield_capacity'=>round(space_shield_capacity($ship),1),
            'landed_on'=>$ship['landed_on'],'sensor_range'=>round($sensorRange,1),'weapon_range'=>round(space_effective_weapon_range($ship),1),
            'cooldown'=>(int)$ship['cooldown'],'resource_share'=>(bool)$ship['resource_share'],
            'capture_planet'=>$ship['capture_planet'],'capture_progress'=>(int)$ship['capture_progress'],'capture_required'=>SPACE_CAPTURE_TICKS,
            'events'=>array_values($ship['events'] ?? []),'planets'=>$planets,'contacts'=>$contacts,
        ];
    }
    $team = $world['teams'][$teamId];
    [$fuel,$metal] = space_team_resource_totals($world,$teamId);
    return [
        'tick'=>(int)$world['tick'], 'world_size'=>SPACE_WORLD_SIZE, 'gravity_enabled'=>(bool)$world['gravity_enabled'],
        'team'=>['id'=>$team['id'],'name'=>$team['name'],'fuel'=>round($fuel,1),'metal'=>round($metal,1),'score'=>(int)$team['score']],
        'ships'=>$resultShips,
    ];
}

function space_boolish(mixed $value): bool { return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? (bool)$value; }

function space_queue_actions(array &$world, string $teamId, array $actions): array {
    $accepted = [];
    foreach (array_slice($actions,0,100) as $raw) {
        if (!is_array($raw)) continue;
        $shipId = (string)($raw['ship_id'] ?? '');
        if (!isset($world['ships'][$shipId]) || $world['ships'][$shipId]['team'] !== $teamId) continue;
        $refuelRaw = $raw['refuel'] ?? false;
        if ((is_int($refuelRaw) || is_float($refuelRaw)) && !is_bool($refuelRaw)) $refuel = max(0.0,(float)$refuelRaw);
        else $refuel = (bool)$refuelRaw;
        $mineRaw = $raw['mine'] ?? false;
        $mine = is_string($mineRaw) ? $mineRaw : (bool)$mineRaw;
        $share = array_key_exists('share_resources',$raw) ? $raw['share_resources'] : null;
        $thrustRaw = $raw['thrust'] ?? 0.0;
        $turnRaw = $raw['turn'] ?? 0.0;
        $fireRaw = $raw['fire'] ?? null;
        $buildRaw = $raw['build'] ?? null;
        $upgradeRaw = $raw['upgrade'] ?? null;
        $world['pending_actions'][$shipId] = [
            'thrust'=>is_numeric($thrustRaw)?(float)$thrustRaw:0.0,
            'turn'=>is_numeric($turnRaw)?(float)$turnRaw:0.0,
            'land'=>(bool)($raw['land'] ?? false),'takeoff'=>(bool)($raw['takeoff'] ?? false),
            'mine'=>$mine,'colonize'=>(bool)($raw['colonize'] ?? false),'capture'=>(bool)($raw['capture'] ?? false),
            'refuel'=>$refuel,
            'fire'=>(is_string($fireRaw)||is_int($fireRaw))?(string)$fireRaw:null,
            'build'=>is_string($buildRaw)?substr($buildRaw,0,64):null,
            'upgrade'=>is_string($upgradeRaw)?substr($upgradeRaw,0,64):null,
            'share_resources'=>$share === null ? null : (bool)$share,
        ];
        $accepted[] = $shipId;
    }
    return $accepted;
}

function space_default_action(): array {
    return ['thrust'=>0.0,'turn'=>0.0,'land'=>false,'takeoff'=>false,'mine'=>false,'colonize'=>false,'capture'=>false,
        'refuel'=>false,'fire'=>null,'build'=>null,'upgrade'=>null,'share_resources'=>null];
}

function space_resource_source_keys(array $world, array $ship, bool $includePlanet=true): array {
    $sources = [['type'=>'ship','id'=>$ship['id']]];
    if (!empty($ship['resource_share'])) {
        $nearby = [];
        foreach ($world['ships'] as $other) {
            if ($other['id'] === $ship['id'] || $other['team'] !== $ship['team'] || empty($other['resource_share'])) continue;
            $d = space_distance($ship['x'],$ship['y'],$other['x'],$other['y']);
            if ($d <= SPACE_TRADE_RANGE) $nearby[] = ['id'=>$other['id'],'distance'=>$d];
        }
        usort($nearby, fn($a,$b)=>$a['distance'] <=> $b['distance']);
        foreach ($nearby as $n) $sources[] = ['type'=>'ship','id'=>$n['id']];
    }
    if ($includePlanet && !empty($ship['landed_on']) && isset($world['planets'][$ship['landed_on']])) {
        $sources[] = ['type'=>'planet','id'=>$ship['landed_on']];
    }
    return $sources;
}

function space_source_amount(array $world, array $source, string $resource): float {
    if ($source['type'] === 'planet') return (float)$world['planets'][$source['id']][$resource.'_stock'];
    return (float)$world['ships'][$source['id']]['cargo_'.$resource];
}

function space_set_source_amount(array &$world, array $source, string $resource, float $value): void {
    if ($source['type'] === 'planet') $world['planets'][$source['id']][$resource.'_stock'] = $value;
    else $world['ships'][$source['id']]['cargo_'.$resource] = $value;
}

function space_consume_resources(array &$world, string $shipId, float $metal=0.0, float $fuel=0.0, bool $includePlanet=true): bool {
    if (!isset($world['ships'][$shipId])) return false;
    $sources = space_resource_source_keys($world,$world['ships'][$shipId],$includePlanet);
    $sumMetal=0.0; $sumFuel=0.0;
    foreach ($sources as $source) { $sumMetal += space_source_amount($world,$source,'metal'); $sumFuel += space_source_amount($world,$source,'fuel'); }
    if ($sumMetal + 1e-9 < $metal || $sumFuel + 1e-9 < $fuel) return false;
    foreach ([['metal',$metal],['fuel',$fuel]] as [$resource,$needed]) {
        $remaining = $needed;
        foreach ($sources as $source) {
            if ($remaining <= 1e-9) break;
            $available = space_source_amount($world,$source,$resource);
            $used = min($available,$remaining);
            space_set_source_amount($world,$source,$resource,$available-$used);
            $remaining -= $used;
        }
    }
    return true;
}

function space_normalize_upgrade(?string $name): ?string {
    if (!$name) return null;
    $key = function_exists('mb_strtolower') ? mb_strtolower(trim($name),'UTF-8') : strtolower(trim($name));
    $aliases = [
        'speed'=>'engine','engine'=>'engine','geschwindigkeit'=>'engine','antrieb'=>'engine',
        'sensors'=>'scanner','sensor'=>'scanner','scanner'=>'scanner','sensoren'=>'scanner',
        'weapons'=>'weapon','weapon'=>'weapon','waffen'=>'weapon','waffe'=>'weapon',
        'shields'=>'shield','shield'=>'shield','schilde'=>'shield','schild'=>'shield',
        'cargo'=>'cargo','fracht'=>'cargo','tank'=>'tank','tankkapazitaet'=>'tank','tankkapazität'=>'tank',
    ];
    return $aliases[$key] ?? null;
}

function space_upgrade_costs(int $level): array {
    return match($level) { 1=>[40.0,10.0], 2=>[70.0,20.0], 3=>[110.0,30.0], default=>[INF,INF] };
}

function space_try_upgrade(array &$world, string $shipId, mixed $requested): bool {
    if (!isset($world['ships'][$shipId])) return false;
    $system = space_normalize_upgrade(is_string($requested)?$requested:null);
    if (!$system) return false;
    $attr = $system.'_level';
    $level = (int)$world['ships'][$shipId][$attr];
    if ($level >= 3) return false;
    $next = $level+1;
    [$metalCost,$fuelCost] = space_upgrade_costs($next);
    if (!space_consume_resources($world,$shipId,$metalCost,$fuelCost,true)) return false;
    $world['ships'][$shipId][$attr] = $next;
    if ($system === 'shield') $world['ships'][$shipId]['shield'] = space_shield_capacity($world['ships'][$shipId]);
    $world['logs'][] = sprintf('Tick %d: %s upgraded %s to L%d (%.0f metal, %.0f fuel).',$world['tick'],$shipId,$system,$next,$metalCost,$fuelCost);
    return true;
}

function space_mine(array &$world, string $shipId, string $planetId, mixed $mode): void {
    if (!isset($world['ships'][$shipId],$world['planets'][$planetId])) return;
    $ship = $world['ships'][$shipId]; $planet = $world['planets'][$planetId];
    if ($planet['owner'] !== null && $planet['owner'] !== $ship['team']) return;
    $free = max(0.0, space_cargo_capacity($ship) - $ship['cargo_fuel'] - $ship['cargo_metal']);
    if ($free <= 1e-9) return;
    $modeText = is_string($mode) ? (function_exists('mb_strtolower') ? mb_strtolower(trim($mode),'UTF-8') : strtolower(trim($mode))) : 'both';
    $wantsFuel = in_array($modeText,['both','beides','true','fuel','treibstoff'],true);
    $wantsMetal = in_array($modeText,['both','beides','true','metal','metall'],true);
    if (!$wantsFuel && !$wantsMetal) { $wantsFuel=$wantsMetal=true; }
    $requests=[];
    if ($wantsFuel) $requests[]=['fuel',min((float)$planet['fuel_stock'],(float)$planet['fuel_rate']*SPACE_MINE_FACTOR)];
    if ($wantsMetal) $requests[]=['metal',min((float)$planet['metal_stock'],(float)$planet['metal_rate']*SPACE_MINE_FACTOR)];
    $total=0.0; foreach($requests as $r) $total += $r[1];
    $scale = $total>0 ? min(1.0,$free/$total) : 0.0;
    foreach($requests as [$resource,$amount]) {
        $moved=$amount*$scale;
        if($resource==='fuel'){ $world['planets'][$planetId]['fuel_stock']-=$moved; $world['ships'][$shipId]['cargo_fuel']+=$moved; }
        else { $world['planets'][$planetId]['metal_stock']-=$moved; $world['ships'][$shipId]['cargo_metal']+=$moved; }
    }
}

function space_refuel(array &$world, string $shipId, mixed $amount): void {
    if (!isset($world['ships'][$shipId])) return;
    $ship = $world['ships'][$shipId];
    if (empty($ship['landed_on'])) return;
    $missing=max(0.0,space_fuel_capacity($ship)-(float)$ship['fuel']);
    if($missing<=1e-9)return;
    $wanted = $amount === true ? $missing : min($missing,max(0.0,(float)$amount));
    if($wanted<=0)return;
    $sources=space_resource_source_keys($world,$ship,true);
    $available=0.0;foreach($sources as $s)$available+=space_source_amount($world,$s,'fuel');
    $movedTotal=min($wanted,$available);$remaining=$movedTotal;
    foreach($sources as $s){if($remaining<=1e-9)break;$value=space_source_amount($world,$s,'fuel');$moved=min($value,$remaining);space_set_source_amount($world,$s,'fuel',$value-$moved);$remaining-=$moved;}
    $world['ships'][$shipId]['fuel'] += $movedTotal;
}

function space_build(array &$world,string $shipId,string $planetId,mixed $roleName): void {
    if(!isset($world['ships'][$shipId],$world['planets'][$planetId]) || !$roleName)return;
    $ship=$world['ships'][$shipId];$planet=$world['planets'][$planetId];
    if($planet['owner']!==$ship['team'])return;
    $role=function_exists('mb_substr') ? mb_substr(trim((string)$roleName),0,32,'UTF-8') : substr(trim((string)$roleName),0,32);if($role==='')$role='standard';
    if(!space_consume_resources($world,$shipId,SPACE_BUILD_METAL_COST,SPACE_BUILD_FUEL_COST,true))return;
    // Re-read ship and planet because resource consumption may have modified state.
    $ship=$world['ships'][$shipId];$planet=$world['planets'][$planetId];
    $newId=space_next_ship_id($world,$ship['team']);
    $angle=$ship['landed_angle']!==null?(float)$ship['landed_angle']:0.0;
    preg_match('/S(\d+)$/',$newId,$m);$n=isset($m[1])?(int)$m[1]:1;
    $spawnAngle=space_mod($angle+32+$n*19,360.0);$rad=deg2rad($spawnAngle);
    $world['ships'][$newId]=space_new_ship([
        'id'=>$newId,'team'=>$ship['team'],'x'=>$planet['x']+cos($rad)*($planet['radius']+3.0),
        'y'=>$planet['y']+sin($rad)*($planet['radius']+3.0),'heading'=>$spawnAngle,'role'=>$role,
        'fuel'=>SPACE_BUILD_FUEL_COST,'landed_on'=>$planetId,'landed_angle'=>$spawnAngle,
    ]);
    $world['teams'][$ship['team']]['score'] += 5;
    $world['logs'][] = "Tick {$world['tick']}: {$ship['team']} built {$newId} with role '{$role}'.";
}

function space_process_landed(array &$world,string $shipId,array $action): bool {
    if(!isset($world['ships'][$shipId]))return false;
    $landedOn=$world['ships'][$shipId]['landed_on'];if(!$landedOn)return false;
    if(!isset($world['planets'][$landedOn])){$world['ships'][$shipId]['landed_on']=null;$world['ships'][$shipId]['landed_angle']=null;return false;}
    $planet=$world['planets'][$landedOn];$ship=$world['ships'][$shipId];
    $angle=$ship['landed_angle']!==null?(float)$ship['landed_angle']:space_angle_to($planet['x'],$planet['y'],$ship['x'],$ship['y']);$rad=deg2rad($angle);
    $world['ships'][$shipId]['x']=$planet['x']+cos($rad)*($planet['radius']+3.0);$world['ships'][$shipId]['y']=$planet['y']+sin($rad)*($planet['radius']+3.0);
    $world['ships'][$shipId]['vx']=0.0;$world['ships'][$shipId]['vy']=0.0;
    if(!empty($action['refuel']))space_refuel($world,$shipId,$action['refuel']);
    $planet=$world['planets'][$landedOn];$ship=$world['ships'][$shipId];
    if(!empty($action['colonize']) && $planet['owner']===null){
        if(space_consume_resources($world,$shipId,SPACE_COLONIZE_METAL_COST,0.0,false)){
            $world['planets'][$landedOn]['owner']=$ship['team'];$world['teams'][$ship['team']]['score']+=20;
            $world['logs'][]="Tick {$world['tick']}: {$world['teams'][$ship['team']]['name']} colonized {$planet['name']}.";
        }
    }
    $planet=$world['planets'][$landedOn];$ship=$world['ships'][$shipId];
    if(!empty($action['capture']) && $planet['owner']!==null && $planet['owner']!==$ship['team']){
        if($ship['capture_planet']!==$planet['id']){$world['ships'][$shipId]['capture_planet']=$planet['id'];$world['ships'][$shipId]['capture_progress']=0;}
        $world['ships'][$shipId]['capture_progress']++;
    }elseif(!empty($ship['capture_planet'])){$world['ships'][$shipId]['capture_planet']=null;$world['ships'][$shipId]['capture_progress']=0;}
    if(!empty($action['mine']))space_mine($world,$shipId,$landedOn,$action['mine']);
    if(!empty($action['build']))space_build($world,$shipId,$landedOn,$action['build']);
    if(!empty($action['upgrade']))space_try_upgrade($world,$shipId,$action['upgrade']);
    if(!empty($action['takeoff'])){
        if(!isset($world['ships'][$shipId]))return false;
        $world['ships'][$shipId]['x']=$planet['x']+cos($rad)*($planet['radius']+34.0);$world['ships'][$shipId]['y']=$planet['y']+sin($rad)*($planet['radius']+34.0);
        $world['ships'][$shipId]['landed_on']=null;$world['ships'][$shipId]['landed_angle']=null;$world['ships'][$shipId]['capture_planet']=null;$world['ships'][$shipId]['capture_progress']=0;
        $world['ships'][$shipId]['heading']=$angle;$world['ships'][$shipId]['vx']=cos($rad)*SPACE_TAKEOFF_SPEED;$world['ships'][$shipId]['vy']=sin($rad)*SPACE_TAKEOFF_SPEED;
        return false;
    }
    return true;
}

function space_apply_turn_and_thrust(array &$ship,array $action): void {
    $requestedTurn=space_clamp((float)($action['turn']??0.0),-SPACE_TURN_LIMIT,SPACE_TURN_LIMIT);
    if(abs($requestedTurn)>1e-9 && $ship['fuel']>0){
        $possible=$ship['fuel']/SPACE_TURN_FUEL_PER_DEG;$sign=$requestedTurn<0?-1.0:1.0;$actualTurn=$sign*min(abs($requestedTurn),$possible);
        $ship['fuel']=max(0.0,$ship['fuel']-abs($actualTurn)*SPACE_TURN_FUEL_PER_DEG);$ship['heading']=space_mod($ship['heading']+$actualTurn,360.0);
    }
    $thrust=space_clamp((float)($action['thrust']??0.0),0.0,1.0);
    if($thrust>0 && $ship['fuel']>0){
        $requestedFuel=SPACE_THRUST_FUEL_USE*$thrust;$actualThrust=$ship['fuel']>=$requestedFuel?$thrust:$thrust*($ship['fuel']/$requestedFuel);
        $used=SPACE_THRUST_FUEL_USE*$actualThrust;$ship['fuel']=max(0.0,$ship['fuel']-$used);
        $ax=cos(deg2rad($ship['heading']))*space_effective_accel($ship)*$actualThrust;$ay=sin(deg2rad($ship['heading']))*space_effective_accel($ship)*$actualThrust;
        $oldSpeed=hypot($ship['vx'],$ship['vy']);$newVx=$ship['vx']+$ax;$newVy=$ship['vy']+$ay;$newSpeed=hypot($newVx,$newVy);$maxSpeed=space_effective_max_speed($ship);
        if($newSpeed>$maxSpeed && $newSpeed>$oldSpeed){if($oldSpeed>=$maxSpeed)return;$scale=$maxSpeed/$newSpeed;$newVx*=$scale;$newVy*=$scale;}
        $ship['vx']=$newVx;$ship['vy']=$newVy;
    }
}

function space_fire(array &$world,string $shipId,mixed $targetId): void {
    if(!$targetId || !isset($world['ships'][$shipId]) || $world['ships'][$shipId]['cooldown']>0)return;
    $targetId=(string)$targetId;if(!isset($world['ships'][$targetId]))return;
    $ship=$world['ships'][$shipId];$target=$world['ships'][$targetId];if($target['team']===$ship['team'])return;
    if(space_distance($ship['x'],$ship['y'],$target['x'],$target['y'])>space_effective_weapon_range($ship))return;
    $damage=space_effective_weapon_damage($ship);$absorbed=min($target['shield'],$damage);$world['ships'][$targetId]['shield']-=$absorbed;$hpDamage=$damage-$absorbed;$world['ships'][$targetId]['hp']-=$hpDamage;$world['ships'][$targetId]['last_hit_tick']=$world['tick'];
    $world['ships'][$targetId]['events'][]=[
        'type'=>'hit','tick'=>$world['tick'],'attacker'=>$shipId,'attacker_team'=>$ship['team'],
        'direction'=>round(space_angle_to($target['x'],$target['y'],$ship['x'],$ship['y']),2),'damage'=>round($damage,1),
        'shield_absorbed'=>round($absorbed,1),'hp_after'=>round(max(0.0,$world['ships'][$targetId]['hp']),1),'shield_after'=>round($world['ships'][$targetId]['shield'],1),
    ];
    $world['ships'][$shipId]['cooldown']=SPACE_SHOT_COOLDOWN;$world['logs'][]=sprintf('Tick %d: %s hit %s for %.1f.',$world['tick'],$shipId,$targetId,$damage);
}

function space_apply_action(array &$world,string $shipId,array $action): void {
    if(!isset($world['ships'][$shipId]))return;
    if($world['ships'][$shipId]['cooldown']>0)$world['ships'][$shipId]['cooldown']--;
    if(array_key_exists('share_resources',$action)&&$action['share_resources']!==null)$world['ships'][$shipId]['resource_share']=(bool)$action['share_resources'];
    if(space_process_landed($world,$shipId,$action))return;
    if(!isset($world['ships'][$shipId]))return;
    if(!empty($action['upgrade']))space_try_upgrade($world,$shipId,$action['upgrade']);
    if(!isset($world['ships'][$shipId]))return;
    $ship=&$world['ships'][$shipId];space_apply_turn_and_thrust($ship,$action);unset($ship);
    space_fire($world,$shipId,$action['fire']??null);
}

function space_gravity_acceleration(array $world,array $ship): array {
    if(empty($world['gravity_enabled']) || !empty($ship['landed_on']))return[0.0,0.0];$gx=0.0;$gy=0.0;
    foreach($world['planets'] as $planet){$dx=$planet['x']-$ship['x'];$dy=$planet['y']-$ship['y'];$d=hypot($dx,$dy);if($d<=$planet['radius']+3.0 || $d>SPACE_GRAVITY_RANGE)continue;
        $m=SPACE_GRAVITY_STRENGTH*($planet['radius']/80.0)**2*(300.0/max($d,300.0))**2;$gx+=$dx/$d*$m;$gy+=$dy/$d*$m;}
    $total=hypot($gx,$gy);if($total>SPACE_GRAVITY_ACCEL_CAP){$gx*=SPACE_GRAVITY_ACCEL_CAP/$total;$gy*=SPACE_GRAVITY_ACCEL_CAP/$total;}return[$gx,$gy];
}

function space_segment_circle_hit(float $x1,float $y1,float $x2,float $y2,float $cx,float $cy,float $radius): ?float {
    $dx=$x2-$x1;$dy=$y2-$y1;$fx=$x1-$cx;$fy=$y1-$cy;$a=$dx*$dx+$dy*$dy;if($a<=1e-12)return null;$b=2.0*($fx*$dx+$fy*$dy);$c=$fx*$fx+$fy*$fy-$radius*$radius;$disc=$b*$b-4.0*$a*$c;if($disc<0)return null;$root=sqrt($disc);$vals=[];
    foreach([(-$b-$root)/(2.0*$a),(-$b+$root)/(2.0*$a)] as $t)if($t>=0.0&&$t<=1.0)$vals[]=$t;return $vals?min($vals):null;
}

function space_land(array &$world,string $shipId,string $planetId): void {
    $ship=$world['ships'][$shipId];$planet=$world['planets'][$planetId];$ang=space_angle_to($planet['x'],$planet['y'],$ship['x'],$ship['y']);$rad=deg2rad($ang);
    $world['ships'][$shipId]['x']=$planet['x']+cos($rad)*($planet['radius']+3.0);$world['ships'][$shipId]['y']=$planet['y']+sin($rad)*($planet['radius']+3.0);$world['ships'][$shipId]['vx']=0.0;$world['ships'][$shipId]['vy']=0.0;$world['ships'][$shipId]['landed_on']=$planetId;$world['ships'][$shipId]['landed_angle']=$ang;$world['logs'][]="Tick {$world['tick']}: {$shipId} landed on {$planet['name']}.";
}

function space_move_ship(array &$world,string $shipId,array $action): void {
    if(!isset($world['ships'][$shipId])||!empty($world['ships'][$shipId]['landed_on']))return;$ship=$world['ships'][$shipId];[$gx,$gy]=space_gravity_acceleration($world,$ship);$world['ships'][$shipId]['vx']+=$gx;$world['ships'][$shipId]['vy']+=$gy;$ship=$world['ships'][$shipId];
    $oldX=$ship['x'];$oldY=$ship['y'];$newX=$oldX+$ship['vx'];$newY=$oldY+$ship['vy'];$speed=hypot($ship['vx'],$ship['vy']);$firstHit=null;
    foreach($world['planets'] as $planet){$t=space_segment_circle_hit($oldX,$oldY,$newX,$newY,$planet['x'],$planet['y'],$planet['radius']+2.0);if($t!==null&&($firstHit===null||$t<$firstHit[0]))$firstHit=[$t,$planet['id']];}
    if($firstHit!==null){[$t,$planetId]=$firstHit;$planet=$world['planets'][$planetId];$hitX=$oldX+($newX-$oldX)*$t;$hitY=$oldY+($newY-$oldY)*$t;$world['ships'][$shipId]['x']=$hitX;$world['ships'][$shipId]['y']=$hitY;
        if(!empty($action['land'])&&$speed<=SPACE_LAND_SPEED){space_land($world,$shipId,$planetId);return;}
        $nx=$hitX-$planet['x'];$ny=$hitY-$planet['y'];$nlen=hypot($nx,$ny)?:1.0;$nx/=$nlen;$ny/=$nlen;$world['ships'][$shipId]['x']=$planet['x']+$nx*($planet['radius']+3.0);$world['ships'][$shipId]['y']=$planet['y']+$ny*($planet['radius']+3.0);$dot=$ship['vx']*$nx+$ship['vy']*$ny;$world['ships'][$shipId]['vx']=($ship['vx']-2.0*$dot*$nx)*0.22;$world['ships'][$shipId]['vy']=($ship['vy']-2.0*$dot*$ny)*0.22;return;}
    $world['ships'][$shipId]['x']=$newX;$world['ships'][$shipId]['y']=$newY;
    if(!empty($action['land'])&&$speed<=SPACE_LAND_SPEED){foreach($world['planets'] as $planet){if(space_distance($newX,$newY,$planet['x'],$planet['y'])<=$planet['radius']+SPACE_LAND_MARGIN){space_land($world,$shipId,$planet['id']);return;}}}
    if($world['ships'][$shipId]['x']<0||$world['ships'][$shipId]['x']>SPACE_WORLD_SIZE){$world['ships'][$shipId]['x']=space_clamp($world['ships'][$shipId]['x'],0.0,SPACE_WORLD_SIZE);$world['ships'][$shipId]['vx']*=-0.35;}
    if($world['ships'][$shipId]['y']<0||$world['ships'][$shipId]['y']>SPACE_WORLD_SIZE){$world['ships'][$shipId]['y']=space_clamp($world['ships'][$shipId]['y'],0.0,SPACE_WORLD_SIZE);$world['ships'][$shipId]['vy']*=-0.35;}
}

function space_reset_captured_ship(array &$ship): void {
    $ship['role']='standard';foreach(['engine_level','tank_level','scanner_level','cargo_level','weapon_level','shield_level'] as $k)$ship[$k]=0;$ship['shield']=0.0;$ship['resource_share']=false;$ship['capture_planet']=null;$ship['capture_progress']=0;$ship['fuel']=min($ship['fuel'],space_fuel_capacity($ship));$ship['cargo_fuel']=min($ship['cargo_fuel'],space_cargo_capacity($ship));$ship['cargo_metal']=min($ship['cargo_metal'],max(0.0,space_cargo_capacity($ship)-$ship['cargo_fuel']));$ship['events']=[];
}

function space_convert_ship(array &$world,string $shipId,string $newTeam,string $planetId): void {
    if(!isset($world['ships'][$shipId]))return;$planet=$world['planets'][$planetId];$newId=space_next_ship_id($world,$newTeam);$ship=$world['ships'][$shipId];unset($world['ships'][$shipId]);$ship['id']=$newId;$ship['team']=$newTeam;space_reset_captured_ship($ship);$world['ships'][$newId]=$ship;$world['logs'][]="Tick {$world['tick']}: {$shipId} was captured while invading {$planet['name']} and is now {$newId}.";
}

function space_resolve_captures(array &$world): void {
    $conversions=[];
    foreach($world['planets'] as $planet){$owner=$planet['owner'];if(!$owner)continue;$hasDefender=false;foreach($world['ships'] as $s){if($s['team']===$owner&&$s['landed_on']===$planet['id']){$hasDefender=true;break;}}if(!$hasDefender)continue;
        foreach($world['ships'] as $s){if($s['team']!==$owner&&$s['landed_on']===$planet['id']&&$s['capture_planet']===$planet['id']&&$s['capture_progress']>0)$conversions[]=[$s['id'],$owner,$planet['id']];}}
    foreach($conversions as [$shipId,$owner,$planetId])if(isset($world['ships'][$shipId]))space_convert_ship($world,$shipId,$owner,$planetId);
    $shipIds=array_keys($world['ships']);foreach($shipIds as $shipId){if(!isset($world['ships'][$shipId]))continue;$ship=$world['ships'][$shipId];if(empty($ship['capture_planet'])||$ship['capture_progress']<SPACE_CAPTURE_TICKS)continue;$planetId=$ship['capture_planet'];
        if(!isset($world['planets'][$planetId])||$ship['landed_on']!==$planetId||in_array($world['planets'][$planetId]['owner'],[null,$ship['team']],true)){$world['ships'][$shipId]['capture_planet']=null;$world['ships'][$shipId]['capture_progress']=0;continue;}
        $oldOwner=$world['planets'][$planetId]['owner'];$world['planets'][$planetId]['owner']=$ship['team'];$world['ships'][$shipId]['capture_planet']=null;$world['ships'][$shipId]['capture_progress']=0;$world['teams'][$ship['team']]['score']+=30;$world['logs'][]="Tick {$world['tick']}: {$world['teams'][$ship['team']]['name']} conquered {$world['planets'][$planetId]['name']} from {$oldOwner}.";
        foreach($world['ships'] as &$other)if($other['capture_planet']===$planetId){$other['capture_planet']=null;$other['capture_progress']=0;}unset($other);
    }
}

function space_produce_planet_resources(array &$world): void {
    foreach($world['planets'] as &$p){
        $fuelCap=(float)($p['fuel_capacity']??1600.0);$metalCap=(float)($p['metal_capacity']??1100.0);
        $p['fuel_stock']=min($fuelCap,$p['fuel_stock']+$p['fuel_rate']*SPACE_PLANET_PRODUCTION_FACTOR);
        $p['metal_stock']=min($metalCap,$p['metal_stock']+$p['metal_rate']*SPACE_PLANET_PRODUCTION_FACTOR);
    }unset($p);
}

function space_recharge_shields(array &$world): void {
    foreach($world['ships'] as &$ship){$cap=space_shield_capacity($ship);if($cap<=0||$ship['shield']>=$cap)continue;if($world['tick']-$ship['last_hit_tick']>=3)$ship['shield']=min($cap,$ship['shield']+2.0*$ship['shield_level']);}unset($ship);
}

function space_run_tick(array &$world): void {
    if(empty($world['running']))return;$world['tick']++;
    foreach($world['ships'] as &$s)$s['events']=[];unset($s);
    $actions=[];foreach(array_keys($world['ships']) as $sid){$actions[$sid]=$world['pending_actions'][$sid]??space_default_action();unset($world['pending_actions'][$sid]);}
    foreach($actions as $sid=>$action)if(isset($world['ships'][$sid])&&array_key_exists('share_resources',$action)&&$action['share_resources']!==null)$world['ships'][$sid]['resource_share']=(bool)$action['share_resources'];
    foreach(array_keys($world['ships']) as $sid)if(isset($world['ships'][$sid]))space_apply_action($world,$sid,$actions[$sid]??space_default_action());
    foreach(array_keys($world['ships']) as $sid)if(isset($world['ships'][$sid]))space_move_ship($world,$sid,$actions[$sid]??space_default_action());
    space_resolve_captures($world);space_produce_planet_resources($world);space_recharge_shields($world);
    foreach(array_keys($world['ships']) as $sid)if(isset($world['ships'][$sid])&&$world['ships'][$sid]['hp']<=0){$world['logs'][]="Tick {$world['tick']}: {$sid} was destroyed.";unset($world['ships'][$sid]);}
    $world['logs']=array_slice($world['logs'],-180);
}

function space_public_world(array $world): array {
    $teams=[];foreach($world['teams'] as $t){[$fuel,$metal]=space_team_resource_totals($world,$t['id']);$ships=0;$planets=0;foreach($world['ships'] as $s)if($s['team']===$t['id'])$ships++;foreach($world['planets'] as $p)if($p['owner']===$t['id'])$planets++;
        $teams[]=['id'=>$t['id'],'name'=>$t['name'],'color'=>$t['color'],'fuel'=>round($fuel,1),'metal'=>round($metal,1),'score'=>$t['score'],'ships'=>$ships,'planets'=>$planets,'connected'=>(($world['tick']-$t['last_seen_tick'])<=5)];}
    $ships=[];foreach($world['ships'] as $s){$x=$s;$x['speed']=round(hypot($s['vx'],$s['vy']),2);$x['fuel_capacity']=round(space_fuel_capacity($s),1);$x['cargo_capacity']=round(space_cargo_capacity($s),1);$x['shield_capacity']=round(space_shield_capacity($s),1);$ships[]=$x;}
    return ['seed'=>$world['seed'],'tick'=>$world['tick'],'ready'=>$world['ready'],'running'=>$world['running'],'gravity_enabled'=>$world['gravity_enabled'],'world_size'=>SPACE_WORLD_SIZE,
        'teams'=>$teams,'planets'=>array_values($world['planets']),'ships'=>$ships,'logs'=>array_slice($world['logs'],-30)];
}
