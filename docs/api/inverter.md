# InverterController API

- Base URL: `https://itrust-tech.id/web`
- Controller id: `inverter`
- Auth: only the endpoints flagged below need a JWT bearer token; others take a `license`/`id` parameter.
- Generated from source by `docs/generate-api-docs.php`.

## Endpoints

| Method | Path | Action | Summary |
| ------ | ---- | ------ | ------- |
| POST | `/inverter/index` | `index` | Lists all Banner models. |
| POST | `/inverter/s` | `s` | {@inheritdoc} |

## Details

### index

`POST /inverter/index`

Lists all Banner models.

```bash
curl -s -X POST "https://itrust-tech.id/web/inverter/index" \
  -H "Authorization: Bearer <token>"
```

### s

`POST /inverter/s`

{@inheritdoc}

```bash
curl -s -X POST "https://itrust-tech.id/web/inverter/s" \
  -H "Authorization: Bearer <token>"
```

