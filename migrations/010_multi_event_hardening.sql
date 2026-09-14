-- 010_multi_event_hardening: post-backfill hardening for the multi-event platform.
-- Applied conditionally by Database::migrate() (see 009 for the tolerant pattern):
--  * participants.event_id NOT NULL is applied only when no NULLs remain.
--  * The attendance unique key is applied only when every event enforces
--    single time-in (otherwise the app-level check remains the rule).

ALTER TABLE participants MODIFY event_id INT NOT NULL;

ALTER TABLE attendance ADD UNIQUE KEY uq_attendance_participant_event_date (participant_id, event_id, attendance_date);
