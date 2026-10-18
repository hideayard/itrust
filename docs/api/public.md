# PublicController API

- Base URL: `https://itrust-tech.id/web`
- Controller id: `public`
- Auth: only the endpoints flagged below need a JWT bearer token; others take a `license`/`id` parameter.
- Generated from source by `docs/generate-api-docs.php`.

## Endpoints

| Method | Path | Action | Summary |
| ------ | ---- | ------ | ------- |
| GET | `/public/get-mt4-accounts` | `get-mt4-accounts` | Get MT4 accounts and groups where remark = "public" |

## Details

### get-mt4-accounts

`GET /public/get-mt4-accounts`

Get MT4 accounts and groups where remark = "public"

This endpoint does NOT require authentication.

GET /public/get-mt4-accounts

Accounts are included if:
- the account itself has remark = "public", OR
- the account's account_id belongs to an Mt4Group with remark = "public"
(matched via the group's mt4_ids JSON array, as in Mt4AccountController::getGroupsData)

```bash
curl -s -H "Authorization: Bearer <token>" "https://itrust-tech.id/web/public/get-mt4-accounts"
```

