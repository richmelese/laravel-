<?php
namespace Modules\User\Admin;

use Illuminate\Http\Request;
use Modules\AdminController;
use Modules\User\Exports\SubscriberExport;
use Modules\User\Models\Subscriber;

class SubscriberController extends AdminController
{
    public function __construct()
    {
        $this->setActiveMenu(route('user.admin.index'));
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    public function index(Request $request)
    {
        $this->checkPermission('newsletter_manage');
        $listCategory = new Subscriber;
        if (!empty($search = $request->query('s'))) {
            $listCategory = $listCategory->where(function ($query) use ($request) {

                $query->where('first_name', 'LIKE', '%' . $request->s . '%');
                $query->orWhere('last_name', 'LIKE', '%' . $request->s . '%');
                $query->orWhere('email', 'LIKE', '%' . $request->s . '%');
            });
        }
        $listCategory = $listCategory->orderBy('created_at', 'asc');
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $rows = $listCategory->paginate($perPage);
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
            'row'         => new Subscriber(),
            'breadcrumbs' => [
                [
                    'name' => __('User'),
                    'url'  => route('user.admin.index')
                ],
                [
                    'name'  => __('Subscribers'),
                    'class' => 'active'
                ],
            ]
        ];
        return view('User::newsletter.subscriber.index', $data);
    }

    public function show(Request $request, $id)
    {
        $this->checkPermission('newsletter_manage');
        $row = Subscriber::find($id);
        if (empty($row)) {
            return response()->json(['message' => __('Not found')], 404);
        }

        return response()->json(['data' => $row]);
    }

    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);

        return $this->store($request);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('newsletter_manage');
        $row = Subscriber::find($id);
        if (empty($row)) {
            return redirect()->back();
        }
        $data = [
            'row'         => $row,
            'breadcrumbs' => [
                [
                    'name' => __('User'),
                    'url'  => route('user.admin.index')
                ],
                [
                    'name' => __('Subscribers'),
                    'url'  => route('user.admin.subscriber.index')
                ],
                [
                    'name'  => __('Edit: :email', ['email' => $row->email]),
                    'class' => 'active'
                ],
            ]
        ];
        return view('User::newsletter.subscriber.detail', $data);
    }

    public function store(Request $request)
    {
        $this->checkPermission('newsletter_manage');
        $request->validate([
            'email'      => 'required|email|max:255',
            'first_name' => 'max:255',
            'last_name'  => 'max:255',
        ]);
        if ($request->input('id')) {
            $row = Subscriber::find($request->input('id'));
        } else {
            $row = new Subscriber();
        }
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Not found')], 404);
            }
            return redirect()->back()->with('error', __('Not found'));
        }
        $check = Subscriber::where('email', $request->input('email'))->first();
        if ($check and $check->id != $request->input('id')) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Email exists')], 422);
            }
            return redirect()->back()->with('error', __('Email exists'));
        }
        $row->fill($request->only(['email', 'first_name', 'last_name']));
        if ($row->save()) {
            if ($this->isApiRequest($request)) {
                $created = !$request->input('id');

                return response()->json([
                    'message' => $created ? __('Subscriber created') : __('Subscriber updated'),
                    'data'    => $row->fresh(),
                ]);
            }

            return redirect()->back()->with('success', __('Subscriber updated'));
        }
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission("newsletter_manage");
        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Select at least 1 item!')], 422);
            }
            return redirect()->back()->with('error', __('Select at least 1 item!'));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Select an Action!')], 422);
            }
            return redirect()->back()->with('error', __('Select an Action!'));
        }
        switch ($action) {
            case "delete":
                foreach ($ids as $id) {
                    $row = Subscriber::find($id);
                    if (!empty($row)) {
                        $row->delete();
                    }
                }
                break;
            default:
                foreach ($ids as $id) {
                    Subscriber::where("id", $id)->update(['status' => $action]);
                }
                break;
        }
        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Updated successfully!')]);
        }

        return redirect()->back()->with('success', __('Updated successfully!'));
    }

    public function export()
    {
        return (new SubscriberExport())->download('subscribers-' . date('M-d-Y') . '.xlsx');
    }
}
