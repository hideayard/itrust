-- ============================================================================
-- GMR invoice detail: split the per-line `fee` into `fee_pct` + `fee_amount`
--
-- Buy invoice lines carry a fee both as a percentage and as the resulting IDR
-- value (sheet columns "Fee (%)" and "Fee (Rp)"), replacing the earlier single
-- `fee` amount. GmrInvoiceDetail exposes the pair as fee_pct / fee_amount,
-- mirroring tax_pct / tax_amount on the invoice header.
--
-- Manual execution on MySQL:
--   mysql -u root -p itrust_040826 < sql/gmr_invoice_detail_fee_pct_amount.sql
--
-- Precondition: the single `fee` column added by the earlier
-- sql/gmr_invoice_detail_add_fee.sql (also present in sql/gmr_schema.sql) still
-- exists. Run once. Re-running errors with "Duplicate column name 'fee_pct'".
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE `gmr_invoice_detail`
  ADD COLUMN `fee_pct`    DECIMAL(8,4)  DEFAULT NULL COMMENT 'Fee percentage (%)' AFTER `amount`,
  ADD COLUMN `fee_amount` DECIMAL(24,2) DEFAULT NULL COMMENT 'Fee value (IDR)'    AFTER `fee_pct`;

-- Carry over whatever the single `fee` column already held, then drop it.
UPDATE `gmr_invoice_detail` SET `fee_amount` = `fee` WHERE `fee` IS NOT NULL;

ALTER TABLE `gmr_invoice_detail` DROP COLUMN `fee`;
