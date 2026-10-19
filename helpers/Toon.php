<?php

namespace app\helpers;

/**
 * TOON (Token-Oriented Object Notation) encoder/decoder.
 *
 * Implements the subset of spec v4.4 (github.com/toon-format/spec) needed to
 * carry JSON-shaped payloads in and out of the API:
 *
 *   - objects (nested objects via indentation)
 *   - primitive arrays (inline form)
 *   - arrays of uniform objects (tabular form, incl. nested field groups)
 *   - mixed / non-uniform arrays (list form, objects as list items)
 *
 * Encoder output always uses comma as the document delimiter and 2-space
 * indentation. The decoder also accepts tab (HTAB) and pipe delimiters, CRLF,
 * a leading BOM, comment lines and blank lines.
 *
 * Not implemented: keyed tabular form (objects of uniform objects encode as
 * nested objects instead, which is still valid TOON), and the strict-mode
 * count checks of spec §14.1 (the decoder is lenient).
 */
class Toon
{
    const INDENT = 2;
    const DELIM = ',';

    /**
     * Encode a PHP value (as produced by json_decode($x, true)) as TOON text.
     *
     * @param mixed $value
     * @param array $options indentSize
     * @return string
     */
    public static function encode($value, array $options = [])
    {
        $indent = isset($options['indentSize']) ? (int) $options['indentSize'] : self::INDENT;
        if ($indent < 1) {
            $indent = self::INDENT;
        }

        return implode("\n", self::encodeRoot($value, $indent));
    }

    /**
     * Decode TOON text into a PHP value (list/assoc arrays, scalars).
     *
     * @param string $toon
     * @param array $options indentSize
     * @return mixed
     */
    public static function decode($toon, array $options = [])
    {
        $indent = isset($options['indentSize']) ? (int) $options['indentSize'] : self::INDENT;
        if ($indent < 1) {
            $indent = self::INDENT;
        }

        $lines = self::prepareLines($toon, $indent);
        if ($lines === []) {
            return [];
        }

        $i = 0;
        $first = $lines[0];

        if (self::isHeaderLine($first['text'])) {
            $parsed = self::parseHeaderAndBody($first['text'], $first['depth'] + 1, $lines, $i, $indent);
            if ($parsed['key'] === null) {
                return $parsed['value'];
            }

            return [$parsed['key'] => $parsed['value']];
        }

        return self::parseObject($lines, $i, $first['depth'], $indent);
    }

    /* =====================================================================
     * Encoding
     * ================================================================== */

    private static function encodeRoot($value, $indent)
    {
        if (is_array($value)) {
            if ($value === []) {
                return ['[]'];
            }
            if (self::isList($value)) {
                return self::encodeArrayBody($value, 0, $indent, null, '');
            }
            $lines = [];
            foreach ($value as $k => $v) {
                $lines = array_merge($lines, self::encodeField($k, $v, 0, 1, $indent, ''));
            }

            return $lines;
        }

        return [self::scalar($value)];
    }

    private static function encodeField($key, $value, $lineDepth, $contentDepth, $indent, $prefix)
    {
        $pad = str_repeat(' ', $lineDepth * $indent);
        $keyTok = self::key($key);

        if (is_array($value)) {
            if ($value !== [] && !self::isList($value)) {
                $lines = [$pad . $prefix . $keyTok . ':'];
                foreach ($value as $k => $v) {
                    $lines = array_merge($lines, self::encodeField($k, $v, $contentDepth, $contentDepth + 1, $indent, ''));
                }

                return $lines;
            }

            return self::encodeArrayBody($value, $lineDepth, $indent, $keyTok, $prefix);
        }

        return [$pad . $prefix . $keyTok . ': ' . self::scalar($value)];
    }

