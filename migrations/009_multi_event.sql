-- 009_multi_event: multi-event platform (All Father).
-- Events get slug/status/schedule; participants become event-owned;
-- event_assignments scopes staff per event; action_logs tagged per event.

ALTER TABLE events
  ADD COLUMN slug VARCHAR(190) NULL AFTER name,
  ADD COLUMN status ENUM('draft','open','closed') NOT NULL DEFAULT 'open' AFTER active,
  ADD COLUMN starts_at DATETIME NULL AFTER status,
  ADD COLUMN ends_at DATETIME NULL AFTER starts_at;

UPDATE events SET slug = CONCAT('event-', id) WHERE slug IS NULL OR slug = '';

UPDATE events SET status = IF(active = 1, 'open', 'draft');

ALTER TABLE events ADD UNIQUE KEY uq_events_slug (slug);

ALTER TABLE participants
  ADD COLUMN event_id INT NULL AFTER id,
  ADD INDEX idx_participants_event (event_id);

UPDATE participants SET event_id = (SELECT id FROM events ORDER BY active DESC, id ASC LIMIT 1) WHERE event_id IS NULL;

ALTER TABLE participants DROP INDEX uq_email;

ALTER TABLE participants ADD UNIQUE KEY uq_participants_event_email (event_id, email);

ALTER TABLE participants ADD CONSTRAINT fk_participants_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE;

ALTER TABLE attendance ADD INDEX idx_attendance_event (event_id);

UPDATE attendance SET event_id = (SELECT id FROM events ORDER BY active DESC, id ASC LIMIT 1) WHERE event_id IS NULL;

ALTER TABLE attendance ADD CONSTRAINT fk_attendance_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS event_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  event_id INT NOT NULL,
  role ENUM('event_admin','checker','seo_viewer') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_assignment (admin_id, event_id),
  INDEX idx_assignment_event (event_id),
  CONSTRAINT fk_assignment_admin FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE,
  CONSTRAINT fk_assignment_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
);

INSERT IGNORE INTO event_assignments (admin_id, event_id, role)
SELECT a.id, e.id, CASE WHEN a.role = 'seo_viewer' THEN 'seo_viewer' ELSE 'checker' END
FROM admins a
CROSS JOIN (SELECT id FROM events ORDER BY active DESC, id ASC LIMIT 1) e
WHERE a.role IN ('checker', 'seo_viewer') AND a.is_active = 1;

ALTER TABLE action_logs
  ADD COLUMN event_id INT NULL AFTER admin_id,
  ADD INDEX idx_action_logs_event (event_id);
