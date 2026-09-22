-- 014_coa_monitor: send monitor, batches, and reusable templates for the
-- Certificate of Appearance flow. MyISAM-safe: no foreign keys. Tables are
-- created only when missing (tolerant execution, like the other migrations).

CREATE TABLE IF NOT EXISTS coa_batches (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id INT NOT NULL,
  created_at DATETIME NULL,
  inclusive_date VARCHAR(32) NULL,
  signatory_name VARCHAR(120) NULL,
  venue_snapshot VARCHAR(255) NULL,
  event_name_snapshot VARCHAR(255) NULL,
  source VARCHAR(16) NULL,
  PRIMARY KEY (id),
  KEY idx_coa_batches_event (event_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS coa_sends (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  batch_id INT UNSIGNED NULL,
  event_id INT NOT NULL,
  participant_id INT NOT NULL,
  attendance_date VARCHAR(32) NULL,
  email VARCHAR(255) NULL,
  status VARCHAR(16) NULL,
  error VARCHAR(255) NULL,
  pdf_path VARCHAR(255) NULL,
  event_name_snapshot VARCHAR(255) NULL,
  venue_snapshot VARCHAR(255) NULL,
  signatory_snapshot VARCHAR(120) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_coa_sends_event (event_id),
  KEY idx_coa_sends_batch (batch_id),
  KEY idx_coa_sends_status (status)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS coa_templates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  venue VARCHAR(255) NULL,
  purpose VARCHAR(255) NULL,
  particulars TEXT NULL,
  signatory_name VARCHAR(120) NULL,
  signatory_title VARCHAR(120) NULL,
  signatory_path VARCHAR(255) NULL,
  logo_path VARCHAR(255) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
