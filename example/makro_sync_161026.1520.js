/**
 * ============================================================
 * KONFIGURASI UTAMA
 * Menggunakan 'var' agar terhindar dari SyntaxError re-declaration.
 * ============================================================
 */
var CONFIG = CONFIG || {
  // Base URL tanpa trailing slash di path akhir
  BASE_URL: 'https://itrust-tech.id/web/',

  // Path prefix per endpoint (bisa 'gmr' atau 'mobile')
  LOGIN_PATH_PREFIX:          'mobile',
  VALIDATE_PATH_PREFIX:       'mobile',
  SYNC_PATH_PREFIX:           'gmr',

  // Endpoint name
  LOGIN_ENDPOINT:             'login',
  VALIDATE_TOKEN_ENDPOINT:    'validate-token',
  SYNC_ENDPOINT:              'sync-invoice',

  STATUS_COL_NAME: 'Sync Status',
  TOKEN_KEY: 'YII2_JWT_TOKEN',

  // Konfigurasi Sheet LEGEND
  LEGEND_SHEET_NAME: 'LEGEND',
  LEGEND_TOKEN_CELL: 'Y1',
  LEGEND_FIELD_COL: 'X',
  LEGEND_VALUE_COL: 'Y',
  LEGEND_START_ROW: 1,

  // Batas maksimal invoice per request (dibaca dari LEGEND!S2)
  BATCH_LIMIT_CELL: 'S2',
  DEFAULT_BATCH_LIMIT: 100,
  PAUSE_MS_BETWEEN_BATCHES: 300
};

/**
 * Bangun URL lengkap dari BASE_URL + path prefix + endpoint.
 * Contoh hasil: https://itrust-tech.id/web/gmr/sync-invoice
 */
function buildUrl_(pathPrefix, endpoint) {
  var base = CONFIG.BASE_URL;
  if (base.charAt(base.length - 1) !== '/') base += '/';
  var prefix = pathPrefix ? (pathPrefix + '/') : '';
  return base + prefix + endpoint;
}

/**
 * ============================================================
 * KONFIGURASI KOLOM INVOICE
 * ------------------------------------------------------------
 * Sheet BUY / SELL (A-K):
 *   A Date | B ID | C INVOICE NUMBER | D BILLED TO |
 *   E PAYMENT METHOD | F ISSUE DATE | G DUE DATE | H STATUS |
 *   I QTY | J PRICE | K AMOUNT
 *
 * Sheet DETAIL BUY (A-G):
 *   A DATE | B ID | C INVOICE NUMBER | D DESCRIPTION |
 *   E QTY | F UNIT PRICE | G AMOUNT
 * ============================================================
 */
var INV_COL = {
  DATE: 0, ID: 1, INVOICE_NUMBER: 2, BILLED_TO: 3,
  PAYMENT_METHOD: 4, ISSUE_DATE: 5, DUE_DATE: 6,
  STATUS: 7, QTY: 8, PRICE: 9, AMOUNT: 10
};

var DETAIL_COL = {
  DATE: 0, ID: 1, INVOICE_NUMBER: 2, DESCRIPTION: 3,
  QTY: 4, UNIT_PRICE: 5, AMOUNT: 6
};

/**
 * ============================================================
 * MENU KUSTOM DI GOOGLE SHEETS
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

  s = s.replace(/<[^>]*>/g, ' ');
  s = s.replace(/\s+/g, ' ').trim();

  if (s.length > 200) s = s.substring(0, 200) + '...';

  return s;
}

/**
 * ============================================================
 * HELPER: Simpan Token + User ke LEGEND
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

/**
 * ============================================================
 * HELPER: Ambil Token dari LEGEND!Y1
 * ============================================================
 */
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

/**
 * ============================================================
 * HELPER: Hapus Token + User dari LEGEND
 * ============================================================
 */
function clearTokenFromLegendSheet() {
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var legendSheet = ss.getSheetByName(CONFIG.LEGEND_SHEET_NAME);
    if (legendSheet) legendSheet.getRange('X1:Y20').clearContent();
  } catch (e) {
    Logger.log('Gagal menghapus data dari sheet LEGEND: ' + e.toString());
  }
}

