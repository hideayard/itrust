/**
 * ============================================================
 * KONFIGURASI UTAMA
 * ============================================================
 */
var CONFIG = CONFIG || {
  BASE_URL: 'https://itrust-tech.id/web/',

  LOGIN_PATH_PREFIX:    'mobile',
  VALIDATE_PATH_PREFIX: 'mobile',
  SYNC_PATH_PREFIX:     'gmr',

  LOGIN_ENDPOINT:             'login',
  VALIDATE_TOKEN_ENDPOINT:    'validate-token',
  SYNC_ENDPOINT:              'sync-invoice',

  STATUS_COL_NAME: 'Sync Status',
  TOKEN_KEY: 'YII2_JWT_TOKEN',
  CLIENT_TZ_KEY: 'CLIENT_TIMEZONE',

  LEGEND_SHEET_NAME: 'LEGEND',
  LEGEND_TOKEN_CELL: 'Y1',
  LEGEND_FIELD_COL: 'X',
  LEGEND_VALUE_COL: 'Y',
  LEGEND_START_ROW: 1,

  BATCH_LIMIT_CELL: 'S2',
  DEFAULT_BATCH_LIMIT: 100,
  PAUSE_MS_BETWEEN_BATCHES: 300,

  // Dipertahankan sebagai fallback default
  SYNC_STATUS_COLUMN:      15,   // O
  SYNC_MODIFIED_AT_COLUMN: 16,   // P

  // Teks status untuk baris dengan amount 0 / kosong
  INVALID_STATUS_TEXT: 'not valid data'
};

/**
 * ============================================================
 * KOLOM SYNC PER JENIS INVOICE (1-based, untuk getRange)
 * ------------------------------------------------------------
 * BUY + SELL : K=AMOUNT | L=Fee % | M=Fee Rp | N=Total | O=Sync Status | P=Date Sync
 * ============================================================
 */
var SYNC_COLUMNS = {
  BUY:  { STATUS: 15, MODIFIED_AT: 16 },  // O, P
  SELL: { STATUS: 15, MODIFIED_AT: 16 }   // O, P
};

/**
 * Bangun URL lengkap.
 */
function buildUrl_(pathPrefix, endpoint) {
  var base = CONFIG.BASE_URL;
  if (base.charAt(base.length - 1) !== '/') base += '/';
  var prefix = pathPrefix ? (pathPrefix + '/') : '';
  return base + prefix + endpoint;
}

/**
 * ============================================================
 * KONFIGURASI KOLOM INVOICE (indeks 0-based untuk baca array)
 * ============================================================
 */
var INV_COL = {
  DATE: 0, ID: 1, INVOICE_NUMBER: 2, BILLED_TO: 3,
  PAYMENT_METHOD: 4, ISSUE_DATE: 5, DUE_DATE: 6,
  STATUS: 7, QTY: 8, PRICE: 9, AMOUNT: 10
};

// Kolom tambahan setelah AMOUNT (dipakai BUY dan SELL)
var INV_COL_EXTRA = {
  FEE_PCT: 11,       // L
  FEE_RP:  12,       // M
  TOTAL:   13,       // N
  SYNC_STATUS: 14,   // O (0-based)
  SYNC_DATE:   15    // P (0-based)
};

var DETAIL_COL = {
  DATE: 0, ID: 1, INVOICE_NUMBER: 2, DESCRIPTION: 3,
  QTY: 4, UNIT_PRICE: 5, AMOUNT: 6
};

/**
 * Helper: pilih kolom sync berdasarkan kind ('buy' / 'sell').
 */
function getSyncCols_(kind) {
  var k = String(kind || 'sell').toLowerCase();
  return k === 'buy' ? SYNC_COLUMNS.BUY : SYNC_COLUMNS.SELL;
}

/**
 * ============================================================
 * HELPER: Cek amount valid (kolom K)
 * ============================================================
 */
function isAmountInvalid_(amount) {
  if (amount === null || amount === undefined || amount === '') return true;
  var num = Number(amount);
  if (isNaN(num)) return true;
  return num === 0;
}

/**
 * ============================================================
 * MENU
 * ============================================================
 */
function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('🚀 Sync Server')
    .addItem('Preview JSON Invoice', 'previewInvoices')
    .addItem('Sinkronkan Invoice Ke Server', 'syncInvoicesToServer')
    .addSeparator()
    .addItem('Cek Status Token & Koneksi', 'checkTokenStatusUserAction')
    .addItem('Login / Update Token', 'showLoginModal')
    .addItem('Logout', 'logout')
    .addToUi();
}

/**
 * ============================================================
 * MODAL LOGIN
 * ============================================================
 */
function showLoginModal() {
  var html = HtmlService.createTemplateFromFile('LoginModal')
    .evaluate()
    .setWidth(400)
    .setHeight(400);
  SpreadsheetApp.getUi().showModalDialog(html, ' ');
}

/**
 * ============================================================
 * HELPER: Bersihkan response body
 * ============================================================
 */
