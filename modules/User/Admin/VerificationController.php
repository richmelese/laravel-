<?php
namespace Modules\User\Admin;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\User\Events\AdminUpdateVerificationData;
use Modules\User\Models\Role;

class VerificationController extends AdminController
{

    public function __construct()
    {
        $this->setActiveMenu(route('user.admin.index'));
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    public function index(Request $request){

        $data = [];
        $this->checkPermission('user_view');
        $username = $request->query('s');
        $listUser = User::query()->orderBy('id','desc');
        if (!empty($username)) {
            $listUser->where(function($query) use($username){
                $query->where('first_name', 'LIKE', '%' . $username . '%');
                $query->orWhere('id',  $username);
                $query->orWhere('phone',  $username);
                $query->orWhere('email', 'LIKE', '%' . $username . '%');
                $query->orWhere('last_name', 'LIKE', '%' . $username . '%');
            });
        }

        if($request->query('role')){
            $listUser->role($request->query('role'));
        }

        switch ($request->input('status')){
            case "pending":
                $listUser->whereIn('verify_submit_status',['new','partial']);
                break;
            case "approved":
                $listUser->whereIn('verify_submit_status',['completed']);
                break;
            default:
                $listUser->whereIn('verify_submit_status',['new','partial','completed']);
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $data = [
            'rows' => $listUser->with(['role'])->paginate($perPage),
            'roles' => Role::all()
        ];

        if ($this->isApiRequest($request)) {
            $rows = $data['rows'];
            return response()->json([
                'data' => $rows->items(),
                'meta' => [
                    'current_page' => $rows->currentPage(),
                    'per_page'     => $rows->perPage(),
                    'total'        => $rows->total(),
                    'last_page'    => $rows->lastPage(),
                ],
                'roles' => $data['roles'],
            ]);
        }

        return view("User::admin.verification.index",$data);
    }

    public function show(Request $request, $id)
    {
        $this->checkPermission('user_view');
        $row = User::with(['role'])->find($id);
        if (empty($row)) {
            return response()->json(['message' => __('Not found')], 404);
        }
        if ($row->id != Auth::user()->id and !Auth::user()->hasPermission('user_update')) {
            return response()->json(['message' => __('Forbidden')], 403);
        }

        return response()->json([
            'data' => [
                'user'                 => $row,
                'verification_fields'  => $row->verification_fields,
                'roles'                => Role::all(),
            ],
        ]);
    }

    public function detail(Request $request, $id)
    {
        $row = User::find($id);
        if (empty($row)) {
            return redirect(route('user.admin.index'));
        }
        if ($row->id != Auth::user()->id and !Auth::user()->hasPermission('user_update')) {
            abort(403);
        }
        $data = [
            'row'   => $row,
            'roles' => Role::all(),
            'breadcrumbs'=>[
                [
                    'name'=>__("Users"),
                    'url'=>route('user.admin.index')
                ],
                [
                    'name'=>__("Verification Request"),
                    'url'=>route('user.admin.verification.index')
                ],
                [
                    'name'=>__("Verify request: :email",['email'=>$row->email]),
                    'class' => 'active'
                ],
            ]
        ];
        return view('User::admin.verification.detail', $data);
    }
    public function store(Request $request, $id)
    {
        $row = User::find($id);
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __("User not found")], 404);
            }
            return redirect()->back()->with("danger",__("User not found"));
        }
        if ($row->id != Auth::user()->id and !Auth::user()->hasPermission('user_update')) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Forbidden')], 403);
            }
            abort(403);
        }

        $fields = $row->verification_fields;
        if(empty($fields)){
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __("No verification field found")], 422);
            }
            return redirect()->back()->with("danger",__("No verification field found"));
        }

        $verifiedFields = $request->input('fields', []);
        $full = true;

        foreach ($fields as $field)
        {
            if(in_array($field['id'],$verifiedFields)){
                $row->addMeta('is_verified_'.$field['id'],1);
            }else{
                $row->addMeta('is_verified_'.$field['id'],0);
                $full = false;
            }
        }

        if($full){
            $row->verify_submit_status = 'completed';
            $row->is_verified = 1;
        }else{
            $row->verify_submit_status = 'partial';
            $row->is_verified = 0;
        }

        $row->save();

        event(new AdminUpdateVerificationData($row,$full));

        if ($this->isApiRequest($request)) {
            $row->load('role');

            return response()->json([
                'message' => __('Updated'),
                'data'    => [
                    'user'                => $row,
                    'verification_fields' => $row->verification_fields,
                ],
            ]);
        }

        return redirect()->back()->with("success",__("Updated"));
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission('user_create');
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Select at leas 1 item!')], 422);
            }
            return redirect()->back()->with('error', __('Select at leas 1 item!'));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Select an Action!')], 422);
            }
            return redirect()->back()->with('error', __('Select an Action!'));
        }

        switch ($action){
            case "delete":
                foreach ($ids as $id) {
                    $query = User::find($id);
                    if (!empty($query)) {
                        $query->verify_submit_status = null;
                        $query->save();
                    }
                }
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Deleted success!')]);
                }
                return redirect()->back()->with('success', __('Deleted success!'));
            default:
                break;
        }

        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('OK')]);
        }
    }
}
