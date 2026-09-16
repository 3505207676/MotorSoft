CREATE TABLE Roles (
    id_rol      INT          PRIMARY KEY AUTO_INCREMENT,
    nombre      VARCHAR(50)  NOT NULL,
    descripcion TEXT,
    estado      VARCHAR(20)  NOT NULL DEFAULT 'Activo',
    matriz_manual TINYINT     NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by  INT          NULL,
    updated_at  DATETIME     NULL,
    updated_by  INT          NULL,
    deleted_at  DATETIME     NULL
    -- Usuarios omitida intencionalmente (dependencia circular)
);

CREATE TABLE Permisos (
    id_permiso  INT          PRIMARY KEY AUTO_INCREMENT,
    nombre      VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL UNIQUE,
    modulo      VARCHAR(50)  NOT NULL,
    descripcion TEXT         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Usuarios (
    id_usuario          INT          PRIMARY KEY AUTO_INCREMENT,
    id_rol              INT          NOT NULL,
    nombre              VARCHAR(255) NOT NULL,
    correo              VARCHAR(255) NOT NULL UNIQUE,
    telefono            VARCHAR(20),
    documento           VARCHAR(20)  NOT NULL UNIQUE,
    estado              VARCHAR(100) NOT NULL DEFAULT 'Activo',
    password_hash       VARCHAR(500) NOT NULL,
    intentos_login      INT          NOT NULL DEFAULT 0,
    token_recuperacion  VARCHAR(250) NULL,
    token_expiracion    DATETIME     NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by          INT          NULL,
    updated_at          DATETIME     NULL,
    updated_by          INT          NULL,
    deleted_at          DATETIME     NULL,

    FOREIGN KEY (id_rol) REFERENCES Roles(id_rol)
);

CREATE TABLE Permisos_Rol (
    id_permiso_modular  INT      PRIMARY KEY AUTO_INCREMENT,
    id_rol              INT      NOT NULL,
    id_permiso          INT      NOT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by          INT      NOT NULL,
    deleted_at          DATETIME NULL,

    FOREIGN KEY (id_rol)     REFERENCES Roles(id_rol),
    FOREIGN KEY (id_permiso) REFERENCES Permisos(id_permiso),
    FOREIGN KEY (created_by) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Especialidades (
    id_especialidad     INT          PRIMARY KEY AUTO_INCREMENT,
    nombre_especialidad VARCHAR(50)  NOT NULL,
    descripcion         VARCHAR(500),
    estado              VARCHAR(50)  NOT NULL DEFAULT 'Activo',
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by          INT          NOT NULL,
    updated_at          DATETIME     NULL,
    updated_by          INT          NULL,
    deleted_at          DATETIME     NULL,

    FOREIGN KEY (created_by) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Usuario_Especialidad (
    id_user_especialidad INT      PRIMARY KEY AUTO_INCREMENT,
    id_usuario           INT      NOT NULL,
    id_especialidad      INT      NOT NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by           INT      NOT NULL,
    deleted_at           DATETIME NULL,

    FOREIGN KEY (id_usuario)      REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (id_especialidad) REFERENCES Especialidades(id_especialidad),
    FOREIGN KEY (created_by)      REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Sesiones (
    id_sesiones      INT          PRIMARY KEY AUTO_INCREMENT,
    id_usuario       INT          NOT NULL,
    token            VARCHAR(255) NOT NULL,
    fecha_inicio     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME     NOT NULL,
    ip_address       VARCHAR(100),
    deleted_at       DATETIME     NULL,

    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Logs_Actoria (
    id_log             INT          PRIMARY KEY AUTO_INCREMENT,
    id_usuario         INT          NOT NULL,
    id_sesiones        INT          NOT NULL,
    accion             VARCHAR(20)  NOT NULL,
    tabla_afectada     VARCHAR(50)  NOT NULL,
    registro_id        INT          NOT NULL,
    valores_anteriores JSON         NULL,
    valores_nuevos     JSON         NULL,
    ip_address         VARCHAR(100),
    user_agent         VARCHAR(255),
    fecha              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_usuario)  REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (id_sesiones) REFERENCES Sesiones(id_sesiones)
);

CREATE TABLE Contratos (
    id_contrato           INT            PRIMARY KEY AUTO_INCREMENT,
    id_usuario            INT            NOT NULL,
    salario_base          DECIMAL(12,2)  NOT NULL,
    porcentaje_comicion   DECIMAL(3,2)   NOT NULL DEFAULT 0.00,        -- [FIX] 00,00 → 0.00
    fecha_ingreso         DATE           DEFAULT (CURRENT_DATE),       -- [FIX] CURRENT_TIME → CURRENT_DATE
    fecha_retiro          DATE,
    estado_contrato       VARCHAR(15)    DEFAULT 'Vigente',            -- [FIX] comillas tipográficas ´´ → ''
    created_at            DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by            INT            NOT NULL,
    updated_at            DATETIME       NULL,
    updated_by            INT            NULL,
    deleted_at            DATETIME       NULL,

    FOREIGN KEY (created_by) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Nominas (
    id_nomina        INT            PRIMARY KEY AUTO_INCREMENT,
    id_contrato      INT            NOT NULL,
    periodo_pago     VARCHAR(30)    NOT NULL,
    fecha_pago       DATE           DEFAULT (CURRENT_DATE),            -- [FIX] CURRENT_TIME → CURRENT_DATE
    total_neto       DECIMAL(12,2)  NOT NULL,
    created_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by       INT            NOT NULL,
    updated_at       DATETIME       NULL,
    updated_by       INT            NULL,
    deleted_at       DATETIME       NULL,

    FOREIGN KEY (created_by)  REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by)  REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (id_contrato) REFERENCES Contratos(id_contrato)
);

CREATE TABLE Nomina_Rol (
    id_nomina_rol        INT            PRIMARY KEY AUTO_INCREMENT,
    id_nomina            INT            NOT NULL,
    tipo_concepto        VARCHAR(15)    NOT NULL,
    descripcion          VARCHAR(250)   NOT NULL,
    valor                DECIMAL(12,2)  NOT NULL,
    created_at           DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by           INT            NOT NULL,
    updated_at           DATETIME       NULL,
    updated_by           INT            NULL,
    deleted_at           DATETIME       NULL,

    FOREIGN KEY (created_by) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (id_nomina)  REFERENCES Nominas(id_nomina)
);

CREATE TABLE Clientes (
    id_cliente           INT          PRIMARY KEY AUTO_INCREMENT,
    nombre               VARCHAR(255) NOT NULL,
    documento            VARCHAR(20)  NOT NULL UNIQUE,
    email                VARCHAR(255),
    telefono             VARCHAR(20)  NOT NULL,
    preferencia_contacto VARCHAR(100),
    estado               VARCHAR(20)  NOT NULL DEFAULT 'Activo',
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by           INT          NOT NULL,
    updated_at           DATETIME     NULL,
    updated_by           INT          NULL,
    deleted_at           DATETIME     NULL,

    FOREIGN KEY (created_by) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Vehiculo (
    id_vehiculo INT          PRIMARY KEY AUTO_INCREMENT,
    id_cliente  INT          NOT NULL,
    placa       VARCHAR(10)  NOT NULL UNIQUE,
    marca       VARCHAR(50)  NOT NULL,
    modelo      VARCHAR(50),
    año         INT,
    tipo        VARCHAR(30)  NOT NULL DEFAULT 'Automóvil',
    estado      VARCHAR(100) NOT NULL DEFAULT 'Activo',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by  INT          NOT NULL,
    updated_at  DATETIME     NULL,
    updated_by  INT          NULL,
    deleted_at  DATETIME     NULL,

    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    FOREIGN KEY (created_by) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Servicios (
    id_servicio INT           PRIMARY KEY AUTO_INCREMENT,
    tipo        VARCHAR(100),
    nombre      VARCHAR(250)  NOT NULL,
    descripcion VARCHAR(500),
    precio      DECIMAL(12,2) NOT NULL,                                -- [FIX] DECIMAL(12.2) → DECIMAL(12,2)
    estado      VARCHAR(50)   NOT NULL DEFAULT 'Activo',
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by  INT           NOT NULL,
    updated_at  DATETIME      NULL,
    updated_by  INT           NULL,
    deleted_at  DATETIME      NULL,

    FOREIGN KEY (created_by) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Orden_Servicio (
    id_orden      INT          PRIMARY KEY AUTO_INCREMENT,
    id_vehiculo   INT          NOT NULL,
    id_usuario    INT          NOT NULL,
    fecha_ingreso DATETIME     NOT NULL,
    fecha_salida  DATETIME     NULL,
    descripcion   TEXT,
    estado        VARCHAR(100) NOT NULL DEFAULT 'Pendiente',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by    INT          NOT NULL,
    updated_at    DATETIME     NULL,
    updated_by    INT          NULL,
    deleted_at    DATETIME     NULL,

    FOREIGN KEY (id_vehiculo) REFERENCES Vehiculo(id_vehiculo),
    FOREIGN KEY (id_usuario)  REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (created_by)  REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by)  REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Detalles_Servicios (
    id_detalle  INT           PRIMARY KEY AUTO_INCREMENT,
    id_orden    INT           NOT NULL,
    id_servicio INT           NOT NULL,
    precio      DECIMAL(12,2) NOT NULL,
    cantidad    INT           NOT NULL DEFAULT 1,
    subtotal    DECIMAL(12,2) NOT NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by  INT           NOT NULL,

    FOREIGN KEY (id_orden)    REFERENCES Orden_Servicio(id_orden),
    FOREIGN KEY (id_servicio) REFERENCES Servicios(id_servicio),
    FOREIGN KEY (created_by)  REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Categorias (
    id_categoria INT         PRIMARY KEY AUTO_INCREMENT,
    nombre       VARCHAR(50) NOT NULL,
    descripcion  TEXT        NOT NULL,
    estado       VARCHAR(50) NOT NULL DEFAULT 'Activo',
    created_at   DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by   INT         NOT NULL,
    updated_at   DATETIME    NULL,
    updated_by   INT         NULL,
    deleted_at   DATETIME    NULL,

    FOREIGN KEY (created_by) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Productos (
    id_producto     INT           PRIMARY KEY AUTO_INCREMENT,
    id_categoria    INT           NOT NULL,
    nombre          VARCHAR(250)  NOT NULL,
    descripcion     VARCHAR(500),
    referencia      VARCHAR(100)  UNIQUE,
    precio_unitario DECIMAL(12,2) NOT NULL,
    tipo            VARCHAR(50),
    codigo_barras   VARCHAR(250),
    estado          VARCHAR(100)  NOT NULL DEFAULT 'Activo',
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by      INT           NOT NULL,
    updated_at      DATETIME      NULL,
    updated_by      INT           NULL,
    deleted_at      DATETIME      NULL,

    FOREIGN KEY (id_categoria) REFERENCES Categorias(id_categoria),
    FOREIGN KEY (created_by)   REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by)   REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Stock (
    id_stock      INT           PRIMARY KEY AUTO_INCREMENT,
    id_producto   INT           NOT NULL,
    cantidad      INT           NOT NULL DEFAULT 0,
    stock_minimo  INT           NOT NULL DEFAULT 10,
    precio_compra DECIMAL(12,2) NOT NULL,
    ubicacion     VARCHAR(50),
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by    INT           NOT NULL,
    updated_at    DATETIME      NULL,
    updated_by    INT           NULL,

    FOREIGN KEY (id_producto) REFERENCES Productos(id_producto),
    FOREIGN KEY (created_by)  REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by)  REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Ventas (
    id_venta   INT           PRIMARY KEY AUTO_INCREMENT,
    id_cliente INT           NOT NULL,
    fecha      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total      DECIMAL(12,2) NOT NULL,
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INT           NOT NULL,
    updated_at DATETIME      NULL,
    updated_by INT           NULL,
    deleted_at DATETIME      NULL,

    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    FOREIGN KEY (created_by) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Detalle_Items (
    id_items        INT           PRIMARY KEY AUTO_INCREMENT,
    itemable_id     INT           NOT NULL,
    itemable_type   VARCHAR(20)   NOT NULL,
    id_producto     INT           NOT NULL,
    cantidad        INT           NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(12,2) NOT NULL,
    descuento       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by      INT           NOT NULL,
    updated_at      DATETIME      NULL,
    updated_by      INT           NULL,
    deleted_at      DATETIME      NULL,

    FOREIGN KEY (id_producto) REFERENCES Productos(id_producto),
    FOREIGN KEY (created_by)  REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by)  REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Movimientos (
    id_movimiento        INT         PRIMARY KEY AUTO_INCREMENT,
    id_producto          INT         NOT NULL,
    tipo_movimiento      VARCHAR(50) NOT NULL,
    cantidad             INT         NOT NULL DEFAULT 0,
    cantidad_antes       INT         NULL,
    cantidad_despues     INT         NULL,
    motivo               VARCHAR(200) NULL,
    fecha_hora           DATETIME    NOT NULL,
    referencia_documento INT         NOT NULL,
    created_by           INT         NOT NULL,

    FOREIGN KEY (id_producto) REFERENCES Productos(id_producto),
    FOREIGN KEY (created_by)  REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Facturas (
    id_factura    INT           PRIMARY KEY AUTO_INCREMENT,
    id_cliente    INT           NOT NULL,
    fecha_emision DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal      DECIMAL(12,2) NOT NULL,
    IVA           DECIMAL(12,2) NOT NULL,
    total         DECIMAL(12,2) NOT NULL,
    metodo_pago   VARCHAR(50)   NOT NULL,
    estado        VARCHAR(20)   NOT NULL,
    tipo_factura  VARCHAR(50)   NOT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by    INT           NOT NULL,
    updated_at    DATETIME      NULL,
    updated_by    INT           NULL,
    deleted_at    DATETIME      NULL,

    FOREIGN KEY (id_cliente)  REFERENCES Clientes(id_cliente),
    FOREIGN KEY (created_by)  REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by)  REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Detalles_Factura (
    id_detalle_factura INT           PRIMARY KEY AUTO_INCREMENT,
    id_factura         INT           NOT NULL,
    tipo_referencia    VARCHAR(50)   NOT NULL,
    id_referencia      INT           NOT NULL,
    descripcion        TEXT,
    monto              DECIMAL(12,2) NOT NULL,
    created_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by         INT           NOT NULL,
    updated_at         DATETIME      NULL,
    updated_by         INT           NULL,
    deleted_at         DATETIME      NULL,

    FOREIGN KEY (id_factura)  REFERENCES Facturas(id_factura),
    FOREIGN KEY (created_by)  REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by)  REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Proveedores (
    id_proveedor INT          PRIMARY KEY AUTO_INCREMENT,
    nombre       VARCHAR(120) NOT NULL,
    nit          VARCHAR(30)  NULL,
    email        VARCHAR(120) NULL,
    telefono     VARCHAR(30)  NOT NULL,
    direccion    VARCHAR(200) NULL,
    estado       VARCHAR(20)  NOT NULL DEFAULT 'Activo',
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by   INT          NOT NULL,
    updated_at   DATETIME     NULL,
    updated_by   INT          NULL,
    deleted_at   DATETIME     NULL
);

CREATE TABLE Conversaciones (
    id_conversacion   INT          PRIMARY KEY AUTO_INCREMENT,
    canal             VARCHAR(20)  NOT NULL DEFAULT 'Interno',
    tipo_contacto     VARCHAR(20)  NOT NULL,
    id_contacto       INT          NOT NULL,
    id_par            INT          NOT NULL DEFAULT 0,
    telefono          VARCHAR(30)  NULL,
    titulo            VARCHAR(120) NULL,
    ultimo_mensaje_at DATETIME     NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by        INT          NOT NULL,
    deleted_at        DATETIME     NULL,
    UNIQUE KEY uq_conv_par (canal, tipo_contacto, id_contacto, id_par)
);

CREATE TABLE Mensajes (
    id_mensaje        INT         PRIMARY KEY AUTO_INCREMENT,
    emisor_id         INT         NOT NULL,
    receptor_id       INT         NOT NULL,
    tipo_emisor       VARCHAR(20) NOT NULL,
    contenido         TEXT        NOT NULL,
    estado            VARCHAR(20) NOT NULL DEFAULT 'Enviado',
    fecha_hora        DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_conversacion   INT         NULL,
    canal             VARCHAR(20) NOT NULL DEFAULT 'Interno',
    id_externo        VARCHAR(80) NULL,
    updated_at        DATETIME    NULL,
    deleted_at        DATETIME    NULL
);

CREATE TABLE Adjuntos (
    id_adjunto      INT          PRIMARY KEY AUTO_INCREMENT,
    entidad_tipo    VARCHAR(20)  NOT NULL,
    entidad_id      INT          NOT NULL,
    id_usuario      INT          NOT NULL,
    nombre_original VARCHAR(255),
    nombre_sistema  VARCHAR(255) NOT NULL,
    ruta            TEXT         NOT NULL,
    extension       VARCHAR(10)  NOT NULL,
    peso_bytes      BIGINT       NOT NULL,
    fecha_subida    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at      DATETIME     NULL,

    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Agenda_Disponible (
    id_horario  INT         PRIMARY KEY AUTO_INCREMENT,
    id_usuario  INT         NOT NULL,
    fecha       DATE        NOT NULL,
    hora_inicio TIME        NOT NULL,
    capacidad   INT         NOT NULL DEFAULT 1,
    estado      VARCHAR(20) NOT NULL DEFAULT 'Disponible',
    created_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by  INT         NOT NULL,
    updated_at  DATETIME    NULL,
    updated_by  INT         NULL,

    FOREIGN KEY (id_usuario)  REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (created_by)  REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by)  REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Citas (
    id_cita     INT         PRIMARY KEY AUTO_INCREMENT,
    id_cliente  INT         NOT NULL,
    id_vehiculo INT         NOT NULL,
    id_orden    INT         NULL,
    id_servicio INT         NOT NULL,
    id_horario  INT         NOT NULL,
    motivo      TEXT,
    estado_cita VARCHAR(20) NOT NULL DEFAULT 'Programada',
    created_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by  INT         NOT NULL,
    updated_at  DATETIME    NULL,
    updated_by  INT         NULL,
    deleted_at  DATETIME    NULL,

    FOREIGN KEY (id_cliente)  REFERENCES Clientes(id_cliente),
    FOREIGN KEY (id_vehiculo) REFERENCES Vehiculo(id_vehiculo),
    FOREIGN KEY (id_orden)    REFERENCES Orden_Servicio(id_orden),
    FOREIGN KEY (id_servicio) REFERENCES Servicios(id_servicio),
    FOREIGN KEY (id_horario)  REFERENCES Agenda_Disponible(id_horario),
    FOREIGN KEY (created_by)  REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by)  REFERENCES Usuarios(id_usuario)
);

--  CONTABILIDAD

CREATE TABLE Cuentas (
    id_cuenta            INT           PRIMARY KEY AUTO_INCREMENT,
    nombre               VARCHAR(50)   NOT NULL,
    tipo                 VARCHAR(15)   NOT NULL,
    saldo_actual         DECIMAL(12,2) NOT NULL DEFAULT 0.00,          -- [FIX] DEFAULT(00.00) → DEFAULT 0.00
    estado               VARCHAR(15)   NOT NULL DEFAULT 'Activa',      -- [FIX] DEFAULT('Activa') → DEFAULT 'Activa'
    created_at           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by           INT           NOT NULL,
    updated_at           DATETIME      NULL,
    updated_by           INT           NULL,
    deleted_at           DATETIME      NULL,

    FOREIGN KEY (created_by) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Sesiones_Caja (
    id_caja            INT            PRIMARY KEY AUTO_INCREMENT,
    id_cuenta          INT            NOT NULL,
    id_usuario         INT            NOT NULL,
    fecha_apertura     DATETIME       DEFAULT CURRENT_TIMESTAMP,
    fecha_cierra       DATETIME,
    monto_apertura     DECIMAL(12,2)  NOT NULL,
    monto_sistema      DECIMAL(12,2),
    monto_real         DECIMAL(12,2),
    diferencia         DECIMAL(12,2),
    estado_sesion      VARCHAR(15)    NOT NULL DEFAULT 'Activa',

    FOREIGN KEY (id_cuenta)  REFERENCES Cuentas(id_cuenta),           -- [FIX] FK duplicadas eliminadas
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Conceptos_Financieros (
    id_concepto          INT          PRIMARY KEY AUTO_INCREMENT,
    nombre               VARCHAR(50)  NOT NULL,
    tipo                 VARCHAR(15)  NOT NULL,
    descripcion          VARCHAR(500) NOT NULL,
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by           INT          NOT NULL,
    updated_at           DATETIME     NULL,
    updated_by           INT          NULL,
    deleted_at           DATETIME     NULL,

    FOREIGN KEY (created_by) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Configuracion (
    id_config    INT          PRIMARY KEY AUTO_INCREMENT,
    clave        VARCHAR(80)  NOT NULL UNIQUE,
    valor        TEXT         NOT NULL,
    tipo         VARCHAR(20)  NOT NULL DEFAULT 'string',
    descripcion  VARCHAR(250) NOT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by   INT          NOT NULL,
    updated_at   DATETIME     NULL,
    updated_by   INT          NULL,

    FOREIGN KEY (created_by) REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by) REFERENCES Usuarios(id_usuario)
);

CREATE TABLE Transacciones_Caja (
    id_transaccion        INT             PRIMARY KEY AUTO_INCREMENT,
    id_concepto           INT             NOT NULL,
    id_factura            INT,
    id_cuenta             INT,
    id_movimiento         INT,
    id_caja               INT,
    monto                 DECIMAL(12,2)   NOT NULL,
    tipo                  VARCHAR(15)     NOT NULL,
    fecha                 DATETIME        DEFAULT CURRENT_TIMESTAMP,
    created_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by            INT             NOT NULL,
    updated_at            DATETIME        NULL,
    updated_by            INT             NULL,
    deleted_at            DATETIME        NULL,

    FOREIGN KEY (created_by)     REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (updated_by)     REFERENCES Usuarios(id_usuario),
    FOREIGN KEY (id_concepto)    REFERENCES Conceptos_Financieros(id_concepto),
    FOREIGN KEY (id_factura)     REFERENCES Facturas(id_factura),
    FOREIGN KEY (id_cuenta)      REFERENCES Cuentas(id_cuenta),
    FOREIGN KEY (id_movimiento)  REFERENCES Movimientos(id_movimiento),
    FOREIGN KEY (id_caja)        REFERENCES Sesiones_Caja(id_caja)
);