function cleanResponseText(text) {
  if (!text) return '';
  var s = String(text);

  if (/<\s*(!doctype|html|head|body|div|span|p|h[1-6]|br)\b/i.test(s)) {
    var titleMatch = s.match(/<title[^>]*>([\s\S]*?)<\/title>/i);
    if (titleMatch && titleMatch[1]) {
      return 'Halaman HTML dari server (kemungkinan endpoint salah / tidak ditemukan). ' +
             'Title: ' + titleMatch[1].trim();
    }
    return 'Halaman HTML dari server (kemungkinan endpoint salah / tidak ditemukan).';
  }

  s = s.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
  if (s.length > 200) s = s.substring(0, 200) + '...';
  return s;
}

/**
 * ============================================================
 * TOKEN STORAGE
 * ============================================================
 */
function saveTokenToLegendSheet(token, user) {
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var legendSheet = ss.getSheetByName(CONFIG.LEGEND_SHEET_NAME);
    if (!legendSheet) legendSheet = ss.insertSheet(CONFIG.LEGEND_SHEET_NAME);

    var rows = [['TOKEN', token]];
    var startRow = CONFIG.LEGEND_START_ROW;
    var endRow   = startRow + rows.length + 5;

    legendSheet
      .getRange(CONFIG.LEGEND_FIELD_COL + startRow + ':' + CONFIG.LEGEND_VALUE_COL + endRow)
      .clearContent();

    rows.forEach(function (pair, idx) {
      var r = startRow + idx;
      legendSheet.getRange(CONFIG.LEGEND_FIELD_COL + r).setValue(pair[0]);
      legendSheet.getRange(CONFIG.LEGEND_VALUE_COL + r).setValue(pair[1]);
    });
  } catch (e) {
    Logger.log('Gagal menyimpan data ke sheet LEGEND: ' + e.toString());
  }
}

function getTokenFromLegendSheet() {
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var legendSheet = ss.getSheetByName(CONFIG.LEGEND_SHEET_NAME);
    if (legendSheet) {
      var token = legendSheet.getRange(CONFIG.LEGEND_TOKEN_CELL).getValue();
      return token ? String(token).trim() : null;
    }
  } catch (e) {
    Logger.log('Gagal membaca token dari sheet LEGEND: ' + e.toString());
  }
  return null;
}

function clearTokenFromLegendSheet() {
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var legendSheet = ss.getSheetByName(CONFIG.LEGEND_SHEET_NAME);
    if (legendSheet) legendSheet.getRange('X1:Y20').clearContent();
  } catch (e) {
    Logger.log('Gagal menghapus data dari sheet LEGEND: ' + e.toString());
  }
}

function getStoredToken() {
  var token = getTokenFromLegendSheet();
  if (token) { Logger.log('Token dari LEGEND!Y1'); return token; }

  token = PropertiesService.getUserProperties().getProperty(CONFIG.TOKEN_KEY);
  if (token) {
    Logger.log('Token dari PropertiesService (fallback)');
    saveTokenToLegendSheet(token, null);
    return token;
  }
  return null;
}

/**
 * ============================================================
 * CLIENT TIMEZONE (untuk Date Sync)
 * ============================================================
 */

/**
 * Simpan timezone browser user. Dipanggil dari modal JS.
 * TZ divalidasi dengan mencoba format tanggal — jika invalid, fallback ke 'UTC'.
 */
function saveClientTimeZone(tz) {
  try {
    tz = String(tz || '').trim();
    if (!tz) tz = 'UTC';

    // Validasi: coba format. Kalau throw, TZ invalid.
    try {
      Utilities.formatDate(new Date(), tz, 'yyyy-MM-dd HH:mm:ss');
    } catch (e) {
      Logger.log('[TZ] TZ tidak valid "' + tz + '", fallback ke UTC');
      tz = 'UTC';
    }

    PropertiesService.getUserProperties().setProperty(CONFIG.CLIENT_TZ_KEY, tz);
    Logger.log('[TZ] Client timezone disimpan: ' + tz);
    return { success: true, tz: tz };
  } catch (e) {
    Logger.log('[TZ] Gagal menyimpan client timezone: ' + e);
    return { success: false };
  }
}

/**
 * Ambil TZ yang dipakai untuk Date Sync.
 * Urutan: client TZ → script TZ → 'UTC'.
 */
function getEffectiveTimezone_() {
  try {
    var clientTz = PropertiesService.getUserProperties().getProperty(CONFIG.CLIENT_TZ_KEY);
    if (clientTz) {
      try {
        Utilities.formatDate(new Date(), clientTz, 'yyyy-MM-dd');
        return clientTz;
      } catch (e) {
        Logger.log('[TZ] Client TZ tersimpan tidak valid ("' + clientTz + '"), fallback.');
      }
    }
  } catch (e) { /* ignore */ }

  try {
    var scriptTz = Session.getScriptTimeZone();
    if (scriptTz) return scriptTz;
  } catch (e) { /* ignore */ }

  return 'UTC';
}

