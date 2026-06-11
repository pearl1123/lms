-- Rename Hybrid modality label to HyFlex in library table (idempotent).
-- UI also maps hybrid→HyFlex via etd_modality_display_label() when DB value unchanged.

UPDATE lib_course_modality
SET modality_desc = 'HyFlex'
WHERE LOWER(TRIM(modality_desc)) IN ('hybrid', 'hybrid learning', 'hybrid course');
