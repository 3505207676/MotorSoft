<?php

require_once __DIR__ . '/../../models/Chat/Conversacion.php';

class ConversacionRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function buscarPorId(int $id): ?Conversacion
    {
        $fila = $this->db->query(
            'SELECT * FROM Conversaciones WHERE id_conversacion = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        )->fetch();
        return $fila ? Conversacion::fromArray($fila) : null;
    }

    public function buscarPorClave(string $canal, string $tipoContacto, int $idContacto, int $idPar = 0): ?Conversacion
    {
        $fila = $this->db->query(
            'SELECT * FROM Conversaciones
             WHERE canal = :can AND tipo_contacto = :tipo AND id_contacto = :cid AND id_par = :par
               AND deleted_at IS NULL
             LIMIT 1',
            [
                ':can'  => $canal,
                ':tipo' => $tipoContacto,
                ':cid'  => $idContacto,
                ':par'  => $idPar,
            ]
        )->fetch();
        return $fila ? Conversacion::fromArray($fila) : null;
    }

    public function buscarParUsuarios(string $canal, int $idA, int $idB): ?Conversacion
    {
        [$min, $max] = Conversacion::parUsuarios($idA, $idB);
        return $this->buscarPorClave($canal, Conversacion::TIPO_USUARIO, $min, $max);
    }

    /** @return Conversacion[] */
    public function listar(array $filtros = []): array
    {
        $sql = 'SELECT * FROM Conversaciones WHERE deleted_at IS NULL';
        $params = [];
        if (!empty($filtros['canal'])) {
            $sql .= ' AND canal = :can';
            $params[':can'] = $filtros['canal'];
        }
        if (!empty($filtros['tipo_contacto'])) {
            $sql .= ' AND tipo_contacto = :tipo';
            $params[':tipo'] = $filtros['tipo_contacto'];
        }
        $sql .= ' ORDER BY (ultimo_mensaje_at IS NULL) ASC, ultimo_mensaje_at DESC, id_conversacion DESC';
        $lista = [];
        foreach ($this->db->query($sql, $params)->fetchAll() as $fila) {
            $lista[] = Conversacion::fromArray($fila);
        }
        return $lista;
    }

    public function guardar(Conversacion $conv): Conversacion
    {
        if ($conv->getIdConversacion() === null) {
            $this->db->query(
                'INSERT INTO Conversaciones
                    (canal, tipo_contacto, id_contacto, id_par, telefono, titulo, ultimo_mensaje_at, created_at, created_by)
                 VALUES (:can, :tipo, :cid, :par, :tel, :tit, :ult, :c, :u)',
                [
                    ':can'  => $conv->getCanal(),
                    ':tipo' => $conv->getTipoContacto(),
                    ':cid'  => $conv->getIdContacto(),
                    ':par'  => $conv->getIdPar(),
                    ':tel'  => $conv->getTelefono(),
                    ':tit'  => $conv->getTitulo(),
                    ':ult'  => $conv->getUltimoMensajeAt()
                        ? $conv->getUltimoMensajeAt()->format('Y-m-d H:i:s')
                        : null,
                    ':c'    => $conv->getCreatedAt()->format('Y-m-d H:i:s'),
                    ':u'    => $conv->getCreatedBy(),
                ]
            );
            $conv->assignId((int) $this->db->lastInsertId());
            return $this->buscarPorId((int) $conv->getIdConversacion()) ?: $conv;
        }
        $this->db->query(
            'UPDATE Conversaciones
             SET telefono = :tel, titulo = :tit, ultimo_mensaje_at = :ult, id_contacto = :cid, id_par = :par
             WHERE id_conversacion = :id',
            [
                ':tel' => $conv->getTelefono(),
                ':tit' => $conv->getTitulo(),
                ':ult' => $conv->getUltimoMensajeAt()
                    ? $conv->getUltimoMensajeAt()->format('Y-m-d H:i:s')
                    : null,
                ':cid' => $conv->getIdContacto(),
                ':par' => $conv->getIdPar(),
                ':id'  => $conv->getIdConversacion(),
            ]
        );
        return $this->buscarPorId((int) $conv->getIdConversacion()) ?: $conv;
    }

    public function tocarUltimoMensaje(int $idConversacion, DateTime $cuando): void
    {
        $this->db->query(
            'UPDATE Conversaciones SET ultimo_mensaje_at = :ult WHERE id_conversacion = :id',
            [
                ':ult' => $cuando->format('Y-m-d H:i:s'),
                ':id'  => $idConversacion,
            ]
        );
    }
}
