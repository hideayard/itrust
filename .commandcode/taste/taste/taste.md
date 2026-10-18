# Taste
- Prefers database changes delivered as a standalone SQL script (e.g. `sql/*.sql`) that can be run manually on MySQL, rather than only via migrations/auto-sync. Confidence: 0.5
- Expects a simple, copy-paste-ready curl example for new/changed HTTP endpoints, and prefers terse response payloads limited to the identifiers that matter (e.g. invoice id + status). Confidence: 0.6
- Wants sync/import logic to be idempotent: a re-sync must update the existing record in place, keyed by the record's natural identity, and must never create duplicate rows (e.g. duplicate invoices). Confidence: 0.7
- Treats "no duplicates" as covering child/detail rows as well as the parent record, and expects existing rows in the database to be audited/cleaned up — not just freshly written data. Confidence: 0.6
- Expects HTTP endpoints to be documented end-to-end: a markdown reference plus an OpenAPI/Swagger-style interactive UI, covering every endpoint with curl examples. Confidence: 0.6
- When documentation exists for one controller, expects it extended to the whole API surface ("pls also create for other controllers") — favors broad coverage over just the piece named. Confidence: 0.5
- Expects list/collection endpoints to support pagination, and prefers it implemented using the project's established convention (e.g. Yii `Pagination` + `X-Pagination-*` headers) rather than a bespoke scheme. Confidence: 0.5
- Writes instructions as short, terse, unpunctuated notes (e.g. "look gmrcontroller, at sync, invoices must not duplicated, just update the old data"; "pls add endpoint to get invoice data (with details)") — prefers concise replies over lengthy explanation. Confidence: 0.6
