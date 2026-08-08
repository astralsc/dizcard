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

CREATE TABLE channels (
    id BIGINT UNSIGNED PRIMARY KEY,
    type TINYINT UNSIGNED NOT NULL DEFAULT 1,
    user_id_1 INT UNSIGNED NOT NULL,
    user_id_2 INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id_1) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id_2) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dm (user_id_1, user_id_2),
    INDEX (user_id_1),
    INDEX (user_id_2)
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

CREATE TABLE group_channels (
    id BIGINT UNSIGNED PRIMARY KEY,
    type TINYINT UNSIGNED NOT NULL DEFAULT 3,
    owner_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) DEFAULT NULL,
    icon VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (owner_id)
);

CREATE TABLE group_channel_recipients (
    channel_id BIGINT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (channel_id, user_id),
    FOREIGN KEY (channel_id) REFERENCES group_channels(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);