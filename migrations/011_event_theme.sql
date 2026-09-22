-- 011_event_theme: per-event registration branding (structured fields only).
-- Applied tolerantly via Database::runMigrations: each column is added when
-- missing (duplicate-column errors are ignored), no foreign keys — the
-- production events table is MyISAM.

ALTER TABLE events ADD COLUMN theme_primary CHAR(7) NULL;

ALTER TABLE events ADD COLUMN theme_accent CHAR(7) NULL;

ALTER TABLE events ADD COLUMN welcome_text VARCHAR(180) NULL;

ALTER TABLE events ADD COLUMN logo_path VARCHAR(255) NULL;

ALTER TABLE events ADD COLUMN banner_path VARCHAR(255) NULL;