/**
 * ============================================================
 * HELPER: Ambil token (LEGEND!Y1 → PropertiesService)
 * ============================================================
 */
function getStoredToken() {
  var token = getTokenFromLegendSheet();

  if (token) {
    Logger.log('Token diambil dari LEGEND!Y1');
    return token;
  }

  token = PropertiesService.getUserProperties().getProperty(CONFIG.TOKEN_KEY);

  if (token) {
    Logger.log('Token diambil dari PropertiesService (fallback)');
    saveTokenToLegendSheet(token, null);
    return token;
  }

  return null;
}

/**
 * ============================================================
 * LOGIN
 * ============================================================
 */
function processLogin(username, password) {
  var url = buildUrl_(CONFIG.LOGIN_PATH_PREFIX, CONFIG.LOGIN_ENDPOINT);
  var payload = { 'username': username, 'password': password };

  var options = {
    method: 'post',
    contentType: 'application/x-www-form-urlencoded',
    payload: payload,
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
    } else {
      return {
        success: false,
        message: result.message || 'Login gagal. Silakan periksa kembali akun Anda.'
      };
    }
  } catch (err) {
    return {
      success: false,
      message: 'Gagal terhubung ke server: ' + err.toString()
    };
  }
}

/**
 * ============================================================
 * VALIDASI TOKEN KE SERVER
 * ============================================================
 */
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
        return {
          valid: false,
          message: 'Respons server tidak valid (bukan JSON): ' + cleanResponseText(rawBody)
        };
      }

      var isValid = (json.valid === true) || (json.success === true);
      var serverMessage = json.message || json.msg || '';

      if (isValid) return { valid: true, message: serverMessage || 'Token aktif dan valid.' };
      return { valid: false, message: serverMessage || 'Token tidak valid / expired.' };
    }

    if (statusCode === 401) {
      var j = null;
      try { j = JSON.parse(rawBody); } catch (e) {}
      return {
        valid: false,
        message: (j && j.message) || 'Sesi token telah kadaluarsa (Expired).'
      };
    }

    if (statusCode === 403) {
      return { valid: false, message: 'Akses ditolak (Forbidden). Token tidak memiliki izin.' };
    }

    if (statusCode === 404) {
      return {
        valid: false,
        message: 'Endpoint validasi tidak ditemukan (HTTP 404). Periksa VALIDATE_TOKEN_ENDPOINT.'
      };
    }

    if (statusCode >= 500) {
      return { valid: false, message: 'Server sedang bermasalah (HTTP ' + statusCode + '). Coba lagi nanti.' };
    }

    return {
      valid: false,
      message: 'Respons server (HTTP ' + statusCode + '): ' + (cleanResponseText(rawBody) || 'Tidak ada pesan.')
    };
  } catch (err) {
    return { valid: false, message: 'Gagal terhubung ke server: ' + err.toString() };
  }
}

/**
 * ============================================================
 * AMBIL TOKEN VALID
 * ============================================================
 */
function getValidToken() {
  var token = getStoredToken();
  if (!token) { showLoginModal(); return null; }

  var valResult = validateTokenOnServer(token);

  if (!valResult.valid) {
    PropertiesService.getUserProperties().deleteProperty(CONFIG.TOKEN_KEY);
    clearTokenFromLegendSheet();
    SpreadsheetApp.getUi().alert(
      'Sesi Anda telah berakhir: ' + valResult.message + '\nSilakan login kembali.'
    );
    showLoginModal();
    return null;
  }

  return token;
}

/**
 * ============================================================
 * CEK STATUS TOKEN (Menu Manual)
 * ============================================================
 */
