<?php

namespace Modules\Announcement\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Announcement extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bc_announcements';

    protected $fillable = [
        'title',
        'content',
        'type',
        'status',
        'start_date',
        'end_date',
        'url',
        'button_label',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
    ];

    public static function getModelName()
    {
        return __('Announcement');
    }

    public static function getTypes(): array
    {
        return [
            'general'      => __('General'),
            'maintenance'  => __('Maintenance'),
            'promotion'    => __('Promotion'),
            'travel_update'=> __('Travel Update'),
            'service'      => __('Service Notice'),
            'warning'      => __('Warning'),
        ];
    }

    /**
     * Returns active announcements visible right now (published, within date range).
     */
    public static function getActive()
    {
        $today = Carbon::today()->toDateString(); // "2026-05-27"
        return static::query()
            ->where('status', 'publish')
            ->where(function ($q) use ($today) {
                // Some MySQL setups store empty datetime inputs as "0000-00-00 00:00:00"
                // which should behave like NULL (no start restriction).
                $q->whereNull('start_date')
                    ->orWhere('start_date', '')
                    ->orWhereRaw("DATE(start_date) = '0000-00-00'")
                    ->orWhereRaw("YEAR(start_date) = 0")
                    ->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                // Treat "no end date" (or empty/zero end_date) as always active.
                $q->whereNull('end_date')
                    ->orWhere('end_date', '')
                    ->orWhereRaw("DATE(end_date) = '0000-00-00'")
                    ->orWhereRaw("YEAR(end_date) = 0")
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->orderByDesc('id')
            ->get();
    }
}
