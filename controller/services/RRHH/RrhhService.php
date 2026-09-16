<?php

require_once __DIR__ . '/../../models/RRHH/Contrato.php';
require_once __DIR__ . '/../../models/RRHH/Nomina.php';
require_once __DIR__ . '/../../models/RRHH/NominaRol.php';
require_once __DIR__ . '/../../models/Seguridad/Usuario.php';
require_once __DIR__ . '/../../repositories/RRHH/ContratoRepository.php';
require_once __DIR__ . '/../../repositories/RRHH/NominaRepository.php';
require_once __DIR__ . '/../../repositories/RRHH/NominaRolRepository.php';
require_once __DIR__ . '/../../repositories/Seguridad/UsuarioRepository.php';
require_once __DIR__ . '/../Seguridad/AuthService.php';
require_once __DIR__ . '/../Caja/CajaService.php';

class RrhhService
{
    private Database $db;
    private ContratoRepository $contratos;
    private NominaRepository $nominas;
    private NominaRolRepository $lineas;
    private UsuarioRepository $usuarios;
    private CajaService $caja;
    private AuthService $auth;
    private ?CorreoService $correo;

    public function __construct(
        Database $db,
        ContratoRepository $contratos,
        NominaRepository $nominas,
        NominaRolRepository $lineas,
        UsuarioRepository $usuarios,
        CajaService $caja,
        AuthService $auth,
        ?CorreoService $correo = null
    ) {
        $this->db        = $db;
        $this->contratos = $contratos;
        $this->nominas   = $nominas;
        $this->lineas    = $lineas;
        $this->usuarios  = $usuarios;
        $this->caja      = $caja;
        $this->auth      = $auth;
        $this->correo    = $correo;
    }

    /** @return Contrato[] */
    public function listarContratos(): array
    {
        return $this->contratos->listar();
    }

    public function buscarContrato(int $id): Contrato
    {
        $contrato = $this->contratos->buscarPorId($id);
        if (!$contrato) {
            throw new AppException('Contrato no encontrado', HTTP_NOT_FOUND);
        }
        return $contrato;
    }

    public function guardarContrato(array $data, Usuario $actor): Contrato
    {
        $idUsuario = (int) ($data['id_usuario'] ?? 0);
        $salario   = (float) ($data['salario_base'] ?? 0);
        $pctUi     = (float) ($data['porcentaje_comision'] ?? $data['porcentaje_comicion'] ?? 0);
        $ingreso   = trim((string) ($data['fecha_ingreso'] ?? ''));
        $estado    = trim((string) ($data['estado_contrato'] ?? Contrato::VIGENTE));

        if ($idUsuario < 1) {
            throw new AppException('Seleccione un empleado', HTTP_BAD_REQUEST);
        }
        $empleado = $this->usuarios->buscarPorId($idUsuario);
        if (!$empleado || !$empleado->isActivo()) {
            throw new AppException('El empleado no existe o está inactivo', HTTP_BAD_REQUEST);
        }
        if ($salario <= 0) {
            throw new AppException('El salario base debe ser mayor a cero', HTTP_BAD_REQUEST);
        }
        if ($pctUi < 0 || $pctUi > 100) {
            throw new AppException('La comisión debe estar entre 0 y 100%', HTTP_BAD_REQUEST);
        }
        $comision = round($pctUi / 100, 2);
        if ($ingreso === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ingreso)) {
            throw new AppException('La fecha de ingreso no es válida', HTTP_BAD_REQUEST);
        }
        if (!in_array($estado, [Contrato::VIGENTE, Contrato::FINALIZADO], true)) {
            throw new AppException('Estado de contrato no válido', HTTP_BAD_REQUEST);
        }
        if ($estado === Contrato::VIGENTE && $this->contratos->vigentePorUsuario($idUsuario)) {
            throw new AppException('Ese empleado ya tiene un contrato vigente', HTTP_BAD_REQUEST);
        }

        $contrato = new Contrato($idUsuario, $salario, (int) $actor->getIdUsuario(), $comision, $ingreso);
        $contrato->setEstadoContrato($estado);
        if ($estado === Contrato::FINALIZADO) {
            $retiro = trim((string) ($data['fecha_retiro'] ?? date('Y-m-d')));
            $contrato->setFechaRetiro($retiro !== '' ? $retiro : date('Y-m-d'));
        }