function checkTokenStatusUserAction() {
  var userProps = PropertiesService.getUserProperties();
  var legendToken = getTokenFromLegendSheet();
  var token = null;
  var tokenSource = '';

  if (legendToken) {
    token = legendToken;
    tokenSource = 'Sheet LEGEND!Y1';
  } else {
    var propToken = userProps.getProperty(CONFIG.TOKEN_KEY);
    if (propToken) {
      token = propToken;
      tokenSource = 'PropertiesService (fallback)';
      saveTokenToLegendSheet(token, null);
    }
  }

  if (!token) {
    SpreadsheetApp.getUi().alert(
      'Status: Belum Login\n\n' +
      'Tidak ada token ditemukan di:\n' +
      '• Sheet LEGEND!Y1\n' +
      '• PropertiesService\n\n' +
      'Silakan login terlebih dahulu.'
    );
    showLoginModal();
    return;
  }

  var result = validateTokenOnServer(token);

  if (result.valid) {
    SpreadsheetApp.getUi().alert(
      '✅ STATUS TOKEN: AKTIF\n\n' +
      '📌 Sumber Token : ' + tokenSource + '\n' +
      '🔑 Token Preview: ' + token.substring(0, 25) + '...\n\n' +
      'Token tersimpan dan terverifikasi valid oleh server.'
    );
  } else {
    var isEndpointIssue = /404|endpoint|HTML|tidak valid \(bukan JSON\)/i.test(result.message);
    var isServerIssue   = /5\d\d|server sedang bermasalah/i.test(result.message);
    var isTokenIssue    = /kadaluarsa|expired|unauthorized|forbidden|invalid|tidak valid/i.test(result.message);

    if (isTokenIssue && !isEndpointIssue && !isServerIssue) {
      userProps.deleteProperty(CONFIG.TOKEN_KEY);
      clearTokenFromLegendSheet();
      SpreadsheetApp.getUi().alert(
        '❌ STATUS TOKEN: TIDAK VALID / EXPIRED\n\n' +
        '📌 Sumber Token : ' + tokenSource + '\n' +
        '📝 Detail       : ' + result.message + '\n\n' +
        'Token lama telah dihapus. Silakan login kembali.'
      );
      showLoginModal();
    } else if (isEndpointIssue || isServerIssue) {
      SpreadsheetApp.getUi().alert(
        '⚠️ GAGAL MEMVALIDASI TOKEN\n\n' +
        '📌 Sumber Token : ' + tokenSource + '\n' +
        '📝 Detail       : ' + result.message + '\n\n' +
        'Token TIDAK dihapus. Periksa koneksi / konfigurasi server, lalu coba lagi.'
      );
    } else {
      SpreadsheetApp.getUi().alert(
        '⚠️ VALIDASI TOKEN GAGAL\n\n' +
        '📌 Sumber Token : ' + tokenSource + '\n' +
        '📝 Detail       : ' + result.message + '\n\n' +
        'Token TIDAK dihapus. Silakan coba lagi atau hubungi administrator.'
      );
    }
  }
}

/**
 * ============================================================
 * LOGOUT
 * ============================================================
 */
function logout() {
  PropertiesService.getUserProperties().deleteProperty(CONFIG.TOKEN_KEY);
  clearTokenFromLegendSheet();
  SpreadsheetApp.getUi().alert(
    'Anda telah logout. Token pada Sheet LEGEND Y1 dan simpanan sistem telah dihapus.'
  );
}

/* ============================================================
 * ============================================================
 *          BAGIAN INVOICE (BUY + SELL + DETAIL)
 * ============================================================
 * ============================================================ */

/**
 * Baca konfigurasi LEGEND E2:J13
 *   E:F = BUY sheet + status
 *   G:H = DETAIL BUY sheet + status
 *   I:J = SELL sheet + status
 */
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

/**
 * Baca semua baris data dari sheet (skip header).
 */
function readSheetRows(sheetName) {
  if (!sheetName) return [];
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(sheetName);
  if (!sheet) {
    Logger.log('Sheet tidak ditemukan: ' + sheetName);
    return [];
  }
  var data = sheet.getDataRange().getValues();
  return data.length > 1 ? data.slice(1) : [];
}

/**
 * Build invoice object[] dari sheet BUY + DETAIL BUY.
 */
