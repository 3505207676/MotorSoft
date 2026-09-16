<?php

require_once __DIR__ . '/../../config/catalogo_roles.php';

/**
 * Sincroniza Roles, Permisos y Permisos_Rol con el catálogo.
 * Idempotente. Una vez por proceso PHP.
 */
class RolPermisoSchema
{
    private Database $db;
    private static bool $listo = false;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function asegurar(): void
    {
        if (self::$listo) {
            return;
        }

        $this->asegurarRoles();
        $this->asegurarColumnaMatriz();
        $this->asegurarPermisos();
        $this->asegurarAsignaciones();

        self::$listo = true;
    }

    private function asegurarColumnaMatriz(): void
    {
        if ($this->tieneColumna('Roles', 'matriz_manual')) {
            return;
        }
        try {
            $this->db->query('ALTER TABLE Roles ADD COLUMN matriz_manual TINYINT NOT NULL DEFAULT 0');
        } catch (Throwable $e) {
            error_log('RolPermisoSchema ALTER Roles.matriz_manual: ' . $e->getMessage());
        }
    }

    private function tieneColumna(string $tabla, string $columna): bool
    {
        $fila = $this->db->query(
            'SELECT COUNT(*) AS n
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND LOWER(TABLE_NAME) = LOWER(:t)
               AND LOWER(COLUMN_NAME) = LOWER(:c)',
            [':t' => $tabla, ':c' => $columna]
        )->fetch();
        return (int) ($fila['n'] ?? 0) > 0;
    }

    private function asegurarRoles(): void
    {
        foreach (catalogo_roles_base() as $nombre => $descripcion) {
            $fila = $this->db->query(
                'SELECT id_rol FROM Roles WHERE nombre = :n AND deleted_at IS NULL LIMIT 1',
                [':n' => $nombre]
            )->fetch();
            if ($fila) {
                $this->db->query(
                    'UPDATE Roles SET descripcion = :d, estado = :e, deleted_at = NULL WHERE id_rol = :id',
                    [':d' => $descripcion, ':e' => 'Activo', ':id' => (int) $fila['id_rol']]
                );
                continue;
            }
            $this->db->query(
                'INSERT INTO Roles (nombre, descripcion, estado) VALUES (:n, :d, :e)',
                [':n' => $nombre, ':d' => $descripcion, ':e' => 'Activo']
            );
        }
    }

    private function asegurarPermisos(): void
    {
        foreach (catalogo_permisos() as $p) {
            [$nombre, $slug, $modulo, $descripcion] = $p;
            $fila = $this->db->query(
                'SELECT id_permiso FROM Permisos WHERE slug = :s LIMIT 1',
                [':s' => $slug]
            )->fetch();
            if ($fila) {
                $this->db->query(
                    'UPDATE Permisos SET nombre = :n, modulo = :m, descripcion = :d WHERE id_permiso = :id',
                    [
                        ':n'  => $nombre,
                        ':m'  => $modulo,
                        ':d'  => $descripcion,
                        ':id' => (int) $fila['id_permiso'],
                    ]
                );
                continue;
            }
            $this->db->query(
                'INSERT INTO Permisos (nombre, slug, modulo, descripcion) VALUES (:n, :s, :m, :d)',
                [
                    ':n' => $nombre,
                    ':s' => $slug,
                    ':m' => $modulo,
                    ':d' => $descripcion,
                ]
            );
        }
    }