/**
 * ============================================================
 * LOGIN / VALIDATE
 * ============================================================
 */
function processLogin(username, password) {
  var url = buildUrl_(CONFIG.LOGIN_PATH_PREFIX, CONFIG.LOGIN_ENDPOINT);
  var options = {
    method: 'post',
    contentType: 'application/x-www-form-urlencoded',
    payload: { 'username': username, 'password': password },
    muteHttpExceptions: true
  };

  try {
    var response = UrlFetchApp.fetch(url, options);
    var statusCode = response.getResponseCode();
    var result = JSON.parse(response.getContentText());

    if (statusCode === 200 && result.success && result.token) {
      PropertiesService.getUserProperties().setProperty(CONFIG.TOKEN_KEY, result.token);
      saveTokenToLegendSheet(result.token, result.user);
      return { success: true };
    }
    return {
      success: false,
      message: result.message || 'Login gagal. Periksa kembali akun Anda.'
    };
  } catch (err) {
    return { success: false, message: 'Gagal terhubung ke server: ' + err.toString() };
  }
}

function validateTokenOnServer(token) {
  if (!token) return { valid: false, message: 'Token tidak ditemukan.' };

  var url = buildUrl_(CONFIG.VALIDATE_PATH_PREFIX, CONFIG.VALIDATE_TOKEN_ENDPOINT);
  var options = {
    method: 'post',
    contentType: 'application/x-www-form-urlencoded',
    payload: { 'token': token },
    muteHttpExceptions: true
  };

  try {
    var response = UrlFetchApp.fetch(url, options);
    var statusCode = response.getResponseCode();
    var rawBody = response.getContentText();

    if (statusCode === 200) {
      var json = null;
      try { json = JSON.parse(rawBody); }
      catch (e) {
        return { valid: false, message: 'Respons server tidak valid (bukan JSON): ' + cleanResponseText(rawBody) };
      }
      var isValid = (json.valid === true) || (json.success === true);
      var serverMessage = json.message || json.msg || '';
      if (isValid) return { valid: true, message: serverMessage || 'Token aktif dan valid.' };
      return { valid: false, message: serverMessage || 'Token tidak valid / expired.' };
    }

    if (statusCode === 401) {
      var j = null; try { j = JSON.parse(rawBody); } catch (e) {}
      return { valid: false, message: (j && j.message) || 'Sesi token telah kadaluarsa (Expired).' };
    }
    if (statusCode === 403) return { valid: false, message: 'Akses ditolak (Forbidden).' };
    if (statusCode === 404) return { valid: false, message: 'Endpoint validasi tidak ditemukan (HTTP 404).' };
    if (statusCode >= 500)  return { valid: false, message: 'Server bermasalah (HTTP ' + statusCode + ').' };

    return { valid: false, message: 'Respons server (HTTP ' + statusCode + '): ' + (cleanResponseText(rawBody) || 'Tidak ada pesan.') };
  } catch (err) {
    return { valid: false, message: 'Gagal terhubung ke server: ' + err.toString() };
  }
}

function getValidToken() {
  var token = getStoredToken();
  if (!token) { showLoginModal(); return null; }

  var valResult = validateTokenOnServer(token);
  if (!valResult.valid) {
    PropertiesService.getUserProperties().deleteProperty(CONFIG.TOKEN_KEY);
    clearTokenFromLegendSheet();
    SpreadsheetApp.getUi().alert('Sesi Anda telah berakhir: ' + valResult.message + '\nSilakan login kembali.');
    showLoginModal();
    return null;
  }
  return token;
}

/**
 * ============================================================
 * CEK STATUS TOKEN
 * ============================================================
 */
