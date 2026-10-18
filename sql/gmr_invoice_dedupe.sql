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
-- the table, for both the header (gmr_invoice) and its lines
-- (gmr_invoice_detail).
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

-- ============================================================================
-- gmr_invoice_detail
--
-- The same invoice could keep one set of lines from the sheet sync and another
-- set from the API sync. syncInvoice() now replaces the whole line set by
-- invoice number, so review first, then uncomment to drop the extra copies.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 4) Review: invoice numbers whose lines come from more than one source.
-- ----------------------------------------------------------------------------
SELECT invoice_number, COUNT(*) AS detail_rows, GROUP_CONCAT(DISTINCT source_sheet) AS source_sheets
FROM gmr_invoice_detail
WHERE invoice_number IS NOT NULL
GROUP BY invoice_number
HAVING COUNT(DISTINCT source_sheet) > 1;

-- ----------------------------------------------------------------------------
-- 5) Drop the non-API copies for invoices the API has already stored.
-- ----------------------------------------------------------------------------
-- DELETE d
-- FROM gmr_invoice_detail AS d
-- JOIN gmr_invoice_detail AS a
--   ON a.invoice_number = d.invoice_number
--  AND a.source_sheet   = 'API'
-- WHERE d.source_sheet <> 'API';
