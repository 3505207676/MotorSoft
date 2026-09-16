<?php

require_once __DIR__ . '/../../models/Chat/Conversacion.php';
require_once __DIR__ . '/../../models/Proveedores/Proveedor.php';

/**
 * Resuelve un contacto polimórfico (Cliente | Usuario | Proveedor) para el chat.
 */
class ChatContactoResolver
{
    private ClienteRepository $clientes;
    private UsuarioRepository $usuarios;
    private ProveedorRepository $proveedores;

    public function __construct(
        ClienteRepository $clientes,
        UsuarioRepository $usuarios,
        ProveedorRepository $proveedores
    ) {
        $this->clientes    = $clientes;
        $this->usuarios    = $usuarios;
        $this->proveedores = $proveedores;
    }

    public function validarTipo(string $tipo): string
    {
        foreach (Conversacion::tiposContacto() as $permitido) {
            if (strcasecmp($tipo, $permitido) === 0) {
                return $permitido;
            }
        }
        throw new AppException('Tipo de contacto no válido', HTTP_BAD_REQUEST);
    }

    public function validarCanal(string $canal): string
    {
        foreach (Conversacion::canales() as $permitido) {
            if (strcasecmp($canal, $permitido) === 0) {
                return $permitido;
            }
        }
        return Conversacion::CANAL_INTERNO;
    }

    /** @return array{tipo_contacto:string,id_contacto:int,nombre:string,documento:string,telefono:string,email:?string,estado:string} */
    public function contacto(string $tipo, int $id): array
    {
        $tipo = $this->validarTipo($tipo);
        if ($id < 1) {
            throw new AppException('Contacto no válido', HTTP_BAD_REQUEST);
        }

        if ($tipo === Conversacion::TIPO_CLIENTE) {
            $cli = $this->clientes->buscarPorId($id, false);
            if (!$cli || !$cli->isActivo()) {
                throw new AppException('Cliente no encontrado', HTTP_NOT_FOUND);
            }
            return [
                'tipo_contacto' => $tipo,
                'id_contacto'   => (int) $cli->getIdCliente(),
                'nombre'        => $cli->getNombre(),
                'documento'     => $cli->getDocumento(),
                'telefono'      => $cli->getTelefono(),
                'email'         => $cli->getEmail(),
                'estado'        => $cli->getEstado(),
            ];
        }

        if ($tipo === Conversacion::TIPO_USUARIO) {
            $usr = $this->usuarios->buscarPorId($id, true);
            if (!$usr || !$usr->isActivo()) {
                throw new AppException('Usuario no encontrado', HTTP_NOT_FOUND);
            }
            return [
                'tipo_contacto' => $tipo,
                'id_contacto'   => (int) $usr->getIdUsuario(),
                'nombre'        => $usr->getNombre(),
                'documento'     => $usr->getDocumento(),
                'telefono'      => (string) ($usr->getTelefono() ?? ''),
                'email'         => $usr->getCorreo(),
                'estado'        => $usr->getEstado(),
            ];
        }

        $prov = $this->proveedores->buscarPorId($id);
        if (!$prov || !$prov->isActivo()) {
            throw new AppException('Proveedor no encontrado', HTTP_NOT_FOUND);
        }
        return [
            'tipo_contacto' => $tipo,
            'id_contacto'   => (int) $prov->getIdProveedor(),
            'nombre'        => $prov->getNombre(),
            'documento'     => (string) ($prov->getNit() ?? ''),
            'telefono'      => $prov->getTelefono(),
            'email'         => $prov->getEmail(),
            'estado'        => $prov->getEstado(),
        ];
    }