    private function asegurarAsignaciones(): void
    {
        $actor = $this->db->query(
            'SELECT id_usuario FROM Usuarios WHERE deleted_at IS NULL ORDER BY id_usuario ASC LIMIT 1'
        )->fetch();
        if (!$actor) {
            return;
        }
        $createdBy = (int) $actor['id_usuario'];

        $permisosPorSlug = [];
        foreach ($this->db->query('SELECT id_permiso, slug FROM Permisos')->fetchAll() as $fila) {
            $permisosPorSlug[(string) $fila['slug']] = (int) $fila['id_permiso'];
        }

        foreach (catalogo_roles_base() as $nombre => $_desc) {
            $rol = $this->db->query(
                'SELECT * FROM Roles WHERE nombre = :n AND deleted_at IS NULL LIMIT 1',
                [':n' => $nombre]
            )->fetch();
            if (!$rol) {
                continue;
            }
            if (!empty($rol['matriz_manual'])) {
                continue;
            }
            $idRol = (int) $rol['id_rol'];
            $deseados = catalogo_slugs_por_rol($nombre);
            $idsDeseados = [];
            foreach ($deseados as $slug) {
                if (!isset($permisosPorSlug[$slug])) {
                    continue;
                }
                $idsDeseados[] = $permisosPorSlug[$slug];
            }
            $this->sincronizarRol($idRol, $idsDeseados, $createdBy);
        }
    }

    public function marcarMatrizManual(int $idRol, bool $manual): void
    {
        $this->db->query(
            'UPDATE Roles SET matriz_manual = :m WHERE id_rol = :id',
            [':m' => $manual ? 1 : 0, ':id' => $idRol]
        );
    }

    /** @param string[] $slugs */
    public function aplicarSlugs(int $idRol, array $slugs, int $createdBy): void
    {
        $permisosPorSlug = [];
        foreach ($this->db->query('SELECT id_permiso, slug FROM Permisos')->fetchAll() as $fila) {
            $permisosPorSlug[(string) $fila['slug']] = (int) $fila['id_permiso'];
        }
        $ids = [];
        foreach ($slugs as $slug) {
            $clave = trim((string) $slug);
            if ($clave !== '' && isset($permisosPorSlug[$clave])) {
                $ids[] = $permisosPorSlug[$clave];
            }
        }
        $this->sincronizarRol($idRol, $ids, $createdBy);
    }

    public function restaurarRolDesdeCatalogo(string $nombre, int $createdBy): void
    {
        $rol = $this->db->query(
            'SELECT id_rol FROM Roles WHERE nombre = :n AND deleted_at IS NULL LIMIT 1',
            [':n' => $nombre]
        )->fetch();
        if (!$rol) {
            return;
        }
        $idRol = (int) $rol['id_rol'];
        $this->marcarMatrizManual($idRol, false);
        $this->aplicarSlugs($idRol, catalogo_slugs_por_rol($nombre), $createdBy);
    }

    /** @param int[] $idsDeseados */
    private function sincronizarRol(int $idRol, array $idsDeseados, int $createdBy): void
    {
        $actuales = $this->db->query(
            'SELECT id_permiso_modular, id_permiso, deleted_at
             FROM Permisos_Rol
             WHERE id_rol = :r',
            [':r' => $idRol]
        )->fetchAll();

        $porPermiso = [];
        foreach ($actuales as $fila) {
            $porPermiso[(int) $fila['id_permiso']] = $fila;
        }

        $idsDeseados = array_values(array_unique($idsDeseados));

        foreach ($idsDeseados as $idPermiso) {
            if (!isset($porPermiso[$idPermiso])) {
                $this->db->query(
                    'INSERT INTO Permisos_Rol (id_rol, id_permiso, created_by) VALUES (:r, :p, :u)',
                    [':r' => $idRol, ':p' => $idPermiso, ':u' => $createdBy]
                );
                continue;
            }
            if (!empty($porPermiso[$idPermiso]['deleted_at'])) {
                $this->db->query(
                    'UPDATE Permisos_Rol SET deleted_at = NULL WHERE id_permiso_modular = :id',
                    [':id' => (int) $porPermiso[$idPermiso]['id_permiso_modular']]
                );
            }
        }

        $setDeseados = array_fill_keys($idsDeseados, true);
        foreach ($porPermiso as $idPermiso => $fila) {
            if (isset($setDeseados[$idPermiso])) {
                continue;
            }
            if (!empty($fila['deleted_at'])) {
                continue;
            }
            $this->db->query(
                'UPDATE Permisos_Rol SET deleted_at = NOW() WHERE id_permiso_modular = :id',
                [':id' => (int) $fila['id_permiso_modular']]
            );
        }
    }
}
