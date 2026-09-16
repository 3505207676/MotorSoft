<?php
require_once __DIR__ . '/../../bootstrap.php';

$id = $_GET['id'] ?? null;
if ($id) {
    seguridad_user_controller()->mostrar();
} else {
    seguridad_user_controller()->listar();
}
