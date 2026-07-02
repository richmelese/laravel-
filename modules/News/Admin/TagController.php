<?php
namespace Modules\News\Admin;

use Illuminate\Http\Request;
use Modules\AdminController;
use Modules\News\Models\Tag;
use Modules\News\Models\TagTranslation;
use Modules\News\Models\NewsTag;

class TagController extends AdminController
{
    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*') || $request->is('api/admin/*');
    }

    public function __construct()
    {
        $this->setActiveMenu(route('news.admin.index'));
    }

    public function index(Request $request)
    {
        $this->checkPermission('news_manage_others');

        $query = Tag::query();
        if ($s = $request->query('s')) {
            $query->where('name', 'LIKE', '%' . $s . '%');
        }
        $query->orderBy('name', 'asc');

        if ($this->isApiRequest($request)) {
            $perPage = max(1, (int) $request->query('per_page', 20));
            $rows = $query->paginate($perPage);
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
            'rows'        => $query->paginate(20),
            'row'         => new Tag(),
            'breadcrumbs' => [
                ['name' => __('News'), 'url' => route('news.admin.index')],
                ['name' => __('Tag'), 'class' => 'active'],
            ],
            'translation' => new TagTranslation(),
        ];
        return view('News::admin.tag.index', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('news_manage_others');

        $row = Tag::find($id);
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Tag not found')], 404);
            }
            return redirect(route('news.admin.tag.index'));
        }

        $translation = $row->translate($request->query('lang', get_main_lang()));
        $data = [
            'row'         => $row,
            'translation' => $translation,
            'parents'     => Tag::get(),
            'enable_multi_lang' => true,
        ];

        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => [
                    'row'               => $data['row'],
                    'translation'       => $data['translation'],
                    'enable_multi_lang' => $data['enable_multi_lang'],
                ],
            ]);
        }
        return view('News::admin.tag.detail', $data);
    }

    public function store(Request $request, $id)
    {
        $this->checkPermission('news_manage_others');

        if ($id > 0) {
            $row = Tag::find($id);
            if (empty($row)) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Tag not found')], 404);
                }
                return redirect(route('news.admin.tag.index'));
            }
        } else {
            $row = new Tag();
        }

        $row->fill($request->input());
        $res = $row->saveOriginOrTranslation($request->input('lang'));

        if ($res) {
            if ($this->isApiRequest($request)) {
                return response()->json([
                    'message' => $id > 0 ? __('Tag updated') : __('Tag Created'),
                    'data'    => $row,
                ], $id > 0 ? 200 : 201);
            }
            if ($id > 0) {
                return back()->with('success', __('Tag updated'));
            }
            return redirect(route('news.admin.tag.index'))->with('success', __('Tag Created'));
        }

        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Could not save tag')], 500);
        }
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission('news_manage_others');

        $ids    = $request->input('ids');
        $action = $request->input('action');

        if (empty($ids) || !is_array($ids)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Please select at least 1 item!')], 422);
            }
            return redirect()->back()->with('error', __('Please select at least 1 item!'));
        }
        if (empty($action)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Please select an Action!')], 422);
            }
            return redirect()->back()->with('error', __('Please select an Action!'));
        }

        if ($action == 'delete') {
            foreach ($ids as $id) {
                $tag = Tag::find($id);
                if (!empty($tag)) {
                    $tag->delete();
                }
                NewsTag::where('tag_id', $id)->delete();
            }
        }

        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Update success!')]);
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
        $res = $query->orderBy('name', 'asc')->limit(50)->get();

        return response()->json(['results' => $res]);
    }
}
