<?php

require_once __DIR__ . '/../../models/Inventario/Categoria.php';

class CategoriaRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function buscarPorId(int $id): ?Categoria
    {
        $stmt = $this->db->query(
            'SELECT * FROM Categorias WHERE id_categoria = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        );
        $fila = $stmt->fetch();
        return $fila ? Categoria::fromArray($fila) : null;
    }

    public function buscarPorNombre(string $nombre): ?Categoria
    {
        $stmt = $this->db->query(
            'SELECT * FROM Categorias WHERE nombre = :n AND deleted_at IS NULL LIMIT 1',
            [':n' => trim($nombre)]
        );
        $fila = $stmt->fetch();
        return $fila ? Categoria::fromArray($fila) : null;
    }

    /** @return Categoria[] */
    public function listar(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM Categorias WHERE deleted_at IS NULL ORDER BY nombre'
        );
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $lista[] = Categoria::fromArray($fila);
        }
        return $lista;
    }

    public function guardar(Categoria $categoria): Categoria
    {
        if ($categoria->getIdCategoria() === null) {
            $this->db->query(
                'INSERT INTO Categorias (nombre, descripcion, estado, created_at, created_by)
                 VALUES (:n, :d, :e, :c, :u)',
                [
                    ':n' => $categoria->getNombre(),
                    ':d' => $categoria->getDescripcion(),
                    ':e' => $categoria->getEstado(),
                    ':c' => $categoria->getCreatedAt()->format('Y-m-d H:i:s'),
                    ':u' => $categoria->getCreatedBy(),
                ]
            );
            $categoria->assignId((int) $this->db->lastInsertId());
            return $categoria;
        }
        $this->db->query(
            'UPDATE Categorias SET
                nombre = :n, descripcion = :d, estado = :e,
                updated_at = :ua, updated_by = :ub, deleted_at = :da
             WHERE id_categoria = :id',
            [
                ':n'  => $categoria->getNombre(),
                ':d'  => $categoria->getDescripcion(),
                ':e'  => $categoria->getEstado(),
                ':ua' => $categoria->getUpdatedAt() ? $categoria->getUpdatedAt()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                ':ub' => $categoria->getUpdatedBy(),
                ':da' => $categoria->getDeletedAt() ? $categoria->getDeletedAt()->format('Y-m-d H:i:s') : null,
                ':id' => $categoria->getIdCategoria(),
            ]
        );
        return $categoria;
    }
}
