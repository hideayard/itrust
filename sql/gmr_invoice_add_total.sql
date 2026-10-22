-- ============================================================================
-- GMR invoice: add the `total` column
--
-- Every invoice carries a total = amount + tax_amount + fee_amount, i.e. the
-- sheet's "Total" column (N on INV_BUY_<MON>). The server derives it whenever
-- the payload does not supply one, so SELL invoices (whose sheet has no total
-- column) get it too.
--
-- The column is added AFTER `amount` on purpose, so this script can run before
-- or after sql/gmr_invoice_add_fee.sql — either order ends up with the same
-- layout (amount, total, tax_pct, tax_amount, fee_pct, fee_amount).
--
-- PREREQUISITE for the backfill below: sql/gmr_invoice_add_fee.sql must already
-- have been run, because it reads `fee_amount`. The ALTER on its own has no
-- such dependency.
--
-- Manual execution on MySQL:
--   mysql -u root -p itrust_040826 < sql/gmr_invoice_add_total.sql
--
-- Run once. Re-running errors with "Duplicate column name 'total'".
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE `gmr_invoice`
  MODIFY COLUMN `amount` DECIMAL(24,2) DEFAULT NULL COMMENT 'Qty x rate (IDR)',
  ADD COLUMN `total` DECIMAL(24,2) DEFAULT NULL COMMENT 'Amount + tax + fee (IDR)' AFTER `amount`;

-- Backfill rows that were stored before the column existed.
UPDATE `gmr_invoice`
SET `total` = COALESCE(`amount`, 0) + COALESCE(`tax_amount`, 0) + COALESCE(`fee_amount`, 0)
WHERE `total` IS NULL;
