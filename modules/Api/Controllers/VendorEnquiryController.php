<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Events\EnquiryReplyCreated;
use Modules\Booking\Models\Enquiry;
use Modules\Booking\Models\EnquiryReply;

/**
 * Vendor "enquiry report" JSON API for SPA clients using Sanctum Bearer tokens.
 * Mirrors Modules\Vendor\Controllers\EnquiryController scope (vendor_id = current user), but returns JSON.
 */
class VendorEnquiryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (! $user->hasPermission('enquiry_view')) {
            return response()->json(['status' => 0, 'message' => __('Permission denied')], 403);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));

        $query = Enquiry::where('vendor_id', $user->id)
            ->whereIn('object_model', array_keys(get_bookable_services()))
            ->orderBy('id', 'desc');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $rows = $query->with('service')->withCount('replies')->paginate($perPage);

        return $this->sendSuccess([
            'data' => $rows->items(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
            'statuses' => Enquiry::$enquiryStatus,
        ]);
    }

    public function update(Request $request, $id)
    {
        $status = $request->input('status');
        $user = Auth::user();

        if (! $user->hasPermission('enquiry_update') || empty($status)) {
            return response()->json(['status' => 0, 'message' => __('Update fail!')], 422);
        }

        $item = Enquiry::where('id', $id)->where('vendor_id', $user->id)->first();
        if (empty($item)) {
            return response()->json(['status' => 0, 'message' => __('Enquiry not found!')], 404);
        }

        $item->status = $status;
        $item->save();

        return $this->sendSuccess(['data' => $item], __('Update success'));
    }

    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user->hasPermission('enquiry_update')) {
            return response()->json(['status' => 0, 'message' => __('Permission denied')], 403);
        }

        $item = Enquiry::where('vendor_id', $user->id)->where('id', $id)->first();
        if (empty($item)) {
            return response()->json(['status' => 0, 'message' => __('Enquiry not found!')], 404);
        }

        $item->delete();

        return $this->sendSuccess([], __('Delete success!'));
    }

    public function replies(Request $request, Enquiry $enquiry)
    {
        $user = Auth::user();
        if ($enquiry->vendor_id != $user->id) {
            return response()->json(['status' => 0, 'message' => __('Enquiry not found!')], 404);
        }
        if (! $user->hasPermission('enquiry_view')) {
            return response()->json(['status' => 0, 'message' => __('Permission denied')], 403);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));
        $rows = $enquiry->replies()->orderByDesc('id')->paginate($perPage);

        return $this->sendSuccess([
            'data' => $rows->items(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
            'enquiry' => $enquiry,
        ]);
    }

    public function replyStore(Request $request, Enquiry $enquiry)
    {
        $user = Auth::user();
        if ($enquiry->vendor_id != $user->id) {
            return response()->json(['status' => 0, 'message' => __('Enquiry not found!')], 404);
        }
        if (! $user->hasPermission('enquiry_view')) {
            return response()->json(['status' => 0, 'message' => __('Permission denied')], 403);
        }

        $request->validate(['content' => 'required']);

        $reply = new EnquiryReply();
        $reply->content = $request->input('content');
        $reply->parent_id = $enquiry->id;
        $reply->user_id = $user->id;
        $reply->save();

        EnquiryReplyCreated::dispatch($reply, $enquiry);

        return $this->sendSuccess(['data' => $reply], __('Reply added'));
    }
}
