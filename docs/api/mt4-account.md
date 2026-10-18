# Mt4AccountController API

- Base URL: `https://itrust-tech.id/web`
- Controller id: `mt4-account`
- Auth: only the endpoints flagged below need a JWT bearer token; others take a `license`/`id` parameter.
- Generated from source by `docs/generate-api-docs.php`.

## Endpoints

| Method | Path | Action | Summary |
| ------ | ---- | ------ | ------- |
| POST | `/mt4-account/accounts` | `accounts` | Optimized lightweight version - 3x faster, 70% less memory |
| POST | `/mt4-account/batch-save-accounts` | `batch-save-accounts` | Batch save multiple accounts at once |
| POST | `/mt4-account/close-all-positions` | `close-all-positions` |  |
| POST | `/mt4-account/close-orders` | `close-orders` | Close all orders for an account |
| POST | `/mt4-account/close-orders-multi` | `close-orders-multi` | Close all orders for multiple accounts |
| POST | `/mt4-account/get-account` | `get-account` | Action to get a single account by ID |
| GET | `/mt4-account/get-accounts` | `get-accounts` | Action to get accounts by user_id or search by path containing text |
| GET | `/mt4-account/get-accounts-by-license` | `get-accounts-by-license` | Action to get accounts by user with license (like your example) |
| GET | `/mt4-account/get-accounts-by-path` | `get-accounts-by-path` |  |
| GET | `/mt4-account/get-accounts-by-path-license` | `get-accounts-by-path-license` | Action to get accounts by path with license (like your example) |
| POST | `/mt4-account/get-accounts-by-user` | `get-accounts-by-user` |  |
| POST | `/mt4-account/get-accounts-by-user-post` | `get-accounts-by-user-post` |  |
| POST | `/mt4-account/get-accounts-summary` | `get-accounts-summary` | Action to get accounts summary by user |
| GET | `/mt4-account/get-closed-orders-by-account` | `get-closed-orders-by-account` | Get closed orders by account ID |
| GET | `/mt4-account/get-open-orders-by-account` | `get-open-orders-by-account` | Get open/modified orders (not closed) by account ID |
| GET | `/mt4-account/get-orders-by-account` | `get-orders-by-account` | Get all orders by account ID (all statuses) |
| GET | `/mt4-account/get-orders-summary-by-account` | `get-orders-summary-by-account` | Get orders summary by account ID |
| GET | `/mt4-account/get-path-hierarchy` | `get-path-hierarchy` | Action to get hierarchy tree for a path |
| POST | `/mt4-account/order-buy` | `order-buy` |  |
| POST | `/mt4-account/order-buy-multi` | `order-buy-multi` | Place buy orders for multiple accounts |
| POST | `/mt4-account/order-sell` | `order-sell` |  |
| POST | `/mt4-account/order-sell-multi` | `order-sell-multi` | Place sell orders for multiple accounts |
| POST | `/mt4-account/save-account` | `save-account` | Action to save or update MT4 account data |
| POST | `/mt4-account/save-account-by-license` | `save-account-by-license` | Alternative version with license authentication (similar to your example) |
| GET | `/mt4-account/search-by-path` | `search-by-path` | Action to search accounts by path (advanced) |
| POST | `/mt4-account/toggle-auto-trade` | `toggle-auto-trade` | Toggle EA (disabled_ea) for an account |
| POST | `/mt4-account/toggle-auto-trade-multi` | `toggle-auto-trade-multi` | Toggle auto trade for multiple accounts |
| POST | `/mt4-account/toggle-buy-sell-status` | `toggle-buy-sell-status` | Toggle buy or sell status for an account |

## Details

### accounts

`POST /mt4-account/accounts`  `/api/accounts`

Optimized lightweight version - 3x faster, 70% less memory

GET /mt4-account/accounts?user_id=123

- requires a JWT bearer token.
- Signature: `$user_id`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/accounts?user_id=" \
  -H "Authorization: Bearer <token>"
