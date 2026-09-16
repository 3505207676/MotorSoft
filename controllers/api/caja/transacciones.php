<?php
require_once __DIR__ . '/../../bootstrap.php';
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    caja_controller()->registrarTransaccion();
} else {
    caja_controller()->listarTransacciones();
}
