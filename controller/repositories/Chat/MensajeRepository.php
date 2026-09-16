<?php

require_once __DIR__ . '/../../models/Chat/Mensaje.php';
require_once __DIR__ . '/../../models/Chat/Conversacion.php';

class MensajeRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    private const SELECT = 'SELECT m.*,
            cv.tipo_contacto,
            cv.id_contacto,
            a.nombre_original AS adjunto_nombre,
            a.extension AS adjunto_ext,
            CASE
                WHEN m.tipo_emisor = \'Cliente\' THEN cl.nombre
                WHEN m.tipo_emisor = \'Proveedor\' THEN pr.nombre
                ELSE u.nombre
            END AS emisor_nombre,
            CASE
                WHEN cv.tipo_contacto = \'Cliente\' THEN cl_c.nombre
                WHEN cv.tipo_contacto = \'Proveedor\' THEN pr_c.nombre
                WHEN cv.tipo_contacto = \'Usuario\' THEN u_c.nombre
                WHEN m.tipo_emisor = \'Cliente\' THEN u2.nombre
                ELSE cl2.nombre
            END AS receptor_nombre
         FROM Mensajes m
         LEFT JOIN Conversaciones cv ON cv.id_conversacion = m.id_conversacion
         LEFT JOIN Clientes cl ON m.tipo_emisor = \'Cliente\' AND cl.id_cliente = m.emisor_id
         LEFT JOIN Usuarios u ON m.tipo_emisor = \'Usuario\' AND u.id_usuario = m.emisor_id
         LEFT JOIN Proveedores pr ON m.tipo_emisor = \'Proveedor\' AND pr.id_proveedor = m.emisor_id
         LEFT JOIN Usuarios u2 ON m.tipo_emisor = \'Cliente\' AND u2.id_usuario = m.receptor_id
         LEFT JOIN Clientes cl2 ON m.tipo_emisor = \'Usuario\' AND cl2.id_cliente = m.receptor_id
         LEFT JOIN Clientes cl_c ON cv.tipo_contacto = \'Cliente\' AND cl_c.id_cliente = cv.id_contacto
         LEFT JOIN Proveedores pr_c ON cv.tipo_contacto = \'Proveedor\' AND pr_c.id_proveedor = cv.id_contacto
         LEFT JOIN Usuarios u_c ON cv.tipo_contacto = \'Usuario\' AND u_c.id_usuario = cv.id_contacto
         LEFT JOIN Adjuntos a ON a.id_adjunto = m.id_adjunto AND a.deleted_at IS NULL';

    public function buscarPorId(int $id): ?Mensaje
    {
        $fila = $this->db->query(
            self::SELECT . ' WHERE m.id_mensaje = :id AND m.deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        )->fetch();
        return $fila ? Mensaje::fromArray($fila) : null;
    }

    public function buscarPorIdExterno(string $idExterno): ?Mensaje
    {
        if ($idExterno === '') {
            return null;
        }
        $fila = $this->db->query(
            self::SELECT . ' WHERE m.id_externo = :ext AND m.deleted_at IS NULL LIMIT 1',
            [':ext' => $idExterno]
        )->fetch();
        return $fila ? Mensaje::fromArray($fila) : null;
    }

    /** @return Mensaje[] */
    public function listarPorConversacion(int $idConversacion): array
    {
        $sql = self::SELECT . ' WHERE m.deleted_at IS NULL AND m.id_conversacion = :id
            ORDER BY m.fecha_hora ASC, m.id_mensaje ASC';
        $lista = [];
        foreach ($this->db->query($sql, [':id' => $idConversacion])->fetchAll() as $fila) {
            $lista[] = Mensaje::fromArray($fila);
        }
        return $lista;
    }

    /** @return Mensaje[] */
    public function listarPorCliente(int $idCliente): array
    {
        $sql = self::SELECT . ' WHERE m.deleted_at IS NULL
            AND (
                m.id_conversacion IN (
                    SELECT c.id_conversacion FROM Conversaciones c
                    WHERE c.deleted_at IS NULL AND c.tipo_contacto = :tipo AND c.id_contacto = :cli0
                      AND c.canal = :can
                )
                OR (
                    (m.id_conversacion IS NULL OR m.id_conversacion = 0)
                    AND (
                        (m.tipo_emisor = :tcli AND m.emisor_id = :c1)
                        OR (m.tipo_emisor = :tusr AND m.receptor_id = :c2)
                    )
                )
            )
            ORDER BY m.fecha_hora ASC, m.id_mensaje ASC';
        $params = [
            ':tipo' => Conversacion::TIPO_CLIENTE,
            ':cli0' => $idCliente,
            ':can'  => Conversacion::CANAL_INTERNO,
            ':tcli' => Mensaje::TIPO_CLIENTE,
            ':c1'   => $idCliente,
            ':tusr' => Mensaje::TIPO_USUARIO,
            ':c2'   => $idCliente,
        ];
        $lista = [];
        foreach ($this->db->query($sql, $params)->fetchAll() as $fila) {
            $lista[] = Mensaje::fromArray($fila);
        }
        return $lista;
    }

    /** @return Mensaje[] */
    public function listarTodos(): array
    {
        $lista = [];
        foreach ($this->db->query(self::SELECT . ' WHERE m.deleted_at IS NULL ORDER BY m.fecha_hora DESC, m.id_mensaje DESC')->fetchAll() as $fila) {
            $lista[] = Mensaje::fromArray($fila);
        }
        return $lista;
    }

    /**
     * Obtiene totales, no leídos y último mensaje de varios hilos en solo
     * dos consultas. Evita dos consultas adicionales por cada contacto.
     *
     * @param int[] $idsConversacion
     * @return array<int,array{total:int,no_leidos:int,ultimo:?Mensaje}>
     */
    public function resumenConversaciones(array $idsConversacion, int $idUsuario): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $idsConversacion),
            static fn (int $id): bool => $id > 0
        )));
        if ($ids === []) {
            return [];
        }

        $ph = [];
        $params = [
            ':leido' => Mensaje::LEIDO,
            ':tipo'  => Mensaje::TIPO_USUARIO,
            ':yo'    => $idUsuario,
        ];
        foreach ($ids as $i => $id) {
            $clave = ':c' . $i;
            $ph[] = $clave;
            $params[$clave] = $id;
        }

        $sql = 'SELECT id_conversacion,
                       COUNT(*) AS total,
                       SUM(
                           CASE
                               WHEN estado <> :leido
                                AND NOT (tipo_emisor = :tipo AND emisor_id = :yo)
                               THEN 1 ELSE 0
                           END
                       ) AS no_leidos,
                       MAX(id_mensaje) AS ultimo_id
                FROM Mensajes
                WHERE deleted_at IS NULL
                  AND id_conversacion IN (' . implode(',', $ph) . ')
                GROUP BY id_conversacion';

        $out = [];
        $ultimos = [];
        foreach ($this->db->query($sql, $params)->fetchAll() as $fila) {
            $idConv = (int) $fila['id_conversacion'];
            $ultimoId = (int) ($fila['ultimo_id'] ?? 0);
            $out[$idConv] = [
                'total'      => (int) ($fila['total'] ?? 0),
                'no_leidos'  => (int) ($fila['no_leidos'] ?? 0),
                'ultimo'     => null,
            ];
            if ($ultimoId > 0) {
                $ultimos[] = $ultimoId;
            }
        }

        if ($ultimos === []) {
            return $out;
        }

        $phUltimos = [];
        $paramsUltimos = [];
        foreach ($ultimos as $i => $id) {
            $clave = ':m' . $i;
            $phUltimos[] = $clave;
            $paramsUltimos[$clave] = $id;
        }
        $filas = $this->db->query(
            self::SELECT . ' WHERE m.deleted_at IS NULL
                AND m.id_mensaje IN (' . implode(',', $phUltimos) . ')',
            $paramsUltimos
        )->fetchAll();
        foreach ($filas as $fila) {
            $mensaje = Mensaje::fromArray($fila);
            $idConv = (int) ($mensaje->getIdConversacion() ?? 0);
            if ($idConv > 0 && isset($out[$idConv])) {
                $out[$idConv]['ultimo'] = $mensaje;
            }
        }

        return $out;
    }

    public function guardar(Mensaje $mensaje): Mensaje
    {
        if ($mensaje->getIdMensaje() === null) {
            $this->db->query(
                'INSERT INTO Mensajes
                    (emisor_id, receptor_id, tipo_emisor, contenido, estado, fecha_hora, id_conversacion, canal, id_externo, id_adjunto)
                 VALUES (:e, :r, :t, :c, :est, :f, :idc, :can, :ext, :adj)',
                [
                    ':e'   => $mensaje->getEmisorId(),
                    ':r'   => $mensaje->getReceptorId(),
                    ':t'   => $mensaje->getTipoEmisor(),
                    ':c'   => $mensaje->getContenido(),
                    ':est' => $mensaje->getEstado(),
                    ':f'   => $mensaje->getFechaHora()->format('Y-m-d H:i:s'),
                    ':idc' => $mensaje->getIdConversacion(),
                    ':can' => $mensaje->getCanal(),
                    ':ext' => $mensaje->getIdExterno(),
                    ':adj' => $mensaje->getIdAdjunto(),
                ]
            );
            $mensaje->assignId((int) $this->db->lastInsertId());
            return $this->buscarPorId((int) $mensaje->getIdMensaje()) ?: $mensaje;
        }
        $this->db->query(
            'UPDATE Mensajes SET estado = :est, updated_at = :u WHERE id_mensaje = :id',
            [
                ':est' => $mensaje->getEstado(),
                ':u'   => date('Y-m-d H:i:s'),
                ':id'  => $mensaje->getIdMensaje(),
            ]
        );
        return $this->buscarPorId((int) $mensaje->getIdMensaje()) ?: $mensaje;
    }

    public function actualizarEstadoPorIdExterno(string $idExterno, string $estado): bool
    {
        $idExterno = trim($idExterno);
        if ($idExterno === '') {
            return false;
        }
        $this->db->query(
            'UPDATE Mensajes SET estado = :est, updated_at = :u
             WHERE id_externo = :ext AND deleted_at IS NULL',
            [
                ':est' => $estado,
                ':u'   => date('Y-m-d H:i:s'),
                ':ext' => $idExterno,
            ]
        );
        return true;
    }

    public function marcarLeidosConversacion(int $idConversacion, string $tipoEmisorPropio, int $emisorId): void
    {
        $this->db->query(
            'UPDATE Mensajes
             SET estado = :leido, updated_at = :u
             WHERE deleted_at IS NULL
               AND id_conversacion = :idc
               AND estado <> :pend
               AND NOT (tipo_emisor = :tipo AND emisor_id = :yo)',
            [
                ':leido' => Mensaje::LEIDO,
                ':u'     => date('Y-m-d H:i:s'),
                ':idc'   => $idConversacion,
                ':pend'  => Mensaje::LEIDO,
                ':tipo'  => $tipoEmisorPropio,
                ':yo'    => $emisorId,
            ]
        );
    }

    public function contarNoLeidosConversacion(int $idConversacion, string $tipoEmisorPropio, int $emisorId): int
    {
        $fila = $this->db->query(
            'SELECT COUNT(*) AS n FROM Mensajes
             WHERE deleted_at IS NULL
               AND id_conversacion = :idc
               AND estado <> :leido
               AND NOT (tipo_emisor = :tipo AND emisor_id = :yo)',
            [
                ':idc'   => $idConversacion,
                ':leido' => Mensaje::LEIDO,
                ':tipo'  => $tipoEmisorPropio,
                ':yo'    => $emisorId,
            ]
        )->fetch();
        return (int) ($fila['n'] ?? 0);
    }

    public function marcarLeidosEntrantes(int $idCliente, bool $comoCliente): void
    {
        if ($comoCliente) {
            $this->db->query(
                'UPDATE Mensajes SET estado = :leido, updated_at = :u
                 WHERE deleted_at IS NULL AND estado <> :pend
                   AND tipo_emisor = :tipo AND receptor_id = :cli',
                [
                    ':leido' => Mensaje::LEIDO,
                    ':u'     => date('Y-m-d H:i:s'),
                    ':pend'  => Mensaje::LEIDO,
                    ':tipo'  => Mensaje::TIPO_USUARIO,
                    ':cli'   => $idCliente,
                ]
            );
            return;
        }
        $this->db->query(
            'UPDATE Mensajes SET estado = :leido, updated_at = :u
             WHERE deleted_at IS NULL AND estado <> :pend
               AND tipo_emisor = :tipo AND emisor_id = :cli',
            [
                ':leido' => Mensaje::LEIDO,
                ':u'     => date('Y-m-d H:i:s'),
                ':pend'  => Mensaje::LEIDO,
                ':tipo'  => Mensaje::TIPO_CLIENTE,
                ':cli'   => $idCliente,
            ]
        );
    }
}
