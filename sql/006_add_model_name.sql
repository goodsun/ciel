ALTER TABLE jobs ADD COLUMN model_name VARCHAR(255) DEFAULT NULL COMMENT 'Model name returned by handler' AFTER worker_id;
