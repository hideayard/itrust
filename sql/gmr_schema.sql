-- ============================================================================
-- GMR sync schema (iTrust 2026 workbook -> GmrController)
--
-- Manual execution on MySQL:
--   mysql -u root -p itrust_040826 < sql/gmr_schema.sql
--
-- Every table is fed by the Google Apps Script (example/gmr-sync.script).
-- Rows are keyed by `sync_key` = "<source_sheet>:<sheet_row>" so the on-edit
-- trigger upserts a single row and the "Full Sync" menu replaces a whole sheet.
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- BUY_<MON>: raw USD buy legs (Day, Date, ID, Nominal, Price, Total)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gmr_buy_trade` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `month`       VARCHAR(16)     DEFAULT NULL COMMENT 'Sheet month: JAN..DEC',
  `source_sheet` VARCHAR(64)    NOT NULL,
  `sheet_row`   INT UNSIGNED    DEFAULT NULL,
  `sync_key`    VARCHAR(191)    NOT NULL,
  `trade_day`   VARCHAR(16)     DEFAULT NULL,
  `trade_date`  DATE            DEFAULT NULL,
  `ref_id`      VARCHAR(32)     DEFAULT NULL COMMENT 'Sheet ID / invoice group',
  `nominal`     DECIMAL(20,2)   DEFAULT NULL COMMENT 'USD nominal',
  `price`       DECIMAL(18,4)   DEFAULT NULL COMMENT 'IDR per USD',
  `total`       DECIMAL(24,2)   DEFAULT NULL COMMENT 'IDR total',
  `created_at`  DATETIME        DEFAULT NULL,
  `updated_at`  DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gmr_buy_trade_sync_key` (`sync_key`),
  KEY `idx_gmr_buy_trade_sheet` (`source_sheet`),
  KEY `idx_gmr_buy_trade_month` (`month`),
  KEY `idx_gmr_buy_trade_date` (`trade_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- <MON> (AUG/SEP/OCT...): exchange transactions (buy + sell per row)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gmr_exchange` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `month`       VARCHAR(16)     DEFAULT NULL,
  `source_sheet` VARCHAR(64)    NOT NULL,
  `sheet_row`   INT UNSIGNED    DEFAULT NULL,
  `sync_key`    VARCHAR(191)    NOT NULL,
  `trade_day`   VARCHAR(16)     DEFAULT NULL,
  `trade_date`  DATE            DEFAULT NULL,
  `ref_id`      VARCHAR(32)     DEFAULT NULL,
  `nominal`     DECIMAL(20,2)   DEFAULT NULL,
  `rate_buy`    DECIMAL(18,4)   DEFAULT NULL,
  `buy_amount`  DECIMAL(24,2)   DEFAULT NULL,
  `rate_sell`   DECIMAL(18,4)   DEFAULT NULL,
  `sell_amount` DECIMAL(24,2)   DEFAULT NULL,
  `fee_pct`     DECIMAL(8,4)    DEFAULT NULL,
  `fee_amount`  DECIMAL(24,2)   DEFAULT NULL,
  `omset`       DECIMAL(24,2)   DEFAULT NULL,
  `profit`      DECIMAL(24,2)   DEFAULT NULL,
  `created_at`  DATETIME        DEFAULT NULL,
  `updated_at`  DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gmr_exchange_sync_key` (`sync_key`),
  KEY `idx_gmr_exchange_sheet` (`source_sheet`),
  KEY `idx_gmr_exchange_month` (`month`),
  KEY `idx_gmr_exchange_date` (`trade_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- INV_BUY_<MON> + INV_SELL_<MON>: invoice headers
-- NOTE: on the INV_BUY sheets the PRICE/AMOUNT columns are swapped vs their
-- own headers; the Apps Script normalises both sheets to rate/amount here.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gmr_invoice` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `month`          VARCHAR(16)     DEFAULT NULL,
  `source_sheet`   VARCHAR(64)     NOT NULL,
  `sheet_row`      INT UNSIGNED    DEFAULT NULL,
  `sync_key`       VARCHAR(191)    NOT NULL,
  `direction`      ENUM('BUY','SELL') NOT NULL DEFAULT 'BUY',
  `invoice_date`   DATE            DEFAULT NULL,
  `ref_id`         VARCHAR(32)     DEFAULT NULL,
  `invoice_number` VARCHAR(64)     DEFAULT NULL,
  `billed_to`      VARCHAR(128)    DEFAULT NULL,
  `payment_method` VARCHAR(64)     DEFAULT NULL,
  `issue_date`     DATE            DEFAULT NULL,
  `due_date`       DATE            DEFAULT NULL,
  `status`         VARCHAR(32)     DEFAULT NULL,
  `qty`            DECIMAL(24,4)   DEFAULT NULL COMMENT 'USD qty',
  `rate`           DECIMAL(18,4)   DEFAULT NULL COMMENT 'IDR per USD',
  `amount`         DECIMAL(24,2)   DEFAULT NULL COMMENT 'IDR total',
  `tax_pct`        DECIMAL(8,4)    DEFAULT NULL COMMENT 'Tax percentage (%)',
  `tax_amount`     DECIMAL(24,2)   DEFAULT NULL COMMENT 'Tax value (IDR)',
  `created_at`     DATETIME        DEFAULT NULL,
  `updated_at`     DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gmr_invoice_sync_key` (`sync_key`),
  KEY `idx_gmr_invoice_sheet` (`source_sheet`),
  KEY `idx_gmr_invoice_number` (`invoice_number`),
  KEY `idx_gmr_invoice_date` (`invoice_date`),
  KEY `idx_gmr_invoice_direction` (`direction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- INV_DETAIL_BUY_<MON>: invoice line items
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gmr_invoice_detail` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `month`          VARCHAR(16)     DEFAULT NULL,
  `source_sheet`   VARCHAR(64)     NOT NULL,
  `sheet_row`      INT UNSIGNED    DEFAULT NULL,
  `sync_key`       VARCHAR(191)    NOT NULL,
  `detail_date`    DATE            DEFAULT NULL,
  `ref_id`         VARCHAR(32)     DEFAULT NULL,
  `invoice_number` VARCHAR(64)     DEFAULT NULL,
  `description`    VARCHAR(255)    DEFAULT NULL,
  `qty`            DECIMAL(24,4)   DEFAULT NULL,
  `unit_price`     DECIMAL(18,4)   DEFAULT NULL,
  `amount`         DECIMAL(24,2)   DEFAULT NULL,
  `fee`            DECIMAL(24,2)   DEFAULT NULL COMMENT 'Line fee (IDR)',
  `created_at`     DATETIME        DEFAULT NULL,
  `updated_at`     DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gmr_invoice_detail_sync_key` (`sync_key`),
  KEY `idx_gmr_invoice_detail_sheet` (`source_sheet`),
  KEY `idx_gmr_invoice_detail_number` (`invoice_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- MONTHLY: monthly rollup
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gmr_monthly_summary` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_sheet`   VARCHAR(64)     NOT NULL,
  `sheet_row`      INT UNSIGNED    DEFAULT NULL,
  `sync_key`       VARCHAR(191)    NOT NULL,
  `month`          VARCHAR(16)     DEFAULT NULL,
  `nominal`        DECIMAL(20,2)   DEFAULT NULL,
  `rate_buy_avg`   DECIMAL(18,4)   DEFAULT NULL,
  `buy_amount`     DECIMAL(24,2)   DEFAULT NULL,
  `rate_sell_avg`  DECIMAL(18,4)   DEFAULT NULL,
  `sell_amount`    DECIMAL(24,2)   DEFAULT NULL,
  `fee`            DECIMAL(24,2)   DEFAULT NULL,
  `profit`         DECIMAL(24,2)   DEFAULT NULL,
  `created_at`     DATETIME        DEFAULT NULL,
  `updated_at`     DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gmr_monthly_summary_sync_key` (`sync_key`),
  KEY `idx_gmr_monthly_summary_month` (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- BANK: daily START/END balances across accounts
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gmr_bank_balance` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_sheet`  VARCHAR(64)     NOT NULL,
  `sheet_row`     INT UNSIGNED    DEFAULT NULL,
  `sync_key`      VARCHAR(191)    NOT NULL,
  `entry_type`    VARCHAR(8)      DEFAULT NULL COMMENT 'START or END',
  `balance_day`   VARCHAR(16)     DEFAULT NULL,
  `balance_date`  DATE            DEFAULT NULL,
  `global_amount` DECIMAL(24,2)   DEFAULT NULL,
  `sisa_usdt`     DECIMAL(20,4)   DEFAULT NULL,
  `tambah_modal`  DECIMAL(24,2)   DEFAULT NULL,
  `out_amount`    DECIMAL(24,2)   DEFAULT NULL,
  `total`         DECIMAL(24,2)   DEFAULT NULL,
  `profit`        DECIMAL(24,2)   DEFAULT NULL,
  `remark`        VARCHAR(255)    DEFAULT NULL,
  `bca`           DECIMAL(24,2)   DEFAULT NULL,
  `bca_bisnis`    DECIMAL(24,2)   DEFAULT NULL,
  `blu_bca`       DECIMAL(24,2)   DEFAULT NULL,
  `mandiri`       DECIMAL(24,2)   DEFAULT NULL,
  `bri`           DECIMAL(24,2)   DEFAULT NULL,
  `bri_vega`      DECIMAL(24,2)   DEFAULT NULL,
  `bni`           DECIMAL(24,2)   DEFAULT NULL,
  `sinarmas`      DECIMAL(24,2)   DEFAULT NULL,
  `total_bank`    DECIMAL(24,2)   DEFAULT NULL,
  `usdt`          DECIMAL(20,4)   DEFAULT NULL,
  `rate`          DECIMAL(18,4)   DEFAULT NULL,
  `total_usdt`    DECIMAL(24,2)   DEFAULT NULL,
  `created_at`    DATETIME        DEFAULT NULL,
  `updated_at`    DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gmr_bank_balance_sync_key` (`sync_key`),
  KEY `idx_gmr_bank_balance_date` (`balance_date`),
  KEY `idx_gmr_bank_balance_type` (`entry_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
