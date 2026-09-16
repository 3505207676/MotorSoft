<?php

require_once __DIR__ . '/../../models/Seguridad/LogAuditoria.php';
require_once __DIR__ . '/../../repositories/Seguridad/LogAuditoriaRepository.php';
require_once __DIR__ . '/AuthService.php';
require_once __DIR__ . '/AuditoriaPresentador.php';

class AuditoriaService
{
    private LogAuditoriaRepository $logs;
    private AuthService $auth;

    public function __construct(LogAuditoriaRepository $logs, AuthService $auth)
    {
        $this->logs = $logs;
        $this->auth = $auth;
    }

    /** @return LogAuditoria[] */
    public function listar(array $filtros = []): array
    {
        return $this->logs->listar($filtros);
    }

    public function buscarPorId(int $id): LogAuditoria
    {
        $log = $this->logs->buscarPorId($id);
        if (!$log) {
            throw new AppException('Registro de auditoría no encontrado', HTTP_NOT_FOUND);
        }
        return $log;
    }

    /** @return string[] */
    public function tablas(): array
    {
        return $this->logs->listarTablas();
    }

    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function listarPresentado(array $query = []): array
    {
        $filtros = $this->normalizarFiltros($query);
        $items = [];
        foreach ($this->listar($filtros) as $log) {
            $items[] = AuditoriaPresentador::presentar($log);
        }
        $total = $this->logs->contar($filtros);
        return [
            'logs'     => $items,
            'modulos'  => AuditoriaPresentador::catalogoModulos(),
            'resumen'  => [
                'total'      => $total,
                'mostrados'  => count($items),
                'hoy'        => $this->logs->contarHoy(),
                'truncado'   => $total > count($items),
                'desde'      => $filtros['desde'] ?? null,
                'hasta'      => $filtros['hasta'] ?? null,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function normalizarFiltros(array $query): array
    {
        $desde = trim((string) ($query['desde'] ?? ''));
        $hasta = trim((string) ($query['hasta'] ?? ''));
        if (!$this->esFecha($desde)) {
            $desde = date('Y-m-01');
        }
        if (!$this->esFecha($hasta)) {
            $hasta = date('Y-m-d');
        }
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $filtros = [
            'desde' => $desde,
            'hasta' => $hasta,
            'limite' => min(1000, max(50, (int) ($query['limite'] ?? 500))),
        ];
        $accion = strtoupper(trim((string) ($query['accion'] ?? '')));
        if ($accion !== '' && $accion !== 'TODOS') {
            $filtros['accion'] = $accion;
        }
        if (!empty($query['id_usuario'])) {
            $filtros['id_usuario'] = (int) $query['id_usuario'];
        }
        $q = trim((string) ($query['q'] ?? $query['busqueda'] ?? ''));
        if ($q !== '') {
            $filtros['q'] = $q;
        }
        $tabla = trim((string) ($query['tabla'] ?? $query['tabla_afectada'] ?? ''));
        if ($tabla !== '') {
            $filtros['tabla'] = $tabla;
        }
        $modulo = strtolower(trim((string) ($query['modulo'] ?? '')));
        if ($modulo !== '' && $modulo !== 'todos') {
            if ($modulo === 'otros') {
                $filtros['excluir_tablas'] = AuditoriaPresentador::tablasConocidas();
            } else {
                $tablas = AuditoriaPresentador::tablasDeModulo($modulo);
                if ($tablas) {
                    $filtros['tablas'] = $tablas;
                }
            }
        }
        return $filtros;
    }

    private function esFecha(string $valor): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return false;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $valor);
        return $dt instanceof DateTime && $dt->format('Y-m-d') === $valor;
    }
}
