<?php

require_once __DIR__ . '/Categoria.php';
require_once __DIR__ . '/Stock.php';

/**
 * Repuesto / ítem de catálogo. Tabla: Productos.
 * Relación: Producto -> Categoria, Producto -> Stock (1 a 1 de negocio).
 */
class Producto
{
    private ?int $idProducto;
    private int $idCategoria;
    private string $nombre;
    private ?string $descripcion;
    private ?string $referencia;
    private float $precioUnitario;
    private ?string $tipo;
    private ?string $codigoBarras;
    private string $estado;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;
    private ?Categoria $categoria;
    private ?Stock $stock;

    public function __construct(
        int $idCategoria,
        string $nombre,
        float $precioUnitario,
        int $createdBy,
        ?string $referencia = null,
        ?int $idProducto = null
    ) {
        $this->idProducto     = $idProducto;
        $this->idCategoria    = $idCategoria;
        $this->nombre         = trim($nombre);
        $this->precioUnitario = round($precioUnitario, 2);
        $this->createdBy      = $createdBy;
        $this->referencia     = $referencia ? strtoupper(trim($referencia)) : null;
        $this->descripcion    = null;
        $this->tipo           = null;
        $this->codigoBarras   = null;
        $this->estado         = 'Activo';
        $this->updatedBy      = null;
        $this->createdAt      = new DateTime();
        $this->updatedAt      = null;
        $this->deletedAt      = null;
        $this->categoria      = null;
        $this->stock          = null;
    }

    public static function fromArray(array $fila, ?Categoria $categoria = null, ?Stock $stock = null): self
    {
        $p = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $p->idProducto     = isset($fila['id_producto']) ? (int) $fila['id_producto'] : null;
        $p->idCategoria    = (int) ($fila['id_categoria'] ?? 0);
        $p->nombre         = $fila['nombre'] ?? '';
        $p->descripcion    = $fila['descripcion'] ?? null;
        $p->referencia     = $fila['referencia'] ?? null;
        $p->precioUnitario = (float) ($fila['precio_unitario'] ?? 0);
        $p->tipo           = $fila['tipo'] ?? null;
        $p->codigoBarras   = $fila['codigo_barras'] ?? null;
        $p->estado         = $fila['estado'] ?? 'Activo';
        $p->createdBy      = (int) ($fila['created_by'] ?? 0);
        $p->updatedBy      = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $p->createdAt      = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $p->updatedAt      = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $p->deletedAt      = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $p->categoria      = $categoria;
        $p->stock          = $stock;
        return $p;
    }

    public function isActivo(): bool
    {
        return strtolower($this->estado) === 'activo' && $this->deletedAt === null;
    }

    public function isStockBajo(): bool
    {
        return $this->stock !== null && $this->stock->estaBajoMinimo();
    }

    public function marcarEliminado(?int $updatedBy = null): void
    {
        $this->deletedAt = new DateTime();
        $this->estado    = 'Inactivo';
        $this->tocarUpdatedAt($updatedBy);
    }

    public function tocarUpdatedAt(?int $idUsuario = null): void
    {
        $this->updatedAt = new DateTime();
        if ($idUsuario !== null) {
            $this->updatedBy = $idUsuario;
        }
    }

    public function getIdProducto(): ?int { return $this->idProducto; }
    public function assignId(int $id): void { $this->idProducto = $id; }
    public function getIdCategoria(): int { return $this->idCategoria; }
    public function setIdCategoria(int $id): void { $this->idCategoria = $id; $this->tocarUpdatedAt(); }
    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): void { $this->nombre = trim($nombre); $this->tocarUpdatedAt(); }
    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $descripcion): void { $this->descripcion = $descripcion; $this->tocarUpdatedAt(); }
    public function getReferencia(): ?string { return $this->referencia; }
    public function setReferencia(?string $referencia): void
    {
        $this->referencia = $referencia ? strtoupper(trim($referencia)) : null;
        $this->tocarUpdatedAt();
    }
    public function getPrecioUnitario(): float { return $this->precioUnitario; }
    public function setPrecioUnitario(float $precio): void { $this->precioUnitario = round($precio, 2); $this->tocarUpdatedAt(); }
    public function getTipo(): ?string { return $this->tipo; }
    public function setTipo(?string $tipo): void { $this->tipo = $tipo; $this->tocarUpdatedAt(); }
    public function getCodigoBarras(): ?string { return $this->codigoBarras; }
    public function setCodigoBarras(?string $codigo): void { $this->codigoBarras = $codigo; $this->tocarUpdatedAt(); }
    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): void { $this->estado = $estado; $this->tocarUpdatedAt(); }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function getDeletedAt(): ?DateTime { return $this->deletedAt; }
    public function getCategoria(): ?Categoria { return $this->categoria; }
    public function setCategoria(Categoria $categoria): void
    {
        $this->categoria   = $categoria;
        $this->idCategoria = (int) $categoria->getIdCategoria();
    }
    public function getStock(): ?Stock { return $this->stock; }
    public function setStock(Stock $stock): void { $this->stock = $stock; }

    public function toArray(): array
    {
        $stock = $this->stock ? $this->stock->toArray() : null;
        return [
            'id_producto'      => $this->idProducto,
            'id_categoria'     => $this->idCategoria,
            'nombre'           => $this->nombre,
            'descripcion'      => $this->descripcion,
            'referencia'       => $this->referencia,
            'codigo'           => $this->referencia,
            'precio_unitario'  => $this->precioUnitario,
            'precio'           => $this->precioUnitario,
            'tipo'             => $this->tipo,
            'codigo_barras'    => $this->codigoBarras,
            'estado'           => $this->estado,
            'categoria'        => $this->categoria ? $this->categoria->getNombre() : null,
            'categoria_obj'    => $this->categoria ? $this->categoria->toArray() : null,
            'stock'            => $stock,
            'cantidad'         => $stock['cantidad'] ?? 0,
            'stock_minimo'     => $stock['stock_minimo'] ?? 0,
            'ubicacion'        => $stock['ubicacion'] ?? null,
            'stock_bajo'       => $this->isStockBajo(),
        ];
    }
}
