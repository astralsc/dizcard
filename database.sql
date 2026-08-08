CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(32) NOT NULL UNIQUE,
    discriminator CHAR(4) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    date_of_birth DATE DEFAULT NULL,
    token VARCHAR(64) NOT NULL,
    settings JSON DEFAULT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE messages (
    id BIGINT UNSIGNED PRIMARY KEY,
    channel_id BIGINT UNSIGNED NOT NULL,
    author_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    nonce VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    edited_at DATETIME DEFAULT NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (channel_id),
    INDEX (author_id)
);