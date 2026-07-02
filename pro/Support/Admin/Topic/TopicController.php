<?php

namespace Pro\Support\Admin\Topic;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AdminController;
use Modules\Language\Models\Language;
use Pro\Support\Models\Topic;
use Pro\Support\Models\TopicCat;
use Pro\Support\Models\TopicTranslation;

class TopicController extends AdminController
{
    private TopicCat $cat;
    private Topic $topic;

    public function __construct(TopicCat $cat, Topic $topic)
    {
        $this->setActiveMenu(route('support.admin.topic.index'));
        $this->cat   = $cat;
        $this->topic = $topic;
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*') || $request->is('api/admin/*') || $request->is('api/support/admin/*');
    }

    public function index(Request $request)
    {
        $this->checkPermission('support_topic_view');

        $query = $this->topic->query()->orderBy('id', 'desc');

        if ($s = $request->query('s')) {
            $query->where('title', 'LIKE', '%' . $s . '%')->orderBy('title', 'asc');
        }
        if ($catId = $request->query('cat_id')) {
            $query->where('cat_id', $catId);
        }

        if ($this->isApiRequest($request)) {
            $perPage = max(1, (int) $request->query('per_page', 20));
            $rows    = $query->with(['author', 'cat'])->paginate($perPage);
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
            'rows'        => $query->with(['author', 'cat'])->paginate(20),
            'categories'  => $this->cat->get()->toTree(),
            'breadcrumbs' => [
                ['name' => __('Topics'), 'url' => route('support.admin.topic.index')],
                ['name' => __('All'), 'class' => 'active'],
            ],
            'languages'  => Language::getActive(false),
            'page_title' => __('Topic Management'),
        ];
        return view('Support::admin.topic.index', $data);
    }

    public function create(Request $request)
    {
        $this->checkPermission('support_topic_create');

        $row = new $this->topic;
        $row->fill(['status' => 'publish']);

        if ($this->isApiRequest($request)) {
            return response()->json([
                'success' => true,
                'data'    => $row,
            ]);
        }

        $data = [
            'categories'  => $this->cat->get()->toTree(),
            'row'         => $row,
            'breadcrumbs' => [
                ['name' => __('Support'), 'url' => 'admin/module/knowleagebase'],
                ['name' => __('Add Support'), 'class' => 'active'],
            ],
            'translation' => new TopicTranslation(),
        ];
        return view('Support::admin.topic.detail', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('support_topic_update');

        $row = $this->topic->find($id);
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['success' => false, 'message' => __('Topic not found')], 404);
            }
            return redirect(route('support.admin.topic.index'));
        }

        $translation = $row->translate($request->query('lang'));

        if ($this->isApiRequest($request)) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'row'               => $row,
                    'translation'       => $translation,
                    'tags'              => $row->tags,
                    'enable_multi_lang' => true,
                ],
            ]);
        }

        $data = [
            'row'               => $row,
            'translation'       => $translation,
            'categories'        => $this->cat->get()->toTree(),
            'tags'              => $row->tags,
            'enable_multi_lang' => true,
        ];
        return view('Support::admin.topic.detail', $data);
    }

    public function store(Request $request, $id)
    {
        if ($id > 0) {
            $this->checkPermission('support_topic_update');
            $row = $this->topic->find($id);
            if (empty($row)) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['success' => false, 'message' => __('Topic not found')], 404);
                }
                return redirect(route('support.admin.topic.index'));
            }
        } else {
            $this->checkPermission('support_topic_create');
            $row            = new $this->topic();
            $row->status    = 'publish';
            $row->author_id = Auth::id();
        }

        $row->fill($request->input());
        $row->display_order = $request->input('display_order');

        $res = $row->saveOriginOrTranslation($request->query('lang'), true);

        if ($res) {
            if (is_default_lang($request->query('lang'))) {
                $row->saveTag($request->input('tag_name'), $request->input('tag_ids'));
            }
            if ($this->isApiRequest($request)) {
                return response()->json([
                    'success' => true,
                    'message' => $id > 0 ? __('Support updated') : __('Topic created'),
                    'data'    => $row->fresh(['cat', 'tags']),
                ], $id > 0 ? 200 : 201);
            }
            if ($id > 0) {
                return back()->with('success', __('Support updated'));
            }
            return redirect(route('support.admin.topic.edit', $row->id))->with('success', __('Topic created'));
        }

        if ($this->isApiRequest($request)) {
            return response()->json(['success' => false, 'message' => __('Could not save topic')], 500);
        }
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission('support_topic_update');

        $ids    = $request->input('ids');
        $action = $request->input('action');

        if (empty($ids) || !is_array($ids)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['success' => false, 'message' => __('No items selected!')], 422);
            }
            return redirect()->back()->with('error', __('No items selected!'));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['success' => false, 'message' => __('Please select an action!')], 422);
            }
            return redirect()->back()->with('error', __('Please select an action!'));
        }

        if ($action === 'delete') {
            foreach ($ids as $id) {
                $query = $this->topic->where('id', $id);
                if (!$this->hasPermission('support_topic_manage_others')) {
                    $query->where('create_user', Auth::id());
                    $this->checkPermission('support_topic_delete');
                }
                $item = $query->first();
                if (!empty($item)) {
                    $item->delete();
                }
            }
        } else {
            foreach ($ids as $id) {
                $query = $this->topic->where('id', $id);
                if (!$this->hasPermission('support_topic_manage_others')) {
                    $query->where('create_user', Auth::id());
                    $this->checkPermission('support_topic_update');
                }
                $query->update(['status' => $action]);
            }
        }

        if ($this->isApiRequest($request)) {
            return response()->json(['success' => true, 'message' => __('Update success!')]);
        }
        return redirect()->back()->with('success', __('Update success!'));
    }

    public function clone($id)
    {
        $a        = $this->topic->find($id);
        $b        = $a->replicate();
        $b->title .= ' Copy';
        $b->status = 'draft';
        $b->save();
        return back()->with('success', __('Duplicated'));
    }
}
