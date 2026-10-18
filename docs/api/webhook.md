# WebhookController API

- Base URL: `https://itrust-tech.id/web`
- Controller id: `webhook`
- Auth: only the endpoints flagged below need a JWT bearer token; others take a `license`/`id` parameter.
- Generated from source by `docs/generate-api-docs.php`.

## Endpoints

| Method | Path | Action | Summary |
| ------ | ---- | ------ | ------- |
| POST | `/webhook/index` | `index` |  |
| POST | `/webhook/telegram` | `telegram` |  |

## Details

### index

`POST /webhook/index`

```bash
curl -s -X POST "https://itrust-tech.id/web/webhook/index" \
  -H "Authorization: Bearer <token>"
```

### telegram

`POST /webhook/telegram`

```bash
curl -s -X POST "https://itrust-tech.id/web/webhook/telegram" \
  -H "Authorization: Bearer <token>"
```

