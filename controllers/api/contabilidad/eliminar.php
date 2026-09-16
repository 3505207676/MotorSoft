<?php
require_once __DIR__ . '/../../bootstrap.php';
try {
    ApiRequest::requireMethod(['POST', 'DELETE']);
    throw new AppException('Los movimientos de caja no se eliminan: quedan para auditoría', HTTP_FORBIDDEN);
} catch (Throwable $e) {
    ApiResponse::fromException($e);
}
