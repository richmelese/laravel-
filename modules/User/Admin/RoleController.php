<?php
namespace Modules\User\Admin;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\AdminController;
use Modules\User\Helpers\PermissionHelper;
use \Modules\User\Models\Role;

class RoleController extends AdminController
{
    protected $role_class;
    public function __construct()
    {
        $this->role_class = Role::class;
        $this->setActiveMenu(route('user.admin.index'));
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    /**
     * Group permission keys by prefix (same structure as the permission matrix screen).
     */
    protected function buildPermissionsGroup(): array
    {
        $permissions = PermissionHelper::all();
        $permissions_group = [
            'other' => [],
        ];
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                $sCheck = strpos($permission, '_');
                if ($sCheck === false) {
                    $permissions_group['other'][] = $permission;
                    continue;
                }
                $grName = substr($permission, 0, $sCheck);
                if (!isset($permissions_group[$grName])) {
                    $permissions_group[$grName] = [];
                }
                $permissions_group[$grName][] = $permission;
            }
        }
        if (empty($permissions_group['other'])) {
            unset($permissions_group['other']);
        }

        return $permissions_group;
    }

    /**
     * JSON envelope for edit-role forms (role + permission catalog + current selection).
     */
    protected function roleEditPayload(Role $role): array
    {
        $role->loadMissing('permissions');
        $permissions = PermissionHelper::all();

        return [
            'role'                 => $role,
            'all_permissions'      => $permissions,
            'permissions_group'    => $this->buildPermissionsGroup(),
            'selected_permissions' => $role->permissions->pluck('permission')->values()->all(),
        ];
    }

    /**
     * @return Role|null Null when updating a missing id (web redirects to index).
     */
    protected function persistRoleFromRequest(Request $request, $id): ?Role
    {
        $this->checkPermission('role_manage');
        $rules = [
            'name' => 'required',
            'code' => [
                'required',
                'alpha',
            ],
        ];
        if ($id > 0) {
            $row = Role::whereId($id)->first();
            if (empty($row)) {
                return null;
            }
            $rules['code'][] = Rule::unique(Role::getTableName(), 'code')->ignore($row->id);
        } else {
            $row = new Role();
            $rules['code'][] = Rule::unique(Role::getTableName(), 'code');
        }
        $this->validate($request, $rules);
        $row->fill($request->only(['name', 'code']));
        if (!$row->save()) {
            abort(500, __('Could not save role'));
        }

        return $row;
    }

    public function index(Request $request)
    {
        $this->checkPermission('role_manage');
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $rows = Role::query()->orderBy('id', 'desc')->paginate($perPage);
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => $rows->items(),
                'meta' => [
                    'current_page' => $rows->currentPage(),
                    'per_page'     => $rows->perPage(),
                    'total'        => $rows->total(),
                    'last_page'    => $rows->lastPage(),
                ],
            ]);
        }
        $data = [
            'rows'        => $rows,
            'breadcrumbs' => [
                [
                    'name' => __("Users"),
                    'url'  => route('user.admin.index'),
                ],
            ],
        ];

        return view('User::admin.role.index', $data);
    }

    public function show(Request $request, $id)
    {
        $this->checkPermission('role_manage');
        $row = Role::with('permissions')->find((int) $id);
        if (empty($row)) {
            return response()->json(['message' => __('Not found')], 404);
        }

        return response()->json([
            'data' => $this->roleEditPayload($row),
        ]);
    }

    public function update(Request $request, $id)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO Mode: You can not do this')], 403);
        }
        $request->validate([
            'permissions'   => 'sometimes|array',
            'permissions.*' => 'string',
        ]);
        $row = $this->persistRoleFromRequest($request, $id);
        if ($row === null) {
            return response()->json(['message' => __('Not found')], 404);
        }
        if ($request->has('permissions')) {
            $row->syncPermissions($request->input('permissions', []));
        }

        return response()->json([
            'message' => __('Role updated'),
            'data'    => $this->roleEditPayload($row->fresh(['permissions'])),
        ]);
    }

    public function create(Request $request)
    {
        $row = new User();
        $row->fill([
            'status' => 'publish'
        ]);

        $data = [
            'row'         => $row,
            'breadcrumbs' => [
                [
                    'name' => __("Users"),
                    'url'  => route('user.admin.index')
                ],
                [
                    'name' => __("Roles"),
                ],
            ]
        ];
        return view('User::admin.role.detail', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('role_manage');
        $row = Role::find((int)$id);
        if (empty($row)) {
            return redirect(route('user.admin.role.index'));
        }
        if (!empty($request->input())) {
            $row->fill($request->input());
            if ($row->save()) {

                return redirect(route('user.admin.role.index'))->with('success', __('Role updated'));
            }
        }
        $data = [
            'row'         => $row,
            'breadcrumbs' => [
                [
                    'name' => __("Users"),
                    'url'  => route('user.admin.index')
                ],
                [
                    'name' => __("Roles"),
                    'url'  => route('user.admin.role.index')
                ],
                [
                    'name' => __("Edit Role"),
                ],
            ]
        ];
        return view('User::admin.role.detail', $data);
    }

    public function store(Request $request, $id){
        if(is_demo_mode()){
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('DEMO Mode: You can not do this')], 403);
            }
            return back()->with('danger',  __('DEMO Mode: You can not do this') );
        }
        $row = $this->persistRoleFromRequest($request, $id);
        if ($row === null) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Not found')], 404);
            }
            return redirect(route('user.admin.role.index'));
        }
        if ($this->isApiRequest($request)) {
            return response()->json([
                'message' => ($id > 0) ? __('Role updated') : __('Role created'),
                'data'    => $this->roleEditPayload($row->fresh(['permissions'])),
            ]);
        }
        if($id > 0 ){
            return back()->with('success',  __('Role updated') );
        }
        return redirect(route('user.admin.role.detail',['id' => $row->id]))->with('success', __('Role created') );
    }

    public function verifyFields(Request $request){

        $this->checkPermission('role_manage');
        $this->setActiveMenu(route('user.admin.index'));

        $data = [
            'roles' => Role::all(),
            'fields'=>setting_item_array('role_verify_fields'),
            'breadcrumbs' => [
                [
                    'name' => __('User'),
                    'url'  => route('user.admin.index')
                ],
                [
                    'name' => __('Role Management'),
                    'url'  => route('user.admin.role.index')
                ],
                [
                    'name' => __('Verify Configs'),
                    'url'  => route('user.admin.role.verifyFields'),
                    'active'=>1
                ],
            ]
        ];
        return view('User::admin.role.verifyFields', $data);

    }
    public function verifyFieldsEdit(Request $request,$id){

        $this->checkPermission('role_manage');

        $this->setActiveMenu(route('user.admin.index'));

        $all = setting_item_array('role_verify_fields');
        $row = $all[$id] ?? [];

        if(empty($row)) return redirect()->back()->with("error",__("Field not found"));

        $row['id'] = $id;

        $data = [
            'roles' => Role::all(),
            'row'=>$row,
            'breadcrumbs' => [
                [
                    'name' => __('User'),
                    'url'  => route('user.admin.index')
                ],
                [
                    'name' => __('Role Management'),
                    'url'  => route('user.admin.role.index')
                ],
                [
                    'name' => __('Verify Configs'),
                    'url'  => route('user.admin.role.verifyFields'),
                ],
                [
                    'name' => __('Edit field: :name',['name'=>$row['name'] ?? $id]),
                    'active'=>1
                ],
            ]
        ];
        return view('User::admin.role.verifyFieldsEdit', $data);

    }

    public function verifyFieldsStore(){
        if(is_demo_mode()){
            return back()->with('danger',  __('DEMO Mode: You can not do this') );
        }

        $this->checkPermission('role_manage');

        $all = setting_item_array('role_verify_fields',[]);
        $id = \request()->input('id');
        $id = Str::snake($id);
        if(empty($id))
        {
            return redirect()->back()->withInput();
        }
        $isAdd = !isset($all[$id]);
        $all[$id] = [
            'name'=>\request()->input('name'),
            'type'=>\request()->input('type'),
            'roles'=>\request()->input('roles'),
            'required'=>\request()->input('required'),
            'order'=>\request()->input('order'),
            'icon'=>\request()->input('icon'),
        ];

        $languages = \Modules\Language\Models\Language::getActive();
        if(!empty($languages) && setting_item('site_enable_multi_lang') && setting_item('site_locale'))
        {
            foreach($languages as $language){
                $key_lang = setting_item('site_locale') != $language->locale ? "_".$language->locale : "";
                $all[$id]['name'.$key_lang] = \request()->input('name'.$key_lang);
            }
        }

        setting_update_item('role_verify_fields',$all);

        return redirect()->back()->with('success', $isAdd? __("Field created") : __("Field saved"));
    }

	public function bulkEdit(Request $request)
	{
        if(is_demo_mode()){
            return back()->with('error',"Demo mode: disabled");
        }
        $this->checkPermission('role_manage');

		$ids = $request->input('ids');
		$action = $request->input('action');
		if (empty($ids))
			return redirect()->back()->with('error', __('Select at leas 1 item!'));
		if (empty($action))
			return redirect()->back()->with('error', __('Select an Action!'));
		if ($action == 'delete') {
			$all = setting_item_array('role_verify_fields',[]);
			$new = Arr::except($all,$ids);
			setting_update_item('role_verify_fields',$new);
            foreach ($ids as $id) {
                $query = Role::where("id", $id);
                $row = $query->first();
                if (!empty($row)) {
                    $row->delete();
                }
            }
            return redirect()->back()->with('success', __('Deleted success!'));
		}
		return redirect()->back()->with('success', __('Updated successfully!'));
	}


	public function permission_matrix()
    {
        $this->checkPermission('role_manage');

        $permissions = PermissionHelper::all();
        $permissions_group = [
            'other' => []
        ];
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                $sCheck = strpos($permission, '_');
                if ($sCheck == false) {
                    $permissions_group['other'][] = $permission;
                    continue;
                }
                $grName = substr($permission, 0, $sCheck);
                if (!isset($permissions_group[$grName]))
                    $permissions_group[$grName] = [];
                $permissions_group[$grName][] = $permission;
            }
        }
        if (empty($permissions_group['other'])) {
            unset($permissions_group['other']);
        }
        $roles = Role::all();
        $selectedIds = [];
        if (!empty($roles)) {
            foreach ($roles as $role) {
                $selectedIds[$role->id] = $role->permissions->pluck('permission')->all();
            }
        }

        $data = [
            'permissions'       => $permissions,
            'roles'             => $roles,
            'permissions_group' => $permissions_group,
            'selectedIds'       => $selectedIds,
            'breadcrumbs' => [
                [
                    'name' => __("Users"),
                    'url'  => route('user.admin.index')
                ],
                [
                    'name' => __("Roles"),
                    'url'  => route('user.admin.role.index')
                ],
                [
                    'name' => __("Permission Matrix"),
                ],
            ]
        ];
        return view('User::admin.role.permission_matrix', $data);
    }

    public function save_permissions(Request $request)
    {
        if(is_demo_mode()){
            return back()->with('danger',  __('DEMO Mode: You can not do this') );
        }
        $this->checkPermission('role_manage');

        $matrix = $request->input('matrix');
        $matrix = is_array($matrix) ? $matrix : [];

        $roles = Role::query()->get();
        foreach ($roles as $role){
            if(empty($matrix[$role->id]))
            {
                $role->syncPermissions();
                continue;
            }
            $permissions = $matrix[$role->id];
            $role->syncPermissions($permissions);
        }
        return redirect()->back()->with('success', __('Permission Matrix updated'));
    }

    public function getForSelect2(Request $request)
    {
        $pre_selected = $request->query('pre_selected');
        $selected = $request->query('selected');
        if($pre_selected && $selected){
            if(is_array($selected))
            {
                $items = $this->role_class::select('id', 'name as text')->whereIn('id',$selected)->take(50)->get();
                return response()->json([
                    'items'=>$items
                ]);
            }else{
                $items = $this->role_class::find((int)$selected);
            }

            return [
                'results'=>$items
            ];
        }
        $q = $request->query('q');
        $query = $this->role_class::select('id','name as text');
        if ($q) {
            $query->where('name', 'like', '%' . $q . '%');
        }
        $res = $query->orderBy('id', 'desc')->limit(20)->get();
        return response()->json([
            'results' => $res
        ]);
    }

}
