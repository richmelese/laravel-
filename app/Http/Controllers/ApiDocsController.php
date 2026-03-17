<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ApiDocsController extends Controller
{
    public function docs(Request $request)
    {
        $url = url('/api/openapi.json');
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>API Docs - Swagger UI</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css" />
  <style>
    html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
    *, *:before, *:after { box-sizing: inherit; }
    body { margin: 0; background: #fafafa; }
  </style>
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
  <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-standalone-preset.js"></script>
  <script>
    window.onload = function() {
      const ui = SwaggerUIBundle({
        url: "{$url}",
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        layout: "BaseLayout",
      });
      window.ui = ui;
    };
  </script>
</body>
</html>
HTML;
        return response($html);
    }

    public function openapi(Request $request)
    {
        $baseUrl = url('');
        $paths = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            // Only document API routes (those under the "api" or "api-admin" prefixes).
            $isPublicApi = str_starts_with($uri, 'api/');
            $isAdminApi = str_starts_with($uri, 'api-admin/');
            if ((!$isPublicApi && !$isAdminApi) || in_array($uri, ['api/docs', 'api/openapi.json'], true)) {
                continue;
            }

            if ($isPublicApi) {
                $path = '/' . ltrim(substr($uri, 4), '/');
            } else {
                // Strip "api-admin/" prefix for admin APIs
                $path = '/' . ltrim(substr($uri, 10), '/');
            }
            if ($path === '') {
                continue;
            }

            $methods = array_diff($route->methods(), ['HEAD', 'OPTIONS']);
            if (empty($methods)) {
                continue;
            }

            $pathParams = [];
            if (preg_match_all('/\{([^}]+)\}/', $path, $matches)) {
                foreach ($matches[1] as $paramName) {
                    $pathParams[] = [
                        'name' => $paramName,
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'string'],
                    ];
                }
            }

            foreach ($methods as $method) {
                $methodKey = strtolower($method);

                $operation = [
                    'summary' => $route->getName() ?: $route->getActionName() ?: strtoupper($method) . ' ' . $path,
                    'responses' => [
                        '200' => ['description' => 'OK'],
                    ],
                ];

                if (!empty($pathParams)) {
                    $operation['parameters'] = $pathParams;
                }

                if (in_array($methodKey, ['post', 'put', 'patch', 'delete'], true)) {
                    $operation['requestBody'] = [
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ],
                    ];
                }

                $middleware = method_exists($route, 'gatherMiddleware') ? $route->gatherMiddleware() : [];
                if (in_array('auth:sanctum', $middleware, true) || in_array('auth:api', $middleware, true)) {
                    $operation['security'] = [['sanctum' => []]];
                }

                $paths[$path][$methodKey] = $operation;
            }
        }

        $openapi = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => config('app.name', 'Booking Core API'),
                'version' => config('app.version', '1.0.0'),
                'description' => 'Auto-generated API documentation.',
            ],
            'servers' => [
                ['url' => $baseUrl . '/api'],
                ['url' => $baseUrl . '/api-admin'],
            ],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'sanctum' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                ],
            ],
        ];

        return response()->json($openapi);
    }
}
