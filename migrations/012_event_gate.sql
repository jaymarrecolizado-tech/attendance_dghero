-- 012_event_gate: optional door-gate layout for an event's registration page.
-- theme_layout: NULL/'default' = standard page; 'gate' = door entrance.
-- The UPDATE renames the Hack for Gov 5 event and enables the gate on it only.
-- Slug ids differ per environment (hack4gov-5-8 on production, hack4gov-5-45
-- locally), so the row is matched on the slug prefix.

ALTER TABLE events ADD COLUMN theme_layout VARCHAR(16) NULL;

UPDATE events
SET name = 'Hack for Gov 5',
    theme_layout = 'gate',
    theme_primary = '#0B1B45',
    theme_accent = '#8A6D00'
WHERE slug LIKE 'hack4gov-5-%';