    private static function encodeArrayBody(array $arr, $lineDepth, $indent, $keyTok, $prefix)
    {
        $pad = str_repeat(' ', $lineDepth * $indent);
        $head = $keyTok === null ? '' : $keyTok;

        if ($arr === []) {
            return [$pad . $prefix . $head . ': []'];
        }

        if (self::allPrimitive($arr)) {
            return [$pad . $prefix . $head . '[' . count($arr) . ']: ' . implode(self::DELIM, array_map([self::class, 'scalar'], $arr))];
        }

        $fields = self::tabularFields($arr);
        if ($fields !== null) {
            $lines = [$pad . $prefix . $head . '[' . count($arr) . ']{' . self::fieldsHeader($fields) . '}:'];
            $rowPad = str_repeat(' ', ($lineDepth + 1) * $indent);
            foreach ($arr as $row) {
                $cells = self::tabularCells($fields, $row);
                $lines[] = $rowPad . implode(self::DELIM, array_map([self::class, 'scalar'], $cells));
            }

            return $lines;
        }

        $lines = [$pad . $prefix . $head . '[' . count($arr) . ']:'];
        foreach ($arr as $item) {
            $lines = array_merge($lines, self::encodeListItem($item, $lineDepth + 1, $indent));
        }

        return $lines;
    }

    private static function encodeListItem($item, $depth, $indent)
    {
        $pad = str_repeat(' ', $depth * $indent);

        if (!is_array($item)) {
            return [$pad . '- ' . self::scalar($item)];
        }

        if ($item === []) {
            return [$pad . '-'];
        }

        if (self::isList($item)) {
            if (self::allPrimitive($item)) {
                return [$pad . '- [' . count($item) . ']: ' . implode(self::DELIM, array_map([self::class, 'scalar'], $item))];
            }
            $lines = [$pad . '- [' . count($item) . ']:'];
            foreach ($item as $inner) {
                $lines = array_merge($lines, self::encodeListItem($inner, $depth + 1, $indent));
            }

            return $lines;
        }

        // Object list item (§10): first field on the hyphen line, the rest at +1,
        // and any scope opened by a field has its content at +2.
        $lines = [];
        $first = true;
        foreach ($item as $k => $v) {
            if ($first) {
                $lines = array_merge($lines, self::encodeField($k, $v, $depth, $depth + 2, $indent, '- '));
                $first = false;
            } else {
                $lines = array_merge($lines, self::encodeField($k, $v, $depth + 1, $depth + 2, $indent, ''));
            }
        }

        return $lines;
    }

    /**
     * Tabular field descriptors for a uniform array of objects, or null when the
     * array must use list form (spec §9.3).
     */
    private static function tabularFields(array $rows)
    {
        $firstKeys = null;
        foreach ($rows as $row) {
            if (!is_array($row) || self::isList($row) || $row === []) {
                return null;
            }
            $keys = array_keys($row);
            if ($firstKeys === null) {
                $firstKeys = $keys;
                continue;
            }
            if (count($keys) !== count($firstKeys)) {
                return null;
            }
            foreach ($firstKeys as $k) {
                if (!array_key_exists($k, $row)) {
                    return null;
                }
            }
        }

        if ($firstKeys === null || $firstKeys === []) {
            return null;
        }

        $fields = [];
        foreach ($firstKeys as $k) {
            $column = [];
            foreach ($rows as $row) {
                $column[] = $row[$k];
            }
            $descriptor = self::columnDescriptor($k, $column);
            if ($descriptor === null) {
                return null;
            }
            $fields[] = $descriptor;
        }

        return $fields;
    }

    private static function columnDescriptor($name, array $values)
    {
        $allPrimitive = true;
        foreach ($values as $v) {
            if (is_array($v)) {
                $allPrimitive = false;
                break;
            }
        }
        if ($allPrimitive) {
            return ['name' => $name, 'children' => null];
        }

        $first = null;
        foreach ($values as $v) {
            if (!is_array($v) || self::isList($v) || $v === []) {
                return null;
            }
            if ($first === null) {
                $first = $v;
                continue;
            }
            if (count($v) !== count($first)) {
                return null;
            }
            foreach (array_keys($first) as $k) {
                if (!array_key_exists($k, $v)) {
                    return null;
                }
            }
        }

        $children = [];
        foreach ($first as $subKey => $unused) {
            $column = [];
            foreach ($values as $v) {
                $column[] = $v[$subKey];
            }
            $child = self::columnDescriptor($subKey, $column);
            if ($child === null) {
                return null;
            }
            $children[] = $child;
        }

        return ['name' => $name, 'children' => $children];
    }

    private static function fieldsHeader(array $fields)
    {
        $parts = [];
        foreach ($fields as $f) {
            $parts[] = $f['children'] === null ? self::key($f['name']) : self::key($f['name']) . '{' . self::fieldsHeader($f['children']) . '}';
        }

        return implode(self::DELIM, $parts);
    }

