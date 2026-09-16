<?php

require_once __DIR__ . '/../../models/Ordenes/Especialidad.php';
require_once __DIR__ . '/../../models/Seguridad/Usuario.php';
require_once __DIR__ . '/../../repositories/Ordenes/EspecialidadRepository.php';
require_once __DIR__ . '/../../repositories/Seguridad/UsuarioRepository.php';
require_once __DIR__ . '/../Seguridad/AuthService.php';

class EspecialidadService
{
    private EspecialidadRepository $especialidades;
    private UsuarioRepository $usuarios;
    private AuthService $auth;

    public function __construct(
        EspecialidadRepository $especialidades,
        UsuarioRepository $usuarios,
        AuthService $auth
    ) {
        $this->especialidades = $especialidades;
        $this->usuarios       = $usuarios;
        $this->auth           = $auth;
    }

    /** @return Especialidad[] */
    public function listar(bool $conUsuarios = false): array
    {
        return $this->especialidades->listar($conUsuarios);
    }

    public function usuarios(int $idEspecialidad): Especialidad
    {
        $esp = $this->especialidades->buscarPorId($idEspecialidad, true);
        if (!$esp) {
            throw new AppException('Especialidad no encontrada', HTTP_NOT_FOUND);
        }
        return $esp;
    }

    public function guardar(array $data, Usuario $actor): Especialidad
    {
        $nombre = trim((string) ($data['nombre_especialidad'] ?? $data['nombre'] ?? ''));
        $desc   = trim((string) ($data['descripcion'] ?? ''));
        $this->validarNombre($nombre);
        if (strlen($desc) > 500) {
            throw new AppException('La descripción no puede superar 500 caracteres', HTTP_BAD_REQUEST);
        }
        if ($this->especialidades->buscarPorNombre($nombre)) {
            throw new AppException('Ya existe una especialidad con ese nombre', HTTP_BAD_REQUEST);
        }

        $esp = new Especialidad($nombre, (int) $actor->getIdUsuario(), $desc !== '' ? $desc : null);
        $guardada = $this->especialidades->guardar($esp);
        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Especialidades',
            'registroId'    => (int) $guardada->getIdEspecialidad(),
            'valoresNuevos' => $guardada->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        return $this->especialidades->buscarPorId((int) $guardada->getIdEspecialidad(), true) ?: $guardada;
    }

    public function actualizar(int $id, array $data, Usuario $actor): Especialidad
    {
        $esp = $this->especialidades->buscarPorId($id);
        if (!$esp) {
            throw new AppException('Especialidad no encontrada', HTTP_NOT_FOUND);
        }
        $nombre = trim((string) ($data['nombre_especialidad'] ?? $data['nombre'] ?? $esp->getNombreEspecialidad()));
        $desc   = array_key_exists('descripcion', $data)
            ? trim((string) $data['descripcion'])
            : (string) ($esp->getDescripcion() ?? '');
        $estado = trim((string) ($data['estado'] ?? $esp->getEstado()));
        $this->validarNombre($nombre);
        if (strlen($desc) > 500) {
            throw new AppException('La descripción no puede superar 500 caracteres', HTTP_BAD_REQUEST);
        }
        if (!in_array($estado, ['Activo', 'Inactivo'], true)) {
            throw new AppException('Estado no válido', HTTP_BAD_REQUEST);
        }
        if ($this->especialidades->buscarPorNombre($nombre, $id)) {
            throw new AppException('Ya existe una especialidad con ese nombre', HTTP_BAD_REQUEST);
        }

        $esp->setNombreEspecialidad($nombre);
        $esp->setDescripcion($desc !== '' ? $desc : null);
        $esp->setEstado($estado);
        $esp->tocarUpdatedAt((int) $actor->getIdUsuario());
        $guardada = $this->especialidades->guardar($esp);
        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'UPDATE',
            'tablaAfectada' => 'Especialidades',
            'registroId'    => $id,
            'valoresNuevos' => $guardada->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        return $this->especialidades->buscarPorId($id, true) ?: $guardada;
    }

