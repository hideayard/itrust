# Spesifikasi API Invoice (untuk backend)

Dokumen ini menjelaskan data & endpoint yang dibutuhkan oleh dashboard invoice
(frontend Astro statis + IndexedDB). Dipakai sebagai acuan saat melengkapi
layanan **GMR sync** di `https://itrust-tech.id/web/gmr/*`.

- **Klien** membaca lewat proxy same-origin `/api/gmr/*` (dev: Vite proxy;
  produksi: Cloudflare Pages Function) dan mengirim `Authorization: Bearer <token>`
  (token dari `POST /web/mobile/login`).
- **Semua angka dashboard dihitung di klien** dari daftar invoice. Jadi minimal
  cukup satu endpoint list yang benar; `monthly_summary` hanya optimasi.
- Data disimpan di IndexedDB dan disinkronkan **inkremental** (`?since=`).

---

## 1. Info yang ditampilkan di dashboard

| Bagian | Field yang dibutuhkan | Sumber |
|---|---|---|
| Kartu **Total invoice** | jumlah baris | turunan |
| Kartu **Pendapatan (lunas)** | `status`, `total` | turunan (sum `total` status lunas) |
| Kartu **Menunggu** | `status` | turunan (count) |
| Kartu **Terlambat** | `status` | turunan (count) |
| Chart **Pendapatan per bulan** | `date`, `status`, `total` | turunan (group lunas per bulan, 6 terakhir) |
| Tabel **Invoice terbaru** (5) | `id`, `billed_to`, `date`, `total`, `status` | turunan (urut `date` desc) |
| Badge **sumber & waktu sinkron** | `syncDate` (per payload) | dari server |
| **Sinkron inkremental** | `updated_at` per baris | dari server (**wajib**) |

Selain itu, halaman daftar invoice memakai: `id`, `billed_to`, `phone`,
`email`, `payment_method`, `date`, `due_date`, `total`, `status` (untuk cari,
urut, filter, dan paginasi di klien) serta `lines[]` untuk halaman detail.

---

## 2. Bentuk baris invoice (kanonik)

Kolom kanonik yang diharapkan. Alias dalam tanda kurung sudah **didukung**
pemetaan klien, jadi backend boleh memakai nama yang sudah ada.

```
id                 string    unik                      (invoice_number | invoiceNumber | number | code)
billed_to          string    nama pelanggan            (customer_name | customerName | customer | name | buyer)
email              string                              (customer_email | customerEmail)
phone              string                              (customer_phone | customerPhone)
address            string                              (customer_address | customerAddress)
date               date      YYYY-MM-DD               (invoice_date | invoiceDate | created_at | createdAt)
due_date           date      YYYY-MM-DD               (dueDate)
payment_method     string                              (paymentMethod | payment)
status             enum      PAID | PENDING | OVERDUE (atau lunas | menunggu | terlambat)
direction          enum      BUY | SELL               (untuk filter API)
total              number    IDR, integer
item_count         number    opsional
lines[]            array     { description, qty, price }
updated_at         datetime  ISO 8601                 (WAJIB untuk sinkron delta)
deleted            boolean   penanda hapus            (disarankan)
```

Pemetaan status di klien:

| Server | Klien |
|---|---|
| `PAID` / `LUNAS` | `lunas` |
| `PENDING` / `UNPAID` / `MENUNGGU` | `menunggu` |
| `OVERDUE` / `TERLAMBAT` | `terlambat` |

Field baris (`lines`): `description` (`name`/`product`/`item`),
`qty` (`quantity`), `price` (`unit_price`/`amount`/`total`).

---

## 3. Kontrak endpoint

### 3.1 `GET /web/gmr/invoices`

Query: `limit`, `offset` (atau `page`), `direction`, `status`, `month`
(mis. `SEP`), `search`, `with_details` (`0|1`), `since` (ISO 8601).

- Tanpa `since` → kembalikan **semua** invoice.
- Dengan `since` → hanya baris `updated_at > since`, **termasuk** baris yang
  baru dihapus (`deleted: true`) agar klien bisa menghapus lokal.
- `with_details=0` boleh mengosongkan `lines` (hanya butuh header).