function buildBuyInvoices(mainSheetName, detailSheetName) {
  var mainRows   = readSheetRows(mainSheetName);
  var detailRows = readSheetRows(detailSheetName);

  // Group detail baris berdasarkan INVOICE NUMBER
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
  mainRows.forEach(function (row) {
    var invNo = String(row[INV_COL.INVOICE_NUMBER] || '').trim();
    if (!invNo) return;

    var lines = detailMap[invNo];

    // Fallback kalau detail tidak ada: pakai QTY/PRICE dari sheet utama
    if (!lines || lines.length === 0) {
      lines = [{
        description: '',
        qty:   Number(row[INV_COL.QTY])   || 0,
        price: Number(row[INV_COL.PRICE]) || 0
      }];
    }

    invoices.push({
      id:            invNo,
      customer:      row[INV_COL.BILLED_TO] || '',
      date:          formatDateValue(row[INV_COL.ISSUE_DATE]),
      dueDate:       formatDateValue(row[INV_COL.DUE_DATE]),
      paymentMethod: row[INV_COL.PAYMENT_METHOD] || '',
      status:        row[INV_COL.STATUS] || '',
      lines:         lines
    });
  });

  return invoices;
}

/**
 * Build invoice object[] dari sheet SELL (1 baris = 1 invoice = 1 line).
 */
function buildSellInvoices(mainSheetName) {
  var mainRows = readSheetRows(mainSheetName);
  var invoices = [];

  mainRows.forEach(function (row) {
    var invNo = String(row[INV_COL.INVOICE_NUMBER] || '').trim();
    if (!invNo) return;

    invoices.push({
      id:            invNo,
      customer:      row[INV_COL.BILLED_TO] || '',
      date:          formatDateValue(row[INV_COL.ISSUE_DATE]),
      dueDate:       formatDateValue(row[INV_COL.DUE_DATE]),
      paymentMethod: row[INV_COL.PAYMENT_METHOD] || '',
      status:        row[INV_COL.STATUS] || '',
      lines: [{
        description: '',
        qty:   Number(row[INV_COL.QTY])   || 0,
        price: Number(row[INV_COL.PRICE]) || 0
      }]
    });
  });

  return invoices;
}

/**
 * Build semua invoice dari konfigurasi LEGEND.
 */
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

/**
 * Preview JSON sebelum kirim.
 */
function previewInvoices() {
  var invoices = buildAllInvoices();

  if (invoices.length === 0) {
    SpreadsheetApp.getUi().alert('Tidak ada invoice yang siap dikirim.');
    return;
  }

  var json = JSON.stringify(invoices, null, 2);

  var html = HtmlService.createHtmlOutput(
    '<!DOCTYPE html><html><head><style>' +
    'body{font-family:Arial,sans-serif;padding:10px;margin:0;}' +
    'h3{margin:0 0 8px 0;}' +
    'textarea{width:100%;height:420px;font-family:monospace;font-size:12px;' +
    'box-sizing:border-box;border:1px solid #ccc;border-radius:4px;}' +
    '</style></head><body>' +
    '<h3>Total invoice: ' + invoices.length + '</h3>' +
    '<textarea readonly>' + json.replace(/</g, '&lt;') + '</textarea>' +
    '</body></html>'
  ).setWidth(760).setHeight(580);

  SpreadsheetApp.getUi().showModalDialog(html, 'Preview JSON Invoice');
}

/* ============================================================
 *  BATCH LIMIT (LEGEND!S2, default 100)
 * ============================================================ */

/**
 * Baca batas maksimal invoice per request dari LEGEND!S2.
 * Jika kosong / bukan angka / <= 0 → pakai default 100.
 */
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
    Logger.log('[BATCH] Error baca limit: ' + e + '. Default = ' + CONFIG.DEFAULT_BATCH_LIMIT);
    return CONFIG.DEFAULT_BATCH_LIMIT;
  }
}

/**
 * Pecah array menjadi potongan sebesar `size`.
 */
function chunkArray_(arr, size) {
  var out = [];
  for (var i = 0; i < arr.length; i += size) {
    out.push(arr.slice(i, i + size));
  }
  return out;
}

/**
 * Fungsi utama: Sync semua invoice ke server.
 */
