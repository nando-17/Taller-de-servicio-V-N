/* ===========================================================
   SISTEMA: Taller de Servicio Técnico
   MOTOR  : MySQL 5.7+ (InnoDB) | UTF8MB4
   NOTA   : Ejecutar con un usuario que tenga permisos CREATE
   =========================================================== */

-- 1) Crear Base de Datos (cambia el nombre si quieres)
CREATE DATABASE IF NOT EXISTS taller_servicio
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE taller_servicio;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

/* ===========================================================
   2) Catálogos y tablas maestras
   =========================================================== */

-- Usuarios del sistema (operadores, admin, técnicos que además usan el sistema)
CREATE TABLE usuarios (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre          VARCHAR(120) NOT NULL,
  email           VARCHAR(160) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  rol             ENUM('ADMIN','OPERADOR','TECNICO') NOT NULL DEFAULT 'OPERADOR',
  activo          TINYINT(1) NOT NULL DEFAULT 1,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Técnicos (catálogo de personal técnico)
CREATE TABLE tecnicos (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombres         VARCHAR(120) NOT NULL,
  apellidos       VARCHAR(120) NOT NULL,
  documento       VARCHAR(30)  NULL,
  telefono        VARCHAR(40)  NULL,
  email           VARCHAR(160) NULL,
  especialidad    VARCHAR(120) NULL,
  activo          TINYINT(1) NOT NULL DEFAULT 1,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Clientes
CREATE TABLE clientes (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  tipo            ENUM('NATURAL','JURIDICA') NOT NULL DEFAULT 'NATURAL',
  nombre_razon    VARCHAR(160) NOT NULL,
  documento       VARCHAR(30)  NULL,
  email           VARCHAR(160) NULL,
  telefono        VARCHAR(40)  NULL,
  direccion       VARCHAR(200) NULL,
  observaciones   VARCHAR(255) NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_clientes_documento (documento),
  KEY idx_clientes_nombre (nombre_razon)
) ENGINE=InnoDB;

-- Tipos de equipo (ej. Laptop, Smartphone, PC, Impresora)
CREATE TABLE tipos_equipo (
  id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre       VARCHAR(80) NOT NULL UNIQUE,
  descripcion  VARCHAR(200) NULL
) ENGINE=InnoDB;

-- Marcas genéricas
CREATE TABLE marcas (
  id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre       VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Modelos por marca
CREATE TABLE modelos (
  id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  marca_id     INT UNSIGNED NOT NULL,
  nombre       VARCHAR(120) NOT NULL,
  UNIQUE KEY uk_modelo (marca_id, nombre),
  CONSTRAINT fk_modelos_marca
    FOREIGN KEY (marca_id) REFERENCES marcas(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Equipos registrados (inventario histórico por cliente)
CREATE TABLE equipos (
  id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  cliente_id       BIGINT UNSIGNED NOT NULL,
  tipo_equipo_id   INT UNSIGNED NOT NULL,
  marca_id         INT UNSIGNED NULL,
  modelo_id        INT UNSIGNED NULL,
  numero_serie     VARCHAR(120) NULL,
  imei             VARCHAR(60)  NULL,
  color            VARCHAR(60)  NULL,
  descripcion      VARCHAR(255) NULL,
  accesorios_base  VARCHAR(255) NULL,
  fecha_registro   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_equipos_cliente
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_equipos_tipo
    FOREIGN KEY (tipo_equipo_id) REFERENCES tipos_equipo(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_equipos_marca
    FOREIGN KEY (marca_id) REFERENCES marcas(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_equipos_modelo
    FOREIGN KEY (modelo_id) REFERENCES modelos(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  KEY idx_equipos_cliente (cliente_id),
  KEY idx_equipos_serie (numero_serie),
  KEY idx_equipos_imei (imei)
) ENGINE=InnoDB;

-- Prioridades para órdenes
CREATE TABLE prioridades (
  id          TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre      VARCHAR(40) NOT NULL UNIQUE,
  sla_horas   INT UNSIGNED NULL
) ENGINE=InnoDB;

-- Estados de la orden de servicio (flujo)
CREATE TABLE estados_orden (
  id        TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  codigo    VARCHAR(30) NOT NULL UNIQUE,
  nombre    VARCHAR(60) NOT NULL,
  orden     TINYINT UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Catálogo de servicios del taller (mano de obra)
CREATE TABLE servicios_catalogo (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre         VARCHAR(160) NOT NULL,
  descripcion    VARCHAR(255) NULL,
  precio_base    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  activo         TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uk_servicio_nombre (nombre)
) ENGINE=InnoDB;

-- Repuestos / Partes
CREATE TABLE repuestos (
  id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  codigo           VARCHAR(80)  NULL,
  nombre           VARCHAR(160) NOT NULL,
  marca_id         INT UNSIGNED NULL,
  modelo_id        INT UNSIGNED NULL,
  unidad           VARCHAR(30)  NOT NULL DEFAULT 'UND',
  precio_costo     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  precio_venta     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock_minimo     INT NOT NULL DEFAULT 0,
  activo           TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uk_repuesto_codigo (codigo),
  KEY idx_repuesto_nombre (nombre),
  CONSTRAINT fk_repuestos_marca
    FOREIGN KEY (marca_id) REFERENCES marcas(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_repuestos_modelo
    FOREIGN KEY (modelo_id) REFERENCES modelos(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Existencias (stock global simple)
CREATE TABLE repuesto_existencias (
  repuesto_id   BIGINT UNSIGNED PRIMARY KEY,
  cantidad      INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_existencias_repuesto
    FOREIGN KEY (repuesto_id) REFERENCES repuestos(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

/* ===========================================================
   3) Órdenes de Servicio y detalle (crear OS antes del kardex)
   =========================================================== */

-- Orden de Servicio (documento principal)
CREATE TABLE ordenes_servicio (
  id                       BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  codigo                   VARCHAR(30) NOT NULL UNIQUE,
  cliente_id               BIGINT UNSIGNED NOT NULL,
  equipo_id                BIGINT UNSIGNED NOT NULL,
  estado_id                TINYINT UNSIGNED NOT NULL,
  prioridad_id             TINYINT UNSIGNED NOT NULL,
  tecnico_asignado_id      BIGINT UNSIGNED NULL,
  fecha_recepcion          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_estimada_entrega   DATETIME NULL,
  fecha_entrega_real       DATETIME NULL,
  falla_reportada          VARCHAR(255) NULL,
  diagnostico_preliminar   VARCHAR(255) NULL,
  ubicacion                VARCHAR(100) NULL,
  garantia                 TINYINT(1) NOT NULL DEFAULT 0,
  password_bloqueo         VARCHAR(80)  NULL,
  accesorios_recibidos     VARCHAR(255) NULL,
  costo_estimado           DECIMAL(10,2) NULL,
  notas                    TEXT NULL,
  created_by               BIGINT UNSIGNED NULL,
  updated_by               BIGINT UNSIGNED NULL,
  created_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_os_cliente
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_os_equipo
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_os_estado
    FOREIGN KEY (estado_id) REFERENCES estados_orden(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_os_prioridad
    FOREIGN KEY (prioridad_id) REFERENCES prioridades(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_os_tecnico
    FOREIGN KEY (tecnico_asignado_id) REFERENCES tecnicos(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_os_created_by
    FOREIGN KEY (created_by) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_os_updated_by
    FOREIGN KEY (updated_by) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  KEY idx_os_codigo (codigo),
  KEY idx_os_estado (estado_id),
  KEY idx_os_cliente (cliente_id),
  KEY idx_os_equipo (equipo_id)
) ENGINE=InnoDB;

-- Movimientos de inventario (kardex)  ← ahora sí, la FK a ordenes_servicio existe
CREATE TABLE movimientos_inventario (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  repuesto_id     BIGINT UNSIGNED NOT NULL,
  tipo            ENUM('INGRESO','SALIDA','AJUSTE') NOT NULL,
  cantidad        INT NOT NULL,
  costo_unitario  DECIMAL(10,2) NULL,
  motivo          VARCHAR(160) NULL,
  orden_id        BIGINT UNSIGNED NULL,
  fecha_mov       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  usuario_id      BIGINT UNSIGNED NULL,
  CONSTRAINT fk_mov_rep
    FOREIGN KEY (repuesto_id) REFERENCES repuestos(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mov_orden
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_mov_user
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  KEY idx_mov_repuesto (repuesto_id),
  KEY idx_mov_fecha (fecha_mov),
  KEY idx_mov_orden (orden_id)
) ENGINE=InnoDB;

-- Historial de estado de la orden (trazabilidad)
CREATE TABLE orden_estado_historial (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  orden_id      BIGINT UNSIGNED NOT NULL,
  estado_id     TINYINT UNSIGNED NOT NULL,
  usuario_id    BIGINT UNSIGNED NULL,
  comentario    VARCHAR(255) NULL,
  fecha_cambio  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_osh_orden
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_osh_estado
    FOREIGN KEY (estado_id) REFERENCES estados_orden(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_osh_user
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  KEY idx_osh_orden (orden_id),
  KEY idx_osh_estado (estado_id)
) ENGINE=InnoDB;

-- Diagnósticos y trabajos (bitácora técnica)
CREATE TABLE diagnosticos (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  orden_id      BIGINT UNSIGNED NOT NULL,
  tecnico_id    BIGINT UNSIGNED NULL,
  descripcion   TEXT NOT NULL,
  fecha         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_diag_orden
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_diag_tecnico
    FOREIGN KEY (tecnico_id) REFERENCES tecnicos(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  KEY idx_diag_orden (orden_id)
) ENGINE=InnoDB;

-- Detalle de servicios (mano de obra aplicada a la orden)
CREATE TABLE orden_servicio_items (
  id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  orden_id          BIGINT UNSIGNED NOT NULL,
  servicio_id       BIGINT UNSIGNED NOT NULL,
  descripcion       VARCHAR(255) NULL,
  cantidad          DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  precio_unitario   DECIMAL(10,2) NOT NULL,
  total_linea       DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_osi_orden
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_osi_servicio
    FOREIGN KEY (servicio_id) REFERENCES servicios_catalogo(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  KEY idx_osi_orden (orden_id)
) ENGINE=InnoDB;

-- Detalle de repuestos consumidos por la orden
CREATE TABLE orden_repuesto_items (
  id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  orden_id          BIGINT UNSIGNED NOT NULL,
  repuesto_id       BIGINT UNSIGNED NOT NULL,
  cantidad          DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  precio_unitario   DECIMAL(10,2) NOT NULL,
  total_linea       DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_ori_orden
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ori_rep
    FOREIGN KEY (repuesto_id) REFERENCES repuestos(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  KEY idx_ori_orden (orden_id),
  KEY idx_ori_rep (repuesto_id)
) ENGINE=InnoDB;

-- Citas / Agenda (para recepción/diagnóstico/entrega)
CREATE TABLE citas (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  cliente_id      BIGINT UNSIGNED NOT NULL,
  equipo_id       BIGINT UNSIGNED NULL,
  tecnico_id      BIGINT UNSIGNED NULL,
  fecha_inicio    DATETIME NOT NULL,
  fecha_fin       DATETIME NOT NULL,
  estado          ENUM('PENDIENTE','CONFIRMADA','ATENDIDA','CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
  notas           VARCHAR(255) NULL,
  created_by      BIGINT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_cita_cliente
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_cita_equipo
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_cita_tecnico
    FOREIGN KEY (tecnico_id) REFERENCES tecnicos(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_cita_user
    FOREIGN KEY (created_by) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  KEY idx_cita_inicio (fecha_inicio),
  KEY idx_cita_estado (estado)
) ENGINE=InnoDB;

/* ===========================================================
   4) Facturación y pagos
   =========================================================== */

-- Comprobantes (facturas/boletas) asociados a una orden
CREATE TABLE facturas (
  id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  orden_id         BIGINT UNSIGNED NOT NULL,
  cliente_id       BIGINT UNSIGNED NOT NULL,
  tipo             ENUM('FACTURA','BOLETA','RECIBO') NOT NULL DEFAULT 'RECIBO',
  serie            VARCHAR(10)  NULL,
  numero           VARCHAR(30)  NULL,
  moneda           ENUM('PEN','USD') NOT NULL DEFAULT 'PEN',
  fecha_emision    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  subtotal         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  impuesto         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  estado           ENUM('EMITIDA','ANULADA','PAGADA','PENDIENTE') NOT NULL DEFAULT 'EMITIDA',
  observaciones    VARCHAR(255) NULL,
  UNIQUE KEY uk_comp_serie_numero (tipo, serie, numero),
  CONSTRAINT fk_fac_orden
    FOREIGN KEY (orden_id) REFERENCES ordenes_servicio(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_fac_cliente
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  KEY idx_fac_fecha (fecha_emision)
) ENGINE=InnoDB;

-- Ítems de la factura
CREATE TABLE factura_items (
  id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  factura_id        BIGINT UNSIGNED NOT NULL,
  tipo_item         ENUM('SERVICIO','REPUESTO') NOT NULL,
  referencia_id     BIGINT UNSIGNED NULL,
  descripcion       VARCHAR(255) NOT NULL,
  cantidad          DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  precio_unitario   DECIMAL(10,2) NOT NULL,
  total_linea       DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_fi_factura
    FOREIGN KEY (factura_id) REFERENCES facturas(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  KEY idx_fi_factura (factura_id),
  KEY idx_fi_tipo (tipo_item)
) ENGINE=InnoDB;

-- Pagos de la factura
CREATE TABLE pagos (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  factura_id     BIGINT UNSIGNED NOT NULL,
  fecha_pago     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  monto          DECIMAL(10,2) NOT NULL,
  metodo_pago    ENUM('EFECTIVO','TARJETA','TRANSFERENCIA','YAPE','PLIN','OTRO') NOT NULL DEFAULT 'EFECTIVO',
  referencia     VARCHAR(120) NULL,
  estado         ENUM('APLICADO','ANULADO','PENDIENTE') NOT NULL DEFAULT 'APLICADO',
  usuario_id     BIGINT UNSIGNED NULL,
  CONSTRAINT fk_pago_factura
    FOREIGN KEY (factura_id) REFERENCES facturas(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_pago_user
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  KEY idx_pago_factura (factura_id),
  KEY idx_pago_fecha (fecha_pago)
) ENGINE=InnoDB;

/* ===========================================================
   5) Datos iniciales de catálogos
   =========================================================== */

INSERT INTO prioridades (nombre, sla_horas) VALUES
  ('BAJA',  168),
  ('MEDIA', 72),
  ('ALTA',  24),
  ('URGENTE', 8)
ON DUPLICATE KEY UPDATE sla_horas=VALUES(sla_horas);

INSERT INTO estados_orden (codigo, nombre, orden) VALUES
  ('RECIBIDO',         'Recibido',               1),
  ('DIAGNOSTICO',      'En diagnóstico',         2),
  ('REPARACION',       'En reparación',          3),
  ('ESPERA_REPUESTO',  'En espera de repuesto',  4),
  ('LISTO',            'Listo para entrega',     5),
  ('ENTREGADO',        'Entregado',              6),
  ('ANULADO',          'Anulado',               99)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), orden=VALUES(orden);

-- Ejemplos básicos (opcional)
INSERT INTO tipos_equipo (nombre) VALUES ('Laptop'), ('Smartphone'), ('PC'), ('Impresora')
ON DUPLICATE KEY UPDATE descripcion=descripcion;

-- Usuario admin por defecto (cambia el hash luego)
INSERT INTO usuarios (nombre, email, password_hash, rol)
VALUES ('Administrador', 'admin@taller.test', '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'ADMIN')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

SET FOREIGN_KEY_CHECKS = 1;

/* ===========================================================
   FIN DEL SCRIPT
   =========================================================== */
