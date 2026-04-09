<?php
namespace Modules\Space\Admin;

use App\Http\Middleware\Dashboard;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Space\Models\Space;
use Modules\Space\Models\SpaceDate;

class AvailabilityController extends \Modules\Space\Controllers\AvailabilityController
{
    protected $indexView = 'Space::admin.availability';

    public function __construct(Space $spaceClass, SpaceDate $spaceDateClass, Booking $bookingClass)
    {
        parent::__construct($spaceClass, $spaceDateClass, $bookingClass);
        $this->setActiveMenu(route('space.admin.index'));
        $this->middleware(function (Request $request, $next) {
            if ($request->is('api-admin/*')) {
                return $next($request);
            }

            return app(Dashboard::class)->handle($request, $next);
        });
    }

}
