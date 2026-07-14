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

            // Document API routes ("api", "api-admin", "admin-api", "api/admin")
            // and selected non-API endpoints
            // that are still relevant to frontend/API clients (e.g. Livewire updates).
            $isPublicApi = str_starts_with($uri, 'api/');
            $isAdminApi = str_starts_with($uri, 'api-admin/');
            $isAdminApiAlias = str_starts_with($uri, 'admin-api/');
            $isApiAdminAlias = str_starts_with($uri, 'api/admin/');
            $isLivewireEndpoint = str_starts_with($uri, 'livewire/');

            if ((!$isPublicApi && !$isAdminApi && !$isAdminApiAlias && !$isApiAdminAlias && !$isLivewireEndpoint) || in_array($uri, ['api/docs', 'api/openapi.json'], true)) {
                continue;
            }

            if ($isPublicApi) {
                $path = '/' . ltrim(substr($uri, 4), '/');
            } elseif ($isAdminApi) {
                // Strip "api-admin/" prefix for admin APIs
                $path = '/' . ltrim(substr($uri, 10), '/');
            } elseif ($isAdminApiAlias) {
                // Strip "admin-api/" prefix for admin APIs (alias)
                $path = '/' . ltrim(substr($uri, 10), '/');
            } elseif ($isApiAdminAlias) {
                // Strip "api/admin/" prefix for admin APIs (alias)
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
                ['name' => 'Event (admin API)', 'description' => 'Event admin JSON endpoints. Permissions: `event_manage_attributes`, etc.'],
                ['name' => 'Location (admin API)', 'description' => 'Location admin JSON endpoints. Requires `location_manage_others` for categories.'],
                ['name' => 'User roles (admin API)', 'description' => 'List roles and edit role name, code, and permission set. Requires `role_manage`.'],
                ['name' => 'Flight (admin API)', 'description' => 'Flights, airlines, airports, seat types, flight seats, and flight attributes. Requires `flight_view`, `flight_create`, `flight_update`, `flight_manage_attributes`, etc.'],
                ['name' => 'Dashboard (admin API)', 'description' => 'CMS home stats: recent bookings, top cards, earning chart. Requires `dashboard_access`.'],
                ['name' => 'User admin (admin API)', 'description' => 'List/create/update users under `/api-admin/user`. Requires `user_view`, `user_create`, or `user_update` as applicable.'],
                ['name' => 'Verification requests (admin API)', 'description' => 'User KYC / verification queue. Requires `user_view` / `user_update` / `user_create` as applicable.'],
                ['name' => 'Subscribers (admin API)', 'description' => 'Newsletter subscribers. Requires `newsletter_manage`.'],
                ['name' => 'Support (API)', 'description' => 'Public + authenticated support ticket/topic JSON endpoints under `/api/support`.'],
                ['name' => 'Support (admin API)', 'description' => 'Admin support topic/ticket JSON endpoints under `/api/support/admin` (Sanctum bearer + permissions).'],
                ['name' => 'Coupon (API)', 'description' => 'Coupon apply/remove and vendor coupon APIs under `/api`.'],
                ['name' => 'Coupon (admin API)', 'description' => 'Admin coupon JSON APIs under `/api-admin/coupon` (Sanctum bearer + coupon permissions).'],
            ],
            'servers' => [
                ['url' => $baseUrl . '/api-admin', 'description' => 'Admin API (use for `/hotel/...` paths in this spec)'],
                ['url' => $baseUrl . '/admin-api', 'description' => 'Admin API alias (legacy/compatibility)'],
                ['url' => $baseUrl . '/api/admin', 'description' => 'Admin API alias (compatibility)'],
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

        if ($routeName === 'api_admin.location.edit' && $methodKey === 'get') {
            $operation['summary'] = 'Location — get one for edit';
            $operation['description'] = 'Load a location by id with translation and parent tree. Requires `location_update`.';
            $operation['tags'] = ['Location (admin API)'];
            $operation['parameters'] ??= [];
            $operation['parameters'][] = [
                'name' => 'lang',
                'in' => 'query',
                'required' => false,
                'description' => 'Locale for translation (defaults to site main language).',
                'schema' => ['type' => 'string', 'example' => 'en'],
            ];
            $operation['responses']['200'] = [
                'description' => 'row, translation, parents (tree), enable_multi_lang.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'row' => ['type' => 'object'],
                                        'translation' => ['type' => 'object'],
                                        'parents' => ['type' => 'array', 'items' => ['type' => 'object']],
                                        'enable_multi_lang' => ['type' => 'boolean'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['404'] = ['description' => 'Location not found'];
        }

        if ($routeName === 'api_admin.location.category.index' && $methodKey === 'get') {
            $operation['summary'] = 'Location — list categories (tree)';
            $operation['description'] = 'Nested location categories. Requires `location_manage_others`.';
            $operation['tags'] = ['Location (admin API)'];
            $operation['parameters'] ??= [];
            $operation['parameters'][] = [
                'name' => 's',
                'in' => 'query',
                'required' => false,
                'description' => 'Search by category name (substring).',
                'schema' => ['type' => 'string'],
            ];
            $operation['responses']['200'] = [
                'description' => 'Tree of categories plus empty row/translation templates.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'rows' => ['type' => 'array', 'items' => ['type' => 'object']],
                                        'row' => ['type' => 'object'],
                                        'translation' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($routeName === 'api_admin.event.attribute.index' && $methodKey === 'get') {
            $operation['summary'] = 'Event — list attributes';
            $operation['description'] = 'Attributes for service `event`. Requires `event_manage_attributes`.';
            $operation['tags'] = ['Event (admin API)'];
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

        if ($routeName === 'api_admin.role.index' && $methodKey === 'get') {
            $operation['summary'] = 'Roles — list all';
            $operation['description'] = 'Paginated roles. Requires `role_manage`.';
            $operation['tags'] = ['User roles (admin API)'];
            $operation['parameters'] ??= [];
            foreach (['page', 'per_page'] as $q) {
                $operation['parameters'][] = [
                    'name' => $q,
                    'in' => 'query',
                    'required' => false,
                    'schema' => ['type' => 'integer', 'example' => $q === 'per_page' ? 20 : 1],
                ];
            }
            $operation['responses']['200'] = [
                'description' => 'Paginated roles.',
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
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($routeName === 'api_admin.role.show' && $methodKey === 'get') {
            $operation['summary'] = 'Roles — get one for edit (JSON form)';
            $operation['description'] = 'Returns the role, full permission catalog, grouped permissions, and `selected_permissions` for this role. Requires `role_manage`.';
            $operation['tags'] = ['User roles (admin API)'];
            $operation['responses']['200'] = [
                'description' => 'Edit-form payload.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'role' => ['type' => 'object'],
                                        'all_permissions' => ['type' => 'array', 'items' => ['type' => 'string']],
                                        'permissions_group' => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
                                        'selected_permissions' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['404'] = ['description' => 'Role not found'];
        }

        if (($routeName === 'api_admin.role.update' || $routeName === 'api_admin.role.patch') && in_array($methodKey, ['put', 'patch'], true)) {
            $operation['summary'] = 'Roles — update (name, code, permissions)';
            $operation['description'] = 'Updates `name` and `code` (required). Optionally send `permissions` (array of permission keys) to replace the role’s permission set (same as permission matrix). Requires `role_manage`.';
            $operation['tags'] = ['User roles (admin API)'];
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['name', 'code'],
                            'properties' => [
                                'name' => ['type' => 'string', 'example' => 'Administrator'],
                                'code' => ['type' => 'string', 'description' => 'Letters only (`alpha` rule).', 'example' => 'admin'],
                                'permissions' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'description' => 'If present, replaces all permissions for this role.',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['200'] = [
                'description' => 'Updated role with same envelope as GET.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string'],
                                'data' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'role' => ['type' => 'object'],
                                        'all_permissions' => ['type' => 'array', 'items' => ['type' => 'string']],
                                        'permissions_group' => ['type' => 'object'],
                                        'selected_permissions' => ['type' => 'array', 'items' => ['type' => 'string']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['403'] = ['description' => 'Demo mode or missing `role_manage`'];
            $operation['responses']['404'] = ['description' => 'Role not found'];
            $operation['responses']['422'] = ['description' => 'Validation error'];
        }

        if ($routeName === 'api_admin.dashboard' && $methodKey === 'get') {
            $operation['summary'] = 'CMS dashboard — stats JSON';
            $operation['description'] = 'Recent bookings, top cards, and earning chart (same data as `/admin` home). Query: `recent_limit` (1–100, default 10), `chart_from` / `chart_to` (unix timestamp or date string) to override the default “this week” chart range. Requires `dashboard_access`.';
            $operation['tags'] = ['Dashboard (admin API)'];
            $operation['parameters'] ??= [];
            foreach (['recent_limit', 'chart_from', 'chart_to'] as $q) {
                $operation['parameters'][] = [
                    'name' => $q,
                    'in' => 'query',
                    'required' => false,
                    'schema' => $q === 'recent_limit' ? ['type' => 'integer'] : ['type' => 'string'],
                ];
            }
            $operation['responses']['200'] = [
                'description' => 'Dashboard payload.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'recent_bookings' => ['type' => 'array'],
                                'top_cards' => ['type' => 'array'],
                                'earning_chart_data' => ['type' => 'object'],
                                'chart_range' => ['type' => 'object'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['403'] = ['description' => 'Missing `dashboard_access`'];
        }

        if ($routeName === 'api_admin.flight.index' && $methodKey === 'get') {
            $operation['summary'] = 'Flight — list';
            $operation['description'] = 'Paginated flights with airline/airports/author. Query: `s`, `vendor_id` (if `flight_manage_others`), `page`, `per_page`. Requires `flight_view`.';
            $operation['tags'] = ['Flight (admin API)'];
            $operation['parameters'] ??= [];
            foreach (['page', 'per_page', 's', 'vendor_id'] as $q) {
                $operation['parameters'][] = [
                    'name' => $q,
                    'in' => 'query',
                    'required' => false,
                    'schema' => in_array($q, ['page', 'per_page'], true)
                        ? ['type' => 'integer']
                        : ['type' => 'string'],
                ];
            }
            $operation['responses']['200'] = [
                'description' => 'Paginated flights.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => ['type' => 'array', 'items' => ['type' => 'object']],
                                'meta' => ['type' => 'object'],
                                'flight_manage_others' => ['type' => 'boolean'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['503'] = ['description' => 'Flight module disabled'];
        }

        if ($routeName === 'api_admin.user.store' && $methodKey === 'post') {
            $operation['summary'] = 'Users — create or update (store)';
            $operation['description'] = 'Same body as admin **Save user**. `id` in the path: use **`0`** to create, or a user id to update. Requires `user_create` (new) or `user_update` (existing). Send JSON with `first_name`, `last_name`, `business_name`, `status`, `role_id`, `email`, `user_name`, etc.';
            $operation['tags'] = ['User admin (admin API)'];
            $operation['parameters'] ??= [];
            foreach ($operation['parameters'] as $idx => $param) {
                if (($param['name'] ?? '') === 'id') {
                    $operation['parameters'][$idx]['description'] = '0 = create user; positive id = update that user.';
                    $operation['parameters'][$idx]['schema'] = ['type' => 'integer', 'example' => 0];
                    break;
                }
            }
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'first_name' => ['type' => 'string'],
                                'last_name' => ['type' => 'string'],
                                'business_name' => ['type' => 'string'],
                                'status' => ['type' => 'string'],
                                'role_id' => ['type' => 'integer'],
                                'email' => ['type' => 'string', 'format' => 'email'],
                                'user_name' => ['type' => 'string'],
                                'phone' => ['type' => 'string'],
                                'is_email_verified' => ['type' => 'boolean'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['200'] = [
                'description' => 'Created or updated user (with `role` when applicable).',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string'],
                                'data' => ['type' => 'object'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['403'] = ['description' => 'Demo mode or missing permission'];
            $operation['responses']['422'] = ['description' => 'Validation error'];
        }

        if ($routeName === 'api_admin.verification.index' && $methodKey === 'get') {
            $operation['summary'] = 'Verification — list requests';
            $operation['description'] = 'Users with verification workflow (`verify_submit_status`). Requires `user_view`. Filter with `status`: omit for all, `pending` (new/partial), `approved` (completed).';
            $operation['tags'] = ['Verification requests (admin API)'];
            $operation['parameters'] ??= [];
            foreach (['page', 'per_page', 's', 'role', 'status'] as $q) {
                $operation['parameters'][] = [
                    'name' => $q,
                    'in' => 'query',
                    'required' => false,
                    'schema' => in_array($q, ['page', 'per_page'], true)
                        ? ['type' => 'integer']
                        : ($q === 'status'
                            ? ['type' => 'string', 'enum' => ['pending', 'approved'], 'description' => 'Omit for all statuses.']
                            : ['type' => 'string']),
                ];
            }
            $operation['responses']['200'] = [
                'description' => 'Paginated users plus `roles` for filters.',
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
                                'roles' => ['type' => 'array', 'items' => ['type' => 'object']],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($routeName === 'api_admin.verification.show' && $methodKey === 'get') {
            $operation['summary'] = 'Verification — get one request (detail)';
            $operation['description'] = 'User row, `verification_fields`, and `roles`. Same access as admin detail: own user or `user_update`.';
            $operation['tags'] = ['Verification requests (admin API)'];
            $operation['responses']['200'] = [
                'description' => 'Detail payload.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'user' => ['type' => 'object'],
                                        'verification_fields' => ['type' => 'array', 'items' => ['type' => 'object']],
                                        'roles' => ['type' => 'array', 'items' => ['type' => 'object']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['403'] = ['description' => 'Forbidden'];
            $operation['responses']['404'] = ['description' => 'User not found'];
        }

        if (($routeName === 'api_admin.verification.update' || $routeName === 'api_admin.verification.patch') && in_array($methodKey, ['put', 'patch'], true)) {
            $operation['summary'] = 'Verification — save field checks';
            $operation['description'] = 'Send `fields` = array of verification field **ids** to mark as verified; omitted fields are stored as not verified. When all verified, status becomes completed. Requires `user_update` (or own user).';
            $operation['tags'] = ['Verification requests (admin API)'];
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'fields' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'description' => 'Field ids to mark verified (must match `verification_fields[].id`).',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['200'] = [
                'description' => 'Updated user and verification_fields snapshot.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string'],
                                'data' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'user' => ['type' => 'object'],
                                        'verification_fields' => ['type' => 'array'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['403'] = ['description' => 'Forbidden'];
            $operation['responses']['404'] = ['description' => 'User not found'];
            $operation['responses']['422'] = ['description' => 'No verification fields configured'];
        }

        if ($routeName === 'api_admin.verification.bulkEdit' && $methodKey === 'post') {
            $operation['summary'] = 'Verification — bulk (clear requests)';
            $operation['description'] = 'Requires `user_create`. `action`: `delete` clears `verify_submit_status` for selected user ids.';
            $operation['tags'] = ['Verification requests (admin API)'];
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['ids', 'action'],
                            'properties' => [
                                'ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                                'action' => ['type' => 'string', 'enum' => ['delete']],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['200'] = ['description' => 'Success'];
            $operation['responses']['422'] = ['description' => 'Missing ids or action'];
        }

        if ($routeName === 'api_admin.subscriber.index' && $methodKey === 'get') {
            $operation['summary'] = 'Subscribers — list';
            $operation['description'] = 'Paginated newsletter subscribers. Requires `newsletter_manage`.';
            $operation['tags'] = ['Subscribers (admin API)'];
            $operation['parameters'] ??= [];
            foreach (['page', 'per_page', 's'] as $q) {
                $operation['parameters'][] = [
                    'name' => $q,
                    'in' => 'query',
                    'required' => false,
                    'schema' => in_array($q, ['page', 'per_page'], true)
                        ? ['type' => 'integer']
                        : ['type' => 'string', 'description' => 'Search first name, last name, email'],
                ];
            }
            $operation['responses']['200'] = [
                'description' => 'Paginated subscribers.',
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
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($routeName === 'api_admin.subscriber.show' && $methodKey === 'get') {
            $operation['summary'] = 'Subscribers — get one';
            $operation['description'] = 'Single subscriber row. Requires `newsletter_manage`.';
            $operation['tags'] = ['Subscribers (admin API)'];
            $operation['responses']['200'] = [
                'description' => 'Subscriber model.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => ['type' => 'object'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['404'] = ['description' => 'Not found'];
        }

        if ($routeName === 'api_admin.subscriber.store' && $methodKey === 'post') {
            $operation['summary'] = 'Subscribers — create';
            $operation['description'] = 'Create a subscriber (no `id` in body). Requires `newsletter_manage`.';
            $operation['tags'] = ['Subscribers (admin API)'];
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['email'],
                            'properties' => [
                                'email' => ['type' => 'string', 'format' => 'email'],
                                'first_name' => ['type' => 'string'],
                                'last_name' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['200'] = [
                'description' => 'Created subscriber.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string'],
                                'data' => ['type' => 'object'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['422'] = ['description' => 'Validation or duplicate email'];
        }

        if (($routeName === 'api_admin.subscriber.update' || $routeName === 'api_admin.subscriber.patch') && in_array($methodKey, ['put', 'patch'], true)) {
            $operation['summary'] = 'Subscribers — update';
            $operation['description'] = 'Update email and names. Requires `newsletter_manage`.';
            $operation['tags'] = ['Subscribers (admin API)'];
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['email'],
                            'properties' => [
                                'email' => ['type' => 'string', 'format' => 'email'],
                                'first_name' => ['type' => 'string'],
                                'last_name' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['200'] = [
                'description' => 'Updated subscriber.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string'],
                                'data' => ['type' => 'object'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['404'] = ['description' => 'Subscriber not found'];
            $operation['responses']['422'] = ['description' => 'Validation or duplicate email'];
        }

        if ($routeName === 'api_admin.subscriber.bulkEdit' && $methodKey === 'post') {
            $operation['summary'] = 'Subscribers — bulk actions';
            $operation['description'] = 'Requires `newsletter_manage`. `action`: `delete` or a `status` value (if your DB has a `status` column).';
            $operation['tags'] = ['Subscribers (admin API)'];
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['ids', 'action'],
                            'properties' => [
                                'ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                                'action' => ['type' => 'string', 'description' => 'e.g. `delete` or status string'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['200'] = ['description' => 'Success'];
            $operation['responses']['422'] = ['description' => 'Missing ids or action'];
        }

        if (
            in_array($routeName, ['api_admin.bus.create', 'api_admin.buses.create', 'api_admin.bus.store.create', 'api_admin.buses.store.create'], true)
            && $methodKey === 'post'
        ) {
            $operation['summary'] = 'Bus — add new bus information';
            $operation['description'] = 'Creates a new bus record in `bc_buses` for the admin panel. Supports core identity, route cities/locations, schedule times, capacity, pricing, media (`image_id`, `gallery`) and active status.';
            $operation['tags'] = ['Boat (admin API)'];
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string', 'description' => 'Alias for title.'],
                                'title' => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                                'bus_number' => ['type' => 'string', 'description' => 'Vehicle Plate.', 'example' => 'AA-12345'],
                                'side_number' => ['type' => 'string', 'description' => 'Side Number (fleet number painted on the side of the bus).', 'example' => '4161'],
                                'bus_type' => ['type' => 'string'],
                                'seat_capacity' => ['type' => 'integer'],
                                'driver_name' => ['type' => 'string'],
                                'driver_phone' => ['type' => 'string'],
                                'departure_city' => ['type' => 'string'],
                                'arrival_city' => ['type' => 'string'],
                                'departure_location' => ['type' => 'string'],
                                'arrival_location' => ['type' => 'string'],
                                'departure_time' => ['type' => 'string', 'description' => 'Supports `H:i`, `H:i:s`, or full datetime.'],
                                'arrival_time' => ['type' => 'string', 'description' => 'Supports `H:i`, `H:i:s`, or full datetime.'],
                                'price' => ['type' => 'number', 'format' => 'float'],
                                'price_in_words' => ['type' => 'string', 'description' => 'Price spelled out in words, for tickets/invoices.', 'example' => 'Five hundred Birr'],
                                'image_id' => ['type' => 'integer'],
                                'gallery' => ['type' => 'array', 'items' => ['type' => 'integer']],
                                'status' => ['type' => 'string', 'example' => 'active'],
                                'is_active' => ['type' => 'boolean'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['201'] = ['description' => 'Bus created'];
            $operation['responses']['422'] = ['description' => 'Validation error'];
        }

        if (in_array($routeName, ['api_admin.bus.edit', 'api_admin.buses.edit'], true) && $methodKey === 'get') {
            $operation['summary'] = 'Bus — get bus information for edit';
            $operation['description'] = 'Returns one bus by id with all editable fields so admin clients can populate edit forms.';
            $operation['tags'] = ['Boat (admin API)'];
            $operation['responses']['200'] = [
                'description' => 'Bus payload.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => ['type' => 'object'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['404'] = ['description' => 'Bus not found'];
        }

        if (in_array($routeName, ['api_admin.bus.index', 'api_admin.buses.index'], true) && $methodKey === 'get') {
            $operation['summary'] = 'Bus — get all bus information (admin)';
            $operation['description'] = 'Returns paginated bus list for admin. Supports search with `s` and pagination using `page` and `per_page`.';
            $operation['tags'] = ['Boat (admin API)'];
            $operation['parameters'] ??= [];
            foreach (['page', 'per_page', 's'] as $q) {
                $operation['parameters'][] = [
                    'name' => $q,
                    'in' => 'query',
                    'required' => false,
                    'schema' => in_array($q, ['page', 'per_page'], true)
                        ? ['type' => 'integer']
                        : ['type' => 'string'],
                ];
            }
            $operation['responses']['200'] = [
                'description' => 'Paginated admin bus list.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => ['type' => 'array', 'items' => ['type' => 'object']],
                                        'current_page' => ['type' => 'integer'],
                                        'per_page' => ['type' => 'integer'],
                                        'total' => ['type' => 'integer'],
                                        'last_page' => ['type' => 'integer'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (
            in_array($routeName, ['api_admin.bus.update', 'api_admin.buses.update', 'api_admin.bus.partial_update', 'api_admin.buses.partial_update'], true)
            && in_array($methodKey, ['put', 'patch'], true)
        ) {
            $operation['summary'] = 'Bus — update bus information';
            $operation['description'] = 'Updates an existing bus by id. Includes support for `description` (string) and all core bus fields.';
            $operation['tags'] = ['Boat (admin API)'];
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string', 'description' => 'Alias for title.'],
                                'title' => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                                'bus_number' => ['type' => 'string', 'description' => 'Vehicle Plate.', 'example' => 'AA-12345'],
                                'side_number' => ['type' => 'string', 'description' => 'Side Number (fleet number painted on the side of the bus).', 'example' => '4161'],
                                'bus_type' => ['type' => 'string'],
                                'seat_capacity' => ['type' => 'integer'],
                                'driver_name' => ['type' => 'string'],
                                'driver_phone' => ['type' => 'string'],
                                'departure_city' => ['type' => 'string'],
                                'arrival_city' => ['type' => 'string'],
                                'departure_location' => ['type' => 'string'],
                                'arrival_location' => ['type' => 'string'],
                                'departure_time' => ['type' => 'string'],
                                'arrival_time' => ['type' => 'string'],
                                'price' => ['type' => 'number', 'format' => 'float'],
                                'price_in_words' => ['type' => 'string', 'description' => 'Price spelled out in words, for tickets/invoices.', 'example' => 'Five hundred Birr'],
                                'image_id' => ['type' => 'integer'],
                                'gallery' => ['type' => 'array', 'items' => ['type' => 'integer']],
                                'status' => ['type' => 'string'],
                                'is_active' => ['type' => 'boolean'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['200'] = ['description' => 'Bus updated'];
            $operation['responses']['404'] = ['description' => 'Bus not found'];
            $operation['responses']['422'] = ['description' => 'Validation error'];
        }

        if (in_array($routeName, ['api.bus.index', 'api.buses.index'], true) && $methodKey === 'get') {
            $operation['summary'] = 'Bus — get all bus information';
            $operation['description'] = 'Returns paginated active buses for public clients. Optional filters: `departure_city`, `arrival_city`, `bus_type`, `page`, and `per_page`.';
            $operation['tags'] = ['Bus (API)'];
            $operation['parameters'] ??= [];
            foreach (['page', 'per_page', 'departure_city', 'arrival_city', 'bus_type'] as $q) {
                $operation['parameters'][] = [
                    'name' => $q,
                    'in' => 'query',
                    'required' => false,
                    'schema' => in_array($q, ['page', 'per_page'], true)
                        ? ['type' => 'integer']
                        : ['type' => 'string'],
                ];
            }
            $operation['responses']['200'] = [
                'description' => 'Paginated list of buses.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'data' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => ['type' => 'array', 'items' => ['type' => 'object']],
                                        'current_page' => ['type' => 'integer'],
                                        'per_page' => ['type' => 'integer'],
                                        'total' => ['type' => 'integer'],
                                        'last_page' => ['type' => 'integer'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (is_string($routeName) && str_starts_with($routeName, 'api.support.')) {
            $operation['tags'] = ['Support (API)'];
        }

        if (is_string($routeName) && str_starts_with($routeName, 'api.support.admin.')) {
            $operation['tags'] = ['Support (admin API)'];
        }

        if (is_string($routeName) && str_starts_with($routeName, 'api.coupon.')) {
            $operation['tags'] = ['Coupon (API)'];
        }

        if (is_string($routeName) && str_starts_with($routeName, 'api_admin.coupon.')) {
            $operation['tags'] = ['Coupon (admin API)'];
        }

        if ($routeName === 'api.support.topic.index' && $methodKey === 'get') {
            $operation['summary'] = 'Support topic — list';
            $operation['parameters'] ??= [];
            foreach (['page', 'per_page', 's', 'catId', 'tagId'] as $q) {
                $operation['parameters'][] = [
                    'name' => $q,
                    'in' => 'query',
                    'required' => false,
                    'schema' => in_array($q, ['page', 'per_page'], true) ? ['type' => 'integer'] : ['type' => 'string'],
                ];
            }
        }

        if ($routeName === 'api.support.ticket.store' && $methodKey === 'post') {
            $operation['summary'] = 'Support ticket — create';
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['title', 'content', 'cat_id'],
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'content' => ['type' => 'string'],
                                'cat_id' => ['type' => 'integer'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['201'] = ['description' => 'Ticket created'];
        }

        if ($routeName === 'api.support.ticket.reply' && $methodKey === 'post') {
            $operation['summary'] = 'Support ticket — reply';
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['content'],
                            'properties' => [
                                'content' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ];
            $operation['responses']['201'] = ['description' => 'Reply created'];
        }

        if ($routeName === 'api.support.ticket.action' && $methodKey === 'post') {
            $operation['summary'] = 'Support ticket — admin/agent action';
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['action'],
                            'properties' => [
                                'action' => ['type' => 'string', 'example' => 'status'],
                                'status' => ['type' => 'string', 'example' => 'closed'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($routeName === 'api.coupon.apply' && $methodKey === 'post') {
            $operation['summary'] = 'Coupon — apply to booking';
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['coupon_code'],
                            'properties' => [
                                'coupon_code' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($routeName === 'api.coupon.remove' && $methodKey === 'post') {
            $operation['summary'] = 'Coupon — remove from booking';
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['coupon_code'],
                            'properties' => [
                                'coupon_code' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($routeName === 'api.coupon.vendor.index' && $methodKey === 'get') {
            $operation['summary'] = 'Vendor coupon — list';
            $operation['parameters'] ??= [];
            foreach (['page', 'per_page', 's'] as $q) {
                $operation['parameters'][] = [
                    'name' => $q,
                    'in' => 'query',
                    'required' => false,
                    'schema' => in_array($q, ['page', 'per_page'], true) ? ['type' => 'integer'] : ['type' => 'string'],
                ];
            }
        }

        if ($routeName === 'api.coupon.vendor.store' && $methodKey === 'post') {
            $operation['summary'] = 'Vendor coupon — create/update';
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['code', 'amount'],
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'code' => ['type' => 'string'],
                                'status' => ['type' => 'string'],
                                'amount' => ['type' => 'number'],
                                'discount_type' => ['type' => 'string', 'enum' => ['fixed', 'percent']],
                                'end_date' => ['type' => 'string', 'format' => 'date'],
                                'min_total' => ['type' => 'number'],
                                'max_total' => ['type' => 'number'],
                                'only_for_user' => ['type' => 'array', 'items' => ['type' => 'integer']],
                                'quantity_limit' => ['type' => 'integer'],
                                'limit_per_user' => ['type' => 'integer'],
                                'image_id' => ['type' => 'integer'],
                                'services' => ['type' => 'array', 'items' => ['type' => 'integer']],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($routeName === 'api_admin.coupon.index' && $methodKey === 'get') {
            $operation['summary'] = 'Coupon admin — list';
            $operation['parameters'] ??= [];
            foreach (['page', 'per_page', 's'] as $q) {
                $operation['parameters'][] = [
                    'name' => $q,
                    'in' => 'query',
                    'required' => false,
                    'schema' => in_array($q, ['page', 'per_page'], true) ? ['type' => 'integer'] : ['type' => 'string'],
                ];
            }
        }

        if ($routeName === 'api_admin.coupon.store' && $methodKey === 'post') {
            $operation['summary'] = 'Coupon admin — create/update';
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['code', 'amount'],
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'code' => ['type' => 'string'],
                                'status' => ['type' => 'string'],
                                'amount' => ['type' => 'number'],
                                'discount_type' => ['type' => 'string', 'enum' => ['fixed', 'percent']],
                                'end_date' => ['type' => 'string', 'format' => 'date'],
                                'min_total' => ['type' => 'number'],
                                'max_total' => ['type' => 'number'],
                                'only_for_user' => ['type' => 'array', 'items' => ['type' => 'integer']],
                                'quantity_limit' => ['type' => 'integer'],
                                'limit_per_user' => ['type' => 'integer'],
                                'image_id' => ['type' => 'integer'],
                                'services' => ['type' => 'array', 'items' => ['type' => 'integer']],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if ($routeName === 'api_admin.coupon.bulkEdit' && $methodKey === 'post') {
            $operation['summary'] = 'Coupon admin — bulk actions';
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['ids', 'action'],
                            'properties' => [
                                'ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                                'action' => ['type' => 'string', 'description' => 'delete | clone | publish | draft'],
                            ],
                        ],
                    ],
                ],
            ];
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
