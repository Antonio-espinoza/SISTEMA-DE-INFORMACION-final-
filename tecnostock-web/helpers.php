<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Autenticación ---
function usuario_actual(): ?array {
    return $_SESSION['usuario'] ?? null;
}

function requerir_login(): void {
    if (!usuario_actual()) {
        header('Location: login.php');
        exit;
    }
}

// --- Mensajes flash ---
function flash_set(string $tipo, string $mensaje): void {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flash_mostrar(): void {
    if (empty($_SESSION['flash'])) return;
    $f = $_SESSION['flash'];
    $clase = $f['tipo'] === 'error' ? 'flash-error' : 'flash-ok';
    echo '<div class="flash ' . $clase . '">' . htmlspecialchars($f['mensaje']) . '</div>';
    unset($_SESSION['flash']);
}

// --- Utilidades ---
function money($valor): string {
    return '$' . number_format((float)$valor, 0, ',', '.');
}

function iconoCategoria(string $nombre): string {
    $n = mb_strtolower($nombre);
    if (str_contains($n, 'audio'))          return '🎧';
    if (str_contains($n, 'carga'))          return '🔌';
    if (str_contains($n, 'almacen'))        return '💾';
    if (str_contains($n, 'perif'))          return '🖱️';
    if (str_contains($n, 'funda') || str_contains($n, 'protec')) return '🛡️';
    return '📦';
}
