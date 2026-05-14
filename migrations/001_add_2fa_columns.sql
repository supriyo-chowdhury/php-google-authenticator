-- migrations/001_add_2fa_columns.sql
-- Run once against your database to add 2FA support to the member table.

ALTER TABLE member
    ADD COLUMN IF NOT EXISTS google_secret      VARCHAR(128)  DEFAULT NULL    COMMENT 'Base32 TOTP secret',
    ADD COLUMN IF NOT EXISTS google_2fa_enabled TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '0 = disabled, 1 = enabled',
    ADD COLUMN IF NOT EXISTS updated_at         TIMESTAMP     NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;
