<?php
declare(strict_types=1);

function space_json(mixed $data, int $status=200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    exit;
}

function space_error(string $detail, int $status=400): never {
    space_json(['detail'=>$detail], $status);
}

function space_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return [];
    try { $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
    catch (Throwable) { space_error('Ungültige JSON-Daten.', 400); }
    if (!is_array($data)) space_error('JSON-Objekt erwartet.', 400);
    return $data;
}

function space_require_method(string $method): void {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) space_error('Methode nicht erlaubt.',405);
}

function space_base_url(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\','/', $_SERVER['SCRIPT_NAME'] ?? '/');
    // API scripts live one or two directories below the project root.
    $marker = strpos($script, '/api/');
    if ($marker !== false) $base = substr($script, 0, $marker);
    else $base = rtrim(dirname($script), '/.');
    return $scheme . '://' . $host . ($base ? $base : '');
}
