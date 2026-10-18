# MobileController API

- Base URL: `https://itrust-tech.id/web`
- Controller id: `mobile`
- Auth: only the endpoints flagged below need a JWT bearer token; others take a `license`/`id` parameter.
- Generated from source by `docs/generate-api-docs.php`.

## Endpoints

| Method | Path | Action | Summary |
| ------ | ---- | ------ | ------- |
| POST | `/mobile/action-change-password` | `action-change-password` |  |
| POST | `/mobile/action-reset-password` | `action-reset-password` |  |
| POST | `/mobile/averaged-telemetry` | `averaged-telemetry` | Endpoint to receive averaged telemetry data |
| POST | `/mobile/change-password` | `change-password` |  |
| POST | `/mobile/check-otp` | `check-otp` | Check/Verify OTP |
| POST | `/mobile/create-device` | `create-device` | Create a new device for the authenticated user |
| POST | `/mobile/debug-table` | `debug-table` |  |
| POST | `/mobile/delete-device` | `delete-device` |  |
| GET | `/mobile/device-telemetry` | `device-telemetry` | Get telemetry data for a specific device by device_id |
| GET | `/mobile/device-telemetry-by-id` | `device-telemetry-by-id` | Get specific telemetry record by ID |
| POST | `/mobile/forgot-password` | `forgot-password` | Updated forgot password action with email debugging |
| POST | `/mobile/generate-otp` | `generate-otp` | Generate OTP for password reset |
| POST | `/mobile/get-account-status` | `get-account-status` |  |
| GET | `/mobile/get-all-scraped-data-v2` | `get-all-scraped-data-v2` |  |
| POST | `/mobile/get-device` | `get-device` |  |
| POST | `/mobile/get-devices` | `get-devices` |  |
| POST | `/mobile/get-devices-with-data` | `get-devices-with-data` |  |
| GET | `/mobile/get-high-impact-events` | `get-high-impact-events` | Get high impact events |
| GET | `/mobile/get-interest-rates` | `get-interest-rates` | Get interest rates |
| GET | `/mobile/get-inverter-settings` | `get-inverter-settings` | Get all active inverter settings. |
| GET | `/mobile/get-latest-events` | `get-latest-events` | Get latest economic events |
| GET | `/mobile/get-latest-scrape-data` | `get-latest-scrape-data` |  |
| GET | `/mobile/get-latest-scrape-data-v2` | `get-latest-scrape-data-v2` | Get latest scraped data V2 - Updated to match web page requirements |
| GET | `/mobile/get-latest-scrape-data-v3` | `get-latest-scrape-data-v3` |  |
| GET | `/mobile/get-public-devices-with-data` | `get-public-devices-with-data` | Get ALL public devices with data - NO AUTHENTICATION REQUIRED |
| GET | `/mobile/get-scrape-data` | `get-scrape-data` | Get scraped data by ID or latest |
| GET | `/mobile/get-scraped-data-stats-v2` | `get-scraped-data-stats-v2` |  |
| POST | `/mobile/get-scraped-data-v2` | `get-scraped-data-v2` |  |
| GET | `/mobile/get-statistics` | `get-statistics` | Get scraping statistics |
| GET | `/mobile/get-technical-analysis` | `get-technical-analysis` | Get technical analysis data |
| POST | `/mobile/get-user-profile` | `get-user-profile` |  |
| POST | `/mobile/get-user-stats` | `get-user-stats` |  |
| POST | `/mobile/get-users` | `get-users` |  |
| GET | `/mobile/get-users-inverter` | `get-users-inverter` | Get list of users where user_tipe is ADMIN or CUSTOMER |
| POST | `/mobile/get-users-old` | `get-users-old` |  |
| POST | `/mobile/health` | `health` | Health check endpoint |
| POST | `/mobile/index` | `index` |  |
| POST | `/mobile/login` | `login` |  |
| POST | `/mobile/options` | `options` | Handle OPTIONS request for CORS preflight |
| POST | `/mobile/register` | `register` | User Registration Endpoint |
| POST | `/mobile/reset-password` | `reset-password` | Reset password using OTP |
| POST | `/mobile/save-investing-data` | `save-investing-data` |  |
| POST | `/mobile/save-myfxbook-data` | `save-myfxbook-data` |  |
| POST | `/mobile/save-scrape-data` | `save-scrape-data` |  |
| POST | `/mobile/save-scrape-data-v2` | `save-scrape-data-v2` |  |
| POST | `/mobile/save-scrape-data2` | `save-scrape-data2` |  |
| POST | `/mobile/set-device` | `set-device` | Connect user_id to unassigned device record. |
| GET | `/mobile/sync-config` | `sync-config` | Endpoint to get sync configuration for mobile app |
| GET | `/mobile/telemetry-stats` | `telemetry-stats` | Endpoint to get telemetry data statistics |
| POST | `/mobile/test-email` | `test-email` | Public action to test email sending |
| POST | `/mobile/update-device` | `update-device` |  |
| POST | `/mobile/update-photo` | `update-photo` |  |
| POST | `/mobile/update-user` | `update-user` |  |
| GET | `/mobile/user-devices` | `user-devices` | Get all devices for the authenticated user |
| POST | `/mobile/validate-token` | `validate-token` |  |
| POST | `/mobile/verify-reset-token` | `verify-reset-token` | Verify Reset Token (optional endpoint) |

