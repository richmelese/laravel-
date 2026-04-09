<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteObject;
use Illuminate\Support\Facades\Cache;
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
        $cacheKey = 'openapi.spec.v1';

        if (config('app.debug') && $request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        $ttlSeconds = config('app.debug') ? 120 : 3600;

        $openapi = Cache::remember($cacheKey, $ttlSeconds, function () {
            return $this->buildOpenApiSpec();
        });

        return response()->json($openapi);
    }

    /**
     * Build the OpenAPI document (expensive: walks all routes and gathers middleware).
     */
    private function buildOpenApiSpec(): array
    {
        $baseUrl = url('');
        $paths = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            // Document API routes ("api", "api-admin") and selected non-API endpoints
            // that are still relevant to frontend/API clients (e.g. Livewire updates).
            $isPublicApi = str_starts_with($uri, 'api/');
            $isAdminApi = str_starts_with($uri, 'api-admin/');
            $isLivewireEndpoint = str_starts_with($uri, 'livewire/');

            if ((!$isPublicApi && !$isAdminApi && !$isLivewireEndpoint) || in_array($uri, ['api/docs', 'api/openapi.json'], true)) {
                continue;
            }

            if ($isPublicApi) {
                $path = '/' . ltrim(substr($uri, 4), '/');
            } elseif ($isAdminApi) {
                // Strip "api-admin/" prefix for admin APIs
                $path = '/' . ltrim(substr($uri, 10), '/');
            } else {
                // Keep non-API endpoints (like Livewire) as full root-relative paths.
                $path = '/' . ltrim($uri, '/');
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
                    'summary' => $this->openApiDefaultSummary($route),
                    'tags' => [$this->openApiDefaultTag($route)],
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

                $routeName = $route->getName();
                $operation = $this->enhanceAdminApiOperation($routeName, $methodKey, $operation);

                $paths[$path][$methodKey] = $operation;
            }
        }

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => config('app.name', 'Booking Core API'),
                'version' => config('app.version', '1.0.0'),
                'description' => 'Auto-generated API documentation. For **`api-admin`** routes, choose the **`api-admin`** server below and send `Authorization: Bearer <token>`. After route changes, run `php artisan cache:clear` or open `/api/openapi.json?refresh=1` (debug only) to refresh this spec.',
            ],
            'tags' => [
                ['name' => 'Hotel admin', 'description' => 'Authenticated admin JSON API under `/api-admin` (Sanctum bearer).'],
                ['name' => 'Room attributes', 'description' => 'Hotel room-level attributes (`service`: `hotel_room`). Permission: `hotel_manage_attributes`.'],
                ['name' => 'Tour (admin API)', 'description' => 'Tour admin JSON endpoints. Requires `tour_view` / `tour_create` / `tour_update` as applicable.'],
                ['name' => 'Space (admin API)', 'description' => 'Space admin JSON endpoints. Permissions: `space_view`, `space_create`, etc.'],
                ['name' => 'Car (admin API)', 'description' => 'Car admin JSON endpoints. Permissions: `car_manage_attributes`, etc.'],
            ],
            'servers' => [
                ['url' => $baseUrl . '/api-admin', 'description' => 'Admin API (use for `/hotel/...` paths in this spec)'],
                ['url' => $baseUrl . '/api', 'description' => 'Public API'],
                ['url' => $baseUrl, 'description' => 'Application root'],
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
    }

    /**
     * Enrich auto-generated OpenAPI operations (summary, tags, query params) for selected admin routes.
     */
    private function enhanceAdminApiOperation(?string $routeName, string $methodKey, array $operation): array
    {
        if ($routeName === 'api_admin.space.recovery' && $methodKey === 'get') {
            $operation['summary'] = 'Space — list trashed (recovery)';
            $operation['description'] = 'Paginated list of soft-deleted spaces. Requires `space_view`.';
            $operation['tags'] = ['Space (admin API)'];
            $operation['parameters'] ??= [];
            foreach (['page', 'per_page', 's', 'vendor_id'] as $q) {
                $operation['parameters'][] = [
                    'name' => $q,
                    'in' => 'query',
                    'required' => false,
                    'schema' => $q === 'per_page' || $q === 'page'
                        ? ['type' => 'integer', 'example' => $q === 'per_page' ? 100 : 1]
                        : ['type' => 'string'],
                ];
            }
            $operation['responses']['200'] = [
                'description' => 'Paginated spaces (recovery).',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => ['type' => 'array', 'items' => ['type' => 'object']],
                                'meta' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'current_page' => ['type' => 'integer'],
                                        'per_page' => ['type' => 'integer'],
                                        'total' => ['type' => 'integer'],
                                        'last_page' => ['type' => 'integer'],
                                    ],
                                ],
                                'recovery' => ['type' => 'boolean', 'example' => true],
                                'space_manage_others' => ['type' => 'boolean'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($routeName === 'api_admin.space.availability.index' && $methodKey === 'get') {
            $operation['summary'] = 'Space — availability calendar (list spaces)';
            $operation['description'] = 'Spaces available for availability management. Requires `space_create`. Use `month` (format `m-Y`) for calendar context.';
            $operation['tags'] = ['Space (admin API)'];
            $operation['parameters'] ??= [];
            foreach (['page', 'per_page', 's', 'month'] as $q) {
                $operation['parameters'][] = [
                    'name' => $q,
                    'in' => 'query',
                    'required' => false,
                    'schema' => $q === 'per_page' || $q === 'page'
                        ? ['type' => 'integer', 'example' => $q === 'per_page' ? 100 : 1]
                        : ($q === 'month' ? ['type' => 'string', 'example' => '04-2026'] : ['type' => 'string']),
                ];
            }
            $operation['responses']['200'] = [
                'description' => 'Paginated spaces with availability UI context.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => ['type' => 'array', 'items' => ['type' => 'object']],
                                'meta' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'current_page' => ['type' => 'integer'],
                                        'per_page' => ['type' => 'integer'],
                                        'total' => ['type' => 'integer'],
                                        'last_page' => ['type' => 'integer'],
                                    ],
                                ],
                                'current_month' => ['type' => 'integer', 'description' => 'Unix timestamp for month start'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($routeName === 'api_admin.car.attribute.index' && $methodKey === 'get') {
            $operation['summary'] = 'Car — list attributes';
            $operation['description'] = 'Attributes for service `car`. Requires `car_manage_attributes`.';
            $operation['tags'] = ['Car (admin API)'];
            $operation['parameters'] ??= [];
            $operation['parameters'][] = [
                'name' => 's',
                'in' => 'query',
                'required' => false,
                'description' => 'Search by attribute name (substring).',
                'schema' => ['type' => 'string'],
            ];
            $operation['responses']['200'] = [
                'description' => 'List of attribute models.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => ['type' => 'array', 'items' => ['type' => 'object']],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($routeName === 'api_admin.space.attribute.index' && $methodKey === 'get') {
            $operation['summary'] = 'Space — list attributes';
            $operation['description'] = 'Attributes for service `space`. Requires `space_manage_attributes`.';
            $operation['tags'] = ['Space (admin API)'];
            $operation['parameters'] ??= [];
            $operation['parameters'][] = [
                'name' => 's',
                'in' => 'query',
                'required' => false,
                'description' => 'Search by attribute name (substring).',
                'schema' => ['type' => 'string'],
            ];
            $operation['responses']['200'] = [
                'description' => 'List of attribute models.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => ['type' => 'array', 'items' => ['type' => 'object']],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($routeName === 'api_admin.hotel.room.attribute.edit' && $methodKey === 'get') {
            $operation['summary'] = 'Room attributes — get one for edit';
            $operation['description'] = 'Loads a room attribute by id with translation and the full list of room attributes. Requires permission `hotel_manage_attributes`.';
            $operation['tags'] = ['Hotel admin', 'Room attributes'];
            $operation['parameters'] ??= [];
            $operation['parameters'][] = [
                'name' => 'lang',
                'in' => 'query',
                'required' => false,
                'description' => 'Locale code for the translation (defaults to the site main language).',
                'schema' => ['type' => 'string', 'example' => 'en'],
            ];
            $operation['responses']['200'] = [
                'description' => 'JSON envelope with row, translation, attributes list, and enable_multi_lang.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'row' => ['type' => 'object', 'description' => 'Attribute model'],
                                        'translation' => ['type' => 'object'],
                                        'attributes' => ['type' => 'array', 'items' => ['type' => 'object']],
                                        'enable_multi_lang' => ['type' => 'boolean'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['404'] = ['description' => 'Attribute not found'];
        }

        return $operation;
    }

    /**
     * Human-readable summary (title) for Swagger from the Laravel route name.
     */
    private function openApiDefaultSummary(RouteObject $route): string
    {
        $name = $route->getName();
        if (is_string($name) && $name !== '') {
            if (str_starts_with($name, 'api_admin.')) {
                $rest = substr($name, strlen('api_admin.'));
                $parts = explode('.', $rest);
                $module = $parts[0] ?? 'admin';
                $tail = array_slice($parts, 1);
                $action = implode(' · ', array_map(static function ($p) {
                    return str_replace('_', ' ', $p);
                }, $tail));

                return ucfirst($module).($action !== '' ? ' — '.$action : '');
            }
            if (str_starts_with($name, 'api.')) {
                $rest = substr($name, 4);
                $parts = explode('.', $rest);
                $module = $parts[0] ?? 'api';
                $tail = array_slice($parts, 1);
                $action = implode(' · ', array_map(static function ($p) {
                    return str_replace('_', ' ', $p);
                }, $tail));

                return ucfirst($module).($action !== '' ? ' — '.$action : '');
            }
        }

        $methods = array_values(array_diff($route->methods(), ['HEAD', 'OPTIONS']));
        $verb = strtoupper($methods[0] ?? 'GET');

        return $verb.' '.$route->uri();
    }

    /**
     * Tag used to group operations in Swagger UI (one tag per operation by default).
     */
    private function openApiDefaultTag(RouteObject $route): string
    {
        $name = $route->getName();
        if (is_string($name) && str_starts_with($name, 'api_admin.')) {
            $rest = substr($name, strlen('api_admin.'));
            $module = explode('.', $rest)[0] ?? 'admin';

            return ucfirst($module).' (admin API)';
        }
        if (is_string($name) && str_starts_with($name, 'api.')) {
            $rest = substr($name, 4);
            $module = explode('.', $rest)[0] ?? 'public';

            return ucfirst($module).' (API)';
        }
        if (str_starts_with($route->uri(), 'livewire/')) {
            return 'Livewire';
        }

        return 'Other';
    }
}
