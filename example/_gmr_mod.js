/**
 * ============================================================================
 * iTrust 2026  <->  GMR  (one-way sync: Google Sheets  ->  Yii2 GmrController)
 * ============================================================================
 *
 * This replaces the generic `itrust-sync.script`. It is tailored to the actual
 * layout of the 2026 workbook and fixes the problems of the old script:
 *
 *   - The old script mapped cells by header name, but most sheets repeat the
 *     same headers across derived blocks (>> AVG >>, >> DAILY >>, SUMMARY), so
 *     values landed on the wrong keys. Here every sheet type has an explicit
 *     column map, so only the real input columns are read.
 *   - The old script decided CREATE vs UPDATE from a "Sync Status" column that
 *     does not exist in this workbook. The server now upserts by `sync_key`, so
 *     no helper column is needed and the sheet is never written to.
 *   - Derived/summary columns and blank formula rows are skipped.
 *   - On the INV_BUY_<MON> sheets the PRICE and AMOUNT columns are swapped
 *     relative to their headers (PRICE holds the IDR total, AMOUNT the rate).
 *     Both BUY and SELL invoices are normalised to { qty, rate, amount }.
 *   - Full-sheet resync is available from a menu, with batching for large
 *     sheets, so a first-time import or a repair is one click.
 *
 * REQUIRED SETUP
 * --------------
 *   Simple `onEdit` triggers cannot call UrlFetchApp. You MUST install an
 *   installable trigger. Easiest way: open the spreadsheet, click the
 *   "GMR Sync" menu (created by onOpen) and choose "Install On-Edit Trigger".
 *
 *   Before that, fill in GMR_CONFIG below: point the URLs at your Yii2 site and
 *   set API_SECRET to the same value as `gmrSyncToken` in config/params.php.
 *
 *   Run "GMR Sync > Test Connection" once to confirm the endpoint answers.
 * ============================================================================
 */

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------
const GMR_CONFIG = {
  // e.g. https://your-domain.com/gmr/sync
  SYNC_URL:    'https://your-domain.com/gmr/sync',
  // e.g. https://your-domain.com/gmr/sync-batch
  BATCH_URL:   'https://your-domain.com/gmr/sync-batch',
  // e.g. https://your-domain.com/gmr  (GET, health check)
  INDEX_URL:   'https://your-domain.com/gmr',
  // Must match config/params.php -> gmrSyncToken
  API_SECRET:  'CHANGE_ME_GMR_SYNC_TOKEN',
  // Rows pushed per request during a full sync.
  BATCH_SIZE:  200,
  TIMEZONE:    'Asia/Jakarta',
  DEBUG:       true
};

const GMR_MONTHS = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

// ---------------------------------------------------------------------------
// Menu / trigger management
// ---------------------------------------------------------------------------
function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('GMR Sync')
    .addItem('Full Sync - All Sheets', 'menuFullSyncAll')
    .addItem('Full Sync - This Sheet', 'menuFullSyncThisSheet')
    .addSeparator()
    .addItem('Install On-Edit Trigger', 'menuInstallTrigger')
    .addItem('Test Connection', 'menuTestConnection')
    .addToUi();
}

function menuFullSyncAll() {
  const ui = SpreadsheetApp.getUi();
  const summary = [];

  SpreadsheetApp.getActiveSpreadsheet().getSheets().forEach(function (sheet) {
    const def = resolveSheet(sheet.getName());
    if (!def) return;
    try {
      const res = syncSheet(def, sheet);
      summary.push(sheet.getName() + ': ' + res.created + ' created, ' + res.updated + ' updated' + tail(res.failed));
    } catch (err) {
      summary.push(sheet.getName() + ': ERROR - ' + err);
    }
  });

  ui.alert('GMR Full Sync', summary.length ? summary.join('\n') : 'No syncable sheets found.', ui.ButtonSet.OK);
}

function menuFullSyncThisSheet() {
  const ui = SpreadsheetApp.getUi();
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  const def = resolveSheet(sheet.getName());

  if (!def) {
    ui.alert('Sheet "' + sheet.getName() + '" is not a GMR sync sheet.');
    return;
  }

  try {
    const res = syncSheet(def, sheet);
    ui.alert('GMR Sync', sheet.getName() + ': ' + res.created + ' created, ' + res.updated + ' updated' + tail(res.failed), ui.ButtonSet.OK);
  } catch (err) {
    ui.alert('GMR Sync ERROR', String(err), ui.ButtonSet.OK);
  }
}

