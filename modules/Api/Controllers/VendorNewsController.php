<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\News\Models\News;
use Modules\News\Models\NewsCategory;

/**
 * Vendor "my news" JSON APIs for SPA clients using Sanctum Bearer tokens.
 * Mirrors web vendor news controller scope (author_id = current user), but returns JSON.
 */
class VendorNewsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (! $user->hasPermission('news_view')) {
            return response()->json(['status' => 0, 'message' => __('Permission denied')], 403);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));

        $query = News::query()->where('author_id', $user->id)->orderBy('id', 'desc');

        if ($cat = $request->query('cat_id')) {
            $query->where('cat_id', $cat);
        }
        if ($search = $request->query('s')) {
            $query->where('title', 'LIKE', '%' . $search . '%');
            $query->orderBy('title', 'asc');
        }

        $rows = $query->with('author')->with('category')->paginate($perPage);

        return $this->sendSuccess([
            'data' => $rows->items(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
            'categories' => NewsCategory::get(),
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user->hasPermission('news_view')) {
            return response()->json(['status' => 0, 'message' => __('Permission denied')], 403);
        }

        $row = News::whereId($id)->where('author_id', $user->id)->first();
        if (empty($row)) {
            return response()->json(['status' => 0, 'message' => __('News not found')], 404);
        }

        $translation = $row->translate($request->query('lang'));

        return $this->sendSuccess([
            'data' => $row,
            'translation' => $translation,
            'tags' => $row->getTags(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['title' => 'required']);

        $user = Auth::user();
        if (! $user->hasPermission('news_create')) {
            return response()->json(['status' => 0, 'message' => __('Permission denied')], 403);
        }

        $row = new News();
        $row->author_id = $user->id;
        $row->fill($request->input());
        if ($request->input('slug')) {
            $row->slug = $request->input('slug');
        }
        if (setting_item('news_vendor_need_approve') && $row->status == 'publish') {
            $row->status = 'draft';
        }

        $res = $row->saveOriginOrTranslation($request->query('lang'), true);
        if (! $res) {
            return response()->json(['status' => 0, 'message' => __('Could not create news')], 500);
        }

        if (is_default_lang($request->query('lang'))) {
            $row->saveTag($request->input('tag_name'), $request->input('tag_ids'));
        }

        return $this->sendSuccess(['data' => $row], __('News created'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['title' => 'required']);

        $user = Auth::user();
        if (! $user->hasPermission('news_update')) {
            return response()->json(['status' => 0, 'message' => __('Permission denied')], 403);
        }

        $row = News::whereId($id)->where('author_id', $user->id)->first();
        if (empty($row)) {
            return response()->json(['status' => 0, 'message' => __('News not found')], 404);
        }

        $old_status = $row->status;
        $row->fill($request->input());
        if ($request->input('slug')) {
            $row->slug = $request->input('slug');
        }
        if (setting_item('news_vendor_need_approve')) {
            if ($old_status != 'publish' and $row->status == 'publish') {
                $row->status = 'draft';
            }
        }

        $res = $row->saveOriginOrTranslation($request->query('lang'), true);
        if (! $res) {
            return response()->json(['status' => 0, 'message' => __('Could not update news')], 500);
        }

        if (is_default_lang($request->query('lang'))) {
            $row->saveTag($request->input('tag_name'), $request->input('tag_ids'));
        }

        return $this->sendSuccess(['data' => $row], __('News updated'));
    }

    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user->hasPermission('news_delete')) {
            return response()->json(['status' => 0, 'message' => __('Permission denied')], 403);
        }

        $row = News::whereId($id)->where('author_id', $user->id)->first();
        if (empty($row)) {
            return response()->json(['status' => 0, 'message' => __('News not found')], 404);
        }

        $row->delete();

        return $this->sendSuccess([], __('News deleted'));
    }

    public function bulkEdit(Request $request)
    {
        $user = Auth::user();
        $ids = $request->input('ids');
        $action = $request->input('action');

        if (empty($ids) || ! is_array($ids)) {
            return response()->json(['status' => 0, 'message' => __('No items selected!')], 422);
        }

        $allowedActions = ['delete', 'draft', 'pending'];
        if (! setting_item('news_vendor_need_approve')) {
            $allowedActions[] = 'publish';
        }
        if (! in_array($action, $allowedActions)) {
            return response()->json(['status' => 0, 'message' => __('Please select an action!')], 422);
        }

        if ($action == 'delete') {
            if (! $user->hasPermission('news_delete')) {
                return response()->json(['status' => 0, 'message' => __('Permission denied')], 403);
            }
            News::where('author_id', $user->id)->whereIn('id', $ids)->delete();
        } else {
            if (! $user->hasPermission('news_update')) {
                return response()->json(['status' => 0, 'message' => __('Permission denied')], 403);
            }
            News::where('author_id', $user->id)->whereIn('id', $ids)->update(['status' => $action]);
        }

        return $this->sendSuccess([], __('Update success!'));
    }
}
