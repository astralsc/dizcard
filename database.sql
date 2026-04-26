CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(32)  NOT NULL UNIQUE,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    date_of_birth DATE         NULL DEFAULT NULL,
    token         VARCHAR(64)  NOT NULL,
    settings      JSON         DEFAULT NULL,
    created_at    DATETIME     NOT NULL
);