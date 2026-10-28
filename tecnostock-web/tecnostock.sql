-- =====================================================
-- BASE DE DATOS: TECNOSTOCK
-- Sistema de inventario — Caso 1
-- Para importar en XAMPP (phpMyAdmin) con MySQL/MariaDB
-- =====================================================

CREATE DATABASE IF NOT EXISTS tecnostock
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE tecnostock;

-- =====================================================
-- TABLA: usuarios
-- =====================================================
CREATE TABLE usuarios (
    id_usuario  INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80) NOT NULL,
    correo      VARCHAR(120) NOT NULL UNIQUE,
    clave       VARCHAR(255) NOT NULL,   -- hash (password_hash de PHP)
    estado      ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'
);

-- =====================================================
-- TABLA: categorias
-- =====================================================
CREATE TABLE categorias (
    id_categoria  INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(60) NOT NULL,
    descripcion   VARCHAR(150),
    estado        ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'
);

-- =====================================================
-- TABLA: productos
-- =====================================================
CREATE TABLE productos (
    id_producto     INT AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(20) NOT NULL UNIQUE,      -- código no se repite
    nombre          VARCHAR(120) NOT NULL,
    descripcion     VARCHAR(255),
    precio          DECIMAL(10,0) NOT NULL CHECK (precio >= 0),
    stock_actual    INT NOT NULL DEFAULT 0 CHECK (stock_actual >= 0),
    stock_minimo    INT NOT NULL DEFAULT 0 CHECK (stock_minimo >= 0),
    estado          ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    id_categoria    INT NOT NULL,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
);

-- =====================================================
-- TABLA: movimientos (entradas / salidas de inventario)
-- =====================================================
CREATE TABLE movimientos (
    id_movimiento   INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo            ENUM('entrada','salida') NOT NULL,
    cantidad        INT NOT NULL CHECK (cantidad > 0),
    observacion     VARCHAR(200),
    id_producto     INT NOT NULL,
    id_usuario      INT NOT NULL,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- =====================================================
-- DATOS: usuario administrador semilla
-- correo: admin@tecnostock.cl
-- clave : admin123   (ya viene hasheada con bcrypt)
-- =====================================================
INSERT INTO usuarios (nombre, correo, clave, estado) VALUES
('Encargado de Inventario', 'admin@tecnostock.cl', '$2b$12$RpN7x8BmXbrLso7stVMalu4FL9.iryw2Pxx/BSyOYYHAy2Zjp07rC', 'activo');

-- =====================================================
-- DATOS: categorias
-- =====================================================
INSERT INTO categorias (nombre, descripcion, estado) VALUES
('Audio',                'Audífonos, parlantes y accesorios de sonido',         'activo'),
('Carga y Cables',       'Cargadores, cables y baterías portátiles',            'activo'),
('Almacenamiento',       'Pendrives, discos duros y tarjetas de memoria',       'activo'),
('Periféricos',          'Mouse, teclados y accesorios de computador',          'activo'),
('Fundas y Protección',  'Fundas, cases y protectores de pantalla',             'activo');

-- =====================================================
-- DATOS: productos (accesorios tecnológicos)
-- =====================================================
INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria) VALUES
('AUD-001', 'Audífonos Bluetooth JBL Tune 510BT',   'Audífonos over-ear inalámbricos, hasta 40h de batería', 19990, 35, 8,  'activo', 1),
('AUD-002', 'Parlante portátil JBL Go 3',           'Parlante Bluetooth resistente al agua',                 29990, 20, 5,  'activo', 1),
('AUD-003', 'Audífonos in-ear con cable',           'Audífonos alámbricos 3.5mm, micrófono integrado',        6990, 60, 15, 'activo', 1),
('CAR-001', 'Cargador rápido 20W USB-C',            'Cargador de pared, carga rápida compatible con iPhone/Android', 9990, 50, 10, 'activo', 2),
('CAR-002', 'Cable USB-C a USB-C 1m',               'Cable trenzado de carga y datos',                        4990, 80, 20, 'activo', 2),
('CAR-003', 'Power bank 10.000 mAh',                'Batería portátil con doble puerto USB',                 14990, 25, 6,  'activo', 2),
('ALM-001', 'Pendrive USB 3.0 64GB',                'Memoria USB de alta velocidad',                          6990, 45, 10, 'activo', 3),
('ALM-002', 'Disco duro externo 1TB',               'Disco portátil USB 3.0',                                39990, 12, 4,  'activo', 3),
('ALM-003', 'Tarjeta microSD 128GB',                'Tarjeta de memoria clase 10 con adaptador',              9990, 30, 8,  'activo', 3),
('PER-001', 'Mouse inalámbrico',                    'Mouse óptico con receptor USB, 3 botones',               8990, 40, 10, 'activo', 4),
('PER-002', 'Teclado mecánico gamer',               'Teclado retroiluminado RGB, switches azules',           24990, 15, 5,  'activo', 4),
('PER-003', 'Hub USB-C 6 en 1',                     'Hub con HDMI, USB 3.0 y lector de tarjetas',            17990, 18, 5,  'activo', 4),
('FUN-001', 'Funda para notebook 14"',              'Funda acolchada resistente al agua',                    12990, 22, 6,  'activo', 5),
('FUN-002', 'Protector de pantalla para celular',   'Vidrio templado 9H, instalación fácil',                  2990, 90, 20, 'activo', 5);

-- =====================================================
-- DATOS: movimientos de ejemplo (kardex inicial)
-- =====================================================
INSERT INTO movimientos (fecha_hora, tipo, cantidad, observacion, id_producto, id_usuario) VALUES
('2026-08-15 09:30:00', 'entrada', 50, 'Compra inicial a proveedor',        1, 1),
('2026-08-20 14:10:00', 'salida',  15, 'Venta mostrador',                    1, 1),
('2026-08-18 10:00:00', 'entrada', 30, 'Reposición de stock',                7, 1),
('2026-08-22 16:45:00', 'salida',  18, 'Venta por catálogo online',          7, 1);

-- =====================================================
-- CONSULTAS ÚTILES DE EJEMPLO
-- =====================================================

-- Productos con stock bajo el mínimo
-- SELECT codigo, nombre, stock_actual, stock_minimo FROM productos WHERE stock_actual <= stock_minimo AND estado='activo';

-- Kardex de un producto
-- SELECT * FROM movimientos WHERE id_producto = 1 ORDER BY fecha_hora DESC;