function syncInvoicesToServer() {
  var token = getValidToken();
  if (!token) return;

  var payloadArray = buildAllInvoices();

  if (payloadArray.length === 0) {
    SpreadsheetApp.getUi().alert('Tidak ada invoice yang siap dikirim.');
    return;
  }

  var ui = SpreadsheetApp.getUi();
  var limit = getBatchLimit();
  var totalBatches = Math.ceil(payloadArray.length / limit);

  var confirm = ui.alert(
    'Konfirmasi Sinkronisasi',
    'Total ' + payloadArray.length + ' invoice akan dikirim.\n' +
    'Akan diproses dalam ' + totalBatches + ' batch ' +
    '(maks ' + limit + ' invoice per batch, dari LEGEND!' + CONFIG.BATCH_LIMIT_CELL + ').\n\n' +
    'Klik NO untuk melihat preview JSON dulu.\n' +
    'Klik YES untuk langsung kirim.',
    ui.ButtonSet.YES_NO
  );

  if (confirm === ui.Button.NO) {
    previewInvoices();
    return;
  }
  if (confirm !== ui.Button.YES) return;

  sendInvoicesPayload_(payloadArray, token);
}

/**
 * Helper: kirim payload ke server dalam beberapa batch.
 * Setiap batch max = getBatchLimit() (dari LEGEND!S2, default 100).
 */
function sendInvoicesPayload_(payloadArray, token) {
  var url = buildUrl_(CONFIG.SYNC_PATH_PREFIX, CONFIG.SYNC_ENDPOINT);
  var ui  = SpreadsheetApp.getUi();

  var limit   = getBatchLimit();
  var batches = chunkArray_(payloadArray, limit);

  Logger.log('[SYNC] ' + payloadArray.length + ' invoice → ' + batches.length +
             ' batch (maks ' + limit + ' per batch) @ ' + url);

  var okCount     = 0;
  var failCount   = 0;
  var totalSent   = 0;
  var failDetails = [];

  for (var i = 0; i < batches.length; i++) {
    var batch = batches[i];
    var label = 'Batch ' + (i + 1) + '/' + batches.length +
                ' (' + batch.length + ' invoice)';

    var options = {
      method: 'post',
      contentType: 'application/json',
      headers: { 'Authorization': 'Bearer ' + token },
      payload: JSON.stringify(batch),   // array langsung
      muteHttpExceptions: true
    };

    try {
      var resp = UrlFetchApp.fetch(url, options);
      var statusCode = resp.getResponseCode();

      if (statusCode === 200 || statusCode === 201) {
        okCount++;
        totalSent += batch.length;
        Logger.log('[SUCCESS] ' + label + ' → ' + cleanResponseText(resp.getContentText()));

      } else if (statusCode === 401) {
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
        failDetails.push(label + ' → HTTP ' + statusCode + ': ' +
                         cleanResponseText(resp.getContentText()));
        Logger.log('[FAIL] ' + label + ' → HTTP ' + statusCode);
      }

    } catch (err) {
      failCount++;
      failDetails.push(label + ' → Error: ' + err.toString());
      Logger.log('[ERROR] ' + label + ' → ' + err.toString());
    }

    if (i < batches.length - 1) {
      Utilities.sleep(CONFIG.PAUSE_MS_BETWEEN_BATCHES);
    }
  }

  var summary =
    'Total invoice       : ' + payloadArray.length + '\n' +
    'Batch dijalankan    : ' + batches.length + '\n' +
    'Batas per batch     : ' + limit + ' (LEGEND!' + CONFIG.BATCH_LIMIT_CELL + ')\n' +
    '✅ Batch sukses     : ' + okCount + '\n' +
    '❌ Batch gagal      : ' + failCount + '\n' +
    '📤 Invoice terkirim : ' + totalSent;

  if (failCount > 0) {
    summary += '\n\nDetail gagal:\n- ' + failDetails.join('\n- ');
  }

  ui.alert(
    failCount === 0 ? '✅ Sinkronisasi Selesai' : '⚠️ Sinkronisasi Selesai (Sebagian Gagal)',
    summary,
    ui.ButtonSet.OK
  );
}

/**
 * ============================================================
 * HELPER: Format Tanggal ke YYYY-MM-DD
 * ============================================================
 */
function formatDateValue(val) {
  if (val instanceof Date) {
    return Utilities.formatDate(val, Session.getScriptTimeZone(), 'yyyy-MM-dd');
  }
  return val;
}