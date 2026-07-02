<?php

namespace Pro\Support\Admin\Topic;

use Illuminate\Http\Request;
use Modules\AdminController;
use Pro\Support\Models\TopicCat;
use Pro\Support\Models\TopicCatTranslation;

class CategoryController extends AdminController
{
    private TopicCat $cat;
    private TopicCatTranslation $catTranslation;

    public function __construct(TopicCat $cat, TopicCatTranslation $catTranslation)
    {
        $this->setActiveMenu(route('support.admin.topic.index'));
        $this->cat            = $cat;
        $this->catTranslation = $catTranslation;
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*') || $request->is('api/admin/*') || $request->is('api/support/admin/*');
    }

    public function index(Request $request)
    {
        $this->checkPermission('support_topic_category');

        $query = $this->cat->query();
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
            'rows'        => $query->get()->toTree(),
            'row'         => new $this->cat(),
            'page_title'  => __('Manage category'),
            'breadcrumbs' => [
                ['name' => __('Topic'), 'url' => route('support.admin.topic.index')],
                ['name' => __('Category'), 'class' => 'active'],
            ],
            'translation' => new $this->catTranslation(),
        ];
        return view('Support::admin.topic.category.index', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('support_topic_category');

        $row = $this->cat::find($id);
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['success' => false, 'message' => __('Category not found')], 404);
            }
            return redirect(route('support.admin.topic.category.index'));
        }

        $translation = $row->translate($request->query('lang', get_main_lang()));

        if ($this->isApiRequest($request)) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'row'               => $row,
                    'translation'       => $translation,
                    'enable_multi_lang' => true,
                ],
            ]);
        }

        $data = [
            'row'               => $row,
            'translation'       => $translation,
            'parents'           => $this->cat::get()->toTree(),
            'enable_multi_lang' => true,
            'page_title'        => __('Edit category'),
        ];
        return view('Support::admin.topic.category.detail', $data);
    }

    public function store(Request $request, $id)
    {
        $this->checkPermission('support_topic_category');

        $request->validate(['name' => 'required']);

        if ($id > 0) {
            $row = $this->cat::find($id);
            if (empty($row)) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['success' => false, 'message' => __('Category not found')], 404);
                }
                return redirect(route('support.admin.topic.category.index'));
            }
        } else {
            $row         = new $this->cat();
            $row->status = 'publish';
        }

        $row->fill($request->input());
        $row->display_order = $request->input('display_order');
        $row->image_id      = $request->input('image_id');

        $res = $row->saveOriginOrTranslation($request->input('lang'));

        if ($res) {
            if ($this->isApiRequest($request)) {
                return response()->json([
                    'success' => true,
                    'message' => $id > 0 ? __('Category updated') : __('Category created'),
                    'data'    => $row,
                ], $id > 0 ? 200 : 201);
            }
            if ($id > 0) {
                return back()->with('success', __('Category updated'));
            }
            return redirect(route('support.admin.topic.category.index'))->with('success', __('Category created'));
        }

        if ($this->isApiRequest($request)) {
            return response()->json(['success' => false, 'message' => __('Could not save category')], 500);
        }
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission('support_topic_category');

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
                $item = $this->cat::find($id);
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
            $item = $this->cat::find($selected);
            return response()->json(['results' => $item ? [['id' => $item->id, 'text' => $item->name]] : []]);
        }

        $q     = $request->query('q');
        $query = $this->cat::select('id', 'name as text')->where('status', 'publish');
        if ($q) {
            $query->where('name', 'like', '%' . $q . '%');
        }
        return response()->json(['results' => $query->orderBy('id', 'desc')->limit(20)->get()]);
    }
}
