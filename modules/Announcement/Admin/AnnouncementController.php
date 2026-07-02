<?php

namespace Modules\Announcement\Admin;

use Illuminate\Http\Request;
use Modules\AdminController;
use Modules\Announcement\Models\Announcement;

class AnnouncementController extends AdminController
{
    public function __construct()
    {
        $this->setActiveMenu(route('announcement.admin.index'));
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    public function index(Request $request)
    {
        $this->checkPermission('announcement_view');

        $query = Announcement::query()->orderByDesc('id');

        if ($s = $request->query('s')) {
            $query->where('title', 'LIKE', '%' . $s . '%');
        }
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
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
                'types' => Announcement::getTypes(),
            ]);
        }

        return view('Announcement::admin.index', [
            'rows'       => $rows,
            'types'      => Announcement::getTypes(),
            'page_title' => __('Announcement Management'),
            'breadcrumbs'=> [
                ['name' => __('Announcements'), 'url' => route('announcement.admin.index')],
                ['name' => __('All'), 'class' => 'active'],
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $this->checkPermission('announcement_view');

        $row = Announcement::find($id);
        if (empty($row)) {
            return response()->json(['message' => __('Announcement not found')], 404);
        }

        return response()->json(['data' => $row]);
    }

    public function store(Request $request, $id = 0)
    {
        if (is_demo_mode()) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
            }
            return back()->with('error', __('DEMO MODE: You are not allowed to change data'));
        }

        $request->validate([
            'title'  => 'required|string|max:255',
            'status' => 'required|in:publish,draft',
            'type'   => 'nullable|string|max:50',
            // Normalize empty strings as NULL in validation (nullable).
            // This prevents MySQL from saving them as "0000-00-00 00:00:00",
            // which would otherwise make announcements appear expired/not active.
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'url'         => 'nullable|string|max:255',
            'button_label'=> 'nullable|string|max:100',
        ]);

        if ($id > 0) {
            $this->checkPermission('announcement_update');
            $row = Announcement::find($id);
            if (empty($row)) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Announcement not found')], 404);
                }
                return redirect(route('announcement.admin.index'));
            }
        } else {
            $this->checkPermission('announcement_create');
            $row = new Announcement();
        }

        $data = $request->only(['title', 'content', 'type', 'status', 'start_date', 'end_date', 'url', 'button_label']);
        // Extra safety for cases where the request sends empty strings.
        foreach (['start_date', 'end_date'] as $key) {
            if (array_key_exists($key, $data) && is_string($data[$key]) && trim($data[$key]) === '') {
                $data[$key] = null;
            }
        }
        $row->fill($data);
        $row->save();

        if ($this->isApiRequest($request)) {
            return response()->json([
                'message' => $id > 0 ? __('Announcement updated') : __('Announcement created'),
                'data'    => $row,
            ], $id > 0 ? 200 : 201);
        }

        return $id > 0
            ? back()->with('success', __('Announcement updated'))
            : redirect(route('announcement.admin.edit', $row->id))->with('success', __('Announcement created'));
    }

    public function create(Request $request)
    {
        $this->checkPermission('announcement_create');

        $row = new Announcement(['status' => 'publish', 'type' => 'general']);

        if ($this->isApiRequest($request)) {
            return response()->json([
                'data'  => ['row' => $row],
                'types' => Announcement::getTypes(),
            ]);
        }

        return view('Announcement::admin.detail', [
            'row'        => $row,
            'types'      => Announcement::getTypes(),
            'page_title' => __('Add Announcement'),
            'breadcrumbs'=> [
                ['name' => __('Announcements'), 'url' => route('announcement.admin.index')],
                ['name' => __('Add'), 'class' => 'active'],
            ],
        ]);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('announcement_update');

        $row = Announcement::find($id);
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Announcement not found')], 404);
            }
            return redirect(route('announcement.admin.index'));
        }

        if ($this->isApiRequest($request)) {
            return response()->json([
                'data'  => ['row' => $row],
                'types' => Announcement::getTypes(),
            ]);
        }

        return view('Announcement::admin.detail', [
            'row'        => $row,
            'types'      => Announcement::getTypes(),
            'page_title' => __('Edit: :name', ['name' => $row->title]),
            'breadcrumbs'=> [
                ['name' => __('Announcements'), 'url' => route('announcement.admin.index')],
                ['name' => __('Edit'), 'class' => 'active'],
            ],
        ]);
    }

    public function destroy(Request $request, $id)
    {
        if (is_demo_mode()) {
            return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
        }

        $this->checkPermission('announcement_delete');

        $row = Announcement::find($id);
        if (empty($row)) {
            return response()->json(['message' => __('Announcement not found')], 404);
        }

        $row->delete();

        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Announcement deleted')]);
        }

        return redirect(route('announcement.admin.index'))->with('success', __('Announcement deleted'));
    }

    public function bulkEdit(Request $request)
    {
        if (is_demo_mode()) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('DEMO MODE: You are not allowed to change data')], 403);
            }
            return redirect()->back()->with('error', __('DEMO MODE: You are not allowed to change data'));
        }

        $ids    = $request->input('ids');
        $action = $request->input('action');

        if (empty($ids) || !is_array($ids)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('No items selected!')], 422);
            }
            return redirect()->back()->with('error', __('No items selected!'));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Please select an action!')], 422);
            }
            return redirect()->back()->with('error', __('Please select an action!'));
        }

        switch ($action) {
            case 'delete':
                $this->checkPermission('announcement_delete');
                Announcement::whereIn('id', $ids)->delete();
                break;
            default:
                $this->checkPermission('announcement_update');
                Announcement::whereIn('id', $ids)->update(['status' => $action]);
                break;
        }

        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Update success!')]);
        }
        return redirect()->back()->with('success', __('Update success!'));
    }
}
