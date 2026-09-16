<?php

require_once __DIR__ . '/../../models/Archivos/Adjunto.php';

class AdjuntoRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function buscarPorId(int $id): ?Adjunto
    {
        $fila = $this->db->query(
            'SELECT * FROM Adjuntos WHERE id_adjunto = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        )->fetch();
        return $fila ? Adjunto::fromArray($fila) : null;
    }

    /** @return Adjunto[] */
    public function listarPorEntidad(string $tipo, int $id): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM Adjuntos
             WHERE entidad_tipo = :t AND entidad_id = :id AND deleted_at IS NULL
             ORDER BY fecha_subida DESC, id_adjunto DESC',
            [':t' => $tipo, ':id' => $id]
        );
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $lista[] = Adjunto::fromArray($fila);
        }
        return $lista;
    }

    public function ultimoDe(string $tipo, int $id): ?Adjunto
    {
        $fila = $this->db->query(
            'SELECT * FROM Adjuntos
             WHERE entidad_tipo = :t AND entidad_id = :id AND deleted_at IS NULL
             ORDER BY fecha_subida DESC, id_adjunto DESC
             LIMIT 1',
            [':t' => $tipo, ':id' => $id]
        )->fetch();
        return $fila ? Adjunto::fromArray($fila) : null;
    }

    public function guardar(Adjunto $adjunto): Adjunto
    {
        if ($adjunto->getIdAdjunto() === null) {
            $this->db->query(
                'INSERT INTO Adjuntos
                    (entidad_tipo, entidad_id, id_usuario, id_cliente, nombre_original,
                     nombre_sistema, ruta, extension, peso_bytes, fecha_subida)
                 VALUES
                    (:tipo, :eid, :uid, :cid, :orig, :sis, :ruta, :ext, :peso, :fecha)',
                [
                    ':tipo'  => $adjunto->getEntidadTipo(),
                    ':eid'   => $adjunto->getEntidadId(),
                    ':uid'   => $adjunto->getIdUsuario(),
                    ':cid'   => $adjunto->getIdCliente(),
                    ':orig'  => $adjunto->getNombreOriginal(),
                    ':sis'   => $adjunto->getNombreSistema(),
                    ':ruta'  => $adjunto->getRuta(),
                    ':ext'   => $adjunto->getExtension(),
                    ':peso'  => $adjunto->getPesoBytes(),
                    ':fecha' => $adjunto->getFechaSubida()->format('Y-m-d H:i:s'),
                ]
            );
            $adjunto->assignId((int) $this->db->lastInsertId());
            return $this->buscarPorId((int) $adjunto->getIdAdjunto()) ?: $adjunto;
        }
        $this->db->query(
            'UPDATE Adjuntos
             SET entidad_tipo = :tipo, entidad_id = :eid, deleted_at = :del
             WHERE id_adjunto = :id',
            [
                ':tipo' => $adjunto->getEntidadTipo(),
                ':eid'  => $adjunto->getEntidadId(),
                ':del'  => $adjunto->getDeletedAt() ? $adjunto->getDeletedAt()->format('Y-m-d H:i:s') : null,
                ':id'   => $adjunto->getIdAdjunto(),
            ]
        );
        return $this->buscarPorId((int) $adjunto->getIdAdjunto()) ?: $adjunto;
    }
}
