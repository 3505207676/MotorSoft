<?php

require_once __DIR__ . '/../Seguridad/Usuario.php';
require_once __DIR__ . '/Cuenta.php';

class SesionCaja
{
    public const ACTIVA  = 'Activa';
    public const CERRADA = 'Cerrada';

    private ?int $idCaja;
    private int $idCuenta;
    private int $idUsuario;
    private DateTime $fechaApertura;
    private ?DateTime $fechaCierra;
    private float $montoApertura;
    private ?float $montoSistema;
    private ?float $montoReal;
    private ?float $diferencia;
    private string $estadoSesion;
    private ?Usuario $usuario;
    private ?Cuenta $cuenta;

    public function __construct(int $idCuenta, int $idUsuario, float $montoApertura)
    {
        $this->idCaja        = null;
        $this->idCuenta      = $idCuenta;
        $this->idUsuario     = $idUsuario;
        $this->fechaApertura = new DateTime();
        $this->fechaCierra   = null;
        $this->montoApertura = round($montoApertura, 2);
        $this->montoSistema  = round($montoApertura, 2);
        $this->montoReal     = null;
        $this->diferencia    = null;
        $this->estadoSesion  = self::ACTIVA;
        $this->usuario       = null;
        $this->cuenta        = null;
    }

    public static function fromArray(array $fila, ?Usuario $usuario = null, ?Cuenta $cuenta = null): self
    {
        $s = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $s->idCaja        = isset($fila['id_caja']) ? (int) $fila['id_caja'] : null;
        $s->idCuenta      = (int) ($fila['id_cuenta'] ?? 0);
        $s->idUsuario     = (int) ($fila['id_usuario'] ?? 0);
        $s->fechaApertura = !empty($fila['fecha_apertura']) ? new DateTime($fila['fecha_apertura']) : new DateTime();
        $s->fechaCierra   = !empty($fila['fecha_cierra']) ? new DateTime($fila['fecha_cierra']) : null;
        $s->montoApertura = (float) ($fila['monto_apertura'] ?? 0);
        $s->montoSistema  = isset($fila['monto_sistema']) ? (float) $fila['monto_sistema'] : null;
        $s->montoReal     = isset($fila['monto_real']) ? (float) $fila['monto_real'] : null;
        $s->diferencia    = isset($fila['diferencia']) ? (float) $fila['diferencia'] : null;
        $s->estadoSesion  = $fila['estado_sesion'] ?? self::ACTIVA;
        $s->usuario       = $usuario;
        $s->cuenta        = $cuenta;
        return $s;
    }

    public function isActiva(): bool
    {
        return $this->estadoSesion === self::ACTIVA && $this->fechaCierra === null;
    }

    public function aplicarMovimiento(string $tipo, float $monto): void
    {
        $base = $this->montoSistema ?? $this->montoApertura;
        $monto = round(abs($monto), 2);
        $this->montoSistema = strtolower($tipo) === 'ingreso' ? $base + $monto : $base - $monto;
    }

    public function cerrar(float $montoReal, float $montoSistema): void
    {
        if (!$this->isActiva()) {
            throw new AppException('La sesión de caja ya está cerrada', HTTP_BAD_REQUEST);
        }
        $this->montoSistema = round($montoSistema, 2);
        $this->montoReal    = round($montoReal, 2);
        $this->diferencia   = round($this->montoReal - $this->montoSistema, 2);
        $this->fechaCierra  = new DateTime();
        $this->estadoSesion = self::CERRADA;
    }

    public function getIdCaja(): ?int { return $this->idCaja; }
    public function assignId(int $id): void { $this->idCaja = $id; }
    public function getIdCuenta(): int { return $this->idCuenta; }
    public function getIdUsuario(): int { return $this->idUsuario; }
    public function getFechaApertura(): DateTime { return $this->fechaApertura; }
    public function getFechaCierra(): ?DateTime { return $this->fechaCierra; }
    public function getMontoApertura(): float { return $this->montoApertura; }
    public function getMontoSistema(): ?float { return $this->montoSistema; }
    public function getMontoReal(): ?float { return $this->montoReal; }
    public function getDiferencia(): ?float { return $this->diferencia; }
    public function getEstadoSesion(): string { return $this->estadoSesion; }
    public function getUsuario(): ?Usuario { return $this->usuario; }
    public function getCuenta(): ?Cuenta { return $this->cuenta; }

    public function toArray(): array
    {
        return [
            'id_caja'        => $this->idCaja,
            'id_cuenta'      => $this->idCuenta,
            'cuenta'         => $this->cuenta ? $this->cuenta->getNombre() : null,
            'id_usuario'     => $this->idUsuario,
            'usuario'        => $this->usuario ? $this->usuario->getNombre() : null,
            'fecha_apertura' => $this->fechaApertura->format('Y-m-d H:i:s'),
            'fecha_cierra'   => $this->fechaCierra ? $this->fechaCierra->format('Y-m-d H:i:s') : null,
            'monto_apertura' => $this->montoApertura,
            'monto_sistema'  => $this->montoSistema,
            'monto_real'     => $this->montoReal,
            'diferencia'     => $this->diferencia,
            'estado_sesion'  => $this->estadoSesion,
        ];
    }
}
