-- Migration: Add delay_time and cost reconciliation support
ALTER TABLE jobs
  ADD COLUMN delay_time INT UNSIGNED DEFAULT NULL COMMENT 'RunPod delayTime ms (queue + cold start)' AFTER execution_time,
  ADD COLUMN cost_reconciled TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = reconciled against Billing API' AFTER delay_time;
