CREATE TABLE clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    apellido VARCHAR(80) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE servicios (
    id_servicio INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    precio DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE solicitudes (
    id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    fecha_evento DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    tipo_evento VARCHAR(80) NOT NULL,
    lugar VARCHAR(150) NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    direccion VARCHAR(200) NOT NULL,
    estado ENUM('pendiente','confirmada','rechazada','cancelada','realizada') NOT NULL DEFAULT 'pendiente',
    observaciones VARCHAR(1000) NOT NULL DEFAULT '',
    whatsapp_estado ENUM('sin_enviar','enviando','enviado','error','no_configurado') NOT NULL DEFAULT 'sin_enviar',
    whatsapp_id VARCHAR(255) DEFAULT NULL,
    creada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente),
    INDEX agenda (fecha_evento, estado, hora_inicio, hora_fin),
    CHECK (hora_fin > hora_inicio)
) ENGINE=InnoDB;

CREATE TABLE detalle_solicitud (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud INT NOT NULL,
    id_servicio INT NOT NULL,
    FOREIGN KEY (id_solicitud) REFERENCES solicitudes(id_solicitud),
    FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio),
    UNIQUE (id_solicitud, id_servicio)
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    usuario VARCHAR(80) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

INSERT INTO servicios (nombre, descripcion, precio) VALUES
('Fotografía', 'Cobertura fotográfica y entrega digital.', 120000),
('Video', 'Filmación del evento y video editado.', 150000),
('Fotografía y video', 'Cobertura completa del evento.', 240000);
