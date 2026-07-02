<?php

namespace Pro\Support\Admin\Topic;

use Illuminate\Http\Request;
use Modules\AdminController;
use Pro\Support\Models\Tag;

class TagController extends AdminController
{
    public function __construct()
    {
        $this->setActiveMenu(route('support.admin.topic.index'));
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*') || $request->is('api/admin/*') || $request->is('api/support/admin/*');
    }

    public function index(Request $request)
    {
        $this->checkPermission('support_topic_create');

        $query = Tag::query();
        if ($s = $request->query('s')) {
            $query->where('name', 'LIKE', '%' . $s . '%');
        }
        $query->orderBy('name', 'asc');

        if ($this->isApiRequest($request)) {
            $perPage = max(1, (int) $request->query('per_page', 20));
            $rows    = $query->paginate($perPage);
            return response()->json([
                'success'   => true,
                'data'      => $rows->items(),
                'total'     => $rows->total(),
                'max_pages' => $rows->lastPage(),
                'meta'      => [
                    'current_page' => $rows->currentPage(),
                    'per_page'     => $rows->perPage(),
                    'total'        => $rows->total(),
                    'last_page'    => $rows->lastPage(),
                ],
            ]);
        }

        $data = [
            'rows'        => $query->paginate(20),
            'row'         => new Tag(),
            'breadcrumbs' => [
                ['name' => __('Support'), 'url' => route('support.admin.topic.index')],
                ['name' => __('Tag'), 'class' => 'active'],
            ],
        ];
        return view('Support::admin.topic.tag.index', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('support_topic_create');

        $row = Tag::find($id);
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['success' => false, 'message' => __('Tag not found')], 404);
            }
            return redirect(route('support.admin.topic.tag.index'));
        }

        if ($this->isApiRequest($request)) {
            return response()->json(['success' => true, 'data' => $row]);
        }

        return view('Support::admin.topic.tag.detail', ['row' => $row, 'enable_multi_lang' => true]);
    }

    public function store(Request $request, $id)
    {
        $this->checkPermission('support_topic_create');

        if ($id > 0) {
            $row = Tag::find($id);
            if (empty($row)) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['success' => false, 'message' => __('Tag not found')], 404);
                }
                return redirect(route('support.admin.topic.tag.index'));
            }
        } else {
            $row = new Tag();
        }

        $row->fill($request->input());
        $res = $row->save();
        $row->saveSEO($request, $request->input('lang'));

        if ($res) {
            if ($this->isApiRequest($request)) {
                return response()->json([
                    'success' => true,
                    'message' => $id > 0 ? __('Tag updated') : __('Tag Created'),
                    'data'    => $row,
                ], $id > 0 ? 200 : 201);
            }
            if ($id > 0) {
                return back()->with('success', __('Tag updated'));
            }
            return redirect(route('support.admin.topic.tag.index'))->with('success', __('Tag Created'));
        }

        if ($this->isApiRequest($request)) {
            return response()->json(['success' => false, 'message' => __('Could not save tag')], 500);
        }
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission('support_topic_create');

        $ids    = $request->input('ids');
        $action = $request->input('action');

        if (empty($ids) || !is_array($ids)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['success' => false, 'message' => __('Please select at least 1 item!')], 422);
            }
            return redirect()->back()->with('error', __('Please select at least 1 item!'));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['success' => false, 'message' => __('Please select an Action!')], 422);
            }
            return redirect()->back()->with('error', __('Please select an Action!'));
        }

        if ($action === 'delete') {
            foreach ($ids as $id) {
                $item = Tag::find($id);
                if (!empty($item)) {
                    $item->delete();
                }
            }
        }

        if ($this->isApiRequest($request)) {
            return response()->json(['success' => true, 'message' => __('Update success!')]);
        }
        return redirect()->back()->with('success', __('Update success!'));
    }

    public function getForSelect2(Request $request)
    {
        $pre_selected = $request->query('pre_selected');
        $selected     = $request->query('selected');

        if ($pre_selected && $selected) {
            $items = Tag::whereIn('id', (array) $selected)->select('id', 'name as text')->get();
            return response()->json(['results' => $items]);
        }

        $q     = $request->query('q');
        $query = Tag::select('id', 'name as text');
        if ($q) {
            $query->where('name', 'like', '%' . $q . '%');
        }
        return response()->json(['results' => $query->orderBy('name', 'asc')->limit(50)->get()]);
    }
}
