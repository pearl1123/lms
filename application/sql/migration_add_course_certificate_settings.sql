ALTER TABLE courses
    ADD COLUMN certificate_prefix VARCHAR(12) NULL AFTER expiry_days,
    ADD COLUMN signatory_name VARCHAR(120) NULL AFTER certificate_prefix,
    ADD COLUMN signatory_title VARCHAR(120) NULL AFTER signatory_name;

-- Optional but recommended for faster duplicate checks/verification lookups.
ALTER TABLE lib_certificates
    ADD UNIQUE KEY uq_lib_certificates_code (certificate_code);
