# EaController API

- Base URL: `https://itrust-tech.id/web`
- Controller id: `ea`
- Auth: only the endpoints flagged below need a JWT bearer token; others take a `license`/`id` parameter.
- Generated from source by `docs/generate-api-docs.php`.

## Endpoints

| Method | Path | Action | Summary |
| ------ | ---- | ------ | ------- |
| POST | `/ea/cancel` | `cancel` |  |
| POST | `/ea/check-license` | `check-license` |  |
| POST | `/ea/check-license-time` | `check-license-time` |  |
| POST | `/ea/check-license-time-v2` | `check-license-time-v2` |  |
| POST | `/ea/check-license-v2` | `check-license-v2` |  |
| POST | `/ea/close` | `close` |  |
| POST | `/ea/get` | `get` | Simplified version that doesn't modify the multi-account list |
| GET | `/ea/get-account-orders` | `get-account-orders` | Get open and closed orders for an account. |
| GET | `/ea/get-dd` | `get-dd` | Get trade DD data by license |
| GET | `/ea/get-orders` | `get-orders` | Get orders for an account |
| GET | `/ea/get-total-wd` | `get-total-wd` | Get total withdraw value by license |
| GET | `/ea/get-wd` | `get-wd` | Get withdraw data by license |
| POST | `/ea/index` | `index` |  |
| POST | `/ea/license` | `license` |  |
| GET | `/ea/order-stats` | `order-stats` | Get order statistics for dashboard |
| POST | `/ea/outlook` | `outlook` |  |
| POST | `/ea/save-dd` | `save-dd` |  |
| POST | `/ea/save-dd-backup` | `save-dd-backup` | Save or update trade DD data |
| POST | `/ea/save-wd` | `save-wd` |  |
| POST | `/ea/sync-account` | `sync-account` |  |
| POST | `/ea/sync-dashboard-orders` | `sync-dashboard-orders` | Sync open/history orders from MT4 dashboard payload. |
| POST | `/ea/sync-orders` | `sync-orders` | Sync closed orders from MT4 |
| POST | `/ea/test-error` | `test-error` |  |
| POST | `/ea/test-message` | `test-message` |  |
| POST | `/ea/test-post` | `test-post` |  |

## Details

### cancel

`POST /ea/cancel`

- Body/params: `id`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/cancel" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": ""
}'
```

### check-license

`POST /ea/check-license`

- Body/params: `id`, `license`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/check-license" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": "",
    "license": ""
}'
```

### check-license-time

`POST /ea/check-license-time`

- Body/params: `license`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/check-license-time" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "license": ""
}'
```

### check-license-time-v2

`POST /ea/check-license-time-v2`

- Query params: `license`.
- Body/params: `license`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/check-license-time-v2?license=" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "license": ""
}'
```

### check-license-v2

`POST /ea/check-license-v2`

- Body/params: `license`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/check-license-v2" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "license": ""
}'
```

### close

`POST /ea/close`

- Body/params: `id`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/close" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": ""
}'
```

### get

`POST /ea/get`

Simplified version that doesn't modify the multi-account list

- Query params: `id`.
- Body/params: `id`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/get?id=" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": ""
}'
```

### get-account-orders

`GET /ea/get-account-orders`

Get open and closed orders for an account.

Fetch orders of the AccountOrders model split into open and closed
groups, with optional pagination, filtering and summary statistics.

Query parameters (GET or POST):
- account_id  (required) The MT4/MT5 account number
- limit       (optional) Number of orders per group, default 100
- offset      (optional) Offset for pagination, default 0
- symbol      (optional) Filter orders by symbol
- from_date   (optional) Only orders opened at/after this date (Y-m-d)
- to_date     (optional) Only orders opened up to this date (Y-m-d)
- with_stats  (optional) 1 to include computed statistics

- requires a JWT bearer token.
- Query params: `account_id`, `limit`, `offset`, `symbol`, `from_date`, `to_date`, `with_stats`.
- Body/params: `account_id`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/ea/get-account-orders?account_id=&limit=&offset=&symbol=&from_date=&to_date=&with_stats="
```

### get-dd

`GET /ea/get-dd`

Get trade DD data by license

