<?php

require_once __DIR__ . '/../../models/Inventario/Categoria.php';
require_once __DIR__ . '/../../repositories/Inventario/CategoriaRepository.php';
require_once __DIR__ . '/../Seguridad/AuthService.php';

class CategoriaService
{
    private CategoriaRepository $categorias;
    private AuthService $auth;

    public function __construct(CategoriaRepository $categorias, AuthService $auth)
    {
        $this->categorias = $categorias;
        $this->auth       = $auth;
    }

    /** @return Categoria[] */
    public function listar(): array
    {
        return $this->categorias->listar();
    }

    public function buscarPorId(int $id): Categoria
    {
        $categoria = $this->categorias->buscarPorId($id);
        if (!$categoria) {
            throw new AppException('Categoría no encontrada', HTTP_NOT_FOUND);
        }
        return $categoria;
    }

    public function buscarPorNombre(string $nombre): ?Categoria
    {
        return $this->categorias->buscarPorNombre(trim($nombre));
    }

    public function obtenerOCrearPorNombre(string $nombre, int $createdBy): Categoria
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new AppException('El nombre de categoría es requerido', HTTP_BAD_REQUEST);
        }
        $existente = $this->categorias->buscarPorNombre($nombre);
        if ($existente) {
            return $existente;
        }
        $categoria = new Categoria($nombre, $createdBy);
        return $this->categorias->guardar($categoria);
    }

    public function guardar(array $data): Categoria
    {
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $createdBy = (int) ($data['created_by'] ?? 0);
        if ($nombre === '' || $createdBy < 1) {
            throw new AppException('nombre y created_by son requeridos', HTTP_BAD_REQUEST);
        }
        $id = (int) ($data['id_categoria'] ?? $data['id'] ?? 0);
        if ($id > 0) {
            $categoria = $this->buscarPorId($id);
            $categoria->setNombre($nombre);
            if (isset($data['descripcion'])) {
                $categoria->setDescripcion((string) $data['descripcion']);
            }
            if (isset($data['estado'])) {
                $categoria->setEstado((string) $data['estado']);
            }
            $categoria->tocarUpdatedAt($data['updated_by'] ?? $createdBy);
            return $this->categorias->guardar($categoria);
        }
        $categoria = new Categoria($nombre, $createdBy, (string) ($data['descripcion'] ?? ''));
        return $this->categorias->guardar($categoria);
    }

    public function eliminar(int $id, array $contexto = []): void
    {
        $categoria = $this->buscarPorId($id);
        $categoria->marcarEliminado($contexto['updated_by'] ?? null);
        $this->categorias->guardar($categoria);
    }
}
