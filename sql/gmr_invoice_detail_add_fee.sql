-- ============================================================================
-- GMR invoice detail: add the per-line `fee` column
--
-- Rendered invoices sent to POST /gmr/sync-invoice (and /gmr/sync-toon) can now
-- carry a `fee` per line. GmrInvoiceDetail exposes it as an IDR amount, stored
-- next to the line's `amount`.
--
-- Manual execution on MySQL:
--   mysql -u root -p itrust_040826 < sql/gmr_invoice_detail_add_fee.sql
--
-- Run once. Re-running errors with "Duplicate column name 'fee'".
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE `gmr_invoice_detail`
  ADD COLUMN `fee` DECIMAL(24,2) DEFAULT NULL COMMENT 'Line fee (IDR)' AFTER `amount`;
