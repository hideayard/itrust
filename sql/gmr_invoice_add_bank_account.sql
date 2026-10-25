-- ============================================================================
-- GMR invoice: add the `account_number` + `account_name` columns
--
-- The main INV_BUY_<MON> / INV_SELL_<MON> sheets now carry the bank account the
-- invoice is settled through, in two columns inserted at F/G ("ACCOUNT NUMBER"
-- and "ACCOUNT NAME") right after PAYMENT METHOD — which shifted every column
-- from ISSUE DATE onward two places to the right. The Apps Script sends them to
-- POST /gmr/sync-invoice (and /gmr/sync-toon) as `account_number` and
-- `account_name`.
--
-- Manual execution on MySQL:
--   mysql -u root -p itrust_040826 < sql/gmr_invoice_add_bank_account.sql
--
-- Run once. Re-running errors with "Duplicate column name 'account_number'".
--
-- Existing rows stay NULL until the sheet is synced again (POST /gmr/sync-invoice
-- replaces the invoice by number and writes the two new columns).
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE `gmr_invoice`
  ADD COLUMN `account_number` VARCHAR(64)  DEFAULT NULL COMMENT 'Bank account number' AFTER `payment_method`,
  ADD COLUMN `account_name`   VARCHAR(128) DEFAULT NULL COMMENT 'Bank account name'   AFTER `account_number`;