function checkTokenStatusUserAction() {
  var userProps = PropertiesService.getUserProperties();
  var legendToken = getTokenFromLegendSheet();
  var token = null, tokenSource = '';

  if (legendToken) { token = legendToken; tokenSource = 'Sheet LEGEND!Y1'; }
  else {
    var propToken = userProps.getProperty(CONFIG.TOKEN_KEY);
    if (propToken) { token = propToken; tokenSource = 'PropertiesService (fallback)'; saveTokenToLegendSheet(token, null); }
  }

  if (!token) {
    SpreadsheetApp.getUi().alert(
      'Status: Belum Login\n\nTidak ada token ditemukan di:\n• Sheet LEGEND!Y1\n• PropertiesService\n\nSilakan login terlebih dahulu.'
    );
    showLoginModal();
    return;
  }

  var result = validateTokenOnServer(token);
  var tzInfo = '📅 Client Timezone : ' + getEffectiveTimezone_() + '\n';

  if (result.valid) {
    SpreadsheetApp.getUi().alert(
      '✅ STATUS TOKEN: AKTIF\n\n📌 Sumber Token : ' + tokenSource + '\n🔑 Token Preview: ' + token.substring(0, 25) + '...\n' + tzInfo + '\nToken tersimpan dan terverifikasi valid oleh server.'
    );
  } else {
    var isEndpointIssue = /404|endpoint|HTML|tidak valid \(bukan JSON\)/i.test(result.message);
    var isServerIssue   = /5\d\d|server sedang bermasalah/i.test(result.message);
    var isTokenIssue    = /kadaluarsa|expired|unauthorized|forbidden|invalid|tidak valid/i.test(result.message);

    if (isTokenIssue && !isEndpointIssue && !isServerIssue) {
      userProps.deleteProperty(CONFIG.TOKEN_KEY);
      clearTokenFromLegendSheet();
      SpreadsheetApp.getUi().alert('❌ STATUS TOKEN: TIDAK VALID / EXPIRED\n\n📌 Sumber Token : ' + tokenSource + '\n📝 Detail       : ' + result.message + '\n' + tzInfo + '\nToken lama telah dihapus. Silakan login kembali.');
      showLoginModal();
    } else if (isEndpointIssue || isServerIssue) {
      SpreadsheetApp.getUi().alert('⚠️ GAGAL MEMVALIDASI TOKEN\n\n📌 Sumber Token : ' + tokenSource + '\n📝 Detail       : ' + result.message + '\n' + tzInfo + '\nToken TIDAK dihapus.');
    } else {
      SpreadsheetApp.getUi().alert('⚠️ VALIDASI TOKEN GAGAL\n\n📌 Sumber Token : ' + tokenSource + '\n📝 Detail       : ' + result.message + '\n' + tzInfo);
    }
  }
}

function logout() {
  PropertiesService.getUserProperties().deleteProperty(CONFIG.TOKEN_KEY);
  clearTokenFromLegendSheet();
  SpreadsheetApp.getUi().alert('Anda telah logout. Token pada Sheet LEGEND Y1 dan simpanan sistem telah dihapus.');
}

/* ============================================================
 *              BAGIAN INVOICE (BUY + SELL + DETAIL)
 * ============================================================ */

function readLegendConfig() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(CONFIG.LEGEND_SHEET_NAME);
  if (!sheet) return [];

  var buyCols    = sheet.getRange('E2:F13').getValues();
  var detailCols = sheet.getRange('G2:H13').getValues();
  var sellCols   = sheet.getRange('I2:J13').getValues();

  var months = [];
  for (var i = 0; i < 12; i++) {
    var buyName    = buyCols[i][0];
    var buyStat    = buyCols[i][1];
    var detailName = detailCols[i][0];
    var detailStat = detailCols[i][1];
    var sellName   = sellCols[i][0];
    var sellStat   = sellCols[i][1];

    months.push({
      buySheet:    (buyName    && Number(buyStat)    === 1) ? String(buyName).trim()    : null,
      detailSheet: (detailName && Number(detailStat) === 1) ? String(detailName).trim() : null,
      sellSheet:   (sellName   && Number(sellStat)   === 1) ? String(sellName).trim()   : null
    });
  }
  return months;
}

function readSheetRows(sheetName) {
  if (!sheetName) return [];
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(sheetName);
  if (!sheet) { Logger.log('Sheet tidak ditemukan: ' + sheetName); return []; }
  var data = sheet.getDataRange().getValues();
  return data.length > 1 ? data.slice(1) : [];
}

/**
 * Build invoice dari sheet BUY + DETAIL BUY.
 */
function buildBuyInvoices(mainSheetName, detailSheetName) {
  var mainRows   = readSheetRows(mainSheetName);
  var detailRows = readSheetRows(detailSheetName);

  var detailMap = {};
  detailRows.forEach(function (d) {
    var invNo = String(d[DETAIL_COL.INVOICE_NUMBER] || '').trim();
    if (!invNo) return;
    if (!detailMap[invNo]) detailMap[invNo] = [];
    detailMap[invNo].push({
      description: d[DETAIL_COL.DESCRIPTION] || '',
      qty:         Number(d[DETAIL_COL.QTY])        || 0,
      price:       Number(d[DETAIL_COL.UNIT_PRICE]) || 0
    });
  });

  var invoices = [];
  mainRows.forEach(function (row, idx) {
    var invNo = String(row[INV_COL.INVOICE_NUMBER] || '').trim();
    if (!invNo) return;

    var src = { sheet: mainSheetName, row: idx + 2, kind: 'buy' };

    if (isAmountInvalid_(row[INV_COL.AMOUNT])) {
      invoices.push({
        source_sheet: mainSheetName,
        id:           invNo,
        __invalid:    true,
        __reason:     'amount = 0 / kosong',
        __src:        src
      });
      return;
    }

    var lines = detailMap[invNo];
    if (!lines || lines.length === 0) {
      lines = [{
        description: '',
        qty:   Number(row[INV_COL.QTY])   || 0,
        price: Number(row[INV_COL.PRICE]) || 0
      }];
    }

    invoices.push({
      source_sheet:  mainSheetName,
      id:            invNo,
      customer:      row[INV_COL.BILLED_TO] || '',
      date:          formatDateValue(row[INV_COL.ISSUE_DATE]),
      dueDate:       formatDateValue(row[INV_COL.DUE_DATE]),
      paymentMethod: row[INV_COL.PAYMENT_METHOD] || '',
      status:        row[INV_COL.STATUS] || '',
      fee_pct:       Number(row[INV_COL_EXTRA.FEE_PCT]) || 0,
      fee_amount:    Number(row[INV_COL_EXTRA.FEE_RP])  || 0,
      total:         Number(row[INV_COL_EXTRA.TOTAL])   || 0,
      lines:         lines,
      __src:         src
    });
  });

  return invoices;
}

