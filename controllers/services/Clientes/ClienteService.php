<?php

require_once __DIR__ . '/../../models/Clientes/Cliente.php';
require_once __DIR__ . '/../../repositories/Clientes/ClienteRepository.php';
require_once __DIR__ . '/../Seguridad/AuthService.php';

class ClienteService
{
    private ClienteRepository $clientes;
    private AuthService $auth;

    public function __construct(ClienteRepository $clientes, AuthService $auth)
    {
        $this->clientes = $clientes;
        $this->auth     = $auth;
    }

    /** @return Cliente[] */
    public function listarClientes(array $filtros = []): array
    {
        return $this->clientes->listar($filtros);
    }

    public function buscarPorId(int $id): Cliente
    {
        $cliente = $this->clientes->buscarPorId($id, true);
        if (!$cliente) {
            throw new AppException('Cliente no encontrado', HTTP_NOT_FOUND);
        }
        return $cliente;
    }

    public function buscarPorDoc(string $documento): Cliente
    {
        $cliente = $this->clientes->buscarPorDocumento($documento);
        if (!$cliente) {
            throw new AppException('Cliente no encontrado', HTTP_NOT_FOUND);
        }
        return $cliente;
    }

    public function guardar(array $data): Cliente
    {
        $id = (int) ($data['id_cliente'] ?? $data['id'] ?? 0);
        if ($id > 0) {
            return $this->actualizar($id, $data);
        }
        return $this->registrar($data);
    }

    public function registrar(array $data): Cliente
    {
        $nombre    = trim((string) ($data['nombre'] ?? ''));
        $documento = trim((string) ($data['documento'] ?? ''));
        $telefono  = trim((string) ($data['telefono'] ?? ''));
        $createdBy = (int) ($data['created_by'] ?? 0);

        if ($nombre === '' || $documento === '' || $telefono === '' || $createdBy < 1) {
            throw new AppException('nombre, documento, telefono y created_by son requeridos', HTTP_BAD_REQUEST);
        }
        if ($this->clientes->buscarPorDocumento($documento)) {
            throw new AppException('El documento ya está registrado', HTTP_BAD_REQUEST);
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new AppException('Email no válido', HTTP_BAD_REQUEST);
        }

        $cliente = new Cliente(
            $nombre,
            $documento,
            $telefono,
            $createdBy,
            $data['email'] ?? null,
            $data['preferencia_contacto'] ?? $data['preferenciaContacto'] ?? null
        );
        if (!empty($data['estado'])) {
            $cliente->setEstado((string) $data['estado']);
        }

        $guardado = $this->clientes->guardar($cliente);
        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Clientes',
            'registroId'    => (int) $guardado->getIdCliente(),
            'valoresNuevos' => $guardado->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        return $this->buscarPorId((int) $guardado->getIdCliente());
    }

    public function actualizar(int $id, array $data = []): Cliente
    {
        $cliente = $this->buscarPorId($id);
        $antes   = $cliente->toArray();

        if (isset($data['nombre'])) {
            $cliente->setNombre(trim((string) $data['nombre']));
        }
        if (isset($data['documento'])) {
            $documento = trim((string) $data['documento']);
            $otro = $this->clientes->buscarPorDocumento($documento, $id);
            if ($otro) {
                throw new AppException('El documento ya está registrado', HTTP_BAD_REQUEST);
            }
            $cliente->setDocumento($documento);
        }
        if (array_key_exists('email', $data)) {
            $email = $data['email'] ? trim((string) $data['email']) : null;
            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new AppException('Email no válido', HTTP_BAD_REQUEST);
            }
            $cliente->setEmail($email);
        }
        if (isset($data['telefono'])) {
            $cliente->setTelefono(trim((string) $data['telefono']));
        }
        if (array_key_exists('preferencia_contacto', $data) || array_key_exists('preferenciaContacto', $data)) {
            $pref = trim((string) ($data['preferencia_contacto'] ?? $data['preferenciaContacto'] ?? ''));
            if ($pref !== '' && !in_array($pref, ['WhatsApp', 'Llamada', 'Email'], true)) {
                throw new AppException('La preferencia debe ser WhatsApp, Llamada o Email', HTTP_BAD_REQUEST);
            }
            $cliente->setPreferenciaContacto($pref !== '' ? $pref : null);
        }
        if (isset($data['estado'])) {
            $cliente->setEstado((string) $data['estado']);
        }

        $cliente->tocarUpdatedAt($data['updated_by'] ?? null);
        $this->clientes->guardar($cliente);

        $this->auth->registrarLog([
            'token'             => $data['token'] ?? null,
            'accion'            => 'UPDATE',
            'tablaAfectada'     => 'Clientes',
            'registroId'        => $id,
            'valoresAnteriores' => $antes,
            'valoresNuevos'     => $cliente->toArray(),
            'ipAddress'         => $data['ip'] ?? null,
        ]);
        return $this->buscarPorId($id);
    }

    public function eliminar(int $id, array $contexto = []): void
    {
        $cliente = $this->buscarPorId($id);
        $activos = 0;
        foreach ($cliente->getVehiculos() as $vehiculo) {
            if ($vehiculo->isActivo()) {
                $activos++;
            }
        }
        if ($activos > 0) {
            throw new AppException('No se puede eliminar: el cliente tiene vehículos activos', HTTP_BAD_REQUEST);
        }

        $cliente->marcarEliminado($contexto['updated_by'] ?? null);
        $this->clientes->guardar($cliente);
        $this->auth->registrarLog([
            'token'         => $contexto['token'] ?? null,
            'accion'        => 'DELETE',
            'tablaAfectada' => 'Clientes',
            'registroId'    => $id,
            'ipAddress'     => $contexto['ip'] ?? null,
        ]);
    }
}
