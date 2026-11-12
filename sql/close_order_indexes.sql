-- Indeks untuk endpoint EA (/ea/get) yang dipanggil setiap detik oleh MetaTrader.
-- Tanpa indeks, dua query di EaController::actionGet melakukan FULL TABLE SCAN
-- (EXPLAIN: type=ALL) sehingga CPU hosting cepat penuh.
--
-- Jalankan sekali di database iTrust (phpMyAdmin / mysql CLI).
-- Jika indeks sudah ada, MySQL mengembalikan error 1061 (duplicate key name) — abaikan.

ALTER TABLE close_order
  ADD INDEX idx_account_status_exp (order_account, order_status, expired_date),
  ADD INDEX idx_status_exp (order_status, expired_date);