```json
{
  "success": true,
  "syncDate": "2026-10-19T05:00:00Z",
  "count": 128,
  "stats": {
    "total": 128,
    "revenue": 98650000,
    "byStatus": { "lunas": 80, "menunggu": 30, "terlambat": 18 }
  },
  "data": [
    {
      "id": "INV-2026-0112",
      "billed_to": "Budi Santoso",
      "email": "budi.santoso@example.com",
      "phone": "+62 812 3456 7890",
      "address": "Jl. Raya Darmo No. 12, Surabaya",
      "date": "2026-09-29",
      "due_date": "2026-10-06",
      "payment_method": "Transfer Bank",
      "status": "PAID",
      "direction": "BUY",
      "total": 21100000,
      "item_count": 3,
      "updated_at": "2026-10-18T09:12:00Z",
      "deleted": false,
      "lines": [
        { "description": "Laptop ASUS ROG Strix G16", "qty": 1, "price": 18900000 },
        { "description": "SSD Samsung 980 Pro 1TB", "qty": 1, "price": 1750000 }
      ]
    }
  ]
}
```

Catatan:
- `total` = nilai akhir invoice (boleh dihitung dari `lines`).
- `updated_at` **wajib** ada di setiap baris (dipakai untuk delta).
- Balasan yang tidak `success` akan membuat klien jatuh ke salinan statis
  `/invoices.json` (hanya pada sinkron penuh).

### 3.2 `GET /web/gmr/monthly_summary?months=6` (opsional)

Untuk chart pendapatan. Bila tidak tersedia, klien menghitung sendiri dari list.

```json
{
  "success": true,
  "data": [
    { "month": "2026-09", "total": 42300000 },
    { "month": "2026-08", "total": 18750000 }
  ]
}
```

### 3.3 `POST /web/gmr/sync`

Endpoint tulis (dipakai klien; saat ini balas `{"success":false,"message":"No rows supplied"}`).
Klien **hanya** memperbarui penyimpanan lokal setelah server membalas sukses.

```json
// request
{
  "entity": "invoice",
  "rows": [
    { "id": "INV-2026-0113", "billed_to": "…", "total": 1500000, "lines": [ … ] }
  ]
}

// response sukses
{ "success": true, "message": "1 row synced" }

// response gagal (status 4xx/5xx)
{ "success": false, "message": "<alasan>" }
```

### 3.4 `POST /web/gmr/sync-toon` (alternatif hemat token)

Sama seperti `POST /web/gmr/sync` / `sync-invoice`, tetapi **isi request body
adalah TOON** (Token-Oriented Object Notation), bukan JSON — jauh lebih ringkas
untuk payload besar. Balasannya tetap JSON.

- Header: `Content-Type: text/toon`.
- Body boleh berbentuk baris generik (`entity` + `rows`) atau invoice
  (`id` + `lines`, atau `{ invoices: [ ... ] }`).
- Aturan saja sama dengan endpoint padanannya (baris di-upsert lewat
  `sync_key` / `id`).

Contoh body — invoice tunggal:

```text
id: INV-20260901-1
customer: NABIL
date: 2026-08-31
dueDate: 2026-08-31
paymentMethod: BANK BCA
status: PAID
lines[2]{description,qty,price}:
  "BUY USD",29365.07,17640
  "BUY USD",113475,17625
```

Contoh body — baris generik (bentuk tabular):

```text
entity: invoice
source_sheet: INV_BUY_SEP
mode: upsert
rows[1]{sync_key,month,invoice_number,billed_to,status,qty,rate,amount}:
  INV_BUY_SEP:5,SEP,INV-20260901-1,NABIL,PAID,29365.07,17640,518000000
```

Response invoice (sama seperti `sync-invoice`):

```json
{
  "success": true,
  "count": 1,
  "invoices": [
    { "invoice_id": 42, "invoice_number": "INV-20260901-1", "status": "PAID", "lines": 2 }
  ]
}
```

Response baris generik (sama seperti `sync`):

```json
{
  "success": true,
  "entity": "invoice",
  "source_sheet": "INV_BUY_SEP",
  "mode": "upsert",
  "received": 1,
  "created": 1,
  "updated": 0,
  "skipped": 0,
  "failed": 0,
  "errors": []
}
```

Catatan sintaks TOON:

- Delimiter default koma, indentasi 2 spasi (tab/`|` juga diterima saat dibaca).
- Array objek yang seragam ditulis **tabular**: `rows[N]{kolom,...}:` lalu baris
  datanya di baris berikutnya (indentasi +1).
- String yang mengandung `,` `:` `"` `[` `]` `{` `}` atau spasi di tepi harus
  dikutip dengan `"..."`.
- Baris kosong dan baris berawalan `#` (komentar) diabaikan.

### 3.5 `GET /web/gmr/invoice-toon` (balasan TOON)

