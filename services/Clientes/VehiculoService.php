<?php

require_once __DIR__ . '/../../models/Clientes/Vehiculo.php';
require_once __DIR__ . '/../../repositories/Clientes/VehiculoRepository.php';
require_once __DIR__ . '/../../repositories/Clientes/ClienteRepository.php';
require_once __DIR__ . '/../Seguridad/AuthService.php';

class VehiculoService
{
    private VehiculoRepository $vehiculos;
    private ClienteRepository $clientes;
    private AuthService $auth;

    public function __construct(
        VehiculoRepository $vehiculos,
        ClienteRepository $clientes,
        AuthService $auth
    ) {
        $this->vehiculos = $vehiculos;
        $this->clientes  = $clientes;
        $this->auth      = $auth;
    }

    /** @return Vehiculo[] */
    public function listarVehiculos(array $filtros = []): array
    {
        return $this->vehiculos->listar($filtros);
    }

    public function buscarPorId(int $id): Vehiculo
    {
        $vehiculo = $this->vehiculos->buscarPorId($id);
        if (!$vehiculo) {
            throw new AppException('Vehículo no encontrado', HTTP_NOT_FOUND);
        }
        return $vehiculo;
    }

    public function guardar(array $data): Vehiculo
    {
        $id = (int) ($data['id_vehiculo'] ?? $data['id'] ?? 0);
        if ($id > 0) {
            return $this->actualizar($id, $data);
        }
        return $this->registrar($data);
    }

    public function registrar(array $data): Vehiculo
    {
        $idCliente = (int) ($data['id_cliente'] ?? $data['cliente_id'] ?? 0);
        $placa     = Vehiculo::normalizarPlaca((string) ($data['placa'] ?? ''));
        $marca     = trim((string) ($data['marca'] ?? ''));
        $createdBy = (int) ($data['created_by'] ?? 0);

        if ($idCliente < 1 || $placa === '' || $marca === '' || $createdBy < 1) {
            throw new AppException('id_cliente, placa, marca y created_by son requeridos', HTTP_BAD_REQUEST);
        }
        if (!Vehiculo::placaValida($placa)) {
            throw new AppException('Placa inválida. Carro: ABC123. Moto: ABC12D', HTTP_BAD_REQUEST);
        }

        $cliente = $this->clientes->buscarPorId($idCliente, false);
        if (!$cliente || !$cliente->isActivo()) {
            throw new AppException('El cliente no existe o está inactivo', HTTP_BAD_REQUEST);
        }
        if ($this->vehiculos->buscarPorPlaca($placa)) {
            throw new AppException('La placa ya está registrada', HTTP_BAD_REQUEST);
        }

        $vehiculo = new Vehiculo(
            $idCliente,
            $placa,
            $marca,
            $createdBy,
            $data['modelo'] ?? null,
            isset($data['anio']) ? (int) $data['anio'] : (isset($data['año']) ? (int) $data['año'] : null),
            null,
            Vehiculo::normalizarTipo($data['tipo'] ?? null, $placa)
        );
        if (!empty($data['estado'])) {
            $vehiculo->setEstado((string) $data['estado']);
        }

        $guardado = $this->vehiculos->guardar($vehiculo);
        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Vehiculo',
            'registroId'    => (int) $guardado->getIdVehiculo(),
            'valoresNuevos' => $guardado->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        return $this->buscarPorId((int) $guardado->getIdVehiculo());
    }

    public function actualizar(int $id, array $data = []): Vehiculo
    {
        $vehiculo = $this->buscarPorId($id);
        $antes    = $vehiculo->toArray();

        if (!empty($data['id_cliente']) || !empty($data['cliente_id'])) {
            $idCliente = (int) ($data['id_cliente'] ?? $data['cliente_id']);
            $cliente = $this->clientes->buscarPorId($idCliente, false);
            if (!$cliente || !$cliente->isActivo()) {
                throw new AppException('El cliente no existe o está inactivo', HTTP_BAD_REQUEST);
            }
            $vehiculo->setIdCliente($idCliente);
        }
        if (isset($data['placa'])) {
            $placa = Vehiculo::normalizarPlaca((string) $data['placa']);
            if (!Vehiculo::placaValida($placa)) {
                throw new AppException('Placa inválida. Carro: ABC123. Moto: ABC12D', HTTP_BAD_REQUEST);
            }
            $otro = $this->vehiculos->buscarPorPlaca($placa, $id);
            if ($otro) {
                throw new AppException('La placa ya está registrada', HTTP_BAD_REQUEST);
            }
            $vehiculo->setPlaca($placa);
        }
        if (isset($data['tipo'])) {
            $vehiculo->setTipo((string) $data['tipo']);
        }
        if (isset($data['marca'])) {
            $vehiculo->setMarca(trim((string) $data['marca']));
        }
        if (array_key_exists('modelo', $data)) {
            $vehiculo->setModelo($data['modelo'] !== null ? (string) $data['modelo'] : null);
        }
        if (array_key_exists('anio', $data) || array_key_exists('año', $data)) {
            $anio = $data['anio'] ?? $data['año'] ?? null;
            $vehiculo->setAnio($anio !== null && $anio !== '' ? (int) $anio : null);
        }
        if (isset($data['estado'])) {
            $vehiculo->setEstado((string) $data['estado']);
        }

        $vehiculo->tocarUpdatedAt($data['updated_by'] ?? null);
        $this->vehiculos->guardar($vehiculo);

        $this->auth->registrarLog([
            'token'             => $data['token'] ?? null,
            'accion'            => 'UPDATE',
            'tablaAfectada'     => 'Vehiculo',
            'registroId'        => $id,
            'valoresAnteriores' => $antes,
            'valoresNuevos'     => $vehiculo->toArray(),
            'ipAddress'         => $data['ip'] ?? null,
        ]);
        return $this->buscarPorId($id);
    }

    public function eliminar(int $id, array $contexto = []): void
    {
        $vehiculo = $this->buscarPorId($id);
        if ($this->vehiculos->tieneOrdenesActivas($id)) {
            throw new AppException('No se puede eliminar: el vehículo tiene órdenes activas', HTTP_BAD_REQUEST);
        }
        $vehiculo->marcarEliminado($contexto['updated_by'] ?? null);
        $this->vehiculos->guardar($vehiculo);
        $this->auth->registrarLog([
            'token'         => $contexto['token'] ?? null,
            'accion'        => 'DELETE',
            'tablaAfectada' => 'Vehiculo',
            'registroId'    => $id,
            'ipAddress'     => $contexto['ip'] ?? null,
        ]);
    }
}
