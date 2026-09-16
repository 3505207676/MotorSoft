<?php

require_once __DIR__ . '/../../models/Configuracion/Configuracion.php';

class ConfiguracionRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function asegurarTabla(): void
    {
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS Configuracion (
                id_config    INT          PRIMARY KEY AUTO_INCREMENT,
                clave        VARCHAR(80)  NOT NULL UNIQUE,
                valor        TEXT         NOT NULL,
                tipo         VARCHAR(20)  NOT NULL DEFAULT \'string\',
                descripcion  VARCHAR(250) NOT NULL,
                created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_by   INT          NOT NULL,
                updated_at   DATETIME     NULL,
                updated_by   INT          NULL
            )'
        );
    }

    public function buscarPorClave(string $clave): ?Configuracion
    {
        $stmt = $this->db->query(
            'SELECT * FROM Configuracion WHERE clave = :c LIMIT 1',
            [':c' => $clave]
        );
        $fila = $stmt->fetch();
        return $fila ? Configuracion::fromArray($fila) : null;
    }

    /** @return Configuracion[] */
    public function listar(): array
    {
        $stmt = $this->db->query('SELECT * FROM Configuracion ORDER BY clave');
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $lista[] = Configuracion::fromArray($fila);
        }
        return $lista;
    }

    public function guardar(Configuracion $config): Configuracion
    {
        if ($config->getIdConfig() === null) {
            $this->db->query(
                'INSERT INTO Configuracion (clave, valor, tipo, descripcion, created_at, created_by)
                 VALUES (:k, :v, :t, :d, :c, :u)',
                [
                    ':k' => $config->getClave(),
                    ':v' => $config->getValor(),
                    ':t' => $config->getTipo(),
                    ':d' => $config->getDescripcion(),
                    ':c' => $config->getCreatedAt()->format('Y-m-d H:i:s'),
                    ':u' => $config->getCreatedBy(),
                ]
            );
            $config->assignId((int) $this->db->lastInsertId());
            return $config;
        }
        $this->db->query(
            'UPDATE Configuracion SET valor = :v, updated_at = :ua, updated_by = :ub WHERE id_config = :id',
            [
                ':v'  => $config->getValor(),
                ':ua' => $config->getUpdatedAt() ? $config->getUpdatedAt()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                ':ub' => $config->getUpdatedBy(),
                ':id' => $config->getIdConfig(),
            ]
        );
        return $config;
    }
}