        $guardado = $this->contratos->guardar($contrato);
        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Contratos',
            'registroId'    => (int) $guardado->getIdContrato(),
            'valoresNuevos' => $guardado->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        if ($this->correo && $empleado) {
            $this->correo->enviarAvisoContrato($empleado, $ingreso);
        }
        return $guardado;
    }

    public function actualizarContrato(int $id, array $data, Usuario $actor): Contrato
    {
        $contrato = $this->buscarContrato($id);

        if (isset($data['id_usuario'])) {
            $idUsuario = (int) $data['id_usuario'];
            $empleado = $this->usuarios->buscarPorId($idUsuario);
            if (!$empleado) {
                throw new AppException('El empleado no existe', HTTP_BAD_REQUEST);
            }
            $contrato->setIdUsuario($idUsuario);
        }
        if (isset($data['salario_base'])) {
            $salario = (float) $data['salario_base'];
            if ($salario <= 0) {
                throw new AppException('El salario base debe ser mayor a cero', HTTP_BAD_REQUEST);
            }
            $contrato->setSalarioBase($salario);
        }
        if (isset($data['porcentaje_comision']) || isset($data['porcentaje_comicion'])) {
            $pctUi = (float) ($data['porcentaje_comision'] ?? $data['porcentaje_comicion']);
            if ($pctUi < 0 || $pctUi > 100) {
                throw new AppException('La comisión debe estar entre 0 y 100%', HTTP_BAD_REQUEST);
            }
            $contrato->setPorcentajeComision(round($pctUi / 100, 2));
        }
        if (!empty($data['fecha_ingreso'])) {
            $ingreso = trim((string) $data['fecha_ingreso']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ingreso)) {
                throw new AppException('La fecha de ingreso no es válida', HTTP_BAD_REQUEST);
            }
            $contrato->setFechaIngreso($ingreso);
        }
        if (isset($data['estado_contrato'])) {
            $estado = trim((string) $data['estado_contrato']);
            if (!in_array($estado, [Contrato::VIGENTE, Contrato::FINALIZADO], true)) {
                throw new AppException('Estado de contrato no válido', HTTP_BAD_REQUEST);
            }
            if ($estado === Contrato::VIGENTE) {
                $otro = $this->contratos->vigentePorUsuario($contrato->getIdUsuario(), $id);
                if ($otro) {
                    throw new AppException('Ese empleado ya tiene un contrato vigente', HTTP_BAD_REQUEST);
                }
                $contrato->setEstadoContrato(Contrato::VIGENTE);
                $contrato->setFechaRetiro(null);
            } else {
                $retiro = trim((string) ($data['fecha_retiro'] ?? date('Y-m-d')));
                $contrato->finalizar($retiro !== '' ? $retiro : date('Y-m-d'), (int) $actor->getIdUsuario());
            }
        }

        $contrato->tocarUpdatedAt((int) $actor->getIdUsuario());
        $guardado = $this->contratos->guardar($contrato);
        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'UPDATE',
            'tablaAfectada' => 'Contratos',
            'registroId'    => $id,
            'valoresNuevos' => $guardado->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        return $guardado;
    }

    public function eliminarContrato(int $id, array $contexto, Usuario $actor): void
    {
        $contrato = $this->buscarContrato($id);
        if ($this->nominas->contarPorContrato($id) > 0) {
            throw new AppException('No se puede eliminar: el contrato tiene nóminas registradas. Finalícelo.', HTTP_BAD_REQUEST);
        }
        $contrato->marcarEliminado((int) $actor->getIdUsuario());
        $this->contratos->guardar($contrato);
        $this->auth->registrarLog([
            'token'         => $contexto['token'] ?? null,
            'accion'        => 'DELETE',
            'tablaAfectada' => 'Contratos',
            'registroId'    => $id,
            'valoresNuevos' => $contrato->toArray(),
            'ipAddress'     => $contexto['ip'] ?? null,
        ]);
    }

    /** @return Nomina[] */
    public function listarNominas(): array
    {
        return $this->nominas->listar();
    }

    public function buscarNomina(int $id): Nomina
    {
        $nomina = $this->nominas->buscarPorId($id);
        if (!$nomina) {
            throw new AppException('Nómina no encontrada', HTTP_NOT_FOUND);
        }
        return $nomina;
    }

    public function registrarNomina(array $data, Usuario $actor): Nomina
    {
        $idContrato = (int) ($data['id_contrato'] ?? 0);
        $periodo    = trim((string) ($data['periodo_pago'] ?? ''));
        $fechaPago  = trim((string) ($data['fecha_pago'] ?? ''));

        if ($idContrato < 1) {
            throw new AppException('Seleccione un contrato vigente', HTTP_BAD_REQUEST);
        }
        $contrato = $this->contratos->buscarPorId($idContrato);
        if (!$contrato || !$contrato->isVigente()) {
            throw new AppException('El contrato no está vigente', HTTP_BAD_REQUEST);
        }
        if ($periodo === '' || strlen($periodo) > 30) {
            throw new AppException('El periodo de pago es obligatorio (máximo 30 caracteres)', HTTP_BAD_REQUEST);
        }
        if ($fechaPago === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPago)) {
            throw new AppException('La fecha de pago no es válida', HTTP_BAD_REQUEST);
        }
        if ($this->nominas->existePeriodo($idContrato, $periodo)) {
            throw new AppException('Ya existe una nómina de ese periodo para este contrato', HTTP_BAD_REQUEST);
        }

        $lineas = $this->armarDetalle($data, $contrato, (int) $actor->getIdUsuario());
        $neto = 0.0;
        foreach ($lineas as $linea) {
            $neto += $linea->esDevengo() ? $linea->getValor() : -$linea->getValor();
        }
        $neto = round($neto, 2);
        if ($neto <= 0) {
            throw new AppException('El total neto de la nómina debe ser mayor a cero', HTTP_BAD_REQUEST);
        }

        $this->caja->sembrarBase((int) $actor->getIdUsuario());
        $this->caja->exigirSesionCaja($actor);

        $nomina = new Nomina($idContrato, $periodo, $fechaPago, $neto, (int) $actor->getIdUsuario());
        $this->db->beginTransaction();
        try {
            $guardada = $this->nominas->guardar($nomina);
            $idNomina = (int) $guardada->getIdNomina();
            foreach ($lineas as $linea) {
                $linea->setIdNomina($idNomina);
                $this->lineas->guardar($linea);
            }
            $this->caja->registrarPagoNomina($neto, $actor, [
                'token' => $data['token'] ?? null,
                'ip'    => $data['ip'] ?? null,
            ]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $final = $this->nominas->buscarPorId((int) $nomina->getIdNomina()) ?: $nomina;
        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Nominas',
            'registroId'    => (int) $final->getIdNomina(),
            'valoresNuevos' => $final->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        return $final;
    }

    /**
     * @return NominaRol[]
     */
    private function armarDetalle(array $data, Contrato $contrato, int $createdBy): array
    {
        $detalleIn = $data['detalle'] ?? [];
        $lineas = [];
        if (is_array($detalleIn) && $detalleIn) {
            foreach ($detalleIn as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $tipo = trim((string) ($item['tipo_concepto'] ?? NominaRol::DEVENGO));
                if (!in_array($tipo, [NominaRol::DEVENGO, NominaRol::DEDUCCION, 'Deducción'], true)) {
                    throw new AppException('El tipo de concepto debe ser Devengo o Deduccion', HTTP_BAD_REQUEST);
                }
                if ($tipo === 'Deducción') {
                    $tipo = NominaRol::DEDUCCION;
                }
                $desc = trim((string) ($item['descripcion'] ?? ''));
                $valor = round(abs((float) ($item['valor'] ?? 0)), 2);
                if ($desc === '' || $valor <= 0) {
                    throw new AppException('Cada línea de nómina necesita descripción y valor mayor a cero', HTTP_BAD_REQUEST);
                }
                if (strlen($desc) > 250) {
                    throw new AppException('La descripción del concepto es demasiado larga', HTTP_BAD_REQUEST);
                }
                $lineas[] = new NominaRol(0, $tipo, $desc, $valor, $createdBy);
            }
        }

        if (!$lineas) {
            $salario = $contrato->getSalarioBase();
            $total   = isset($data['total_neto']) ? round((float) $data['total_neto'], 2) : $salario;
            if ($total <= 0) {
                throw new AppException('El total neto debe ser mayor a cero', HTTP_BAD_REQUEST);
            }
            $lineas[] = new NominaRol(0, NominaRol::DEVENGO, 'Salario base', $salario, $createdBy);
            $diff = round($total - $salario, 2);
            if ($diff > 0) {
                $lineas[] = new NominaRol(0, NominaRol::DEVENGO, 'Otros devengos / comisión', $diff, $createdBy);
            } elseif ($diff < 0) {
                $lineas[] = new NominaRol(0, NominaRol::DEDUCCION, 'Descuentos', abs($diff), $createdBy);
            }
        }

        return $lineas;
    }
}