function menuInstallTrigger() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();

  ScriptApp.getProjectTriggers().forEach(function (trigger) {
    if (trigger.getHandlerFunction() === 'handleSheetEdit') {
      ScriptApp.deleteTrigger(trigger);
    }
  });

  ScriptApp.newTrigger('handleSheetEdit').forSpreadsheet(ss).onEdit().create();
  SpreadsheetApp.getUi().alert('Installable on-edit trigger installed for handleSheetEdit.');
}

function menuTestConnection() {
  const res = UrlFetchApp.fetch(GMR_CONFIG.INDEX_URL, { method: 'get', muteHttpExceptions: true });
  SpreadsheetApp.getUi().alert('HTTP ' + res.getResponseCode() + '\n\n' + res.getContentText());
}

// ---------------------------------------------------------------------------
// On-edit (installable trigger)
// ---------------------------------------------------------------------------
function handleSheetEdit(e) {
  if (!e || !e.range) return;

  const sheet = e.range.getSheet();
  const def = resolveSheet(sheet.getName());
  if (!def) return;

  // Only react when one of the mapped input columns was edited.
  const allowed = def.cols.map(function (pair) { return pair[0]; });
  const firstCol = e.range.getColumn();
  const lastCol = firstCol + e.range.getNumColumns() - 1;
  let touched = false;
  for (let c = firstCol; c <= lastCol; c++) {
    if (allowed.indexOf(c) !== -1) { touched = true; break; }
  }
  if (!touched) return;

  const sheetName = sheet.getName();
  const lastColIndex = sheet.getLastColumn();
  const firstRow = Math.max(e.range.getRow(), def.dataStartRow);
  const lastRow = e.range.getRow() + e.range.getNumRows() - 1;

  for (let row = firstRow; row <= lastRow; row++) {
    const values = sheet.getRange(row, 1, 1, lastColIndex).getValues()[0];
    const carry = def.entity === 'bank_balance' ? findNearestDate(sheet, row) : null;
    const record = buildRecord(def, values, carry);
    if (!record) continue;

    record.sync_key = sheetName + ':' + row;
    record.sheet_row = row;

    try {
      pushRow(def, sheetName, record);
      log('EDIT row ' + row + ' -> ' + record.sync_key);
    } catch (err) {
      log('ERROR row ' + row + ' (' + sheetName + '): ' + err);
    }
  }
}

// ---------------------------------------------------------------------------
// Sync drivers
// ---------------------------------------------------------------------------
function syncSheet(def, sheet) {
  const rows = collectRows(def, sheet);
  if (!rows.length) return { created: 0, updated: 0, failed: 0 };
  return pushRows(def, sheet.getName(), rows);
}

function collectRows(def, sheet) {
  const lastRow = sheet.getLastRow();
  if (lastRow < def.dataStartRow) return [];

  const lastCol = sheet.getLastColumn();
  const values = sheet.getRange(def.dataStartRow, 1, lastRow - def.dataStartRow + 1, lastCol).getValues();
  const out = [];
  let carryDate = null;

  for (let i = 0; i < values.length; i++) {
    const sheetRow = def.dataStartRow + i;
    const record = buildRecord(def, values[i], carryDate);
    if (!record) continue;

    if (def.entity === 'bank_balance') {
      if (record.balance_date) carryDate = record.balance_date;
      else if (carryDate) record.balance_date = carryDate;
    }

    record.sync_key = sheet.getName() + ':' + sheetRow;
    record.sheet_row = sheetRow;
    out.push(record);
  }

  return out;
}

function pushRow(def, sheetName, record) {
  const payload = {
    entity: def.entity,
    source_sheet: sheetName,
    mode: 'upsert',
    rows: [record]
  };
  if (def.month) payload.month = def.month;

  return postJson(GMR_CONFIG.SYNC_URL, payload);
}

function pushRows(def, sheetName, rows) {
  const totals = { created: 0, updated: 0, failed: 0 };

  for (let i = 0; i < rows.length; i += GMR_CONFIG.BATCH_SIZE) {
    const payload = {
      entity: def.entity,
      source_sheet: sheetName,
      // First chunk clears the sheet's previous rows, the rest are appended.
      mode: i === 0 ? 'replace' : 'append',
      rows: rows.slice(i, i + GMR_CONFIG.BATCH_SIZE)
    };
    if (def.month) payload.month = def.month;

    const res = postJson(GMR_CONFIG.BATCH_URL, payload);
    totals.created += res.created || 0;
    totals.updated += res.updated || 0;
    totals.failed += res.failed || 0;
  }

  return totals;
}

