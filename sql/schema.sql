-- CIEL Database Schema

CREATE TABLE users (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    google_id   VARCHAR(255)   NOT NULL UNIQUE,
    email       VARCHAR(255)   NOT NULL,
    name        VARCHAR(255)   NOT NULL DEFAULT '',
    balance     DECIMAL(10, 6) NOT NULL DEFAULT 0.000000 COMMENT 'USD -- CHECK (balance >= 0) enforced in application (status.php: AND balance >= cost before deduction)',
    is_active   TINYINT(1)     NOT NULL DEFAULT 1,
    created_at  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE jobs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    runpod_job_id   VARCHAR(255)    DEFAULT NULL,
    endpoint_id     VARCHAR(255)    NOT NULL,
    type            ENUM('image', 'video', 'edit') NOT NULL DEFAULT 'image',
    status          ENUM('pending', 'processing', 'done', 'failed', 'deleted') NOT NULL DEFAULT 'pending',
    params          JSON            NOT NULL COMMENT 'prompt, negative_prompt, width, height, steps, cfg, seed, etc.',
    cost_runpod     DECIMAL(10, 6)  DEFAULT NULL COMMENT 'RunPod actual cost USD',
    cost_user       DECIMAL(10, 6)  DEFAULT NULL COMMENT 'cost_runpod * MARGIN_RATE',
    output_path     VARCHAR(512)    DEFAULT NULL COMMENT 'storage/users/{user_id}/generates/{job_id}.jpg',
    execution_time  INT UNSIGNED    DEFAULT NULL COMMENT 'ms',
    delay_time      INT UNSIGNED    DEFAULT NULL COMMENT 'RunPod delayTime ms (queue + cold start)',
    worker_id       VARCHAR(255)    DEFAULT NULL COMMENT 'RunPod workerId (= podId in Billing API)',
    cost_reconciled TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = reconciled against Billing API',
    created_at      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_status (user_id, status),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchases (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL,
    stripe_session_id   VARCHAR(255)    NOT NULL UNIQUE,
    stripe_payment_id   VARCHAR(255)    DEFAULT NULL,
    amount              DECIMAL(10, 6)  NOT NULL COMMENT 'USD',
    status              ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    created_at          DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE transactions (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    type        ENUM('purchase', 'generation', 'storage') NOT NULL,
    amount      DECIMAL(10, 6)  NOT NULL COMMENT 'positive=credit, negative=debit',
    balance     DECIMAL(10, 6)  NOT NULL COMMENT 'balance after this transaction',
    job_id      BIGINT UNSIGNED DEFAULT NULL,
    purchase_id BIGINT UNSIGNED DEFAULT NULL,
    note        VARCHAR(255)    DEFAULT NULL,
    created_at  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE billing_records (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bucket_time     DATETIME        NOT NULL COMMENT 'Billing bucket start (UTC)',
    bucket_size     VARCHAR(10)     NOT NULL DEFAULT 'hour',
    grouping_type   VARCHAR(20)     NOT NULL COMMENT 'endpointId, gpuTypeId, or podId',
    grouping_value  VARCHAR(255)    NOT NULL COMMENT 'The actual ID/name value',
    amount          DECIMAL(15, 12) NOT NULL COMMENT 'USD',
    time_billed_ms  BIGINT UNSIGNED NOT NULL,
    disk_billed_gb  INT UNSIGNED    NOT NULL DEFAULT 0,
    fetched_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bucket (bucket_time, bucket_size, grouping_type, grouping_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE api_keys (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label          VARCHAR(255)   NOT NULL COMMENT 'Human-readable label',
    provider       VARCHAR(50)    NOT NULL DEFAULT 'runpod',
    encrypted_key  VARBINARY(512) NOT NULL COMMENT 'AES-256-CBC encrypted',
    iv             VARBINARY(16)  NOT NULL COMMENT 'Initialization vector',
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
