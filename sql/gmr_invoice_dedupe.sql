-- ============================================================================
-- GMR invoice de-duplication (one-off maintenance)
--
-- Invoices posted to POST /gmr/sync-invoice used to be keyed by
-- "API:<DIRECTION>:<invoice_number>". Because DIRECTION is inferred from the
-- line descriptions it can change between runs, which made the server insert a
-- second row for the same invoice number instead of updating the first one.
--
-- GmrController::syncInvoice() now keys invoices by the invoice number alone,
-- so no new duplicates are created. This script removes the ones already in
-- the table.
--
-- Manual execution on MySQL:
--   mysql -u root -p itrust_040826 < sql/gmr_invoice_dedupe.sql
--
-- The DELETE/UPDATE below are commented out on purpose. Run the SELECT first,
-- review the numbers, then uncomment the statements and run again.
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- 1) Review: invoice numbers that currently have more than one API row.
-- ----------------------------------------------------------------------------
SELECT invoice_number, COUNT(*) AS rows_on_file, GROUP_CONCAT(sync_key ORDER BY id) AS sync_keys
FROM gmr_invoice
WHERE source_sheet = 'API' AND invoice_number IS NOT NULL
GROUP BY invoice_number
HAVING COUNT(*) > 1;

-- ----------------------------------------------------------------------------
-- 2) Delete the extra rows, keeping the oldest (lowest id) one per number.
-- ----------------------------------------------------------------------------
-- DELETE dup
-- FROM gmr_invoice AS dup
-- JOIN gmr_invoice AS keep
--   ON keep.invoice_number = dup.invoice_number
--  AND keep.source_sheet   = 'API'
--  AND dup.source_sheet    = 'API'
--  AND keep.id < dup.id;

-- ----------------------------------------------------------------------------
-- 3) Align the surviving rows with the new "API:<invoice_number>" key.
-- ----------------------------------------------------------------------------
-- UPDATE gmr_invoice
-- SET sync_key = CONCAT('API:', invoice_number)
-- WHERE source_sheet = 'API'
--   AND invoice_number IS NOT NULL
--   AND sync_key <> CONCAT('API:', invoice_number);
