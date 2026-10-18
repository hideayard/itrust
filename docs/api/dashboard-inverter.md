# DashboardInverterController API

- Base URL: `https://itrust-tech.id/web`
- Controller id: `dashboard-inverter`
- Auth: only the endpoints flagged below need a JWT bearer token; others take a `license`/`id` parameter.
- Generated from source by `docs/generate-api-docs.php`.

## Endpoints

| Method | Path | Action | Summary |
| ------ | ---- | ------ | ------- |
| POST | `/dashboard-inverter/index` | `index` | Lists all Banner models. |
| POST | `/dashboard-inverter/s` | `s` | {@inheritdoc} |

## Details

### index

`POST /dashboard-inverter/index`

Lists all Banner models.

```bash
curl -s -X POST "https://itrust-tech.id/web/dashboard-inverter/index" \
  -H "Authorization: Bearer <token>"
```

### s

`POST /dashboard-inverter/s`

{@inheritdoc}

```bash
curl -s -X POST "https://itrust-tech.id/web/dashboard-inverter/s" \
  -H "Authorization: Bearer <token>"
```

