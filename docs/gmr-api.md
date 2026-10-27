# GMR Invoice & Workbook Sync API

Endpoints exposed by `app\controllers\GmrController` (routes in `config/web.php`).
The `POST` endpoints receive the iTrust 2026 workbook sync from the Google Apps
Script; the `GET` endpoints read invoices back.

- **Interactive docs (Swagger UI):** open `web/docs/index.html` in a browser
  (served at `/web/docs/`). It loads every spec under `web/docs/openapi/`.
- **Spec file:** `web/docs/openapi/gmr.json` (OpenAPI 3.0.3).

## Base URL

```text
https://itrust-tech.id/web      # production
```

All paths below are relative to that base (e.g. `/gmr/invoices` ->
`https://itrust-tech.id/web/gmr/invoices`).

## Authentication

Every endpoint except `GET /gmr` (health) requires a bearer token. The token is
`gmrSyncToken` in `config/params.php` and must match `API_SECRET` in the Apps
Script.

```http
Authorization: Bearer CHANGE_ME_GMR_SYNC_TOKEN
```

A missing/wrong token returns `401` with `{"success": false, "message": "Unauthorized"}`.

## Endpoints

| Method | Path                 | Auth | Purpose                                        |
| ------ | -------------------- | ---- | ---------------------------------------------- |
| GET    | `/gmr`               | no   | Health check / entity list                     |
| POST   | `/gmr/sync`          | yes  | Upsert a single row                            |
| POST   | `/gmr/sync-batch`    | yes  | Upsert many rows, optionally replace a sheet   |
| POST   | `/gmr/sync-invoice`  | yes  | Upsert rendered invoices with their line items |
| GET    | `/gmr/invoices`      | yes  | List invoices with their line items            |
| GET    | `/gmr/invoice`       | yes  | Get one invoice with its line items            |

## Entities (for `/gmr/sync` and `/gmr/sync-batch`)

`entity` is one of the keys below; the row fields accepted per entity are:

| Entity            | Row fields                                                                                  |
| ----------------- | ------------------------------------------------------------------------------------------- |
| `buy_trade`       | `month`, `trade_day`, `trade_date`, `ref_id`, `nominal`, `price`, `total`                    |
| `exchange`        | `month`, `trade_day`, `trade_date`, `ref_id`, `nominal`, `rate_buy`, `buy_amount`, `rate_sell`, `sell_amount`, `fee_pct`, `fee_amount`, `omset`, `profit` |
| `invoice`         | `month`, `direction`, `invoice_date`, `ref_id`, `invoice_number`, `billed_to`, `payment_method`, `account_number`, `account_name`, `issue_date`, `due_date`, `status`, `qty`, `rate`, `amount`, `total`, `tax_pct`, `tax_amount`, `fee_pct`, `fee_amount` |
| `invoice_detail`  | `month`, `detail_date`, `ref_id`, `invoice_number`, `description`, `qty`, `unit_price`, `amount`, `fee_pct`, `fee_amount` |
| `monthly_summary` | `month`, `nominal`, `rate_buy_avg`, `buy_amount`, `rate_sell_avg`, `sell_amount`, `fee`, `profit` |
| `bank_balance`    | `entry_type`, `balance_day`, `balance_date`, `global_amount`, `sisa_usdt`, `tambah_modal`, `out_amount`, `total`, `profit`, `remark`, `bca`, `bca_bisnis`, `blu_bca`, `mandiri`, `bri`, `bri_vega`, `bni`, `sinarmas`, `total_bank`, `usdt`, `rate`, `total_usdt` |

Rows are matched by `sync_key` (or by `source_sheet:sheet_row` when both are
provided), so a re-sync updates the same row instead of inserting a new one.

---

### GET /gmr — health check

No authentication. Returns the service name and the entity keys.

```bash
curl -s "https://itrust-tech.id/web/gmr"
```

