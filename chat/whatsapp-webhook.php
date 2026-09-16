<?php
ob_start();
ini_set('display_errors', '0');
require_once __DIR__ . '/../../bootstrap.php';
chat_controller()->webhookWhatsApp();
