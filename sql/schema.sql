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