    public function eliminar(int $id, array $contexto, Usuario $actor): void
    {
        $esp = $this->especialidades->buscarPorId($id, true);
        if (!$esp) {
            throw new AppException('Especialidad no encontrada', HTTP_NOT_FOUND);
        }
        $this->especialidades->desasignarTodos($id);
        $esp->marcarEliminado((int) $actor->getIdUsuario());
        $this->especialidades->guardar($esp);
        $this->auth->registrarLog([
            'token'         => $contexto['token'] ?? null,
            'accion'        => 'DELETE',
            'tablaAfectada' => 'Especialidades',
            'registroId'    => $id,
            'valoresNuevos' => $esp->toArray(),
            'ipAddress'     => $contexto['ip'] ?? null,
        ]);
    }

    /**
     * @param int[] $idsEspecialidad
     * @return Especialidad[]
     */
    public function asignarAUsuario(int $idUsuario, array $idsEspecialidad, array $contexto, Usuario $actor): array
    {
        $empleado = $this->usuarios->buscarPorId($idUsuario);
        if (!$empleado) {
            throw new AppException('Usuario no encontrado', HTTP_NOT_FOUND);
        }
        $ids = $this->idsEspecialidadValidos($idsEspecialidad);
        $this->especialidades->reemplazarAsignacionesUsuario($idUsuario, $ids, (int) $actor->getIdUsuario());
        $this->auth->registrarLog([
            'token'         => $contexto['token'] ?? null,
            'accion'        => 'UPDATE',
            'tablaAfectada' => 'Usuario_Especialidad',
            'registroId'    => $idUsuario,
            'valoresNuevos' => ['id_usuario' => $idUsuario, 'especialidades' => $ids],
            'ipAddress'     => $contexto['ip'] ?? null,
        ]);
        return $this->especialidades->listarPorUsuario($idUsuario);
    }

    /**
     * @param int[] $idsUsuario
     */
    public function asignarUsuarios(int $idEspecialidad, array $idsUsuario, array $contexto, Usuario $actor): Especialidad
    {
        $esp = $this->especialidades->buscarPorId($idEspecialidad);
        if (!$esp) {
            throw new AppException('Especialidad no encontrada', HTTP_NOT_FOUND);
        }
        $ids = [];
        foreach ($idsUsuario as $id) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $usuario = $this->usuarios->buscarPorId($id);
            if (!$usuario || !$usuario->isActivo()) {
                throw new AppException('Hay un usuario inválido o inactivo en la asignación', HTTP_BAD_REQUEST);
            }
            $ids[] = $id;
        }
        $this->especialidades->reemplazarUsuariosEspecialidad($idEspecialidad, $ids, (int) $actor->getIdUsuario());
        $this->auth->registrarLog([
            'token'         => $contexto['token'] ?? null,
            'accion'        => 'UPDATE',
            'tablaAfectada' => 'Usuario_Especialidad',
            'registroId'    => $idEspecialidad,
            'valoresNuevos' => ['id_especialidad' => $idEspecialidad, 'usuarios' => $ids],
            'ipAddress'     => $contexto['ip'] ?? null,
        ]);
        return $this->usuarios($idEspecialidad);
    }

    private function validarNombre(string $nombre): void
    {
        if (strlen($nombre) < 2 || strlen($nombre) > 50) {
            throw new AppException('El nombre de la especialidad debe tener entre 2 y 50 caracteres', HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param mixed $idsEspecialidad
     * @return int[]
     */
    private function idsEspecialidadValidos($idsEspecialidad): array
    {
        if (!is_array($idsEspecialidad)) {
            throw new AppException('La lista de especialidades no es válida', HTTP_BAD_REQUEST);
        }
        $ids = [];
        foreach ($idsEspecialidad as $id) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $esp = $this->especialidades->buscarPorId($id);
            if (!$esp || !$esp->isActiva()) {
                throw new AppException('Hay una especialidad inválida o inactiva en la asignación', HTTP_BAD_REQUEST);
            }
            $ids[] = $id;
        }
        return array_values(array_unique($ids));
    }
}
