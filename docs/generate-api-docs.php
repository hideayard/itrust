<?php
/**
 * Regenerates the API reference for the integration controllers.
 *
 *   php docs/generate-api-docs.php
 *
 * It reads each controller's source + the urlManager rules in config/web.php
 * and writes:
 *
 *   docs/api/<controller>.md            human reference + curl examples
 *   web/docs/openapi/<controller>.json  one OpenAPI 3 spec per controller
 *   web/docs/index.html                 Swagger UI index over every spec
 *   docs/api/README.md                  index of the generated docs
 *
 * The GMR spec (web/docs/openapi/gmr.json) and docs/gmr-api.md are hand
 * written and are intentionally not regenerated here.
 */

define('YII_ENV', 'prod');
define('YII_ENV_DEV', false);
define('YII_DEBUG', false);

$root = dirname(__DIR__);
$baseUrl = 'https://itrust-tech.id/web';

$controllers = [
    'MobileController',
    'EaController',
    'Mt4AccountController',
    'TelemetryDeviceController',
    'ScrapedDataController',
    'WebhookController',
    'BotController',
    'PublicController',
    'InverterController',
    'DashboardInverterController',
];

$config = require $root . '/config/web.php';
$rules = $config['components']['urlManager']['rules'] ?? [];

/* -------------------------------------------------------------------------
 * Route rules: map a controller/action route to the pretty paths that reach it
 * ---------------------------------------------------------------------- */
