# ScrapedDataController API

- Base URL: `https://itrust-tech.id/web`
- Controller id: `scraped-data`
- Auth: only the endpoints flagged below need a JWT bearer token; others take a `license`/`id` parameter.
- Generated from source by `docs/generate-api-docs.php`.

## Endpoints

| Method | Path | Action | Summary |
| ------ | ---- | ------ | ------- |
| GET | `/scraped-data/logs` | `logs` | Get logs by pair and timeframe |
| POST | `/scraped-data/save` | `save` | Save scraped data endpoint |
| GET | `/scraped-data/statistics` | `statistics` | Get statistics for a pair |
| POST | `/scraped-data/test` | `test` | Test endpoint |

## Details

### logs

`GET /scraped-data/logs`

Get logs by pair and timeframe

- Query params: `pair`, `timeframe`, `limit`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/scraped-data/logs?pair=&timeframe=&limit="
```

### save

`POST /scraped-data/save`

Save scraped data endpoint

- reads a JSON request body.

```bash
curl -s -X POST "https://itrust-tech.id/web/scraped-data/save" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### statistics

`GET /scraped-data/statistics`

Get statistics for a pair

- Query params: `pair`.

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/scraped-data/statistics?pair="
```

### test

`POST /scraped-data/test`

Test endpoint

```bash
curl -s -X POST "https://itrust-tech.id/web/scraped-data/test" \
  -H "Authorization: Bearer <token>"
```