```json
{
  "success": true,
  "service": "GMR sync",
  "entities": ["buy_trade", "exchange", "invoice", "invoice_detail", "monthly_summary", "bank_balance"]
}
```

---

### POST /gmr/sync — upsert a single row

Body: `entity`, optional `source_sheet` / `month` / `mode`, and `rows` (or a
single `record`). Response counts are `created` / `updated` / `skipped` /
`failed`.

Two row rules apply to `/gmr/sync` and `/gmr/sync-batch`:

- **Rows with `amount` <= 0 are skipped** (not inserted or updated) and counted
  in `skipped`. Rows without an `amount` field (e.g. `exchange`, `bank_balance`)
  are unaffected.
- The originating sheet name is saved to `source_sheet`. A row-level
  `source_sheet` wins over the payload-level one; it is written on insert and
  refreshed on update.

```bash
curl -s -X POST "https://itrust-tech.id/web/gmr/sync" \
  -H "Authorization: Bearer CHANGE_ME_GMR_SYNC_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "entity": "invoice",
    "source_sheet": "INV_BUY_SEP",
    "month": "SEP",
    "rows": [
      {
        "sync_key": "INV_BUY_SEP:5",
        "sheet_row": 5,
        "direction": "BUY",
        "invoice_date": "2026-09-01",
        "invoice_number": "INV-20260901-1",
        "billed_to": "NABIL",
        "payment_method": "BANK BCA",
        "status": "PAID",
        "qty": 29365.07,
        "rate": 17640,
        "amount": 518000000
      }
    ]
  }'
```

```json
{
  "success": true,
  "entity": "invoice",
  "source_sheet": "INV_BUY_SEP",
  "mode": "upsert",
  "received": 1,
  "created": 1,
  "updated": 0,
  "skipped": 0,
  "failed": 0,
  "errors": []
}
```

---

### POST /gmr/sync-batch — upsert many rows / replace a sheet

Same payload and row rules as `/gmr/sync`, but `rows` is the full batch. With
`"mode": "replace"` and a `source_sheet`, the sheet's existing rows are deleted
first (used for a full-sheet resync).

```bash
curl -s -X POST "https://itrust-tech.id/web/gmr/sync-batch" \
  -H "Authorization: Bearer CHANGE_ME_GMR_SYNC_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "entity": "exchange",
    "source_sheet": "SEP",
    "mode": "replace",
    "rows": [
      { "sync_key": "SEP:3", "sheet_row": 3, "trade_date": "2026-09-01", "nominal": 15000, "rate_buy": 17640, "buy_amount": 264600000 },
      { "sync_key": "SEP:4", "sheet_row": 4, "trade_date": "2026-09-02", "nominal": 20000, "rate_buy": 17650, "buy_amount": 353000000 }
    ]
  }'
```

Response is the same shape as `/gmr/sync`.

---

### POST /gmr/sync-invoice — upsert invoices (+ line items)

Accepts one of:

- a single invoice object,
- a raw array of invoices,
- `{ "invoices": [ ... ] }`.

Each invoice is keyed by `id` (= invoice number). A repeated call updates the
same header and replaces its **entire** line set, so nothing is duplicated.

```bash
curl -s -X POST "https://itrust-tech.id/web/gmr/sync-invoice" \
  -H "Authorization: Bearer CHANGE_ME_GMR_SYNC_TOKEN" \
  -H "Content-Type: application/json" \
  -d '[
    {
      "id": "INV-20260901-1",
      "customer": "NABIL",
      "date": "2026-08-31",
      "dueDate": "2026-08-31",
      "paymentMethod": "BANK BCA",
      "accountNumber": "1234567890",
      "accountName": "PT I TRUST TEKNOLOGI",
      "status": "PAID",
      "fee_pct": 0.5,
      "fee_amount": 2590000,
      "total": 520590000,
      "lines": [
        { "description": "BUY USD", "qty": 29365.07, "price": 17640, "fee_pct": 0.5,  "fee_amount": 2590000 },
        { "description": "BUY USD", "qty": 113475,   "price": 17625, "fee_pct": 0.35, "fee_amount": 7000000 }
      ]
    }
  ]'
```