## Details

### action-change-password

`POST /mobile/action-change-password`

- Query params: `token`.
- Body/params: `token`, `new_password`, `confirm_password`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/action-change-password?token=" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "",
    "new_password": "",
    "confirm_password": ""
}'
```

### action-reset-password

`POST /mobile/action-reset-password`

- Body/params: `email`, `otp`, `new_password`, `confirm_password`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/action-reset-password" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "",
    "otp": "",
    "new_password": "",
    "confirm_password": ""
}'
```

### averaged-telemetry

`POST /mobile/averaged-telemetry`

Endpoint to receive averaged telemetry data

POST /mobile/averaged-telemetry

- reads a JSON request body.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/averaged-telemetry" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### change-password

`POST /mobile/change-password`

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/change-password" \
  -H "Authorization: Bearer <token>"
```

### check-otp

`POST /mobile/check-otp`

Check/Verify OTP

POST /check-otp
Params: email, otp

- Body/params: `email`, `otp`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/check-otp" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "",
    "otp": ""
}'
```

### create-device

`POST /mobile/create-device`

Create a new device for the authenticated user

POST /mobile/create-device

Parameters (POST):
- device_id (required): Unique device identifier
- device_name (optional): Device name
- device_alias (optional): Device alias
- device_description (optional): Device description
- device_remark (optional): Device remark

Response:
{
"success": true,
"message": "Device created successfully",
"data": {
"id": 1,
"device_id": "device_123",
"device_name": "My Device",
"device_alias": "Solar Panel",
"device_description": "Main solar panel",
"device_remark": "Installed 2024",
"is_active": true,
"created_at": "2024-01-01 12:00:00"
}
}

- requires a JWT bearer token.
- Body/params: `user_id`, `device_id`, `device_name`, `device_alias`, `device_description`, `device_remark`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/create-device" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "",
    "device_id": "",
    "device_name": "",
    "device_alias": "",
    "device_description": "",
    "device_remark": ""
}'
```

### debug-table

`POST /mobile/debug-table`

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/debug-table" \
  -H "Authorization: Bearer <token>"
```

### delete-device

`POST /mobile/delete-device`

- requires a JWT bearer token.
- Signature: `$id` (required).

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/delete-device?id=" \
  -H "Authorization: Bearer <token>"
```

### device-telemetry

`GET /mobile/device-telemetry`

Get telemetry data for a specific device by device_id

- Query params: `start_date`, `end_date`, `limit`, `offset`, `order`.
- Signature: `$device_id`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/device-telemetry?start_date=&end_date=&limit=&offset=&order=&device_id="
```

### device-telemetry-by-id

`GET /mobile/device-telemetry-by-id`

Get specific telemetry record by ID

- Signature: `$id`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/device-telemetry-by-id?id="
```

### forgot-password

`POST /mobile/forgot-password`

Updated forgot password action with email debugging

- Body/params: `email`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/forgot-password" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "email": ""
}'
```

### generate-otp

`POST /mobile/generate-otp`

Generate OTP for password reset

POST /generate-otp
Params: email

- Body/params: `email`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/generate-otp" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "email": ""
}'
```

### get-account-status

`POST /mobile/get-account-status`

- Signature: `$account_id` (required), `$license`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/get-account-status?account_id=&license=" \
  -H "Authorization: Bearer <token>"
```

### get-all-scraped-data-v2

`GET /mobile/get-all-scraped-data-v2`

- Query params: `limit`, `offset`, `pair`, `timeframe`, `date_from`, `date_to`, `sort`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-all-scraped-data-v2?limit=&offset=&pair=&timeframe=&date_from=&date_to=&sort="
```

### get-device

`POST /mobile/get-device`

- requires a JWT bearer token.
- Signature: `$id` (required).

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/get-device?id=" \
  -H "Authorization: Bearer <token>"
```

### get-devices

`POST /mobile/get-devices`

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/get-devices" \
  -H "Authorization: Bearer <token>"
