<?php
namespace Modules\Report\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Booking\Events\EnquiryReplyCreated;
use Modules\Booking\Models\Enquiry;
use Modules\Booking\Models\EnquiryReply;


class EnquiryController extends AdminController
{
    /**
     * @var Enquiry
     */
    protected $enquiryClass;

    public function __construct(Enquiry $enquiry)
    {
        $this->setActiveMenu(route('booking.admin.index'));
        $this->enquiryClass = $enquiry;

    }

    public function index(Request $request)
    {
        $this->checkPermission('enquiry_view');
        $query = $this->enquiryClass->query()->where('status', '!=', 'draft');
        if (!empty($request->s)) {
            $query->where('email', 'LIKE', '%' . $request->s . '%');
            $query->orderBy('email', 'asc');
            $title_page = __('Search results: ":s"', ["s" => $request->s]);
        }
        $query->whereIn('object_model', array_keys(get_bookable_services()));
        $query->orderBy('id','desc');
        $data = [
            'rows'                  => $query->withCount(['replies'])->paginate(20),
            'breadcrumbs' => [
                [
                    'name' => __('Enquiry'),
                    'url'  => route('report.admin.enquiry.index')
                ],
                [
                    'name'  => __('All'),
                    'class' => 'active'
                ],
            ],
            'enquiry_update'        => $this->hasPermission('enquiry_update'),
            'enquiry_manage_others' => $this->hasPermission('enquiry_manage_others'),
            'statues'        => $this->enquiryClass->enquiryStatus,
            'page_title'=> $title_page ?? __("Enquiry Management")
        ];

        return view('Report::admin.enquiry.index', $data);
    }

    public function apiIndex(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if (!method_exists($user, 'hasPermission') || !$user->hasPermission('enquiry_view')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = $this->enquiryClass->query()
            ->where('status', '!=', 'draft')
            ->whereIn('object_model', array_keys(get_bookable_services()))
            ->withCount(['replies'])
            ->orderByDesc('id');

        if ($search = $request->query('s')) {
            $query->where('email', 'LIKE', '%' . $search . '%');
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = max(1, min((int) $request->query('per_page', 20), 200));

        return response()->json([
            'data' => $query->paginate($perPage),
            'permissions' => [
                'enquiry_update' => $user->hasPermission('enquiry_update'),
                'enquiry_manage_others' => $user->hasPermission('enquiry_manage_others'),
            ],
            'statuses' => $this->enquiryClass->enquiryStatus,
        ]);
    }

    public function apiBulkEdit(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if (!method_exists($user, 'hasPermission') || !$user->hasPermission('enquiry_update')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ids = $request->input('ids', []);
        $action = (string) $request->input('action', '');
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'No items selected'], 422);
        }
        if ($action === '') {
            return response()->json(['message' => 'Please select action'], 422);
        }

        $affected = 0;
        foreach ($ids as $id) {
            $query = $this->enquiryClass->query()->where('id', (int) $id);
            if (!$user->hasPermission('enquiry_manage_others')) {
                $query->where('vendor_id', $user->id);
            }

            if ($action === 'delete') {
                $affected += $query->delete();
                continue;
            }

            $item = $query->first();
            if ($item) {
                $item->status = $action;
                $item->save();
                $affected++;
            }
        }

        return response()->json([
            'message' => 'Update success',
            'affected' => $affected,
        ]);
    }

    public function apiReplies(Enquiry $enquiry, Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if (!method_exists($user, 'hasPermission') || !$user->hasPermission('enquiry_view')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if (!$user->hasPermission('enquiry_manage_others') && (int) $enquiry->vendor_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = max(1, min((int) $request->query('per_page', 20), 200));

        return response()->json([
            'data' => $enquiry->replies()->orderByDesc('id')->paginate($perPage),
            'enquiry' => $enquiry,
        ]);
    }

    public function apiReplyStore(Enquiry $enquiry, Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if (!method_exists($user, 'hasPermission') || !$user->hasPermission('enquiry_view')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if (!$user->hasPermission('enquiry_manage_others') && (int) $enquiry->vendor_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'content' => 'required|string',
        ]);

        $reply = new EnquiryReply();
        $reply->content = $request->input('content');
        $reply->parent_id = $enquiry->id;
        $reply->user_id = $user->id;
        $reply->save();

        EnquiryReplyCreated::dispatch($reply, $enquiry);

        return response()->json([
            'message' => 'Reply added',
            'data' => $reply,
        ], 201);
    }

    public function bulkEdit(Request $request)
    {
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            return redirect()->back()->with('error', __('No items selected'));
        }
        if (empty($action)) {
            return redirect()->back()->with('error', __('Please select action'));
        }
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = $this->enquiryClass->query()->where("id", $id);
                if (!$this->hasPermission('enquiry_manage_others')) {
                    $query->where("vendor_id", Auth::id());
                    $this->checkPermission('enquiry_update');
                }
                $query->first();
                if(!empty($query)){
                    $query->delete();
                }
            }
        } else {
            foreach ($ids as $id) {
                $query = $this->enquiryClass->query()->where("id", $id);
                if (!$this->hasPermission('enquiry_manage_others')) {
                    $query->where("vendor_id", Auth::id());
                    $this->checkPermission('enquiry_update');
                }
                $item = $query->first();
                if(!empty($item)){
                    $item->status = $action;
                    $item->save();
                }
            }
        }
        return redirect()->back()->with('success', __('Update success'));
    }

    public function reply(Enquiry $enquiry,Request  $request){
        $this->checkPermission('enquiry_view');

        $data = [
            'rows'=>$enquiry->replies()->orderByDesc('id')->paginate(20),

            'breadcrumbs' => [
                [
                    'name' => __('Enquiry'),
                    'url'  => route('report.admin.enquiry.index')
                ],
                [
                    'name'  => __('Enquiry :name',['name'=>'#'.$enquiry->id.' - '.($enquiry->service->title ?? '')]),
                ],
                [
                    'name'  => __('All Replies'),
                    'class' => 'active'
                ],
            ],
            'page_title'=>__("Replies"),
            'enquiry'=>$enquiry
        ];

        return view("Report::admin.enquiry.reply",$data);
    }

    public function replyStore(Enquiry $enquiry,Request  $request){
        $this->checkPermission('enquiry_view');

        $request->validate([
            'content'=>'required'
        ]);

        $reply = new EnquiryReply();
        $reply->content = $request->input('content');
        $reply->parent_id = $enquiry->id;
        $reply->user_id = auth()->id();

        $reply->save();

        EnquiryReplyCreated::dispatch($reply,$enquiry);

        return back()->with('success',__("Reply added"));
    }

}
