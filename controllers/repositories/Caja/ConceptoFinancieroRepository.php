<?php

require_once __DIR__ . '/../../models/Caja/ConceptoFinanciero.php';

class ConceptoFinancieroRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function buscarPorId(int $id): ?ConceptoFinanciero
    {
        $stmt = $this->db->query(
            'SELECT * FROM Conceptos_Financieros WHERE id_concepto = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        );
        $fila = $stmt->fetch();
        return $fila ? ConceptoFinanciero::fromArray($fila) : null;
    }

    public function buscarPorNombre(string $nombre): ?ConceptoFinanciero
    {
        $stmt = $this->db->query(
            'SELECT * FROM Conceptos_Financieros WHERE nombre = :n AND deleted_at IS NULL LIMIT 1',
            [':n' => trim($nombre)]
        );
        $fila = $stmt->fetch();
        return $fila ? ConceptoFinanciero::fromArray($fila) : null;
    }

    /** @return ConceptoFinanciero[] */
    public function listar(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM Conceptos_Financieros WHERE deleted_at IS NULL ORDER BY tipo, nombre'
        );
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $lista[] = ConceptoFinanciero::fromArray($fila);
        }
        return $lista;
    }

    public function guardar(ConceptoFinanciero $concepto): ConceptoFinanciero
    {
        $this->db->query(
            'INSERT INTO Conceptos_Financieros (nombre, tipo, descripcion, created_at, created_by)
             VALUES (:n, :t, :d, :c, :u)',
            [
                ':n' => $concepto->getNombre(),
                ':t' => $concepto->getTipo(),
                ':d' => $concepto->getDescripcion(),
                ':c' => date('Y-m-d H:i:s'),
                ':u' => $concepto->getCreatedBy(),
            ]
        );
        $concepto->assignId((int) $this->db->lastInsertId());
        return $concepto;
    }
}
