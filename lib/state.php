<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/engine.php';

/**
 * Atomically loads, optionally advances, mutates and saves the single game world.
 * Callback signature: function(array &$world, array &$meta): mixed
 */
function space_with_world_lock(callable $callback, bool $advance=true): mixed {
    $pdo = space_pdo();
    $pdo->beginTransaction();
    try {
        $row = $pdo->query('SELECT id,state_json,last_tick_at,tick_seconds FROM space_ag_state WHERE id=1 FOR UPDATE')->fetch();
        if (!$row) throw new RuntimeException('Datenbank ist nicht initialisiert. Bitte install.php aufrufen.');
        $originalJson = (string)$row['state_json'];
        $originalLastTickAt = (float)$row['last_tick_at'];
        $originalTickSeconds = (float)$row['tick_seconds'];
        $world = json_decode($originalJson, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($world)) $world = space_empty_world();
        $meta = ['last_tick_at'=>(float)$row['last_tick_at'],'tick_seconds'=>(float)$row['tick_seconds']];
        $now = microtime(true);

        if ($advance && !empty($world['ready']) && !empty($world['running'])) {
            $tickSeconds = max(0.15, min(6.0, (float)$meta['tick_seconds']));
            $elapsed = max(0.0, $now - (float)$meta['last_tick_at']);
            $due = (int)floor($elapsed / $tickSeconds);
            if ($due > 0) {
                $limit = max(1, (int)(space_config()['max_catchup_ticks'] ?? 8));
                $run = min($due, $limit);
                for ($i=0; $i<$run; $i++) space_run_tick($world);
                // If the site was idle for a long time, deliberately drop old backlog.
                $meta['last_tick_at'] = $due > $limit ? $now : ((float)$meta['last_tick_at'] + $run*$tickSeconds);
            }
        } elseif ($advance) {
            // Paused/uninitialized worlds must not accumulate a catch-up backlog.
            $meta['last_tick_at'] = $now;
        }

        $result = $callback($world, $meta);
        $newJson = json_encode($world, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRESERVE_ZERO_FRACTION|JSON_THROW_ON_ERROR);
        $newLastTickAt = (float)$meta['last_tick_at'];
        $newTickSeconds = max(0.15,min(6.0,(float)$meta['tick_seconds']));
        if ($newJson !== $originalJson || abs($newLastTickAt-$originalLastTickAt) > 1e-6 || abs($newTickSeconds-$originalTickSeconds) > 1e-9) {
            $stmt = $pdo->prepare('UPDATE space_ag_state SET state_json=?, last_tick_at=?, tick_seconds=? WHERE id=1');
            $stmt->execute([$newJson,$newLastTickAt,$newTickSeconds]);
        }
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
