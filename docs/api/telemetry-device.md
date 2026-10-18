# TelemetryDeviceController API

- Base URL: `https://itrust-tech.id/web`
- Controller id: `telemetry-device`
- Auth: requires a JWT bearer token on every endpoint except `options` (`Authorization: Bearer <jwt>`).
- Generated from source by `docs/generate-api-docs.php`.

## Endpoints

| Method | Path | Action | Summary |
| ------ | ---- | ------ | ------- |
| PATCH | `/telemetry-device/activate` | `activate` | PATCH /telemetry-device/{id}/activate - Activate device |
| POST | `/telemetry-device/bulk-assign` | `bulk-assign` | POST /telemetry-device/bulk-assign - Bulk assign devices to user (Admin only) |
| GET | `/telemetry-device/by-user` | `by-user` | GET /telemetry-device/user/{user_id} - Get devices by user ID (Admin only) |
| POST | `/telemetry-device/create` | `create` | POST /telemetry-device - Create new device |
| DELETE | `/telemetry-device/delete` | `delete` | DELETE /telemetry-device/{id} - Delete device (soft delete by setting is_active = 0) |
| GET | `/telemetry-device/index` | `index` | GET /telemetry-device - List all devices with pagination |
| POST | `/telemetry-device/options` | `options` | Handle OPTIONS request for CORS preflight |
| GET | `/telemetry-device/search` | `search` | GET /telemetry-device/search - Search devices |
| GET | `/telemetry-device/stats` | `stats` | GET /telemetry-device/{id}/stats - Get device statistics |
| GET | `/telemetry-device/telemetry` | `telemetry` | GET /telemetry-device/{id}/telemetry - Get telemetry data for specific device |
| PUT | `/telemetry-device/update` | `update` | PUT /telemetry-device/{id} - Update device |
| GET | `/telemetry-device/view` | `view` | GET /telemetry-device/{id} - Get device by ID |

## Details

### activate

`PATCH /telemetry-device/activate`  `/telemetry-device/{id}/activate`

PATCH /telemetry-device/{id}/activate - Activate device

- requires a JWT bearer token.
- Signature: `$id` (required).

```bash
curl -s -X PATCH "https://itrust-tech.id/web/telemetry-device/activate?id=" \
  -H "Authorization: Bearer <token>"
```

### bulk-assign

`POST /telemetry-device/bulk-assign`

POST /telemetry-device/bulk-assign - Bulk assign devices to user (Admin only)

- requires a JWT bearer token.
- Body/params: `user_id`, `device_ids`.

```bash
curl -s -X POST "https://itrust-tech.id/web/telemetry-device/bulk-assign" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "",
    "device_ids": ""
}'
```

### by-user

`GET /telemetry-device/by-user`  `/telemetry-device/user/{user_id}`

GET /telemetry-device/user/{user_id} - Get devices by user ID (Admin only)

- requires a JWT bearer token.
- Signature: `$user_id` (required).

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/telemetry-device/by-user?user_id="
```

### create

`POST /telemetry-device/create`

POST /telemetry-device - Create new device

- requires a JWT bearer token.

```bash
curl -s -X POST "https://itrust-tech.id/web/telemetry-device/create" \
  -H "Authorization: Bearer <token>"
```

### delete

`DELETE /telemetry-device/delete`  `/telemetry-device/{id}/delete`

DELETE /telemetry-device/{id} - Delete device (soft delete by setting is_active = 0)

- requires a JWT bearer token.
- Signature: `$id` (required).

```bash
curl -s -X DELETE "https://itrust-tech.id/web/telemetry-device/delete?id=" \
  -H "Authorization: Bearer <token>"
```

### index

`GET /telemetry-device/index`  `/telemetry-device`

GET /telemetry-device - List all devices with pagination

Admin: all devices, User: only their devices

- requires a JWT bearer token.
- Query params: `per_page`, `page`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/telemetry-device/index?per_page=&page="
```

### options

`POST /telemetry-device/options`

Handle OPTIONS request for CORS preflight

```bash
curl -s -X POST "https://itrust-tech.id/web/telemetry-device/options" \
  -H "Authorization: Bearer <token>"
```

### search

`GET /telemetry-device/search`

GET /telemetry-device/search - Search devices

- requires a JWT bearer token.
- Query params: `q`, `limit`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/telemetry-device/search?q=&limit="
```

### stats

`GET /telemetry-device/stats`  `/telemetry-device/{id}/stats`

GET /telemetry-device/{id}/stats - Get device statistics

- requires a JWT bearer token.
- Query params: `start_date`, `end_date`.
- Signature: `$id` (required).

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/telemetry-device/stats?start_date=&end_date=&id="
```

### telemetry

`GET /telemetry-device/telemetry`  `/telemetry-device/{id}/telemetry`

GET /telemetry-device/{id}/telemetry - Get telemetry data for specific device

- requires a JWT bearer token.
- Query params: `start_date`, `end_date`, `limit`, `offset`, `order`.
- Signature: `$id` (required).

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/telemetry-device/telemetry?start_date=&end_date=&limit=&offset=&order=&id="
```

### update

`PUT /telemetry-device/update`  `/telemetry-device/{id}/update`

PUT /telemetry-device/{id} - Update device

- requires a JWT bearer token.
- Signature: `$id` (required).

```bash
curl -s -X PUT "https://itrust-tech.id/web/telemetry-device/update?id=" \
  -H "Authorization: Bearer <token>"
```

### view

`GET /telemetry-device/view`  `/telemetry-device/{id}`

GET /telemetry-device/{id} - Get device by ID

- requires a JWT bearer token.
- Signature: `$id` (required).

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/telemetry-device/view?id="
```