    private static function tabularCells(array $fields, array $row)
    {
        $cells = [];
        foreach ($fields as $f) {
            if ($f['children'] === null) {
                $cells[] = $row[$f['name']];
            } else {
                $cells = array_merge($cells, self::tabularCells($f['children'], $row[$f['name']]));
            }
        }

        return $cells;
    }

    /* =====================================================================
     * Encoding - scalars, strings, keys
     * ================================================================== */

    private static function scalar($value)
    {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_float($value)) {
            return self::number($value);
        }

        return self::stringToken((string) $value);
    }

    private static function number($n)
    {
        if (!is_finite($n)) {
            return 'null';
        }
        if ($n == 0) {
            return '0';
        }
        $abs = abs($n);
        if ($abs >= 1e-6 && $abs < 1e21) {
            $s = sprintf('%.15F', $n);
            $s = rtrim(rtrim($s, '0'), '.');
            if ($s === '-0') {
                $s = '0';
            }

            return $s;
        }

        return (string) $n;
    }

    private static function stringToken($s)
    {
        if (!self::needsQuote($s)) {
            return $s;
        }

        return '"' . self::escape($s) . '"';
    }

    private static function needsQuote($s)
    {
        if ($s === '' || $s !== trim($s)) {
            return true;
        }
        if ($s === 'true' || $s === 'false' || $s === 'null') {
            return true;
        }
        if (preg_match('/^[+-]?[0-9]+(?:\.[0-9]+)?(?:e[+-]?[0-9]+)?$/i', $s)) {
            return true;
        }
        if (strpbrk($s, ':"\\[]{}' . self::DELIM) !== false) {
            return true;
        }
        if (preg_match('/[\x00-\x1F]/', $s)) {
            return true;
        }
        if ($s[0] === '-' || $s[0] === '#') {
            return true;
        }

        return false;
    }

    private static function escape($s)
    {
        $out = '';
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $c = $s[$i];
            $o = ord($c);
            if ($c === '\\') {
                $out .= '\\\\';
            } elseif ($c === '"') {
                $out .= '\\"';
            } elseif ($c === "\n") {
                $out .= '\\n';
            } elseif ($c === "\r") {
                $out .= '\\r';
            } elseif ($c === "\t") {
                $out .= '\\t';
            } elseif ($o < 0x20) {
                $out .= sprintf('\\u%04x', $o);
            } else {
                $out .= $c;
            }
        }

        return $out;
    }

    private static function key($k)
    {
        $k = (string) $k;
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $k)) {
            return $k;
        }

        return '"' . self::escape($k) . '"';
    }

    private static function isList(array $a)
    {
        if ($a === []) {
            return true;
        }
        $i = 0;
        foreach ($a as $k => $unused) {
            if ($k !== $i) {
                return false;
            }
            $i++;
        }

        return true;
    }

    private static function allPrimitive(array $a)
    {
        foreach ($a as $v) {
            if (is_array($v)) {
                return false;
            }
        }

        return true;
    }

    /* =====================================================================
     * Decoding
     * ================================================================== */

    private static function prepareLines($toon, $indent)
    {
        $toon = str_replace("\r\n", "\n", (string) $toon);
        $toon = str_replace("\r", "\n", $toon);
        if (strncmp($toon, "\xEF\xBB\xBF", 3) === 0) {
            $toon = substr($toon, 3);
        }

        $out = [];
        foreach (explode("\n", $toon) as $line) {
            if (preg_match('/^( *)#/', $line)) {
                continue;
            }
            $line = rtrim($line, " \t");
            if (trim($line) === '') {
                continue;
            }
            $ws = strspn($line, " \t");
            $indentStr = substr($line, 0, $ws);
            $spaces = 0;
            $len = strlen($indentStr);
            for ($i = 0; $i < $len; $i++) {
                $spaces += $indentStr[$i] === "\t" ? $indent : 1;
            }
            $out[] = ['depth' => intdiv($spaces, $indent), 'text' => substr($line, $ws)];
        }

        return $out;
    }

    private static function isHeaderLine($text)
    {
        $bracket = self::findUnquoted($text, '[');
        if ($bracket === -1) {
            return false;
        }
        $colon = self::findUnquoted($text, ':');

        return $colon !== -1 && $bracket < $colon;
    }

    private static function parseObject(array $lines, &$i, $depth, $indent)
    {
        $obj = [];
        $n = count($lines);

        while ($i < $n) {
            $ln = $lines[$i];
            if ($ln['depth'] !== $depth) {
                break;
            }
            $text = $ln['text'];

            if (self::isHeaderLine($text)) {
                $i++;
                $hv = self::parseHeaderAndBody($text, $depth + 1, $lines, $i, $indent);
                $obj[$hv['key'] === null ? '' : $hv['key']] = $hv['value'];
                continue;
            }

            $colon = self::findUnquoted($text, ':');
            if ($colon === -1) {
                $i++;
                continue;
            }

            $key = self::decodeKey(substr($text, 0, $colon));
            $rest = trim(substr($text, $colon + 1));
            $i++;

            if ($rest === '') {
                if ($i < $n && $lines[$i]['depth'] > $depth) {
                    $obj[$key] = self::parseObject($lines, $i, $lines[$i]['depth'], $indent);
                } else {
                    $obj[$key] = [];
                }
            } elseif ($rest === '[]') {
                $obj[$key] = [];
            } else {
                $obj[$key] = self::parsePrimitive($rest);
            }
        }

        return $obj;
    }

    private static function parseHeaderAndBody($text, $contentDepth, array $lines, &$i, $indent)
    {
        $h = self::parseHeaderLine($text);
        if ($h === null) {
            return ['key' => null, 'value' => self::parsePrimitive(trim($text))];
        }

        if ($h['fields'] !== null) {
            $value = $h['keyed']
                ? self::parseEntryRows($lines, $i, $contentDepth, $h['delim'], $h['fields'])
                : self::parseRows($lines, $i, $contentDepth, $h['delim'], $h['fields']);

            return ['key' => $h['key'], 'value' => $value];
        }

        if ($h['inline'] !== '') {
            $values = array_map(function ($v) {
                return self::parsePrimitive(trim($v));
            }, self::splitTopLevel($h['inline'], $h['delim']));

            return ['key' => $h['key'], 'value' => $values];
        }

        return ['key' => $h['key'], 'value' => self::parseListItems($lines, $i, $contentDepth, $indent)];
    }

    private static function parseHeaderLine($text)
    {
        $bracket = self::findUnquoted($text, '[');
        $colon = self::findUnquoted($text, ':');
        if ($bracket === -1 || $colon === -1 || $bracket > $colon) {
            return null;
        }

        $keyRaw = trim(substr($text, 0, $bracket));
        $key = $keyRaw === '' ? null : self::decodeKey($keyRaw);

        $close = self::findUnquoted($text, ']', $bracket + 1);
        if ($close === -1) {
            return null;
        }

        $inside = substr($text, $bracket + 1, $close - $bracket - 1);
        $keyed = strpos($inside, ':') !== false;
        $delim = self::DELIM;

        if ($keyed) {
            $parts = explode(':', $inside, 2);
            $lenStr = $parts[0];
            $after = $parts[1];
        } else {
            $lenStr = $inside;
            $after = '';
            $last = substr($inside, -1);
            if ($last === "\t" || $last === '|') {
                $lenStr = substr($inside, 0, -1);
                $after = $last;
            }
        }

        if ($after !== '') {
            $delim = $after[0] === "\t" ? "\t" : $after[0];
        }

        $p = $close + 1;
        $textLen = strlen($text);
        while ($p < $textLen && $text[$p] === ' ') {
            $p++;
        }

        $fields = null;
        if ($p < $textLen && $text[$p] === '{') {
            $end = self::findMatchingBrace($text, $p);
            $fieldsStr = substr($text, $p + 1, $end - $p - 1);
            $fields = self::parseFields($fieldsStr, $delim);
            $p = $end + 1;
            while ($p < $textLen && $text[$p] === ' ') {
                $p++;
            }
        }

        if ($p >= $textLen || $text[$p] !== ':') {
            return null;
        }

        return [
            'key' => $key,
            'length' => (int) $lenStr,
            'delim' => $delim,
            'keyed' => $keyed,
            'fields' => $fields,
            'inline' => trim(substr($text, $p + 1)),
        ];
    }

    private static function parseFields($str, $delim)
    {
        $fields = [];
        foreach (self::splitTopLevel($str, $delim) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $brace = self::findUnquoted($part, '{');
            if ($brace !== -1) {
                $name = trim(substr($part, 0, $brace));
                $end = self::findMatchingBrace($part, $brace);
                $inner = substr($part, $brace + 1, $end - $brace - 1);
                $fields[] = ['name' => self::decodeKey($name), 'children' => self::parseFields($inner, $delim)];
            } else {
                $fields[] = ['name' => self::decodeKey($part), 'children' => null];
            }
        }

        return $fields;
    }

    private static function assignFields(array $fields, array $cells, &$idx)
    {
        $obj = [];
        foreach ($fields as $f) {
            if ($f['children'] === null) {
                $obj[$f['name']] = self::parsePrimitive(isset($cells[$idx]) ? trim($cells[$idx]) : '');
                $idx++;
            } else {
                $obj[$f['name']] = self::assignFields($f['children'], $cells, $idx);
            }
        }

        return $obj;
    }

    private static function parseRows(array $lines, &$i, $contentDepth, $delim, array $fields)
    {
        $rows = [];
        $n = count($lines);
        while ($i < $n && $lines[$i]['depth'] === $contentDepth) {
            $cells = self::splitTopLevel($lines[$i]['text'], $delim);
            $idx = 0;
            $rows[] = self::assignFields($fields, $cells, $idx);
            $i++;
        }

        return $rows;
    }

    private static function parseEntryRows(array $lines, &$i, $contentDepth, $delim, array $fields)
    {
        $obj = [];
        $n = count($lines);
        while ($i < $n && $lines[$i]['depth'] === $contentDepth) {
            $text = $lines[$i]['text'];
            $colon = self::findUnquoted($text, ':');
            if ($colon === -1) {
                break;
            }
            $key = self::decodeKey(substr($text, 0, $colon));
            $cells = self::splitTopLevel(trim(substr($text, $colon + 1)), $delim);
            $idx = 0;
            $obj[$key] = self::assignFields($fields, $cells, $idx);
            $i++;
        }

        return $obj;
    }

    private static function parseListItems(array $lines, &$i, $itemDepth, $indent)
    {
        $items = [];
        $n = count($lines);
        while ($i < $n && $lines[$i]['depth'] === $itemDepth) {
            $text = $lines[$i]['text'];
            if ($text !== '-' && strpos($text, '- ') !== 0) {
                break;
            }
            $items[] = self::parseListItem($lines, $i, $itemDepth, $indent);
        }

        return $items;
    }

    private static function parseListItem(array $lines, &$i, $depth, $indent)
    {
        $rest = trim(substr($lines[$i]['text'], 1));

        if ($rest === '' || $rest === '[]') {
            $i++;

            return [];
        }

        if (self::isHeaderLine($rest)) {
            $h = self::parseHeaderLine($rest);
            if ($h !== null && $h['key'] === null) {
                $i++;

                return self::parseHeaderAndBody($rest, $depth + 1, $lines, $i, $indent)['value'];
            }
            $i++;

            return self::parseListItemObject($rest, $depth, $lines, $i, $indent);
        }

        if (self::findUnquoted($rest, ':') === -1) {
            $i++;

            return self::parsePrimitive($rest);
        }

        $i++;

        return self::parseListItemObject($rest, $depth, $lines, $i, $indent);
    }

    private static function parseListItemObject($firstText, $d, array $lines, &$i, $indent)
    {
        $obj = [];
        $n = count($lines);

        if (self::isHeaderLine($firstText)) {
            $hv = self::parseHeaderAndBody($firstText, $d + 2, $lines, $i, $indent);
            $obj[$hv['key'] === null ? '' : $hv['key']] = $hv['value'];
        } else {
            $c = self::findUnquoted($firstText, ':');
            $key = self::decodeKey(substr($firstText, 0, $c));
            $after = trim(substr($firstText, $c + 1));
            $obj[$key] = self::parseValueOrScope($after, $d, $lines, $i, $indent);
        }

        while ($i < $n && $lines[$i]['depth'] === $d + 1) {
            $text = $lines[$i]['text'];
            if (self::isHeaderLine($text)) {
                $i++;
                $hv = self::parseHeaderAndBody($text, $d + 2, $lines, $i, $indent);
                $obj[$hv['key'] === null ? '' : $hv['key']] = $hv['value'];
                continue;
            }
            $c = self::findUnquoted($text, ':');
            if ($c === -1) {
                break;
            }
            $key = self::decodeKey(substr($text, 0, $c));
            $after = trim(substr($text, $c + 1));
            $i++;
            $obj[$key] = self::parseValueOrScope($after, $d, $lines, $i, $indent);
        }

        return $obj;
    }

    private static function parseValueOrScope($after, $d, array $lines, &$i, $indent)
    {
        if ($after === '') {
            if ($i < count($lines) && $lines[$i]['depth'] > $d + 1) {
                return self::parseObject($lines, $i, $lines[$i]['depth'], $indent);
            }

            return [];
        }
        if ($after === '[]') {
            return [];
        }

        return self::parsePrimitive($after);
    }

    private static function parsePrimitive($token)
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }
        if ($token[0] === '"') {
            return self::unescape(substr($token, 1, -1));
        }
        if ($token === 'true') {
            return true;
        }
        if ($token === 'false') {
            return false;
        }
        if ($token === 'null') {
            return null;
        }
        if (preg_match('/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?$/', $token)) {
            if (strpos($token, '.') === false && stripos($token, 'e') === false) {
                return (int) $token;
            }

            return (float) $token;
        }

        return $token;
    }

    private static function decodeKey($token)
    {
        $token = trim($token);
        if ($token !== '' && $token[0] === '"' && substr($token, -1) === '"') {
            return self::unescape(substr($token, 1, -1));
        }

        return $token;
    }

    private static function unescape($s)
    {
        $out = '';
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $c = $s[$i];
            if ($c !== '\\' || $i + 1 >= $len) {
                $out .= $c;
                continue;
            }
            $n = $s[++$i];
            switch ($n) {
                case '\\':
                    $out .= '\\';
                    break;
                case '"':
                    $out .= '"';
                    break;
                case 'n':
                    $out .= "\n";
                    break;
                case 'r':
                    $out .= "\r";
                    break;
                case 't':
                    $out .= "\t";
                    break;
                case 'u':
                    $hex = substr($s, $i + 1, 4);
                    $out .= mb_convert_encoding(pack('H*', $hex), 'UTF-8', 'UTF-16BE');
                    $i += 4;
                    break;
                default:
                    $out .= $n;
            }
        }

        return $out;
    }

    private static function findUnquoted($s, $needle, $from = 0)
    {
        $inQuotes = false;
        $len = strlen($s);
        for ($i = $from; $i < $len; $i++) {
            $c = $s[$i];
            if ($inQuotes) {
                if ($c === '\\') {
                    $i++;
                    continue;
                }
                if ($c === '"') {
                    $inQuotes = false;
                }
                continue;
            }
            if ($c === '"') {
                $inQuotes = true;
                continue;
            }
            if ($c === $needle) {
                return $i;
            }
        }

        return -1;
    }

    private static function findMatchingBrace($s, $open)
    {
        $depth = 0;
        $inQuotes = false;
        $len = strlen($s);
        for ($i = $open; $i < $len; $i++) {
            $c = $s[$i];
            if ($inQuotes) {
                if ($c === '\\') {
                    $i++;
                    continue;
                }
                if ($c === '"') {
                    $inQuotes = false;
                }
                continue;
            }
            if ($c === '"') {
                $inQuotes = true;
            } elseif ($c === '{') {
                $depth++;
            } elseif ($c === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return $len - 1;
    }

    private static function splitTopLevel($s, $delim)
    {
        $parts = [];
        $cur = '';
        $depth = 0;
        $inQuotes = false;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $c = $s[$i];
            if ($inQuotes) {
                $cur .= $c;
                if ($c === '\\' && $i + 1 < $len) {
                    $cur .= $s[++$i];
                } elseif ($c === '"') {
                    $inQuotes = false;
                }
                continue;
            }
            if ($c === '"') {
                $inQuotes = true;
                $cur .= $c;
            } elseif ($c === '{') {
                $depth++;
                $cur .= $c;
            } elseif ($c === '}') {
                $depth--;
                $cur .= $c;
            } elseif ($c === $delim && $depth === 0) {
                $parts[] = $cur;
                $cur = '';
            } else {
                $cur .= $c;
            }
        }
        $parts[] = $cur;

        return $parts;
    }
}
