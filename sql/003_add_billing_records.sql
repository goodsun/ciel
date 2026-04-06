-- Migration: Add billing_records table
-- Stores raw RunPod Billing API responses across all grouping types.

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
