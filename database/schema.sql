CREATE DATABASE inventario_equipos;

USE inventario_equipos;

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL
);

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    cargo VARCHAR(100),
    departamento VARCHAR(100)
);

CREATE TABLE equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    auranet VARCHAR(20),
    nombre_equipo VARCHAR(50),
    marca VARCHAR(50),
    modelo VARCHAR(50),
    serie VARCHAR(50),
    procesador VARCHAR(50),
    disco VARCHAR(50),
    ram VARCHAR(20),
    fecha_compra DATE,
    costo DECIMAL(10, 2),
    estado VARCHAR(20),
    categoria_id INT,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

CREATE TABLE asignaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT,
    usuario_id INT,
    fecha_asignacion DATE,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Insertar algunas categorías de ejemplo
INSERT INTO categorias (nombre) VALUES 
('PC de Escritorio'), 
('Laptop'), 
('Celular'), 
('Tablet'), 
('Impresora');