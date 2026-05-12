<?php
/**
 * Created by PhpStorm.
 * User: h2 gaming
 * Date: 8/13/2019
 * Time: 10:19 PM
 */
namespace Modules\Core\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Core\Models\NotificationPush;

class NotificationController extends AdminController
{
    protected function adminNotificationQuery()
    {
        return NotificationPush::query()->where(function($q){
            $q->where('for_admin',1);
            $q->orWhere('notifiable_id', Auth::id());
        });
    }

    public function markAsRead(Request $request){
        $id = $request->get('id');
        $updated = 0;
        if(!empty($id))
        {
            $updated = $this->adminNotificationQuery()->where('id', $id)->update([
                'read_at' => now()
            ]);
        }
        return response()->json([
            'success' => true,
            'updated' => $updated,
        ], 200);
    }

    public function markAllAsRead(Request $request){
        $updated = $this->adminNotificationQuery()
            ->where('read_at', null)
            ->update([
                'read_at' => now()
            ]);
        return response()->json([
            'success' => true,
            'updated' => $updated,
        ], 200);
    }

    public function loadNotify(Request $request)
    {
        $type = $request->get('type', '');
        $query = $this->adminNotificationQuery();

        if($type == 'unread'){
            $query->where('read_at', null);
        }

        if($type == 'read'){
            $query->where('read_at', '!=', null);
        }

        $query->orderBy('created_at','desc');
        $data = [
            'rows'                  => $query->paginate(20),
            'page_title'            => __("All Notifications"),
            'type'                  => $type
        ];
        return view('Core::admin.notification.index', $data);
    }

    public function indexApi(Request $request)
    {
        $type = $request->get('type', '');
        $perPage = max(1, min(100, (int) $request->get('per_page', 20)));
        $query = $this->adminNotificationQuery();

        if($type === 'unread'){
            $query->whereNull('read_at');
        }

        if($type === 'read'){
            $query->whereNotNull('read_at');
        }

        $query->orderBy('created_at','desc');
        $rows = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $rows->items(),
            'total' => $rows->total(),
            'max_pages' => $rows->lastPage(),
            'unread_total' => $this->adminNotificationQuery()->whereNull('read_at')->count(),
            'type' => $type,
        ]);
    }

    public function showApi($id)
    {
        $notification = $this->adminNotificationQuery()->where('id', $id)->first();
        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => __('Notification not found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $notification,
        ]);
    }
}