```

### batch-save-accounts

`POST /mt4-account/batch-save-accounts`

Batch save multiple accounts at once

- requires a JWT bearer token.
- Body/params: `accounts`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/batch-save-accounts" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "accounts": ""
}'
```

### close-all-positions

`POST /mt4-account/close-all-positions`

- requires a JWT bearer token.
- Body/params: `account_id`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/close-all-positions" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "account_id": ""
}'
```

### close-orders

`POST /mt4-account/close-orders`

Close all orders for an account

POST: account_id

- requires a JWT bearer token.
- Body/params: `account_id`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/close-orders" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "account_id": ""
}'
```

### close-orders-multi

`POST /mt4-account/close-orders-multi`

Close all orders for multiple accounts

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/close-orders-multi" \
  -H "Authorization: Bearer <token>"
```

### get-account

`POST /mt4-account/get-account`

Action to get a single account by ID

- Signature: `$id` (required).

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/get-account?id=" \
  -H "Authorization: Bearer <token>"
```

### get-accounts

`GET /mt4-account/get-accounts`

Action to get accounts by user_id or search by path containing text

GET parameters:
- user_id (optional) - filter by user ID
- path (optional) - search text in path field (partial match)
- search (optional) - search across multiple fields
- status (optional) - filter by status
- account_type (optional) - filter by account type

- Query params: `user_id`, `path`, `search`, `status`, `account_type`, `page`, `limit`, `sort_by`, `sort_order`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mt4-account/get-accounts?user_id=&path=&search=&status=&account_type=&page=&limit=&sort_by=&sort_order="
```

### get-accounts-by-license

`GET /mt4-account/get-accounts-by-license`

Action to get accounts by user with license (like your example)

- Query params: `license`, `path`, `search`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mt4-account/get-accounts-by-license?license=&path=&search="
```

### get-accounts-by-path

`GET /mt4-account/get-accounts-by-path`

- Query params: `path`, `include_children`, `level`, `search`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mt4-account/get-accounts-by-path?path=&include_children=&level=&search="
```

### get-accounts-by-path-license

`GET /mt4-account/get-accounts-by-path-license`

Action to get accounts by path with license (like your example)

- requires a JWT bearer token.
- Query params: `license`, `path`, `include_children`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mt4-account/get-accounts-by-path-license?license=&path=&include_children="
```

### get-accounts-by-user

`POST /mt4-account/get-accounts-by-user`

- requires a JWT bearer token.
- Signature: `$user_id`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/get-accounts-by-user?user_id=" \
  -H "Authorization: Bearer <token>"
```

### get-accounts-by-user-post

`POST /mt4-account/get-accounts-by-user-post`

- Body/params: `user_id`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/get-accounts-by-user-post" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": ""
}'
```

### get-accounts-summary

`POST /mt4-account/get-accounts-summary`

Action to get accounts summary by user

- Signature: `$userId`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/get-accounts-summary?userId=" \
  -H "Authorization: Bearer <token>"
```

### get-closed-orders-by-account

`GET /mt4-account/get-closed-orders-by-account`

Get closed orders by account ID

- requires a JWT bearer token.
- Query params: `account_id`, `limit`, `offset`.
- Body/params: `account_id`, `limit`, `offset`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mt4-account/get-closed-orders-by-account?account_id=&limit=&offset="
```

### get-open-orders-by-account

`GET /mt4-account/get-open-orders-by-account`

Get open/modified orders (not closed) by account ID

- requires a JWT bearer token.
- Query params: `account_id`.
- Body/params: `account_id`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mt4-account/get-open-orders-by-account?account_id="
```

### get-orders-by-account

`GET /mt4-account/get-orders-by-account`

Get all orders by account ID (all statuses)

- requires a JWT bearer token.
- Query params: `account_id`.
- Body/params: `account_id`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mt4-account/get-orders-by-account?account_id="
```

### get-orders-summary-by-account

`GET /mt4-account/get-orders-summary-by-account`

Get orders summary by account ID

