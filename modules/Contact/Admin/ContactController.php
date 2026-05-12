<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 6/5/2019
 * Time: 11:31 AM
 */
namespace Modules\Contact\Admin;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Modules\AdminController;
use Modules\Contact\Models\Contact;

class ContactController extends AdminController
{
    public function __construct()
    {
        if(Route::has('booking.admin.index'))
        $this->setActiveMenu(route('booking.admin.index'));
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    public function index(Request $request)
    {
        $this->checkPermission('contact_manage');

        $s = $request->query('s');
        $query = Contact::query()->orderBy('id', 'desc');
        if ($s) {
            $query->where(function ($builder) use ($s) {
                $builder->where('name', 'LIKE', '%' . $s . '%')
                    ->orWhere('email', 'LIKE', '%' . $s . '%')
                    ->orWhere('message', 'LIKE', '%' . $s . '%');
            });
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $rows = $query->paginate($perPage);

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
                    'name' => __('Contact Submissions'),
                    'url'  => route('contact.admin.index')
                ],
                [
                    'name'  => __('All'),
                    'class' => 'active'
                ],
            ]
        ];
        return view('Contact::admin.index', $data);
    }

    public function getForSelect2(Request $request)
    {
        $q = $request->query('q');
        $query = Contact::select('id', 'title as text');
        if ($q) {
            $query->where('title', 'like', '%' . $q . '%');
        }
        $res = $query->orderBy('id', 'desc')->limit(20)->get();
        return response()->json([
            'results' => $res
        ]);
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission('contact_manage');

        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids)) {
            return redirect()->back()->with('error', __('Please select at least 1 item!'));
        }
        if (empty($action)) {
            return redirect()->back()->with('error', __('No Action is selected!'));
        }
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = Contact::where("id", $id)->first();
                if(!empty($query)){
                    $query->delete();
                }
            }
        } else {
            foreach ($ids as $id) {
                $query = Contact::where("id", $id);
                $query->update(['status' => $action]);
            }
        }
        return redirect()->back()->with('success', __('Update success!'));
    }
}