```json
{
  "success": true,
  "count": 1,
  "invoices": [
    { "invoice_id": 42, "invoice_number": "INV-20260901-1", "status": "PAID", "lines": 2 }
  ]
}
```

Accepted aliases: `invoice_number` for `id`; `billed_to` for `customer`;
`invoice_date` for `date`; `due_date` for `dueDate`; `issue_date` for
`issueDate`; `payment_method` for `paymentMethod`; `account_number` for
`accountNumber`; `account_name` for `accountName`; `unit_price` for `price`.
`direction` (`BUY`/`SELL`) is optional and otherwise inferred from the line
descriptions. `month` defaults to the month of the invoice date.

The bank account the invoice is settled through rides along with the payment
method: `account_number` and `account_name` (columns F and G on both the BUY and
SELL sheets, right after PAYMENT METHOD). Both default to `null`.

A fee can sit on the invoice header and on each line, always as a pair:
`fee_pct` (the percentage) and `fee_amount` (the resulting IDR value), mirroring
`tax_pct` / `tax_amount`. Both default to `0`. Accepted aliases: `feePct` for
`fee_pct`, `feeValue` for `fee_amount`, and on a line a legacy `fee` is read as
`fee_amount`.

The header also carries `total`, taken verbatim from the sheet's own Total column
(P on both the BUY and SELL sheets). The sheet formula is the source of truth, so
the server never recomputes it; an empty cell is stored as `0`.

The header's `qty`, `rate` and `amount` work the same way: when the payload
supplies them they are stored as sent, straight from the workbook's QTY / PRICE /
AMOUNT columns. Note that on the INV_BUY sheets the PRICE column holds the IDR
total and the AMOUNT column holds the rate — the reverse of INV_SELL, where
PRICE is the rate and AMOUNT the total. The Apps Script sends each direction
already normalised to `{ qty, rate, amount }`.

A payload that omits them (a line-only shape) still works: they are then derived
from the line items — `qty` = Σ line qty, `amount` = Σ (qty × price), `rate` =
amount / qty.

`source_sheet` may be given per invoice or at the payload level
(`{ "source_sheet": "INV_BUY_SEP", "invoices": [ ... ] }`); the invoice-level
value wins. It is stored on the header and copied to its line items, and
refreshed on update when supplied. When omitted, a new invoice defaults to
`"API"` and an existing one keeps its current value. (`/gmr/sync-toon` behaves
the same.)

---

### GET /gmr/invoices — list invoices with line items

Returns headers newest-first, each with its line items under `details`.

**Filters** (all optional): `id`, `invoice_number`; `direction` (`BUY`/`SELL`),
`month` (`JAN`..`DEC`) and `status` each accept a comma-separated list
(e.g. `status=PAID,CANCEL`); `ref_id`; `source_sheet` (e.g. `API`);
`billed_to` (substring); `search` (substring of `invoice_number` or
`billed_to`); `date_from`/`date_to` (invoice date); `created_from`/`created_to`
and `updated_from`/`updated_to` (record timestamps, `YYYY-MM-DD`, inclusive);
`with_details` (`0` to omit lines).

**Sorting**: `sort_by` (default `id`) and `sort_order` (`asc`/`desc`, default
`desc`). Sortable columns: `id`, `invoice_number`, `direction`, `month`,
`invoice_date`, `issue_date`, `due_date`, `billed_to`, `payment_method`,
`status`, `qty`, `rate`, `amount`, `created_at`, `updated_at`. Unknown fields
fall back to `id`.

**Pagination**: `page` (1-based, default 1), `per_page` (default 50, max 200,
alias `limit`), or `offset` (rows to skip; used when `page` is omitted). The
response carries a `pagination` object and, like the telemetry endpoints,
`X-Pagination-Total-Count` / `X-Pagination-Page-Count` / `X-Pagination-Current-Page`
/ `X-Pagination-Per-Page` response headers.

