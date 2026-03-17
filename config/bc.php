<?php
$documentMime = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
$documentExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
$imageMime = ['image/png', 'image/jpeg', 'image/gif', 'image/bmp'];
$imageExt = ['png', 'jpg', 'jpeg', 'gif', 'bmp'];
return [
    'active_theme' => env('BC_ACTIVE_THEME', defined('BC_INIT_THEME') ? BC_INIT_THEME : 'BC'),
    "media" => [
        "groups" => [
            "default" => [
                "ext" => ["jpg", 'jpeg', 'png', 'gif', 'bmp', 'docx', 'JPG'],
                "mime" => ["image/png", "image/jpeg", "image/gif", "image/bmp", 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                "max_size" => 20000000, // In Bytes, default is 20MB,
                "max_width" => env('ALLOW_IMAGE_MAX_WIDTH', 4000),
                "max_height" => env('ALLOW_IMAGE_MAX_HEIGHT', 4000)
            ],
            "image" => [
                "ext" => ["jpg", 'jpeg', 'png', 'gif', 'bmp', 'JPG'],
                "mime" => ["image/png", "image/jpeg", "image/gif", "image/bmp"],
                "max_size" => 20000000, // In Bytes, default is 20MB,
                "max_width" => env('ALLOW_IMAGE_MAX_WIDTH', 4000),
                "max_height" => env('ALLOW_IMAGE_MAX_HEIGHT', 4000)
            ],
            'private_verification'=>[
                'ext'=>array_merge($documentExt, $imageExt),
                'mime'=>array_merge($documentMime, $imageMime),
                'max_size'=>50000000,// In Bytes, default is 50MB,
                'max_width'=>4000,
                'max_height'=>4000
            ]
        ],
        "optimize_image" => env('BC_MEDIA_OPTIMIZE_IMAGE', true),
        "preview_direct" => env("BC_MEDIA_PREVIEW_DIRECT", true),
    ],
    'disable_require_change_pw' => env('DISABLE_REQUIRE_CHANGE_PW', false),
    'cdn_url' => env('CDN_URL', ''),
    'simple_salt'=>env('SIMPLE_SALT',env('APP_KEY')),
    'site_styles'=>[]
];
