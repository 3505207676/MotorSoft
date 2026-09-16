<?php

/**
 * Ficha del mecánico autenticado. No expone salario ni datos de otros usuarios.
 */
class PerfilMecanicoService
{
    private UserService $usuarios;
    private EspecialidadRepository $especialidades;
    private ContratoRepository $contratos;
    private DashboardRepository $dashboard;

    public function __construct(
        UserService $usuarios,
        EspecialidadRepository $especialidades,
        ContratoRepository $contratos,
        DashboardRepository $dashboard
    ) {
        $this->usuarios       = $usuarios;
        $this->especialidades = $especialidades;
        $this->contratos      = $contratos;
        $this->dashboard      = $dashboard;
    }

    public function obtener(Usuario $usuario): array
    {
        $id = (int) $usuario->getIdUsuario();
        $publico = $usuario->toPublicArray();
        $especialidades = array_map(static function (Especialidad $esp) {
            return [
                'id_especialidad'     => $esp->getIdEspecialidad(),
                'nombre_especialidad' => $esp->getNombreEspecialidad(),
            ];
        }, $this->especialidades->listarPorUsuario($id));

        $contrato = $this->contratos->vigentePorUsuario($id);
        $conteo = $this->dashboard->conteoOrdenesMecanico($id);

        $rol = $publico['rol'] ?? null;
        $nombreRol = is_array($rol) ? (string) ($rol['nombre'] ?? 'Mecánico') : (string) ($rol ?: 'Mecánico');

        return [
            'id_usuario'     => $id,
            'nombre'         => $publico['nombre'] ?? '',
            'correo'         => $publico['correo'] ?? '',
            'telefono'       => $publico['telefono'] ?? '',
            'documento'      => $publico['documento'] ?? '',
            'estado'         => $publico['estado'] ?? 'Activo',
            'rol'            => $nombreRol,
            'especialidades' => $especialidades,
            'fecha_ingreso'  => $contrato ? $contrato->getFechaIngreso() : null,
            'created_at'     => $usuario->getCreatedAt() ? $usuario->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'estadisticas'   => [
                'ordenes_asignadas'  => $conteo['total'],
                'ordenes_activas'    => $conteo['activas'],
                'ordenes_completadas'=> $conteo['completadas'],
            ],
        ];
    }

    public function actualizar(Usuario $actor, array $data): array
    {
        $permitidos = [
            'nombre'   => $data['nombre'] ?? null,
            'telefono' => array_key_exists('telefono', $data) ? $data['telefono'] : null,
            'correo'   => $data['correo'] ?? $data['email'] ?? null,
        ];
        $payload = [
            'updated_by' => $actor->getIdUsuario(),
            'token'      => $data['token'] ?? null,
            'ip'         => $data['ip'] ?? null,
            'actor'      => $actor,
        ];
        if ($permitidos['nombre'] !== null && $permitidos['nombre'] !== '') {
            $payload['nombre'] = $permitidos['nombre'];
        }
        if ($permitidos['telefono'] !== null) {
            $payload['telefono'] = $permitidos['telefono'];
        }
        if ($permitidos['correo'] !== null && $permitidos['correo'] !== '') {
            $payload['correo'] = $permitidos['correo'];
        }
        $actualizado = $this->usuarios->actualizarUsuario((int) $actor->getIdUsuario(), $payload);
        return $this->obtener($actualizado);
    }
}