- requires a JWT bearer token.
- Query params: `account_id`.
- Body/params: `account_id`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mt4-account/get-orders-summary-by-account?account_id="
```

### get-path-hierarchy

`GET /mt4-account/get-path-hierarchy`

Action to get hierarchy tree for a path

- Query params: `path`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mt4-account/get-path-hierarchy?path="
```

### order-buy

`POST /mt4-account/order-buy`

- requires a JWT bearer token.
- Body/params: `account_id`, `lot`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/order-buy" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "account_id": "",
    "lot": ""
}'
```

### order-buy-multi

`POST /mt4-account/order-buy-multi`

Place buy orders for multiple accounts

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/order-buy-multi" \
  -H "Authorization: Bearer <token>"
```

### order-sell

`POST /mt4-account/order-sell`

- requires a JWT bearer token.
- Body/params: `account_id`, `lot`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/order-sell" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "account_id": "",
    "lot": ""
}'
```

### order-sell-multi

`POST /mt4-account/order-sell-multi`

Place sell orders for multiple accounts

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/order-sell-multi" \
  -H "Authorization: Bearer <token>"
```

### save-account

`POST /mt4-account/save-account`

Action to save or update MT4 account data

Client sends POST data: account_id, buy_order_count, total_buy_lot,
sell_order_count, total_sell_lot, total_profit, account_balance,
account_equity, floating_value

- requires a JWT bearer token.
- Body/params: `account_id`, `buy_order_count`, `total_buy_lot`, `sell_order_count`, `total_sell_lot`, `total_profit`, `account_balance`, `account_equity`, `floating_value`, `bot_name`, `leverage`, `currency`, `server`, `broker`, `account_type`, `status`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/save-account" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "account_id": "",
    "buy_order_count": "",
    "total_buy_lot": "",
    "sell_order_count": "",
    "total_sell_lot": "",
    "total_profit": "",
    "account_balance": "",
    "account_equity": "",
    "floating_value": "",
    "bot_name": "",
    "leverage": "",
    "currency": "",
    "server": "",
    "broker": "",
    "account_type": "",
    "status": ""
}'
```

### save-account-by-license

`POST /mt4-account/save-account-by-license`

Alternative version with license authentication (similar to your example)

- Body/params: `license`, `account_id`, `buy_order_count`, `total_buy_lot`, `sell_order_count`, `total_sell_lot`, `total_profit`, `account_balance`, `account_equity`, `floating_value`, `bot_name`, `server`, `broker`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/save-account-by-license" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "license": "",
    "account_id": "",
    "buy_order_count": "",
    "total_buy_lot": "",
    "sell_order_count": "",
    "total_sell_lot": "",
    "total_profit": "",
    "account_balance": "",
    "account_equity": "",
    "floating_value": "",
    "bot_name": "",
    "server": "",
    "broker": ""
}'
```

### search-by-path

`GET /mt4-account/search-by-path`

Action to search accounts by path (advanced)

- Query params: `q`, `exact`, `user_id`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mt4-account/search-by-path?q=&exact=&user_id="
```

### toggle-auto-trade

`POST /mt4-account/toggle-auto-trade`

Toggle EA (disabled_ea) for an account

POST: account_id, disabled_ea (0 = enabled, 1 = disabled)

- requires a JWT bearer token.
- Body/params: `account_id`, `disabled_ea`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/toggle-auto-trade" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "account_id": "",
    "disabled_ea": ""
}'
```

### toggle-auto-trade-multi

`POST /mt4-account/toggle-auto-trade-multi`

Toggle auto trade for multiple accounts

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/toggle-auto-trade-multi" \
  -H "Authorization: Bearer <token>"
```

### toggle-buy-sell-status

`POST /mt4-account/toggle-buy-sell-status`

Toggle buy or sell status for an account

POST: account_id, type (buy|sell), status (0=disabled, 1=enabled)

- requires a JWT bearer token.
- Body/params: `account_id`, `type`, `status`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mt4-account/toggle-buy-sell-status" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "account_id": "",
    "type": "",
    "status": ""
}'
```

