-- 013_event_coa: per-event Certificate of Appearance settings.
-- coa_enabled 1 turns on auto-generate + auto-send after a signed scan.
-- coa_particulars is rendered one line per table row ("Label - Value");
-- when NULL the service applies the restore defaults.
-- coa_logo_path / coa_signatory_path are server-side image paths
-- (uploads placed under storage/ by the operator or branding tooling).

ALTER TABLE events ADD COLUMN coa_enabled TINYINT(1) NULL;

ALTER TABLE events ADD COLUMN coa_venue VARCHAR(255) NULL;

ALTER TABLE events ADD COLUMN coa_purpose VARCHAR(255) NULL;

ALTER TABLE events ADD COLUMN coa_particulars TEXT NULL;

ALTER TABLE events ADD COLUMN coa_signatory_name VARCHAR(120) NULL;

ALTER TABLE events ADD COLUMN coa_signatory_title VARCHAR(120) NULL;

ALTER TABLE events ADD COLUMN coa_signatory_path VARCHAR(255) NULL;

ALTER TABLE events ADD COLUMN coa_logo_path VARCHAR(255) NULL;
