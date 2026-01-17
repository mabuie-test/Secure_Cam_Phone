<?php

$dsn = "pgsql:host=postgresql://vigia:fphibofQmjlp7RVfCeiRAgoZ4CNuWYjV@dpg-d5lnco6mcj7s73bhnlu0-a.oregon-postgres.render.com/vigia_agnc;port=5432;dbname=vigia_agnc";
$user = "vigia";
$password = "fphibofQmjlp7RVfCeiRAgoZ4CNuWYjV";

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS devices (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id INTEGER NOT NULL,
    device_token VARCHAR(128) NOT NULL UNIQUE,
    device_name VARCHAR(255),
    last_seen TIMESTAMP,
    status VARCHAR(32) DEFAULT 'active',
    CONSTRAINT fk_devices_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS frames_meta (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    device_id INTEGER NOT NULL,
    filename TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_frames_device
        FOREIGN KEY (device_id)
        REFERENCES devices(id)
        ON DELETE CASCADE
);
SQL;

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo->exec($sql);
    echo "Schema criado com sucesso";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
