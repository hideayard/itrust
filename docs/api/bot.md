# BotController API

- Base URL: `https://itrust-tech.id/web`
- Controller id: `bot`
- Auth: only the endpoints flagged below need a JWT bearer token; others take a `license`/`id` parameter.
- Generated from source by `docs/generate-api-docs.php`.

## Endpoints

| Method | Path | Action | Summary |
| ------ | ---- | ------ | ------- |
| POST | `/bot/index` | `index` |  |
| POST | `/bot/webhook` | `webhook` |  |

## Details

### index

`POST /bot/index`

```bash
curl -s -X POST "https://itrust-tech.id/web/bot/index" \
  -H "Authorization: Bearer <token>"
```

### webhook

`POST /bot/webhook`

- reads a JSON request body.

```bash
curl -s -X POST "https://itrust-tech.id/web/bot/webhook" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

