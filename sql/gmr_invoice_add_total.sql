-- ============================================================================
-- GMR invoice: add the `total` column
--
-- The invoice total is the sheet's own "Total" column (N on both INV_BUY_<MON>
-- and INV_SELL_<MON>) and is stored exactly as sent: the sheet formula is the
-- source of truth, so the server never recomputes it. When the cell is empty
-- the stored value is 0.
--
-- The column is added AFTER `amount` on purpose, so this script can run before
-- or after sql/gmr_invoice_add_fee.sql — either order ends up with the same
-- layout (amount, total, tax_pct, tax_amount, fee_pct, fee_amount).
--
-- Manual execution on MySQL:
--   mysql -u root -p itrust_040826 < sql/gmr_invoice_add_total.sql
--
-- Run once. Re-running errors with "Duplicate column name 'total'".
--
-- Existing rows stay NULL until the sheet is synced again (POST /gmr/sync-invoice
-- replaces the invoice by number and writes the sheet Total).
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE `gmr_invoice`
  MODIFY COLUMN `amount` DECIMAL(24,2) DEFAULT NULL COMMENT 'Qty x rate (IDR)',
  ADD COLUMN `total` DECIMAL(24,2) DEFAULT NULL COMMENT 'Sheet Total (IDR)' AFTER `amount`;
