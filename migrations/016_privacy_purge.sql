-- 016_privacy_purge: consent columns, event purge markers, jobs and notices.
-- MyISAM-safe: no foreign keys.

CREATE TABLE IF NOT EXISTS privacy_jobs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id INT NOT NULL,
  status VARCHAR(32) NULL,
  participant_count INT NOT NULL DEFAULT 0,
  mailed INT NOT NULL DEFAULT 0,
  skipped_no_email INT NOT NULL DEFAULT 0,
  backup_path VARCHAR(255) NULL,
  drive_id VARCHAR(128) NULL,
  drive_url VARCHAR(512) NULL,
  error VARCHAR(255) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_privacy_jobs_event (event_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS privacy_notices (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id INT UNSIGNED NULL,
  event_id INT NOT NULL,
  kind VARCHAR(16) NULL,
  to_email VARCHAR(191) NULL,
  first_name VARCHAR(100) NULL,
  event_name VARCHAR(255) NULL,
  status VARCHAR(16) NULL,
  error VARCHAR(255) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_privacy_notices_job (job_id),
  KEY idx_privacy_notices_status (status)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