function buildSellInvoices(mainSheetName) {
  var mainRows = readSheetRows(mainSheetName);
  var invoices = [];

  mainRows.forEach(function (row, idx) {
    var invNo = String(row[INV_COL.INVOICE_NUMBER] || '').trim();
    if (!invNo) return;

    var src = { sheet: mainSheetName, row: idx + 2, kind: 'sell' };

    if (isAmountInvalid_(row[INV_COL.AMOUNT])) {
      invoices.push({
        source_sheet: mainSheetName,
        id:           invNo,
        __invalid:    true,
        __reason:     'amount = 0 / kosong',
        __src:        src
      });
      return;
    }

    invoices.push({
      source_sheet:  mainSheetName,
      id:            invNo,
      customer:      row[INV_COL.BILLED_TO] || '',
      date:          formatDateValue(row[INV_COL.ISSUE_DATE]),
      dueDate:       formatDateValue(row[INV_COL.DUE_DATE]),
      paymentMethod: row[INV_COL.PAYMENT_METHOD] || '',
      status:        row[INV_COL.STATUS] || '',
      fee_pct:       Number(row[INV_COL_EXTRA.FEE_PCT]) || 0,
      fee_amount:    Number(row[INV_COL_EXTRA.FEE_RP])  || 0,
      total:         Number(row[INV_COL_EXTRA.TOTAL])   || 0,
      lines: [{
        description: '',
        qty:   Number(row[INV_COL.QTY])   || 0,
        price: Number(row[INV_COL.PRICE]) || 0
      }],
      __src: src
    });
  });

  return invoices;
}

function buildAllInvoices() {
  var months = readLegendConfig();
  var invoices = [];

  months.forEach(function (m) {
    if (m.buySheet) {
      Array.prototype.push.apply(invoices, buildBuyInvoices(m.buySheet, m.detailSheet));
    }
    if (m.sellSheet) {
      Array.prototype.push.apply(invoices, buildSellInvoices(m.sellSheet));
    }
  });

  return invoices;
}

function splitInvoices_(allInvoices) {
  var valid = [], invalid = [];
  allInvoices.forEach(function (inv) {
    if (inv.__invalid) invalid.push(inv); else valid.push(inv);
  });
  return { valid: valid, invalid: invalid };
}

/**
 * PREVIEW JSON
 */
function previewInvoices() {
  var allInvoices = buildAllInvoices();
  if (allInvoices.length === 0) {
    SpreadsheetApp.getUi().alert('Tidak ada invoice yang siap dikirim.');
    return;
  }

  var parts = splitInvoices_(allInvoices);
  var validList   = parts.valid;
  var invalidList = parts.invalid;

  var cleanValid = validList.map(function (inv) {
    var c = {};
    Object.keys(inv).forEach(function (k) {
      if (k !== '__src' && k !== '__invalid' && k !== '__reason') c[k] = inv[k];
    });
    return c;
  });

  var json = JSON.stringify(cleanValid, null, 2);

  var invalidHtml = '';
  if (invalidList.length > 0) {
    var rows = invalidList.map(function (inv) {
      return '<tr><td>' + (inv.source_sheet || '') + '</td><td>' +
             (inv.id || '') + '</td><td>' + (inv.__reason || '') + '</td></tr>';
    }).join('');
    invalidHtml =
      '<h3 style="color:#c0392b;">⚠️ Invoice Tidak Valid — Dikecualikan dari Sync: ' +
      invalidList.length + '</h3>' +
      '<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;font-size:12px;">' +
      '<tr style="background:#f2dede;"><th>Sheet</th><th>Invoice ID</th><th>Alasan</th></tr>' +
      rows +
      '</table><br>';
  }

  var html = HtmlService.createHtmlOutput(
    '<!DOCTYPE html><html><head><style>' +
    'body{font-family:Arial,sans-serif;padding:10px;margin:0;}' +
    'h3{margin:0 0 8px 0;}' +
    'textarea{width:100%;height:340px;font-family:monospace;font-size:12px;' +
    'box-sizing:border-box;border:1px solid #ccc;border-radius:4px;}' +
    '</style></head><body>' +
    '<h3>✅ Invoice Valid (akan dikirim): ' + cleanValid.length + '</h3>' +
    '<textarea readonly>' + json.replace(/</g, '&lt;') + '</textarea>' +
    '<br>' + invalidHtml +
    '</body></html>'
  ).setWidth(780).setHeight(640);

  SpreadsheetApp.getUi().showModalDialog(html, 'Preview JSON Invoice');
}