- Query params: `license`.
- Body/params: `license`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/ea/get-dd?license="
```

### get-orders

`GET /ea/get-orders`

Get orders for an account

- Query params: `account_id`, `limit`, `offset`, `from_date`, `to_date`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/ea/get-orders?account_id=&limit=&offset=&from_date=&to_date="
```

### get-total-wd

`GET /ea/get-total-wd`

Get total withdraw value by license

- Query params: `license`.
- Body/params: `license`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/ea/get-total-wd?license="
```

### get-wd

`GET /ea/get-wd`

Get withdraw data by license

- Query params: `license`.
- Body/params: `license`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/ea/get-wd?license="
```

### index

`POST /ea/index`

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/index" \
  -H "Authorization: Bearer <token>"
```

### license

`POST /ea/license`

- Body/params: `id`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/license" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": ""
}'
```

### order-stats

`GET /ea/order-stats`

Get order statistics for dashboard

- Query params: `account_id`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/ea/order-stats?account_id="
```

### outlook

`POST /ea/outlook`

- Body/params: `id`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/outlook" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "id": ""
}'
```

### save-dd

`POST /ea/save-dd`

- Body/params: `license`, `account`, `wk_dd`, `wk_percentage_dd`, `wk_date`, `wk_equity`, `all_dd`, `all_percentage_dd`, `all_date`, `all_equity`, `disabled_ea`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/save-dd" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "license": "",
    "account": "",
    "wk_dd": "",
    "wk_percentage_dd": "",
    "wk_date": "",
    "wk_equity": "",
    "all_dd": "",
    "all_percentage_dd": "",
    "all_date": "",
    "all_equity": "",
    "disabled_ea": ""
}'
```

### save-dd-backup

`POST /ea/save-dd-backup`

Save or update trade DD data

- Body/params: `license`, `account`, `wk_dd`, `wk_percentage_dd`, `wk_date`, `wk_equity`, `all_dd`, `all_percentage_dd`, `all_date`, `all_equity`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/save-dd-backup" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "license": "",
    "account": "",
    "wk_dd": "",
    "wk_percentage_dd": "",
    "wk_date": "",
    "wk_equity": "",
    "all_dd": "",
    "all_percentage_dd": "",
    "all_date": "",
    "all_equity": ""
}'
```

### save-wd

`POST /ea/save-wd`

- Body/params: `license`, `account`, `wd_value`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/save-wd" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "license": "",
    "account": "",
    "wd_value": ""
}'
```

### sync-account

`POST /ea/sync-account`

- Body/params: `bot_name`, `disabled_ea`, `buy_status`, `sell_status`, `broker`, `license`, `account_id`, `leverage`, `currency`, `server`, `buy_order_count`, `total_buy_lot`, `sell_order_count`, `total_sell_lot`, `total_profit`, `account_balance`, `account_equity`, `floating_value`, `min_lot`, `timestamp`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/sync-account" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "bot_name": "",
    "disabled_ea": "",
    "buy_status": "",
    "sell_status": "",
    "broker": "",
    "license": "",
    "account_id": "",
    "leverage": "",
    "currency": "",
    "server": "",
    "buy_order_count": "",
    "total_buy_lot": "",
    "sell_order_count": "",
    "total_sell_lot": "",
    "total_profit": "",
    "account_balance": "",
    "account_equity": "",
    "floating_value": "",
    "min_lot": "",
    "timestamp": ""
}'
```

### sync-dashboard-orders

`POST /ea/sync-dashboard-orders`

Sync open/history orders from MT4 dashboard payload.

Expected payload:
{
"success": true,
"data": [{"ticket": 1, "openTime": "2026-08-03T16:40:01", ...}],
"login": "15896020"
}

- reads a JSON request body.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/sync-dashboard-orders" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### sync-orders

`POST /ea/sync-orders`

Sync closed orders from MT4

Accepts single order or batch array

- reads a JSON request body.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/sync-orders" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### test-error

`POST /ea/test-error`

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/test-error" \
  -H "Authorization: Bearer <token>"
```

### test-message

`POST /ea/test-message`

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/test-message" \
  -H "Authorization: Bearer <token>"
```

### test-post

`POST /ea/test-post`

- reads a JSON request body.
- Body/params: `data`.

```bash
curl -s -X POST "https://itrust-tech.id/web/ea/test-post" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "data": ""
}'
```

