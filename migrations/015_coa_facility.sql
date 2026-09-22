-- 015_coa_facility: signatory library with uploaded e-signatures, template
-- and event signatory references, and scheduled sends (Plan#12).
-- MyISAM-safe: no foreign keys. Columns and tables are added only when
-- missing (tolerant execution).

CREATE TABLE IF NOT EXISTS coa_signatories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  title VARCHAR(120) NULL,
  signature_path VARCHAR(255) NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

ALTER TABLE coa_templates ADD COLUMN signatory_id INT UNSIGNED NULL;

ALTER TABLE events ADD COLUMN coa_signatory_id INT UNSIGNED NULL;

ALTER TABLE coa_sends ADD COLUMN send_at DATETIME NULL;

ALTER TABLE coa_batches ADD COLUMN template_id INT UNSIGNED NULL;
