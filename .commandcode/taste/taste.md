# Taste

- Prefers database changes delivered as a standalone SQL script (e.g. `sql/*.sql`) that can be run manually on MySQL, rather than only via migrations/auto-sync. Confidence: 0.5
- Expects a simple, copy-paste-ready curl example for new/changed HTTP endpoints, and prefers terse response payloads limited to the identifiers that matter (e.g. invoice id + status). Confidence: 0.6
- Wants sync/import logic to be idempotent: a re-sync must update the existing record in place, keyed by the record's natural identity, and must never create duplicate rows (e.g. duplicate invoices). Confidence: 0.7
- Writes instructions as short, terse, unpunctuated notes (e.g. "look gmrcontroller, at sync, invoices must not duplicated, just update the old data") — prefers concise replies over lengthy explanation. Confidence: 0.5
