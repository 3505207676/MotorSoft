<?php

require_once __DIR__ . '/../../models/Agenda/Horario.php';
require_once __DIR__ . '/../../models/Agenda/Cita.php';
require_once __DIR__ . '/../../models/Seguridad/Usuario.php';
require_once __DIR__ . '/../../models/Clientes/Cliente.php';
require_once __DIR__ . '/../../repositories/Agenda/HorarioRepository.php';
require_once __DIR__ . '/../../repositories/Agenda/CitaRepository.php';
require_once __DIR__ . '/../../repositories/Seguridad/UsuarioRepository.php';
require_once __DIR__ . '/../../repositories/Clientes/ClienteRepository.php';
require_once __DIR__ . '/../../repositories/Clientes/VehiculoRepository.php';
require_once __DIR__ . '/../../repositories/Ordenes/ServicioRepository.php';
require_once __DIR__ . '/../Ordenes/OrdenesService.php';
require_once __DIR__ . '/../Seguridad/AuthService.php';

class AgendaService
{
    private Database $db;
    private HorarioRepository $horarios;
    private CitaRepository $citas;
    private UsuarioRepository $usuarios;
    private ClienteRepository $clientes;
    private VehiculoRepository $vehiculos;
    private ServicioRepository $servicios;
    private OrdenesService $ordenes;
    private AuthService $auth;

    public function __construct(
        Database $db,
        HorarioRepository $horarios,
        CitaRepository $citas,
        UsuarioRepository $usuarios,
        ClienteRepository $clientes,
        VehiculoRepository $vehiculos,
        ServicioRepository $servicios,
        OrdenesService $ordenes,
        AuthService $auth
    ) {
        $this->db        = $db;
        $this->horarios  = $horarios;
        $this->citas     = $citas;
        $this->usuarios  = $usuarios;
        $this->clientes  = $clientes;
        $this->vehiculos = $vehiculos;
        $this->servicios = $servicios;
        $this->ordenes   = $ordenes;
        $this->auth      = $auth;
    }

    /** @return Usuario[] */
    public function mecanicos(): array
    {
        $lista = [];
        foreach ($this->usuarios->listar() as $u) {
            if ($u->isActivo() && $u->esMecanico()) {
                $lista[] = $u;
            }
        }
        return $lista;
    }

    /** @return Horario[] */
    public function listarHorarios(array $filtros = []): array
    {
        return $this->horarios->listar($filtros);
    }