```bash
# first page, 10 per page, with line items
curl -s -H "Authorization: Bearer CHANGE_ME_GMR_SYNC_TOKEN" \
  "https://itrust-tech.id/web/gmr/invoices?page=1&per_page=10"

# second page of a filtered set
curl -s -H "Authorization: Bearer CHANGE_ME_GMR_SYNC_TOKEN" \
  "https://itrust-tech.id/web/gmr/invoices?direction=BUY&status=PAID&month=SEP&search=NABIL&page=2&per_page=25"

# sorted by amount, biggest first
curl -s -H "Authorization: Bearer CHANGE_ME_GMR_SYNC_TOKEN" \
  "https://itrust-tech.id/web/gmr/invoices?sort_by=amount&sort_order=desc"

# multi-value filter, sorted by invoice date ascending
curl -s -H "Authorization: Bearer CHANGE_ME_GMR_SYNC_TOKEN" \
  "https://itrust-tech.id/web/gmr/invoices?status=PAID,CANCEL&sort_by=invoice_date&sort_order=asc"

# headers only
curl -s -H "Authorization: Bearer CHANGE_ME_GMR_SYNC_TOKEN" \
  "https://itrust-tech.id/web/gmr/invoices?with_details=0"
```

```json
{
  "success": true,
  "count": 1,
  "total": 137,
  "pagination": {
    "total": 137,
    "count": 1,
    "page": 1,
    "per_page": 50,
    "page_count": 3,
    "has_next": true,
    "has_prev": false
  },
  "invoices": [
    {
      "id": 42,
      "invoice_number": "INV-20260901-1",
      "direction": "BUY",
      "month": "AUG",
      "source_sheet": "API",
      "invoice_date": "2026-08-31",
      "issue_date": "2026-08-31",
      "due_date": "2026-08-31",
      "ref_id": null,
      "billed_to": "NABIL",
      "payment_method": "BANK BCA",
      "account_number": "1234567890",
      "account_name": "PT I TRUST TEKNOLOGI",
      "status": "PAID",
      "qty": 142830.07,
      "rate": 17627.34,
      "amount": 2518000000,
      "tax_pct": 0,
      "tax_amount": 0,
      "fee_pct": 0,
      "fee_amount": 0,
      "total": 2518000000,
      "details": [
        { "id": 501, "detail_date": "2026-08-31", "ref_id": null, "description": "BUY USD", "qty": 29365.07, "unit_price": 17640, "amount": 518000000, "fee_pct": 0, "fee_amount": 0 }
      ]
    }
  ]
}
```

---

### GET /gmr/invoice — one invoice with line items

Provide `id` (preferred) or `invoice_number`. Returns `422` if neither is given
and `404` if no invoice matches.

```bash
curl -s -H "Authorization: Bearer CHANGE_ME_GMR_SYNC_TOKEN" \
  "https://itrust-tech.id/web/gmr/invoice?id=42"

curl -s -H "Authorization: Bearer CHANGE_ME_GMR_SYNC_TOKEN" \
  "https://itrust-tech.id/web/gmr/invoice?invoice_number=INV-20260901-1"
```

```json
{
  "success": true,
  "invoice": { "id": 42, "invoice_number": "INV-20260901-1", "details": [ ... ] }
}
```

---

## Status codes

| Code | Meaning                                                        |
| ---- | -------------------------------------------------------------- |
| 200  | OK                                                             |
| 401  | Missing/wrong bearer token                                     |
| 404  | `GET /gmr/invoice` found nothing                               |
| 422  | Bad payload (unknown entity, no rows, invalid invoice, ...)    |

## Offline Swagger UI

`web/docs/index.html` loads Swagger UI from `cdn.jsdelivr.net`. If the server has
no internet access, download `swagger-ui-dist` once and point the `<link>`/`<script>`
tags in that file at the local copies.