```

### get-devices-with-data

`POST /mobile/get-devices-with-data`

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/get-devices-with-data" \
  -H "Authorization: Bearer <token>"
```

### get-high-impact-events

`GET /mobile/get-high-impact-events`

Get high impact events

Endpoint: GET /mobile/get-high-impact-events?currency=USD&limit=10

Response:
{
"success": true,
"events": [...],
"total": 8
}

- Query params: `currency`, `limit`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-high-impact-events?currency=&limit="
```

### get-interest-rates

`GET /mobile/get-interest-rates`

Get interest rates

Endpoint: GET /mobile/get-interest-rates?country=United States&limit=10

Response:
{
"success": true,
"interestRates": [...],
"total": 5
}

- Query params: `country`, `limit`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-interest-rates?country=&limit="
```

### get-inverter-settings

`GET /mobile/get-inverter-settings`

Get all active inverter settings.

Returns every settings row where settings_status = 1 and settings_type = 'inverter'.

Endpoint: GET /mobile/get-inverter-settings

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-inverter-settings"
```

### get-latest-events

`GET /mobile/get-latest-events`

Get latest economic events

Endpoint: GET /mobile/get-latest-events?currency=EUR&limit=20

Response:
{
"success": true,
"events": [...],
"total": 15
}

- Query params: `currency`, `limit`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-latest-events?currency=&limit="
```

### get-latest-scrape-data

`GET /mobile/get-latest-scrape-data`

- Query params: `pair`, `timeframe`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-latest-scrape-data?pair=&timeframe="
```

### get-latest-scrape-data-v2

`GET /mobile/get-latest-scrape-data-v2`

Get latest scraped data V2 - Updated to match web page requirements

Endpoint: GET /mobile/get-latest-scrape-data-v2
Parameters: pair, timeframe (optional)

- Query params: `pair`, `timeframe`, `hours`.
- Body/params: `pair`, `timeframe`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-latest-scrape-data-v2?pair=&timeframe=&hours="
```

### get-latest-scrape-data-v3

`GET /mobile/get-latest-scrape-data-v3`

- Query params: `pair`, `timeframe`, `source`, `limit`, `hoursBack`, `includeRaw`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-latest-scrape-data-v3?pair=&timeframe=&source=&limit=&hoursBack=&includeRaw="
```

### get-public-devices-with-data

`GET /mobile/get-public-devices-with-data`

Get ALL public devices with data - NO AUTHENTICATION REQUIRED

For demo purposes only - returns all active devices from all users with their telemetry data

Endpoint: GET /mobile/get-public-devices-with-data

Response:
{
"success": true,
"message": "Public devices retrieved successfully with telemetry data",
"data": [...],
"summary": {...}
}

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-public-devices-with-data"
```

### get-scrape-data

`GET /mobile/get-scrape-data`

Get scraped data by ID or latest

Endpoint: GET /mobile/get-scrape-data?id=123
Endpoint: GET /mobile/get-scrape-data?limit=10
Endpoint: GET /mobile/get-scrape-data?date=2024-01-07
Endpoint: GET /mobile/get-scrape-data?startDate=2024-01-01&endDate=2024-01-07

Response:
{
"success": true,
"data": [...],
"total": 10
}

- Query params: `id`, `limit`, `date`, `startDate`, `endDate`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-scrape-data?id=&limit=&date=&startDate=&endDate="
```

### get-scraped-data-stats-v2

`GET /mobile/get-scraped-data-stats-v2`

- Query params: `pair`, `timeframe`, `days`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-scraped-data-stats-v2?pair=&timeframe=&days="
```

### get-scraped-data-v2

`POST /mobile/get-scraped-data-v2`

- Signature: `$id` (required).

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/get-scraped-data-v2?id=" \
  -H "Authorization: Bearer <token>"
```

### get-statistics

`GET /mobile/get-statistics`

Get scraping statistics

Endpoint: GET /mobile/get-statistics?startDate=2024-01-01&endDate=2024-01-07

Response:
{
"success": true,
"statistics": [...],
"total": 7
}

- Query params: `startDate`, `endDate`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-statistics?startDate=&endDate="
```

### get-technical-analysis

`GET /mobile/get-technical-analysis`

Get technical analysis data

Endpoint: GET /mobile/get-technical-analysis?limit=5

Response:
{
"success": true,
"technicalAnalysis": [...],
"total": 5
}

- Query params: `limit`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-technical-analysis?limit="
```

### get-user-profile

`POST /mobile/get-user-profile`

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/get-user-profile" \
  -H "Authorization: Bearer <token>"
```

### get-user-stats

`POST /mobile/get-user-stats`

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/get-user-stats" \
  -H "Authorization: Bearer <token>"
