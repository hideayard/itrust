-- ============================================================================
-- GMR invoice: add the `tax_pct` + `tax_amount` columns
--
-- Rendered invoices sent to POST /gmr/sync-invoice (and /gmr/sync-toon) can now
-- carry a tax on the invoice header: `tax_pct` (the percentage) and
-- `tax_amount` (the resulting IDR value).
--
-- Manual execution on MySQL:
--   mysql -u root -p itrust_040826 < sql/gmr_invoice_add_tax.sql
--
-- Run once. Re-running errors with "Duplicate column name 'tax_pct'".
-- If you already ran the earlier single-column `tax` version of this migration,
-- also run the commented DROP at the bottom to remove the obsolete column.
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE `gmr_invoice`
  ADD COLUMN `tax_pct`    DECIMAL(8,4)  DEFAULT NULL COMMENT 'Tax percentage (%)' AFTER `amount`,
  ADD COLUMN `tax_amount` DECIMAL(24,2) DEFAULT NULL COMMENT 'Tax value (IDR)'    AFTER `tax_pct`;

-- Only needed if the obsolete single `tax` column exists from the earlier run.
-- ALTER TABLE `gmr_invoice` DROP COLUMN `tax`;