function postJson(url, payload) {
  const res = UrlFetchApp.fetch(url, {
    method: 'post',
    contentType: 'application/json',
    headers: { 'Authorization': 'Bearer ' + GMR_CONFIG.API_SECRET },
    payload: JSON.stringify(payload),
    muteHttpExceptions: true
  });

  const code = res.getResponseCode();
  const body = res.getContentText();

  if (code < 200 || code >= 300) {
    throw new Error('HTTP ' + code + ': ' + body);
  }

  try {
    return JSON.parse(body);
  } catch (err) {
    return {};
  }
}

// ---------------------------------------------------------------------------
// Sheet registry: maps a sheet name to an entity + explicit column map
// ---------------------------------------------------------------------------
function resolveSheet(name) {
  let m;

  if (name === 'BANK') {
    return mkDef('bank_balance', null, null, 2,
      [
        [1, 'balance_day'], [2, 'balance_date'], [3, 'entry_type'], [4, 'global_amount'],
        [5, 'sisa_usdt'], [6, 'tambah_modal'], [7, 'out_amount'], [8, 'total'], [9, 'profit'],
        [11, 'remark'], [12, 'bca'], [13, 'bca_bisnis'], [14, 'blu_bca'], [15, 'mandiri'],
        [16, 'bri'], [17, 'bri_vega'], [18, 'bni'], [19, 'sinarmas'], [20, 'total_bank'],
        [21, 'usdt'], [22, 'rate'], [23, 'total_usdt']
      ],
      ['balance_date'],
      ['global_amount', 'sisa_usdt', 'tambah_modal', 'out_amount', 'total', 'profit', 'bca',
       'bca_bisnis', 'blu_bca', 'mandiri', 'bri', 'bri_vega', 'bni', 'sinarmas', 'total_bank',
       'usdt', 'rate', 'total_usdt'],
      ['entry_type']);
  }

  if (name === 'MONTHLY') {
    return mkDef('monthly_summary', null, null, 3,
      [[1, 'month'], [2, 'nominal'], [3, 'rate_buy_avg'], [4, 'buy_amount'],
       [5, 'rate_sell_avg'], [6, 'sell_amount'], [7, 'fee'], [8, 'profit']],
      [],
      ['nominal', 'rate_buy_avg', 'buy_amount', 'rate_sell_avg', 'sell_amount', 'fee', 'profit'],
      ['month']);
  }

  if ((m = name.match(/^BUY_([A-Z]{3})$/)) && GMR_MONTHS.indexOf(m[1]) !== -1) {
    return mkDef('buy_trade', m[1], null, 2,
      [[1, 'trade_day'], [2, 'trade_date'], [3, 'ref_id'], [4, 'nominal'], [5, 'price'], [6, 'total']],
      ['trade_date'],
      ['nominal', 'price', 'total'],
      ['trade_date', 'nominal', 'total']);
  }

  // INV_BUY: PRICE holds the IDR total, AMOUNT holds the rate (headers swapped).
  if ((m = name.match(/^INV_BUY_([A-Z]{3})$/)) && GMR_MONTHS.indexOf(m[1]) !== -1) {
    return mkDef('invoice', m[1], 'BUY', 2,
      [[1, 'invoice_date'], [2, 'ref_id'], [3, 'invoice_number'], [4, 'billed_to'],
       [5, 'payment_method'], [6, 'issue_date'], [7, 'due_date'], [8, 'status'],
       [9, 'qty'], [10, 'amount'], [11, 'rate']],
      ['invoice_date', 'issue_date', 'due_date'],
      ['qty', 'amount', 'rate'],
      ['invoice_number', 'qty', 'amount']);
  }

  if ((m = name.match(/^INV_SELL_([A-Z]{3})$/)) && GMR_MONTHS.indexOf(m[1]) !== -1) {
    return mkDef('invoice', m[1], 'SELL', 2,
      [[1, 'invoice_date'], [2, 'ref_id'], [3, 'invoice_number'], [4, 'billed_to'],
       [5, 'payment_method'], [6, 'issue_date'], [7, 'due_date'], [8, 'status'],
       [9, 'qty'], [10, 'rate'], [11, 'amount']],
      ['invoice_date', 'issue_date', 'due_date'],
      ['qty', 'rate', 'amount'],
      ['invoice_number', 'qty', 'amount']);
  }

  if ((m = name.match(/^INV_DETAIL_BUY_([A-Z]{3})$/)) && GMR_MONTHS.indexOf(m[1]) !== -1) {
    return mkDef('invoice_detail', m[1], null, 2,
      [[1, 'detail_date'], [2, 'ref_id'], [3, 'invoice_number'], [4, 'description'],
       [5, 'qty'], [6, 'unit_price'], [7, 'amount']],
      ['detail_date'],
      ['qty', 'unit_price', 'amount'],
      ['invoice_number', 'qty', 'amount']);
  }

  if (GMR_MONTHS.indexOf(name) !== -1) {
    return mkDef('exchange', name, null, 3,
      [[1, 'trade_day'], [2, 'trade_date'], [3, 'ref_id'], [4, 'nominal'], [5, 'rate_buy'],
       [6, 'buy_amount'], [7, 'rate_sell'], [8, 'sell_amount'], [9, 'fee_pct'],
       [10, 'fee_amount'], [11, 'omset'], [12, 'profit']],
      ['trade_date'],
      ['nominal', 'rate_buy', 'buy_amount', 'rate_sell', 'sell_amount', 'fee_pct', 'fee_amount', 'omset', 'profit'],
      ['trade_date', 'nominal', 'buy_amount', 'sell_amount']);
  }

  return null;
}

