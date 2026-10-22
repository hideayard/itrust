-- ============================================================================
-- GMR invoice: add the `fee_pct` + `fee_amount` columns
--
-- The main INV_BUY_<MON> sheet carries a fee on the invoice header (columns
-- "Fee (%)" and "Fee (Rp)"), separate from the per-line fee on
-- gmr_invoice_detail. Rendered invoices sent to POST /gmr/sync-invoice (and
-- /gmr/sync-toon) can carry it as `fee_pct` (the percentage) and `fee_amount`
-- (the resulting IDR value), mirroring tax_pct / tax_amount.
--
-- Manual execution on MySQL:
--   mysql -u root -p itrust_040826 < sql/gmr_invoice_add_fee.sql
--
-- Run once. Re-running errors with "Duplicate column name 'fee_pct'".
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE `gmr_invoice`
  ADD COLUMN `fee_pct`    DECIMAL(8,4)  DEFAULT NULL COMMENT 'Fee percentage (%)' AFTER `tax_amount`,
  ADD COLUMN `fee_amount` DECIMAL(24,2) DEFAULT NULL COMMENT 'Fee value (IDR)'    AFTER `fee_pct`;
