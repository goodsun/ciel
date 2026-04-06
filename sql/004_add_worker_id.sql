-- Migration: Add worker_id to jobs for per-pod billing attribution
ALTER TABLE jobs
  ADD COLUMN worker_id VARCHAR(255) DEFAULT NULL COMMENT 'RunPod workerId (= podId in Billing API)' AFTER delay_time;