function mkDef(entity, month, direction, dataStartRow, cols, dateFields, numFields, identity) {
  return {
    entity: entity,
    month: month,
    direction: direction,
    dataStartRow: dataStartRow,
    cols: cols,
    dateFields: dateFields,
    numFields: numFields,
    identity: identity
  };
}

// ---------------------------------------------------------------------------
// Row building / value coercion
// ---------------------------------------------------------------------------
function buildRecord(def, values, carryDate) {
  const record = {};

  def.cols.forEach(function (pair) {
    const index = pair[0] - 1;
    const field = pair[1];
    const raw = values[index];

    if (def.dateFields.indexOf(field) !== -1) {
      record[field] = toDate(raw);
    } else if (def.numFields.indexOf(field) !== -1) {
      record[field] = toNumber(raw);
    } else {
      record[field] = toStr(raw);
    }
  });

  if (def.direction) record.direction = def.direction;
  if (def.month) record.month = def.month;

  if (def.entity === 'bank_balance' && !record.balance_date && carryDate) {
    record.balance_date = carryDate;
  }

  const blank = def.identity.every(function (field) {
    const value = record[field];
    return value === null || value === undefined || value === '';
  });

  return blank ? null : record;
}

function findNearestDate(sheet, row) {
  if (row <= 2) return null;

  const values = sheet.getRange(2, 2, row - 2, 1).getValues();
  for (let i = values.length - 1; i >= 0; i--) {
    const date = toDate(values[i][0]);
    if (date) return date;
  }

  return null;
}

function toDate(value) {
  if (value === null || value === undefined || value === '') return null;

  if (value instanceof Date && !isNaN(value.getTime())) {
    return Utilities.formatDate(value, GMR_CONFIG.TIMEZONE, 'yyyy-MM-dd');
  }

  // Excel/Sheets serial date fallback (e.g. 46267).
  if (typeof value === 'number' && value > 20000 && value < 80000) {
    const ms = Math.round((value - 25569) * 86400 * 1000);
    return Utilities.formatDate(new Date(ms), 'UTC', 'yyyy-MM-dd');
  }

  const text = String(value).trim();

  if (/^\d{4}-\d{2}-\d{2}/.test(text)) return text.substring(0, 10);

  const m = text.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
  if (m) {
    return Utilities.formatDate(new Date(Number(m[3]), Number(m[2]) - 1, Number(m[1])), GMR_CONFIG.TIMEZONE, 'yyyy-MM-dd');
  }

  return null;
}

function toNumber(value) {
  if (value === null || value === undefined || value === '') return null;
  if (typeof value === 'number') return isFinite(value) ? value : null;

  // Strip currency separators and any error/placeholder text (#REF!, -, ...).
  const text = String(value).replace(/[,\s]/g, '').replace(/[^0-9.\-]/g, '');
  if (text === '' || text === '-' || text === '.') return null;

  const n = Number(text);
  return isFinite(n) ? n : null;
}

function toStr(value) {
  if (value === null || value === undefined) return null;
  if (value instanceof Date) return Utilities.formatDate(value, GMR_CONFIG.TIMEZONE, 'yyyy-MM-dd');

  const text = String(value).trim();
  return text === '' ? null : text;
}

function tail(failed) {
  return failed ? ', ' + failed + ' failed' : '';
}

function log(message) {
  if (GMR_CONFIG.DEBUG) Logger.log('[GMR] ' + message);
}

module.exports = { resolveSheet: resolveSheet, buildRecord: buildRecord, toDate: toDate, toNumber: toNumber, toStr: toStr };