$ruleMap = [];
foreach ($rules as $key => $route) {
    if (!is_string($key) || !is_string($route)) {
        continue;
    }
    $method = 'ANY';
    $pattern = $key;
    if (preg_match('/^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)\s+(.+)$/i', $key, $m)) {
        $method = strtoupper($m[1]);
        $pattern = $m[2];
    }
    $ruleMap[$route][] = ['method' => $method, 'pattern' => trim($pattern, '/')];
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

function to_id(string $name): string
{
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
}

function controller_id(string $class): string
{
    return to_id((string) preg_replace('/Controller$/', '', $class));
}

/** Split source into method chunks with their offsets. */
function parse_methods(string $src): array
{
    if (!preg_match_all('/^[ \t]*(public|protected|private)\s+function\s+(\w+)\s*\(([^)]*)\)/m', $src, $m, PREG_OFFSET_CAPTURE)) {
        return [];
    }

    $methods = [];
    $count = count($m[0]);
    for ($i = 0; $i < $count; $i++) {
        $start = $m[0][$i][1];
        $end = $i + 1 < $count ? $m[0][$i + 1][1] : strlen($src);
        $methods[] = [
            'visibility' => $m[1][$i][0],
            'name' => $m[2][$i][0],
            'params' => $m[3][$i][0],
            'body' => substr($src, $start, $end - $start),
            'before' => substr($src, 0, $start),
        ];
    }

    return $methods;
}

/** The docblock immediately above a method, or null. */
function preceding_doc(string $before): ?string
{
    $trimmed = rtrim($before);
    if (substr($trimmed, -2) !== '*/') {
        return null;
    }

    $open = strrpos($trimmed, '/**');
    if ($open === false) {
        return null;
    }

    return substr($trimmed, $open + 3, -2);
}

/** Split a docblock into [summary, description]. */
function doc_parts(?string $doc): array
{
    if ($doc === null || trim($doc) === '') {
        return ['', ''];
    }

    $lines = [];
    foreach (preg_split('/\r\n|\n|\r/', $doc) as $line) {
        $line = trim(ltrim(trim($line), '*'));
        $lines[] = $line;
    }

    $summary = '';
    $description = [];
    $seen = false;
    foreach ($lines as $line) {
        if (strpos(ltrim($line), '@') === 0) {
            continue;
        }
        if (!$seen) {
            if ($line === '') {
                continue;
            }
            $summary = $line;
            $seen = true;
            continue;
        }
        $description[] = $line;
    }

    return [$summary, trim(implode("\n", $description))];
}

/** Signature params: name => ['name','default','optional'] */
function signature_params(string $params): array
{
    $out = [];
    if (trim($params) === '') {
        return $out;
    }

    foreach (explode(',', $params) as $raw) {
        $raw = trim($raw);
        if ($raw === '') {
            continue;
        }
        if (!preg_match('/^\$(\w+)\s*(=\s*(.*))?$/s', $raw, $m)) {
            continue;
        }
        $out[] = [
            'name' => $m[1],
            'default' => $m[3] ?? null,
            'optional' => isset($m[2]) && $m[2] !== '',
        ];
    }

    return $out;
}

/** Request keys + flags read from a method body. */
function request_info(string $body): array
{
    $post = [];
    $get = [];
    if (preg_match_all('/->post\(\s*[\'"]([^\'"]+)[\'"]/', $body, $m)) {
        $post = $m[1];
    }
    if (preg_match_all('/->get\(\s*[\'"]([^\'"]+)[\'"]/', $body, $m)) {
        $get = $m[1];
    }

    return [
        'post' => array_values(array_unique($post)),
        'get' => array_values(array_unique($get)),
        'raw' => (bool) preg_match('/getRawBody\(\)|getBodyParams\(\)/', $body),
        'jwt' => (bool) preg_match('/JwtHelper::validate|getTokenFromRequest|UnauthorizedHttpException|HttpBearerAuth/', $body),
    ];
}

/** Turn a route pattern into an OpenAPI path + its parameter names. */
function openapi_path(string $pattern): array
{
    $names = [];
    $path = preg_replace_callback(
        '/<\s*(\w+)\s*(?::[^>]*)?>/',
        function ($m) use (&$names) {
            $names[] = $m[1];
            return '{' . $m[1] . '}';
        },
        $pattern
    );

    return ['/' . $path, $names];
}

function md_inline(string $text): string
{
    return trim(str_replace(["\r", "\n", '|'], [' ', ' ', '\\|'], $text));
}

/* -------------------------------------------------------------------------
 * Build
 * ---------------------------------------------------------------------- */

@mkdir($root . '/docs/api', 0777, true);
@mkdir($root . '/web/docs/openapi', 0777, true);

$generated = [];

foreach ($controllers as $class) {
    $file = $root . '/controllers/' . $class . '.php';
    if (!is_file($file)) {
        fwrite(STDERR, "skip: $class (no file)\n");
        continue;
    }

    $src = file_get_contents($file);
    $cid = controller_id($class);
    // A controller that installs HttpBearerAuth protects every action except
    // the CORS preflight; otherwise only the actions that validate a token.
    $controllerBearer = strpos($src, 'HttpBearerAuth') !== false;

    $classDoc = '';
    $classPos = strpos($src, 'class ' . $class);
    if ($classPos !== false) {
        [$classDoc] = doc_parts(preceding_doc(substr($src, 0, $classPos)));
    }

    $endpoints = [];
    foreach (parse_methods($src) as $method) {
        if ($method['visibility'] !== 'public' || strpos($method['name'], 'action') !== 0) {
            continue;
        }

        $actionId = to_id(substr($method['name'], 6));
        $route = $cid . '/' . $actionId;
        $explicit = $ruleMap[$route] ?? [];

        [$summary, $description] = doc_parts(preceding_doc($method['before']));
        $req = request_info($method['body']);
        $params = signature_params($method['params']);

        // Precedence: explicit route verb > verb named in the docblock > inferred.
        $method_verb = (!$req['post'] && !$req['raw'] && $req['get']) ? 'GET' : 'POST';
        if (preg_match('/^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)\b/i', $summary, $mv)) {
            $method_verb = strtoupper($mv[1]);
        }
        foreach ($explicit as $rule) {
            if ($rule['method'] !== 'ANY') {
                $method_verb = $rule['method'];
                break;
            }
        }

        // Default route always works; pretty aliases may also exist.
        $paths = [['path' => '/' . $route, 'params' => []]];
        $seenPaths = ['/' . $route => true];
        foreach ($explicit as $rule) {
            [$apiPath, $names] = openapi_path($rule['pattern']);
            if (isset($seenPaths[$apiPath])) {
                continue;
            }
            $seenPaths[$apiPath] = true;
            $paths[] = ['path' => $apiPath, 'params' => $names];
        }

        $endpoints[] = [
            'actionId' => $actionId,
            'verb' => $method_verb,
            'paths' => $paths,
            'summary' => $summary,
            'description' => $description,
            'params' => $params,
            'post' => $req['post'],
            'getParams' => $req['get'],
            'raw' => $req['raw'],
            'jwt' => $actionId !== 'options' && ($req['jwt'] || $controllerBearer),
        ];
    }

    usort($endpoints, function ($a, $b) {
        return strcmp($a['actionId'], $b['actionId']);
    });

    $generated[] = [
        'class' => $class,
        'cid' => $cid,
        'doc' => $classDoc,
        'jwt' => $controllerBearer,
        'endpoints' => $endpoints,
    ];

    /* ---------- Markdown ---------- */
    $md = ['# ' . $class . ' API', ''];
    if ($classDoc !== '') {
        $md[] = $classDoc;
        $md[] = '';
    }
    $md[] = '- Base URL: `' . $baseUrl . '`';
    $md[] = '- Controller id: `' . $cid . '`';
    $md[] = '- Auth: ' . ($controllerBearer
        ? 'requires a JWT bearer token on every endpoint except `options` (`Authorization: Bearer <jwt>`).'
        : 'only the endpoints flagged below need a JWT bearer token; others take a `license`/`id` parameter.');
    $md[] = '- Generated from source by `docs/generate-api-docs.php`.';
    $md[] = '';
    $md[] = '## Endpoints';
    $md[] = '';
    $md[] = '| Method | Path | Action | Summary |';
    $md[] = '| ------ | ---- | ------ | ------- |';
    foreach ($endpoints as $e) {
        $md[] = '| ' . $e['verb'] . ' | `' . $e['paths'][0]['path'] . '` | `' . $e['actionId'] . '` | ' . md_inline($e['summary']) . ' |';
    }
    $md[] = '';
    $md[] = '## Details';
    $md[] = '';

    foreach ($endpoints as $e) {
        $md[] = '### ' . $e['actionId'];
        $md[] = '';
        $md[] = '`' . $e['verb'] . ' ' . implode('`  `', array_map(function ($p) {
            return $p['path'];
        }, $e['paths'])) . '`';
        $md[] = '';
        if ($e['summary'] !== '') {
            $md[] = $e['summary'];
            $md[] = '';
        }
        if ($e['description'] !== '') {
            $md[] = $e['description'];
            $md[] = '';
        }

        $notes = [];
        if ($e['jwt']) {
            $notes[] = 'requires a JWT bearer token';
        }
        if ($e['raw']) {
            $notes[] = 'reads a JSON request body';
        }
        if ($notes) {
            $md[] = '- ' . implode('; ', $notes) . '.';
        }
        if ($e['getParams']) {
            $md[] = '- Query params: ' . implode(', ', array_map(function ($k) {
                return '`' . $k . '`';
            }, $e['getParams'])) . '.';
        }
        if ($e['post']) {
            $md[] = '- Body/params: ' . implode(', ', array_map(function ($k) {
                return '`' . $k . '`';
            }, $e['post'])) . '.';
        }
        if ($e['params']) {
            $md[] = '- Signature: ' . implode(', ', array_map(function ($p) {
                return '`$' . $p['name'] . '`' . ($p['optional'] ? '' : ' (required)');
            }, $e['params'])) . '.';
        }
        if ($e['getParams'] || $e['post'] || $e['raw'] || $e['jwt'] || $e['params']) {
            $md[] = '';
        }

        // curl
        $url = $baseUrl . $e['paths'][0]['path'];
        $query = [];
        foreach ($e['getParams'] as $k) {
            $query[$k] = '';
        }
        foreach ($e['params'] as $p) {
            $query[$p['name']] = '';
        }
        $qs = http_build_query($query);

        if ($e['verb'] === 'GET') {
            $curl = 'curl -s -H "Authorization: Bearer <token>" "' . $url . ($qs ? '?' . $qs : '') . '"';
        } else {
            $body = [];
            foreach ($e['post'] as $k) {
                $body[$k] = '';
            }
            $lines = ['curl -s -X ' . $e['verb'] . ' "' . $url . ($qs ? '?' . $qs : '') . '"'];
            $lines[] = '  -H "Authorization: Bearer <token>"';
            if (!empty($body) || $e['raw']) {
                $lines[] = '  -H "Content-Type: application/json"';
                $lines[] = "  -d '" . json_encode($body ?: new stdClass(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "'";
            }
            $curl = implode(" \\\n", $lines);
        }
        $md[] = '```bash';
        $md[] = $curl;
        $md[] = '```';
        $md[] = '';
    }

    file_put_contents($root . '/docs/api/' . $cid . '.md', implode("\n", $md) . "\n");

    /* ---------- OpenAPI ---------- */
    $paths = [];
    foreach ($endpoints as $e) {
        foreach ($e['paths'] as $i => $entry) {
            $path = $entry['path'];
            $pathParams = $entry['params'];

            $parameters = [];
            foreach ($pathParams as $name) {
                $parameters[] = [
                    'name' => $name,
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => 'string'],
                ];
            }
            foreach ($e['getParams'] as $name) {
                $parameters[] = [
                    'name' => $name,
                    'in' => 'query',
                    'required' => false,
                    'schema' => ['type' => 'string'],
                ];
            }
            foreach ($e['params'] as $p) {
                if (in_array($p['name'], $pathParams, true)) {
                    continue;
                }
                $parameters[] = [
                    'name' => $p['name'],
                    'in' => 'query',
                    'required' => !$p['optional'],
                    'schema' => ['type' => 'string'],
                ];
            }

            $operation = [
                'tags' => [$class],
                'operationId' => $e['actionId'] . ($i > 0 ? '-' . $i : ''),
                'summary' => $e['summary'] !== '' ? $e['summary'] : $e['actionId'],
            ];
            if ($e['description'] !== '') {
                $operation['description'] = $e['description'];
            }
            if ($parameters) {
                $operation['parameters'] = $parameters;
            }
            if ($e['verb'] !== 'GET') {
                $props = [];
                foreach ($e['post'] as $k) {
                    $props[$k] = ['type' => 'string'];
                }
                if ($props) {
                    $operation['requestBody'] = [
                        'required' => true,
                        'content' => [
                            'application/json' => ['schema' => ['type' => 'object', 'properties' => $props]],
                        ],
                    ];
                } elseif ($e['raw']) {
                    $operation['requestBody'] = [
                        'required' => true,
                        'content' => ['application/json' => ['schema' => ['type' => 'object', 'additionalProperties' => true]]],
                    ];
                }
            }

            $responses = [
                '200' => ['description' => 'OK'],
            ];
            if ($e['jwt']) {
                $responses['401'] = ['description' => 'Unauthorized'];
            }
            $operation['responses'] = $responses;

            if (!$controllerBearer && $e['jwt']) {
                $operation['security'] = [['bearerAuth' => []]];
            }

            $paths[$path][strtolower($e['verb'])] = $operation;
        }
    }

    $spec = [
        'openapi' => '3.0.3',
        'info' => [
            'title' => $class . ' API',
            'version' => '1.0.0',
            'description' => ($classDoc !== '' ? $classDoc . "\n\n" : '') . "Generated from controllers/$class.php by docs/generate-api-docs.php.",
        ],
        'servers' => [
            ['url' => '/web', 'description' => 'This host (app served under /web)'],
            ['url' => $baseUrl, 'description' => 'Production'],
        ],
        'tags' => [['name' => $class]],
        'paths' => $paths,
    ];
    $spec['components']['securitySchemes']['bearerAuth'] = [
        'type' => 'http',
        'scheme' => 'bearer',
        'description' => 'JWT (jwtSecret in config/params.php).',
    ];
    if ($controllerBearer) {
        $spec['security'] = [['bearerAuth' => []]];
    }

    file_put_contents(
        $root . '/web/docs/openapi/' . $cid . '.json',
        json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
    );

    fwrite(STDOUT, sprintf("%-26s %2d endpoints\n", $class, count($endpoints)));
}

/* -------------------------------------------------------------------------
 * Markdown index
 * ---------------------------------------------------------------------- */
$readme = ['# API reference', '', 'Generated by `php docs/generate-api-docs.php`.', '', '| Controller | Endpoints | Docs | Spec |', '| ---------- | --------- | ---- | ---- |'];
$readme[] = '| GMR (hand written) | 6 | [gmr-api.md](../gmr-api.md) | [gmr.json](../../web/docs/openapi/gmr.json) |';
foreach ($generated as $g) {
    $readme[] = '| ' . $g['class'] . ' | ' . count($g['endpoints']) . ' | [' . $g['cid'] . '.md](./' . $g['cid'] . '.md) | [' . $g['cid'] . '.json](../../web/docs/openapi/' . $g['cid'] . '.json) |';
}
file_put_contents($root . '/docs/api/README.md', implode("\n", $readme) . "\n");

/* -------------------------------------------------------------------------
 * Swagger UI index over every spec in web/docs/openapi
 * ---------------------------------------------------------------------- */
$specs = glob($root . '/web/docs/openapi/*.json') ?: [];
$entries = [];
foreach ($specs as $specFile) {
    $name = basename($specFile, '.json');
    $json = json_decode((string) file_get_contents($specFile), true);
    $title = $json['info']['title'] ?? $name;
    $entries[] = ['name' => $title, 'url' => './openapi/' . basename($specFile)];
}
usort($entries, function ($a, $b) {
    if (stripos($a['name'], 'GMR') !== false) {
        return -1;
    }
    if (stripos($b['name'], 'GMR') !== false) {
        return 1;
    }
    return strcasecmp($a['name'], $b['name']);
});

$urlsJs = json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
$primaryJs = json_encode($entries[0]['name'] ?? '', JSON_UNESCAPED_SLASHES);

$index = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>API docs - Swagger UI</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
  <style>
    body { margin: 0; background: #fafafa; }
    .topbar { display: none; }
    #auth-hint {
      font: 13px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: #fff3cd; border-bottom: 1px solid #ffe69c; padding: 8px 16px; color: #664d03;
    }
    #auth-hint code { background: #fff; padding: 1px 5px; border-radius: 4px; }
  </style>
</head>
<body>
  <div id="auth-hint">
    Pick a spec from the top-right dropdown. Authenticated calls need a bearer token:
    click <strong>Authorize</strong> and paste the token (GMR uses the
    <code>gmrSyncToken</code> from <code>config/params.php</code>; mobile/JWT routes use the token from <code>mobile/login</code>).
  </div>
  <div id="swagger-ui"></div>
  <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
  <script>
    window.onload = function () {
      SwaggerUIBundle({
        urls: __URLS__,
        "urls.primaryName": __PRIMARY__,
        dom_id: '#swagger-ui',
        deepLinking: true,
        persistAuthorization: true,
        displayRequestDuration: true,
        tryItOutEnabled: true,
        presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
        plugins: [SwaggerUIBundle.plugins.DownloadUrl],
        layout: 'StandaloneLayout'
      });
    };
  </script>
</body>
</html>
HTML;

file_put_contents(
    $root . '/web/docs/index.html',
    str_replace(['__URLS__', '__PRIMARY__'], [$urlsJs, $primaryJs], $index) . "\n"
);

fwrite(STDOUT, "wrote docs/api/*.md, web/docs/openapi/*.json, web/docs/index.html (" . count($entries) . " specs)\n");