```

### get-users

`POST /mobile/get-users`

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/get-users" \
  -H "Authorization: Bearer <token>"
```

### get-users-inverter

`GET /mobile/get-users-inverter`

Get list of users where user_tipe is ADMIN or CUSTOMER

Endpoint: GET /mobile/get-users
Parameters: token (required), user_tipe (optional: ADMIN, CUSTOMER, or all), limit, offset

Response:
{
"success": true,
"message": "Users retrieved successfully",
"data": [...],
"total": 10
}

- requires a JWT bearer token.
- Query params: `user_tipe`, `limit`, `offset`, `search`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/get-users-inverter?user_tipe=&limit=&offset=&search="
```

### get-users-old

`POST /mobile/get-users-old`

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/get-users-old" \
  -H "Authorization: Bearer <token>"
```

### health

`POST /mobile/health`

Health check endpoint

GET /mobile/health

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/health" \
  -H "Authorization: Bearer <token>"
```

### index

`POST /mobile/index`

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/index" \
  -H "Authorization: Bearer <token>"
```

### login

`POST /mobile/login`

- Body/params: `username`, `password`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/login" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "",
    "password": ""
}'
```

### options

`POST /mobile/options`

Handle OPTIONS request for CORS preflight

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/options" \
  -H "Authorization: Bearer <token>"
```

### register

`POST /mobile/register`

User Registration Endpoint

- Body/params: `name`, `email`, `username`, `password`, `confirm_password`, `phone`, `user_type`, `accept_terms`, `account`, `license`, `telegram_id`, `telegram_username`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/register" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "",
    "email": "",
    "username": "",
    "password": "",
    "confirm_password": "",
    "phone": "",
    "user_type": "",
    "accept_terms": "",
    "account": "",
    "license": "",
    "telegram_id": "",
    "telegram_username": ""
}'
```

### reset-password

`POST /mobile/reset-password`

Reset password using OTP

POST /reset-password
Params: email, otp, new_password, confirm_password

- Body/params: `email`, `otp`, `new_password`, `confirm_password`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/reset-password" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "",
    "otp": "",
    "new_password": "",
    "confirm_password": ""
}'
```

### save-investing-data

`POST /mobile/save-investing-data`

- reads a JSON request body.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/save-investing-data" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### save-myfxbook-data

`POST /mobile/save-myfxbook-data`

- reads a JSON request body.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/save-myfxbook-data" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### save-scrape-data

`POST /mobile/save-scrape-data`

- reads a JSON request body.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/save-scrape-data" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### save-scrape-data-v2

`POST /mobile/save-scrape-data-v2`

- reads a JSON request body.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/save-scrape-data-v2" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### save-scrape-data2

`POST /mobile/save-scrape-data2`

- reads a JSON request body.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/save-scrape-data2" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### set-device

`POST /mobile/set-device`

Connect user_id to unassigned device record.

Params: token, user_id, device

- requires a JWT bearer token.
- Query params: `token`, `user_id`, `device`.
- Body/params: `token`, `user_id`, `device`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/set-device?token=&user_id=&device=" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "",
    "user_id": "",
    "device": ""
}'
```

### sync-config

`GET /mobile/sync-config`

Endpoint to get sync configuration for mobile app

GET /mobile/sync-config

- Query params: `token`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/sync-config?token="
```

### telemetry-stats

`GET /mobile/telemetry-stats`

Endpoint to get telemetry data statistics

GET /mobile/telemetry-stats

- Query params: `token`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/telemetry-stats?token="
```

### test-email

`POST /mobile/test-email`

Public action to test email sending

Can be called via: POST /test-email with optional email and name fields

- Query params: `email`.
- Body/params: `email`, `name`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/test-email?email=" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "",
    "name": ""
}'
```

### update-device

`POST /mobile/update-device`

- requires a JWT bearer token.
- Signature: `$id` (required).

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/update-device?id=" \
  -H "Authorization: Bearer <token>"
```

### update-photo

`POST /mobile/update-photo`

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/update-photo" \
  -H "Authorization: Bearer <token>"
```

### update-user

`POST /mobile/update-user`

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/update-user" \
  -H "Authorization: Bearer <token>"
```

### user-devices

`GET /mobile/user-devices`

Get all devices for the authenticated user

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/mobile/user-devices"
```

### validate-token

`POST /mobile/validate-token`

- Query params: `token`.
- Body/params: `token`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/validate-token?token=" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "token": ""
}'
```

### verify-reset-token

`POST /mobile/verify-reset-token`

Verify Reset Token (optional endpoint)

- Body/params: `token`.

```bash
curl -s -X POST "https://itrust-tech.id/web/mobile/verify-reset-token" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "token": ""
}'
```