/* ============================================================
 *  BATCH LIMIT & UTIL
 * ============================================================ */

function getBatchLimit() {
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName(CONFIG.LEGEND_SHEET_NAME);
    if (!sheet) return CONFIG.DEFAULT_BATCH_LIMIT;

    var raw = sheet.getRange(CONFIG.BATCH_LIMIT_CELL).getValue();
    if (raw === '' || raw === null || raw === undefined) {
      Logger.log('[BATCH] ' + CONFIG.BATCH_LIMIT_CELL + ' kosong. Default = ' + CONFIG.DEFAULT_BATCH_LIMIT);
      return CONFIG.DEFAULT_BATCH_LIMIT;
    }
    var limit = parseInt(raw, 10);
    if (isNaN(limit) || limit <= 0) {
      Logger.log('[BATCH] Nilai tidak valid: "' + raw + '". Default = ' + CONFIG.DEFAULT_BATCH_LIMIT);
      return CONFIG.DEFAULT_BATCH_LIMIT;
    }
    Logger.log('[BATCH] Limit dari LEGEND!' + CONFIG.BATCH_LIMIT_CELL + ' = ' + limit);
    return limit;
  } catch (e) {
    Logger.log('[BATCH] Error: ' + e + '. Default = ' + CONFIG.DEFAULT_BATCH_LIMIT);
    return CONFIG.DEFAULT_BATCH_LIMIT;
  }
}

function chunkArray_(arr, size) {
  var out = [];
  for (var i = 0; i < arr.length; i += size) out.push(arr.slice(i, i + size));
  return out;
}

/**
 * ============================================================
 * SYNC UTAMA — Step 1: capture client TZ via modal, lalu trigger sync
 * ============================================================
 */
function syncInvoicesToServer() {
  var html = HtmlService.createHtmlOutput(
    '<!DOCTYPE html><html><head><style>' +
    'body{font-family:Arial,sans-serif;padding:18px;text-align:center;color:#555;margin:0;}' +
    '.spinner{width:28px;height:28px;border:3px solid #e0e0e0;border-top:3px solid #3498db;' +
    'border-radius:50%;animation:spin 0.8s linear infinite;display:inline-block;margin:6px 0 12px 0;}' +
    '@keyframes spin{100%{transform:rotate(360deg);}}' +
    '.msg{font-size:13px;}' +
    '</style></head><body>' +
    '<div class="spinner"></div>' +
    '<div class="msg">Menyiapkan sinkronisasi...</div>' +
    '<script>' +
    '(function(){' +
    '  var tz = "UTC";' +
    '  try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone || "UTC"; } catch(e){}' +
    '  var triggered = false;' +
    '  function trigger(){' +
    '    if (triggered) return;' +
    '    triggered = true;' +
    '    try { google.script.run.runSyncAfterModal(); } catch(e){}' +
    '    try { google.script.host.close(); } catch(e){}' +
    '  }' +
    '  google.script.run' +
    '    .withSuccessHandler(trigger)' +
    '    .withFailureHandler(trigger)' +
    '    .saveClientTimeZone(tz);' +
    '})();' +
    '</script>' +
    '</body></html>'
  ).setWidth(320).setHeight(140);

  SpreadsheetApp.getUi().showModalDialog(html, '⏳ Sync');
}

/**
 * Dipanggil dari modal setelah TZ tersimpan.
 * Delay kecil agar modal selesai ditutup sebelum ui.alert muncul.
 */
function runSyncAfterModal() {
  Utilities.sleep(300);
  runSyncFlow_();
}

/**
 * Body sync asli.
 */
