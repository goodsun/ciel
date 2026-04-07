-- Move POD_IDS and API keys from .env to DB

CREATE TABLE api_keys (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label          VARCHAR(255)   NOT NULL COMMENT 'Human-readable label',
    provider       VARCHAR(50)    NOT NULL DEFAULT 'runpod',
    encrypted_key  VARBINARY(512) NOT NULL COMMENT 'AES-256-GCM encrypted',
    iv             VARBINARY(16)  NOT NULL COMMENT 'Initialization vector (12 bytes for GCM)',
    tag            VARBINARY(16)  NOT NULL DEFAULT '' COMMENT 'GCM authentication tag (16 bytes)',
    is_active      TINYINT(1)     NOT NULL DEFAULT 1,
    created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider_active (provider, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE endpoints (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    endpoint_id  VARCHAR(255)   NOT NULL COMMENT 'RunPod endpoint ID',
    api_key_id   BIGINT UNSIGNED DEFAULT NULL,
    type         ENUM('image', 'video', 'edit') NOT NULL,
    name         VARCHAR(255)   NOT NULL DEFAULT '',
    steps        INT UNSIGNED   NOT NULL DEFAULT 25,
    cfg          DECIMAL(4,1)   NOT NULL DEFAULT 7.0,
    hint         VARCHAR(1024)  NOT NULL DEFAULT '',
    sort_order   INT UNSIGNED   NOT NULL DEFAULT 0,
    is_active    TINYINT(1)     NOT NULL DEFAULT 1,
    created_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_endpoint (endpoint_id),
    INDEX idx_type_active (type, is_active, sort_order),
    FOREIGN KEY fk_api_key (api_key_id) REFERENCES api_keys(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API keys are inserted via storeApiKey() in src/crypto.php (encrypted at rest).
-- After inserting, update endpoints.api_key_id to link them.