Sama seperti `GET /web/gmr/invoice` (pakai `id` atau `invoice_number`), tetapi
**balasannya teks TOON** (`Content-Type: text/toon`). Berguna saat data invoice
diteruskan ke agen/LLM yang membaca TOON.

```bash
curl -s -H "Authorization: Bearer <token>" \
  "https://itrust-tech.id/web/gmr/invoice-toon?id=42"
```

Contoh balasan:

```text
success: true
invoice:
  id: 42
  invoice_number: INV-20260901-1
  direction: BUY
  month: AUG
  source_sheet: API
  invoice_date: 2026-08-31
  issue_date: 2026-08-31
  due_date: 2026-08-31
  ref_id: null
  billed_to: NABIL
  payment_method: BANK BCA
  status: PAID
  qty: 142830.07
  rate: 17627.34
  amount: 2518000000
  details[1]{id,detail_date,ref_id,description,qty,unit_price,amount}:
    501,2026-08-31,null,BUY USD,29365.07,17640,518000000
```

Baris `details` (dan header) mengikuti urutan kolom serializer, jadi nama kolom
di `{...}` sinkron dengan `GET /web/gmr/invoice`. Nilai desimal dikeluarkan
sebagai float; bila angka berasal dari kolom `DECIMAL` presisi penuh bisa
memunculkan digit tambahan (mis. `142830.070000000006985`) — pembaca cukup
membulatkan sesuai kebutuhan.

Error tetap JSON: `422` bila `id`/`invoice_number` tidak diberikan, `404` bila
invoice tidak ditemukan.

---

## 4. Prompt siap tempel (sisi backend)

> Salin blok di bawah ini ke pengembang/agen backend.

Konteks: frontend (Astro statis + IndexedDB) membaca data invoice dari layanan
"GMR sync" di `https://itrust-tech.id/web/gmr/*`. Mohon lengkapi endpoint
berikut supaya dashboard bisa menampilkan ringkasan, chart, dan daftar invoice.

1. `GET /web/gmr/invoices`
   - Query: `limit`, `offset`, `direction` (BUY|SELL), `status`, `month`
     (mis. `SEP`), `search`, `with_details` (0|1), `since` (ISO 8601).
   - Tanpa `since` → kembalikan SEMUA invoice.
   - Dengan `since` → hanya baris dengan `updated_at > since`, TERMASUK baris
     yang baru dihapus (tandai `deleted: true`) supaya klien bisa hapus lokal.
   - Response:

```json
{
  "success": true,
  "syncDate": "<ISO waktu server>",
  "count": 128,
  "stats": {
    "total": 128,
    "revenue": 98650000,
    "byStatus": { "lunas": 80, "menunggu": 30, "terlambat": 18 }
  },
  "data": [
    {
      "id": "<string unik>",
      "billed_to": "<nama pelanggan>",
      "email": "<string>",
      "phone": "<string>",
      "address": "<string>",
      "date": "YYYY-MM-DD",
      "due_date": "YYYY-MM-DD",
      "payment_method": "<string>",
      "status": "PAID|PENDING|OVERDUE",
      "direction": "BUY|SELL",
      "total": 0,
      "item_count": 0,
      "updated_at": "<ISO 8601>",
      "deleted": false,
      "lines": [{ "description": "<string>", "qty": 1, "price": 0 }]
    }
  ]
}
```

   - `total` = nilai akhir invoice (boleh dihitung dari `lines`).
   - `updated_at` WAJIB ada di setiap baris (dipakai untuk sinkron delta).
   - `with_details=0` boleh mengosongkan `lines`.

2. `GET /web/gmr/monthly_summary?months=6` (opsional, untuk chart)
   - Response: `{ "success": true, "data": [ { "month": "YYYY-MM", "total": 0 } ] }`
     dengan `total` khusus invoice lunas.

3. `POST /web/gmr/sync`
   - Body: `{ "entity": "invoice", "rows": [ <invoice>... ] }`
   - Validasi + simpan, lalu balas `{ "success": true, "message": "<ringkas>" }`.
   - Bila gagal: `{ "success": false, "message": "<alasan>" }` dengan status 4xx/5xx.

Ketentuan umum:
   - Auth: header `Authorization: Bearer <token>` (token dari `POST /web/mobile/login`).
   - Semua tanggal ISO 8601; kolom tanggal invoice pakai `YYYY-MM-DD`.
   - `status` dipetakan di klien: `PAID`→lunas, `PENDING`/`UNPAID`→menunggu,
     `OVERDUE`→terlambat.
   - Nilai uang integer Rupiah (tanpa desimal).
