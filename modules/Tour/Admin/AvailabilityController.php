<?php
namespace Modules\Tour\Admin;

use App\Http\Middleware\Dashboard;
use Illuminate\Http\Request;

class AvailabilityController extends \Modules\Tour\Controllers\AvailabilityController
{
    protected $indexView = 'Tour::admin.availability';

    public function __construct()
    {
        parent::__construct();
        $this->setActiveMenu(route('tour.admin.index'));
        $this->middleware(function (Request $request, $next) {
            if ($request->is('api-admin/*')) {
                return $next($request);
            }
            return app(Dashboard::class)->handle($request, $next);
        });
    }

}