    public function crearHorario(array $data, Usuario $actor): Horario
    {
        if (!$actor->puede('agenda.horarios')) {
            throw new AppException('No puede crear horarios', HTTP_FORBIDDEN);
        }
        $idMec = (int) ($data['id_usuario'] ?? 0);
        if ($actor->esMecanico() && !$actor->esAdministrador()) {
            $idMec = (int) $actor->getIdUsuario();
        }
        $mecanico = $this->usuarios->buscarPorId($idMec);
        if (!$mecanico || !$mecanico->esMecanico()) {
            throw new AppException('Seleccione un mecánico válido', HTTP_BAD_REQUEST);
        }
        $fecha = trim((string) ($data['fecha'] ?? ''));
        $hora  = trim((string) ($data['hora_inicio'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new AppException('La fecha del horario no es válida', HTTP_BAD_REQUEST);
        }
        if ($fecha < date('Y-m-d')) {
            throw new AppException('No se pueden crear horarios en fechas pasadas', HTTP_BAD_REQUEST);
        }
        if (!preg_match('/^\d{2}:\d{2}/', $hora)) {
            throw new AppException('La hora de inicio no es válida', HTTP_BAD_REQUEST);
        }
        $capacidad = max(1, (int) ($data['capacidad'] ?? 1));
        if ($this->horarios->existeDuplicado($idMec, $fecha, $hora)) {
            throw new AppException('Ese mecánico ya tiene un horario a esa hora', HTTP_BAD_REQUEST);
        }
        $horario = new Horario($idMec, $fecha, $hora, (int) $actor->getIdUsuario(), $capacidad);
        $guardado = $this->horarios->guardar($horario);
        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Agenda_Disponible',
            'registroId'    => (int) $guardado->getIdHorario(),
            'valoresNuevos' => $guardado->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        return $guardado;
    }

    public function cancelarHorario(int $id, array $contexto, Usuario $actor): Horario
    {
        $horario = $this->horarios->buscarPorId($id);
        if (!$horario) {
            throw new AppException('Horario no encontrado', HTTP_NOT_FOUND);
        }
        if ($actor->esMecanico() && !$actor->esAdministrador()
            && $horario->getIdUsuario() !== (int) $actor->getIdUsuario()) {
            throw new AppException('Solo puede cancelar sus propios horarios', HTTP_FORBIDDEN);
        }
        if ($this->citas->contarActivasPorHorario($id) > 0) {
            throw new AppException('Hay citas en ese horario. Cancele las citas primero.', HTTP_BAD_REQUEST);
        }
        $horario->setEstado(Horario::CANCELADO);
        $horario->tocarUpdatedAt((int) $actor->getIdUsuario());
        return $this->horarios->guardar($horario);
    }

    /** @return Cita[] */
    public function listarCitas(array $filtros = []): array
    {
        return $this->citas->listar($filtros);
    }

    public function buscarCita(int $id): Cita
    {
        $cita = $this->citas->buscarPorId($id);
        if (!$cita) {
            throw new AppException('Cita no encontrada', HTTP_NOT_FOUND);
        }
        return $cita;
    }

    public function crearCita(array $data, array $actor): Cita
    {
        $esCliente = ($actor['tipo'] ?? '') === 'cliente';
        /** @var Cliente|null $clienteActor */
        $clienteActor = $actor['cliente'] ?? null;
        /** @var Usuario|null $usuarioActor */
        $usuarioActor = $actor['usuario'] ?? null;

        $idCliente  = (int) ($data['id_cliente'] ?? 0);
        $idVehiculo = (int) ($data['id_vehiculo'] ?? 0);
        $idServicio = (int) ($data['id_servicio'] ?? 0);
        $idHorario  = (int) ($data['id_horario'] ?? 0);
        $motivo     = trim((string) ($data['motivo'] ?? ''));

        if ($esCliente && $clienteActor) {
            $idCliente = (int) $clienteActor->getIdCliente();
        }
        if ($idCliente < 1 || $idVehiculo < 1 || $idServicio < 1 || $idHorario < 1) {
            throw new AppException('Cliente, vehículo, servicio y horario son obligatorios', HTTP_BAD_REQUEST);
        }

        $cliente = $this->clientes->buscarPorId($idCliente, false);
        if (!$cliente || !$cliente->isActivo()) {
            throw new AppException('Cliente no válido', HTTP_BAD_REQUEST);
        }
        $vehiculo = $this->vehiculos->buscarPorId($idVehiculo);
        if (!$vehiculo || $vehiculo->getIdCliente() !== $idCliente) {
            throw new AppException('El vehículo no pertenece a ese cliente', HTTP_BAD_REQUEST);
        }
        $servicio = $this->servicios->buscarPorId($idServicio);
        if (!$servicio || !$servicio->isActivo()) {
            throw new AppException('Servicio no disponible', HTTP_BAD_REQUEST);
        }
        $horario = $this->horarios->buscarPorId($idHorario);
        if (!$horario || !$horario->isDisponible()) {
            throw new AppException('Ese horario ya no está disponible', HTTP_BAD_REQUEST);
        }
        if ($horario->getFecha() < date('Y-m-d')) {
            throw new AppException('No se puede agendar en una fecha pasada', HTTP_BAD_REQUEST);
        }

        $createdBy = $esCliente
            ? $horario->getIdUsuario()
            : (int) $usuarioActor->getIdUsuario();

        $cita = new Cita($idCliente, $idVehiculo, $idServicio, $idHorario, $createdBy, $motivo !== '' ? $motivo : null);

        $this->db->beginTransaction();
        try {
            $ocupadas = $this->citas->contarActivasPorHorario($idHorario);
            if ($ocupadas >= $horario->getCapacidad()) {
                throw new AppException('Ese horario ya no tiene cupo', HTTP_BAD_REQUEST);
            }
            $guardada = $this->citas->guardar($cita);
            $ocupadas++;
            if ($ocupadas >= $horario->getCapacidad()) {
                $horario->setEstado(Horario::OCUPADO);
                $horario->tocarUpdatedAt($createdBy);
                $this->horarios->guardar($horario);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $final = $this->citas->buscarPorId((int) $cita->getIdCita()) ?: $guardada;
        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Citas',
            'registroId'    => (int) $final->getIdCita(),
            'valoresNuevos' => $final->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        return $final;
    }

    public function actualizarCita(int $id, array $data, array $actor): Cita
    {
        $cita = $this->buscarCita($id);
        $esCliente = ($actor['tipo'] ?? '') === 'cliente';
        $clienteActor = $actor['cliente'] ?? null;
        $usuarioActor = $actor['usuario'] ?? null;
        $horarioAnteriorId = $cita->getIdHorario();

        if ($cita->tieneOrden()) {
            $nuevoEstadoPedido = isset($data['estado_cita']) ? trim((string) $data['estado_cita']) : $cita->getEstadoCita();
            $cambiaEstructura = (!empty($data['id_vehiculo']) && (int) $data['id_vehiculo'] !== $cita->getIdVehiculo())
                || (!empty($data['id_servicio']) && (int) $data['id_servicio'] !== $cita->getIdServicio())
                || (!empty($data['id_horario']) && (int) $data['id_horario'] !== $cita->getIdHorario());
            if ($nuevoEstadoPedido === Cita::CANCELADA) {
                throw new AppException(
                    'La cita ya tiene la orden de trabajo #' . $cita->getIdOrden() . '. No se puede cancelar.',
                    HTTP_BAD_REQUEST
                );
            }
            if ($cambiaEstructura) {
                throw new AppException(
                    'La cita ya tiene la orden #' . $cita->getIdOrden() . '. Edite la orden, no la cita.',
                    HTTP_BAD_REQUEST
                );
            }
        }

        if ($esCliente) {
            if (!$clienteActor || (int) $clienteActor->getIdCliente() !== $cita->getIdCliente()) {
                throw new AppException('No puede modificar esta cita', HTTP_FORBIDDEN);
            }
            $nuevoEstado = trim((string) ($data['estado_cita'] ?? Cita::CANCELADA));
            if ($nuevoEstado !== Cita::CANCELADA) {
                throw new AppException('Desde el portal solo puede cancelar la cita', HTTP_FORBIDDEN);
            }
        } else {
            if (!$usuarioActor) {
                throw new AppException('No puede modificar esta cita', HTTP_FORBIDDEN);
            }
            if (isset($data['motivo'])) {
                $cita->setMotivo(trim((string) $data['motivo']) !== '' ? trim((string) $data['motivo']) : null);
            }
            $nuevoEstado = isset($data['estado_cita']) ? trim((string) $data['estado_cita']) : $cita->getEstadoCita();
            $permitidos = [Cita::PROGRAMADA, Cita::CONFIRMADA, Cita::COMPLETADA, Cita::CANCELADA];
            if (!in_array($nuevoEstado, $permitidos, true)) {
                throw new AppException('Estado de cita no válido', HTTP_BAD_REQUEST);
            }
            if ($nuevoEstado === Cita::COMPLETADA && !$cita->tieneOrden()
                && strcasecmp($cita->getEstadoCita(), Cita::COMPLETADA) !== 0) {
                throw new AppException(
                    'Para atender la cita, conviértala en orden de trabajo.',
                    HTTP_BAD_REQUEST
                );
            }
            $antes = $cita->getEstadoCita();
            if ($nuevoEstado === Cita::CANCELADA && strcasecmp($antes, Cita::CANCELADA) !== 0
                && !$usuarioActor->puede('citas.cancelar')) {
                throw new AppException('No puede cancelar citas', HTTP_FORBIDDEN);
            }
            if ($nuevoEstado === Cita::CONFIRMADA && strcasecmp($antes, Cita::CONFIRMADA) !== 0
                && !$usuarioActor->puede('citas.confirmar') && !$usuarioActor->puede('citas.crear')) {
                throw new AppException('No puede confirmar citas', HTTP_FORBIDDEN);
            }
            if ($usuarioActor && $usuarioActor->esMecanico() && !$usuarioActor->esAdministrador()) {
                $horario = $this->horarios->buscarPorId($cita->getIdHorario());
                if (!$horario || $horario->getIdUsuario() !== (int) $usuarioActor->getIdUsuario()) {
                    throw new AppException('Solo puede gestionar sus propias citas', HTTP_FORBIDDEN);
                }
            } else {
                $this->aplicarDatosCitaStaff($cita, $data);
            }
        }

        $cita->setEstadoCita($nuevoEstado);
        $updatedBy = $esCliente
            ? $cita->getCreatedBy()
            : (int) $usuarioActor->getIdUsuario();
        $cita->tocarUpdatedAt($updatedBy);

        $this->db->beginTransaction();
        try {
            $guardada = $this->citas->guardar($cita);
            $this->sincronizarCupoHorario($horarioAnteriorId, $updatedBy);
            if ($guardada->getIdHorario() !== $horarioAnteriorId) {
                $this->sincronizarCupoHorario($guardada->getIdHorario(), $updatedBy);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'UPDATE',
            'tablaAfectada' => 'Citas',
            'registroId'    => $id,
            'valoresNuevos' => $guardada->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        return $this->citas->buscarPorId($id) ?: $guardada;
    }

    /**
     * Abre la OT con el vehículo, el servicio y el mecánico del horario.
     * @return array{cita:array,orden:array}
     */
    public function convertirCitaEnOrden(int $id, array $data, Usuario $actor): array
    {
        if (!$actor->puede('citas.convertir_orden')) {
            throw new AppException('No puede convertir citas en órdenes de trabajo', HTTP_FORBIDDEN);
        }

        $cita = $this->buscarCita($id);
        if ($cita->isCancelada()) {
            throw new AppException('No se puede convertir una cita cancelada', HTTP_BAD_REQUEST);
        }
        if ($cita->tieneOrden()) {
            throw new AppException(
                'Esta cita ya tiene la orden de trabajo #' . $cita->getIdOrden(),
                HTTP_BAD_REQUEST
            );
        }

        $horario = $this->horarios->buscarPorId($cita->getIdHorario());
        if (!$horario) {
            throw new AppException('La cita no tiene horario de mecánico', HTTP_BAD_REQUEST);
        }

        $idMecanico = (int) $horario->getIdUsuario();
        $mecanico = $this->usuarios->buscarPorId($idMecanico);
        if (!$mecanico || !$mecanico->isActivo()) {
            throw new AppException('El mecánico del horario no está disponible', HTTP_BAD_REQUEST);
        }

        $servicioNombre = trim((string) ($cita->toArray()['servicio_nombre'] ?? 'Servicio'));
        $motivo = trim((string) ($cita->getMotivo() ?? ''));
        $descripcion = $motivo !== ''
            ? $motivo
            : ('Cita #' . $cita->getIdCita() . ' · ' . $servicioNombre);

        $createdBy = (int) $actor->getIdUsuario();
        $this->db->beginTransaction();
        try {
            $orden = $this->ordenes->registrarOrdenes([
                'id_vehiculo' => $cita->getIdVehiculo(),
                'id_usuario'  => $idMecanico,
                'created_by'  => $createdBy,
                'descripcion' => $descripcion,
                'servicios'   => [['id_servicio' => $cita->getIdServicio(), 'cantidad' => 1]],
                'token'       => $data['token'] ?? null,
                'ip'          => $data['ip'] ?? null,
            ]);
            $cita->setIdOrden((int) $orden->getIdOrden());
            $cita->setEstadoCita(Cita::COMPLETADA);
            $cita->tocarUpdatedAt($createdBy);
            $this->citas->guardar($cita);
            $this->sincronizarCupoHorario($cita->getIdHorario(), $createdBy);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $final = $this->citas->buscarPorId($id) ?: $cita;
        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'UPDATE',
            'tablaAfectada' => 'Citas',
            'registroId'    => $id,
            'valoresNuevos' => [
                'id_orden'    => $final->getIdOrden(),
                'estado_cita' => $final->getEstadoCita(),
            ],
            'ipAddress'     => $data['ip'] ?? null,
        ]);

        return [
            'cita'  => $final->toArray(),
            'orden' => $this->ordenes->serializarOrden($orden),
        ];
    }

    /** @param array<string,mixed> $data */
    private function aplicarDatosCitaStaff(Cita $cita, array $data): void
    {
        if (!empty($data['id_vehiculo'])) {
            $idVehiculo = (int) $data['id_vehiculo'];
            $vehiculo = $this->vehiculos->buscarPorId($idVehiculo);
            if (!$vehiculo || $vehiculo->getIdCliente() !== $cita->getIdCliente()) {
                throw new AppException('El vehículo no pertenece a ese cliente', HTTP_BAD_REQUEST);
            }
            $cita->setIdVehiculo($idVehiculo);
        }
        if (!empty($data['id_servicio'])) {
            $idServicio = (int) $data['id_servicio'];
            $servicio = $this->servicios->buscarPorId($idServicio);
            if (!$servicio || !$servicio->isActivo()) {
                throw new AppException('Servicio no disponible', HTTP_BAD_REQUEST);
            }
            $cita->setIdServicio($idServicio);
        }
        $nuevoHorarioId = !empty($data['id_horario']) ? (int) $data['id_horario'] : $cita->getIdHorario();
        if ($nuevoHorarioId !== $cita->getIdHorario()) {
            $horario = $this->horarios->buscarPorId($nuevoHorarioId);
            if (!$horario || strcasecmp($horario->getEstado(), Horario::CANCELADO) === 0) {
                throw new AppException('Ese horario no está disponible', HTTP_BAD_REQUEST);
            }
            if ($horario->getFecha() < date('Y-m-d')) {
                throw new AppException('No se puede mover la cita a una fecha pasada', HTTP_BAD_REQUEST);
            }
            $ocupadas = $this->citas->contarActivasPorHorario($nuevoHorarioId);
            if ($ocupadas >= $horario->getCapacidad()) {
                throw new AppException('Ese horario ya no tiene cupo', HTTP_BAD_REQUEST);
            }
            $cita->setIdHorario($nuevoHorarioId);
        }
    }

    private function sincronizarCupoHorario(int $idHorario, int $updatedBy): void
    {
        $horario = $this->horarios->buscarPorId($idHorario);
        if (!$horario || strcasecmp($horario->getEstado(), Horario::CANCELADO) === 0) {
            return;
        }
        $ocupadas = $this->citas->contarActivasPorHorario($idHorario);
        $nuevo = $ocupadas >= $horario->getCapacidad() ? Horario::OCUPADO : Horario::DISPONIBLE;
        if (strcasecmp($horario->getEstado(), $nuevo) === 0) {
            return;
        }
        $horario->setEstado($nuevo);
        $horario->tocarUpdatedAt($updatedBy);
        $this->horarios->guardar($horario);
    }
}