    /**
     * @return array<int,array{tipo_contacto:string,id_contacto:int,nombre:string,documento:string,telefono:string,email:?string,estado:string}>
     */
    public function directorio(string $tipo, int $excluirUsuario = 0, string $busqueda = ''): array
    {
        $tipo = $this->validarTipo($tipo);
        $q = mb_strtolower(trim($busqueda));
        $out = [];

        if ($tipo === Conversacion::TIPO_CLIENTE) {
            foreach ($this->clientes->listar(['estado' => 'Activo']) as $cli) {
                $out[] = [
                    'tipo_contacto' => $tipo,
                    'id_contacto'   => (int) $cli->getIdCliente(),
                    'nombre'        => $cli->getNombre(),
                    'documento'     => $cli->getDocumento(),
                    'telefono'      => $cli->getTelefono(),
                    'email'         => $cli->getEmail(),
                    'estado'        => $cli->getEstado(),
                ];
            }
        } elseif ($tipo === Conversacion::TIPO_USUARIO) {
            foreach ($this->usuarios->listar() as $usr) {
                if (!$usr->isActivo()) {
                    continue;
                }
                if ($excluirUsuario > 0 && (int) $usr->getIdUsuario() === $excluirUsuario) {
                    continue;
                }
                $out[] = [
                    'tipo_contacto' => $tipo,
                    'id_contacto'   => (int) $usr->getIdUsuario(),
                    'nombre'        => $usr->getNombre(),
                    'documento'     => $usr->getDocumento(),
                    'telefono'      => (string) ($usr->getTelefono() ?? ''),
                    'email'         => $usr->getCorreo(),
                    'estado'        => $usr->getEstado(),
                    'es_admin'      => $usr->esAdministrador(),
                ];
            }
        } else {
            foreach ($this->proveedores->listar(['estado' => 'Activo']) as $prov) {
                $out[] = [
                    'tipo_contacto' => $tipo,
                    'id_contacto'   => (int) $prov->getIdProveedor(),
                    'nombre'        => $prov->getNombre(),
                    'documento'     => (string) ($prov->getNit() ?? ''),
                    'telefono'      => $prov->getTelefono(),
                    'email'         => $prov->getEmail(),
                    'estado'        => $prov->getEstado(),
                ];
            }
        }

        if ($q === '') {
            return $out;
        }
        return array_values(array_filter($out, static function (array $c) use ($q) {
            $blob = mb_strtolower(($c['nombre'] ?? '') . ' ' . ($c['documento'] ?? '') . ' ' . ($c['telefono'] ?? ''));
            return str_contains($blob, $q);
        }));
    }

    /** @return array{tipo_contacto:string,id_contacto:int,nombre:string,documento:string,telefono:string,email:?string,estado:string}|null */
    public function buscarPorTelefono(string $telefono): ?array
    {
        $cli = $this->clientes->buscarPorTelefono($telefono);
        if ($cli) {
            return $this->contacto(Conversacion::TIPO_CLIENTE, (int) $cli->getIdCliente());
        }
        $prov = $this->proveedores->buscarPorTelefono($telefono);
        if ($prov) {
            return $this->contacto(Conversacion::TIPO_PROVEEDOR, (int) $prov->getIdProveedor());
        }
        $usr = $this->usuarios->buscarPorTelefono($telefono);
        if ($usr) {
            return $this->contacto(Conversacion::TIPO_USUARIO, (int) $usr->getIdUsuario());
        }
        return null;
    }

    public function crearProveedorWhatsApp(string $nombre, string $telefono, int $createdBy): array
    {
        $prov = new Proveedor($nombre !== '' ? $nombre : ('WhatsApp ' . $telefono), $telefono, $createdBy);
        $this->proveedores->guardar($prov);
        return $this->contacto(Conversacion::TIPO_PROVEEDOR, (int) $prov->getIdProveedor());
    }

    public function crearProveedor(array $data, int $createdBy): array
    {
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $telefono = trim((string) ($data['telefono'] ?? ''));
        if ($nombre === '' || $telefono === '') {
            throw new AppException('Nombre y teléfono del proveedor son obligatorios', HTTP_BAD_REQUEST);
        }
        $prov = new Proveedor(
            $nombre,
            $telefono,
            $createdBy,
            trim((string) ($data['nit'] ?? '')) ?: null,
            trim((string) ($data['email'] ?? '')) ?: null
        );
        $this->proveedores->guardar($prov);
        return $this->contacto(Conversacion::TIPO_PROVEEDOR, (int) $prov->getIdProveedor());
    }
}
