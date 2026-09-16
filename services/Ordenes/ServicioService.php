<?php

require_once __DIR__ . '/../../models/Ordenes/Servicio.php';
require_once __DIR__ . '/../../repositories/Ordenes/ServicioRepository.php';
require_once __DIR__ . '/../Seguridad/AuthService.php';

class ServicioService
{
    private ServicioRepository $servicios;
    private AuthService $auth;

    public function __construct(ServicioRepository $servicios, AuthService $auth)
    {
        $this->servicios = $servicios;
        $this->auth      = $auth;
    }

    /**
     * @param int|string|array|null $parametro
     * @return Servicio[]|Servicio
     */
    public function listarServicios($parametro = null)
    {
        if (is_numeric($parametro)) {
            $servicio = $this->servicios->buscarPorId((int) $parametro);
            if (!$servicio) {
                throw new AppException('Servicio no encontrado', HTTP_NOT_FOUND);
            }
            return $servicio;
        }

        $filtros = [];
        if (is_string($parametro) && $parametro !== '') {
            $filtros['busqueda'] = $parametro;
        } elseif (is_array($parametro)) {
            $filtros = $parametro;
        }

        return $this->servicios->listar($filtros);
    }

    public function crearServicio(array $data): Servicio
    {
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $precio = (float) ($data['precio'] ?? 0);
        $createdBy = (int) ($data['created_by'] ?? 0);

        if ($nombre === '' || $precio < 0 || $createdBy < 1) {
            throw new AppException('nombre, precio y usuario creador son requeridos', HTTP_BAD_REQUEST);
        }

        $servicio = new Servicio(
            $nombre,
            $precio,
            $createdBy,
            $data['tipo'] ?? null,
            $data['descripcion'] ?? null
        );
        if (!empty($data['estado'])) {
            $servicio->setEstado((string) $data['estado']);
        }

        $guardado = $this->servicios->guardar($servicio);
        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Servicios',
            'registroId'    => (int) $guardado->getIdServicio(),
            'valoresNuevos' => $guardado->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        return $guardado;
    }

    public function actualizarServicio(int $id, array $data = []): Servicio
    {
        $servicio = $this->servicios->buscarPorId($id);
        if (!$servicio) {
            throw new AppException('Servicio no encontrado', HTTP_NOT_FOUND);
        }
        $antes = $servicio->toArray();

        if (isset($data['nombre'])) {
            $servicio->setNombre(trim((string) $data['nombre']));
        }
        if (array_key_exists('tipo', $data)) {
            $servicio->setTipo($data['tipo'] !== null ? (string) $data['tipo'] : null);
        }
        if (array_key_exists('descripcion', $data)) {
            $servicio->setDescripcion($data['descripcion'] !== null ? (string) $data['descripcion'] : null);
        }
        if (isset($data['precio'])) {
            $servicio->setPrecio((float) $data['precio']);
        }
        if (isset($data['estado'])) {
            $servicio->setEstado((string) $data['estado']);
        }

        $servicio->tocarUpdatedAt(isset($data['updated_by']) ? (int) $data['updated_by'] : null);
        $guardado = $this->servicios->guardar($servicio);

        $this->auth->registrarLog([
            'token'             => $data['token'] ?? null,
            'accion'            => 'UPDATE',
            'tablaAfectada'     => 'Servicios',
            'registroId'        => $id,
            'valoresAnteriores' => $antes,
            'valoresNuevos'     => $guardado->toArray(),
            'ipAddress'         => $data['ip'] ?? null,
        ]);
        return $guardado;
    }

    public function desactivarServicio(int $id, array $contexto = []): Servicio
    {
        $servicio = $this->servicios->buscarPorId($id);
        if (!$servicio) {
            throw new AppException('Servicio no encontrado', HTTP_NOT_FOUND);
        }
        $antes = $servicio->toArray();
        $servicio->desactivar();
        $servicio->tocarUpdatedAt($contexto['updated_by'] ?? null);
        $guardado = $this->servicios->guardar($servicio);

        $this->auth->registrarLog([
            'token'             => $contexto['token'] ?? null,
            'accion'            => 'UPDATE',
            'tablaAfectada'     => 'Servicios',
            'registroId'        => $id,
            'valoresAnteriores' => $antes,
            'valoresNuevos'     => $guardado->toArray(),
            'ipAddress'         => $contexto['ip'] ?? null,
        ]);
        return $guardado;
    }
}