function runSyncFlow_() {
  var token = getValidToken();
  if (!token) return;

  var allInvoices = buildAllInvoices();
  if (allInvoices.length === 0) {
    SpreadsheetApp.getUi().alert('Tidak ada invoice yang siap dikirim.');
    return;
  }

  var parts = splitInvoices_(allInvoices);
  var validInvoices   = parts.valid;
  var invalidInvoices = parts.invalid;

  var ui = SpreadsheetApp.getUi();
  var limit = getBatchLimit();
  var totalBatches = validInvoices.length > 0
    ? Math.ceil(validInvoices.length / limit)
    : 0;

  var effectiveTz = getEffectiveTimezone_();

  var msg = 'Total invoice valid   : ' + validInvoices.length + '\n';
  if (invalidInvoices.length > 0) {
    msg += 'Invoice tidak valid : ' + invalidInvoices.length +
           '  → akan ditandai "' + CONFIG.INVALID_STATUS_TEXT + '" & TIDAK dikirim\n';
  }
  msg += '\nTimezone Date Sync   : ' + effectiveTz + '\n';
  msg += '\nAkan diproses dalam ' + totalBatches + ' batch ' +
    '(maks ' + limit + ' invoice per batch, dari LEGEND!' + CONFIG.BATCH_LIMIT_CELL + ').\n\n' +
    'Klik NO untuk melihat preview JSON dulu.\n' +
    'Klik YES untuk langsung kirim.';

  var confirm = ui.alert('Konfirmasi Sinkronisasi', msg, ui.ButtonSet.YES_NO);
  if (confirm === ui.Button.NO) { previewInvoices(); return; }
  if (confirm !== ui.Button.YES) return;

  if (invalidInvoices.length > 0) {
    try { markInvalidRows_(invalidInvoices); }
    catch (markErr) { Logger.log('[MARK INVALID ERROR] ' + markErr); }
  }

  if (validInvoices.length === 0) {
    ss_toastDone_();
    ui.alert(
      '⚠️ Tidak Ada Invoice Valid',
      'Semua ' + invalidInvoices.length + ' invoice ditandai "' +
      CONFIG.INVALID_STATUS_TEXT + '".\n\n' +
      'Silakan perbaiki kolom AMOUNT (K) terlebih dahulu, lalu jalankan sync ulang.',
      ui.ButtonSet.OK
    );
    return;
  }

  sendInvoicesPayload_(validInvoices, token, invalidInvoices.length);
}

function ss_toastDone_() {
  try {
    SpreadsheetApp.getActiveSpreadsheet().toast('Selesai.', '✅ Sync', 3);
  } catch (e) { /* ignore */ }
}

function sendInvoicesPayload_(payloadArray, token, invalidCount) {
  var url = buildUrl_(CONFIG.SYNC_PATH_PREFIX, CONFIG.SYNC_ENDPOINT);
  var ui  = SpreadsheetApp.getUi();
  var ss  = SpreadsheetApp.getActiveSpreadsheet();

  var limit   = getBatchLimit();
  var batches = chunkArray_(payloadArray, limit);
  var totalBatches = batches.length;

  Logger.log('[SYNC] ' + payloadArray.length + ' invoice valid (+' +
             (invalidCount || 0) + ' invalid skipped) → ' + totalBatches +
             ' batch (maks ' + limit + ') @ ' + url);

  var okCount   = 0;
  var failCount = 0;
  var totalSent = 0;
  var failDetails = [];

  for (var i = 0; i < totalBatches; i++) {
    var batch = batches[i];
    var label = 'Batch ' + (i + 1) + '/' + totalBatches + ' (' + batch.length + ' invoice)';

    ss.toast(
      'Mengirim ' + label + '...\n' +
      'Berhasil: ' + totalSent + '/' + payloadArray.length + ' invoice',
      '🔄 Sync Berjalan',
      -1
    );

    var sources = batch.map(function (inv) { return inv.__src || null; });
    var cleanBatch = batch.map(function (inv) {
      var c = {};
      Object.keys(inv).forEach(function (k) {
        if (k !== '__src' && k !== '__invalid' && k !== '__reason') c[k] = inv[k];
      });
      return c;
    });

    var options = {
      method: 'post',
      contentType: 'application/json',
      headers: { 'Authorization': 'Bearer ' + token },
      payload: JSON.stringify(cleanBatch),
      muteHttpExceptions: true
    };

    try {
      var resp = UrlFetchApp.fetch(url, options);
      var statusCode = resp.getResponseCode();
      var rawBody = resp.getContentText();

      if (statusCode === 200 || statusCode === 201) {
        okCount++;
        totalSent += batch.length;

        try { writeSyncFlags_(sources); }
        catch (flagErr) { Logger.log('[FLAG ERROR] ' + flagErr); }

        Logger.log('[SUCCESS] ' + label + ' → ' + cleanResponseText(rawBody));

      } else if (statusCode === 401) {
        ss.toast('Sesi habis. Menghentikan proses...', '⚠️ Unauthorized', 5);
        PropertiesService.getUserProperties().deleteProperty(CONFIG.TOKEN_KEY);
        clearTokenFromLegendSheet();

        ui.alert(
          'Sesi Habis di ' + label + '\n\n' +
          'Berhasil terkirim sebelum berhenti: ' + totalSent + ' invoice.\n' +
          'Silakan login kembali, lalu jalankan sync ulang.'
        );
        showLoginModal();
        return;
      } else {
        failCount++;
        failDetails.push(label + ' → HTTP ' + statusCode + ': ' + cleanResponseText(rawBody));
        Logger.log('[FAIL] ' + label + ' → HTTP ' + statusCode);
      }

    } catch (err) {
      failCount++;
      failDetails.push(label + ' → Error: ' + err.toString());
      Logger.log('[ERROR] ' + label + ' → ' + err.toString());
    }

    ss.toast(
      'Selesai ' + label + '\n' +
      'Berhasil: ' + totalSent + '/' + payloadArray.length + ' invoice',
      '🔄 Sync Berjalan',
      -1
    );

    if (i < totalBatches - 1) Utilities.sleep(CONFIG.PAUSE_MS_BETWEEN_BATCHES);
  }

  ss.toast('Selesai.', '✅ Sync', 3);

  var summary =
    'Total invoice valid       : ' + payloadArray.length + '\n';
  if (invalidCount > 0) {
    summary += 'Invoice tidak valid       : ' + invalidCount +
               ' (ditandai "' + CONFIG.INVALID_STATUS_TEXT + '", tidak dikirim)\n';
  }
  summary +=
    'Batch dijalankan          : ' + totalBatches + '\n' +
    'Batas per batch           : ' + limit + ' (LEGEND!' + CONFIG.BATCH_LIMIT_CELL + ')\n' +
    '🌐 Timezone Date Sync     : ' + getEffectiveTimezone_() + '\n' +
    '✅ Batch sukses           : ' + okCount + '\n' +
    '❌ Batch gagal            : ' + failCount + '\n' +
    '📤 Invoice terkirim       : ' + totalSent;

  if (failCount > 0) summary += '\n\nDetail gagal:\n- ' + failDetails.join('\n- ');

  ui.alert(
    failCount === 0 ? '✅ Sinkronisasi Selesai' : '⚠️ Sinkronisasi Selesai (Sebagian Gagal)',
    summary,
    ui.ButtonSet.OK
  );
}

