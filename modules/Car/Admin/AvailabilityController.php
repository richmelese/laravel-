<?php
namespace Modules\Car\Admin;

use App\Http\Middleware\Dashboard;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Car\Models\Car;
use Modules\Car\Models\CarDate;

class AvailabilityController extends \Modules\Car\Controllers\AvailabilityController
{
    protected $carClass;
    protected $carDateClass;
    protected $bookingClass;
    protected $indexView = 'Car::admin.availability';

    public function __construct(Car $carClass, CarDate $carDateClass, Booking $bookingClass)
    {
        parent::__construct($carClass, $carDateClass, $bookingClass);
        $this->setActiveMenu(route('car.admin.index'));
        $this->middleware(function (Request $request, $next) {
            if ($request->is('api-admin/*')) {
                return $next($request);
            }

            return app(Dashboard::class)->handle($request, $next);
        });
    }

}
