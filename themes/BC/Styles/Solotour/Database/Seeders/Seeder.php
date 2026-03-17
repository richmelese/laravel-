<?php
namespace Themes\BC\Styles\Solotour\Database\Seeders;

use Illuminate\Database\Seeder as LaravelSeeder;

class Seeder extends LaravelSeeder
{
    public function run($params = [])
    {
        $freshInstall = $params['fresh'] ?? false;
    }
}