/**
 * ============================================================
 *  TANDAI BARIS INVALID
 *  BUY  → O (kolom 15)
 *  SELL → L (kolom 12)
 * ============================================================
 */
function markInvalidRows_(invalidInvoices) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();

  var groups = {};
  invalidInvoices.forEach(function (inv) {
    if (!inv.__src || !inv.__src.sheet || !inv.__src.row) return;
    var kind = inv.__src.kind || 'sell';
    var key = inv.__src.sheet + '||' + kind;
    if (!groups[key]) groups[key] = { sheet: inv.__src.sheet, kind: kind, rows: [] };
    groups[key].rows.push(inv.__src.row);
  });

  Object.keys(groups).forEach(function (key) {
    var g = groups[key];
    var sheet = ss.getSheetByName(g.sheet);
    if (!sheet) return;

    var cols = getSyncCols_(g.kind);

    g.rows.forEach(function (rowNum) {
      sheet.getRange(rowNum, cols.STATUS)
           .setValue(CONFIG.INVALID_STATUS_TEXT);
    });
  });

  Logger.log('[INVALID] Ditandai "' + CONFIG.INVALID_STATUS_TEXT + '" pada ' +
             invalidInvoices.length + ' baris.');
}

/**
 * ============================================================
 *  TULIS FLAG KE KOLOM SYNC STATUS & DATE SYNC
 *  ------------------------------------------------------------
 *  BUY  → O (status), P (date sync)
 *  SELL → L (status), M (date sync)
 *
 *  Date Sync memakai CLIENT TIMEZONE (dari browser user).
 *  Fallback: Script TZ → 'UTC'.
 * ============================================================
 */
function writeSyncFlags_(sources) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();

  var tz = getEffectiveTimezone_();
  var ts;
  try {
    ts = Utilities.formatDate(new Date(), tz, 'yyyy-MM-dd HH:mm:ss');
  } catch (e) {
    Logger.log('[TZ] Format gagal dengan TZ "' + tz + '", fallback script TZ: ' + e);
    ts = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
  }
  Logger.log('[SYNC FLAGS] Timestamp: ' + ts + ' (TZ: ' + tz + ')');

  var groups = {};
  sources.forEach(function (src) {
    if (!src || !src.sheet || !src.row) return;
    var kind = src.kind || 'sell';
    var key = src.sheet + '||' + kind;
    if (!groups[key]) groups[key] = { sheet: src.sheet, kind: kind, rows: [] };
    groups[key].rows.push(src.row);
  });

  Object.keys(groups).forEach(function (key) {
    var g = groups[key];
    var sheet = ss.getSheetByName(g.sheet);
    if (!sheet) return;

    var cols = getSyncCols_(g.kind);

    g.rows.forEach(function (rowNum) {
      var currentRaw = sheet.getRange(rowNum, cols.STATUS).getValue();
      var current = String(currentRaw || '').trim().toLowerCase();

      var newStatus;
      if (current === 'synced' || current === 'modified') {
        newStatus = 'modified';
      } else {
        newStatus = 'synced';
      }

      sheet.getRange(rowNum, cols.STATUS).setValue(newStatus);
      sheet.getRange(rowNum, cols.MODIFIED_AT).setValue(ts);
    });
  });
}

/**
 * ============================================================
 * HELPER: Format Tanggal ke YYYY-MM-DD
 * (Untuk issueDate / dueDate — pakai Script TZ agar tidak shift)
 * ============================================================
 */
function formatDateValue(val) {
  if (val instanceof Date) {
    return Utilities.formatDate(val, Session.getScriptTimeZone(), 'yyyy-MM-dd');
  }
  return val;
}