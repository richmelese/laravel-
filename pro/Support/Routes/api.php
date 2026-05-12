<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Pro\Support\Models\Tag;
use Pro\Support\Models\Ticket;
use Pro\Support\Models\TicketCat;
use Pro\Support\Models\TicketReply;
use Pro\Support\Models\Topic;
use Pro\Support\Models\TopicCat;

Route::prefix('support')->group(function () {
    Route::prefix('topic')->group(function () {
        Route::get('/', function (Request $request) {
            $query = Topic::query()->where('status', 'publish')->with(['cat', 'tags']);

            if ($request->filled('s')) {
                $query->where('title', 'like', '%' . $request->query('s') . '%');
            }
            if ($request->filled('catId')) {
                $query->where('cat_id', $request->query('catId'));
            }
            if ($request->filled('tagId')) {
                $query->whereHas('tags', function ($q) use ($request) {
                    $q->where('bc_support_topic_tags.id', $request->query('tagId'));
                });
            }

            $rows = $query->orderByDesc('id')->paginate((int) $request->query('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $rows->items(),
                'total' => $rows->total(),
                'max_pages' => $rows->lastPage(),
            ]);
        })->name('api.support.topic.index');

        Route::get('/{slug}', function (string $slug) {
            $topic = Topic::query()->where('slug', $slug)->where('status', 'publish')->with(['cat', 'tags'])->first();
            if (!$topic) {
                return response()->json(['success' => false, 'message' => 'Topic not found'], 404);
            }

            $topic->increment('views');

            return response()->json([
                'success' => true,
                'data' => $topic->fresh(['cat', 'tags']),
            ]);
        })->name('api.support.topic.show');

        Route::get('/cat/{slug}', function (Request $request, string $slug) {
            $category = TopicCat::query()->where('slug', $slug)->first();
            if (!$category) {
                return response()->json(['success' => false, 'message' => 'Category not found'], 404);
            }

            $rows = Topic::query()
                ->where('status', 'publish')
                ->where('cat_id', $category->id)
                ->with(['cat', 'tags'])
                ->orderByDesc('id')
                ->paginate((int) $request->query('per_page', 20));

            return response()->json([
                'success' => true,
                'category' => $category,
                'data' => $rows->items(),
                'total' => $rows->total(),
                'max_pages' => $rows->lastPage(),
            ]);
        })->name('api.support.topic.category.show');

        Route::get('/tag/{slug}', function (Request $request, string $slug) {
            $tag = Tag::query()->where('slug', $slug)->first();
            if (!$tag) {
                return response()->json(['success' => false, 'message' => 'Tag not found'], 404);
            }

            $rows = Topic::query()
                ->where('status', 'publish')
                ->whereHas('tags', function ($q) use ($tag) {
                    $q->where('bc_support_topic_tags.id', $tag->id);
                })
                ->with(['cat', 'tags'])
                ->orderByDesc('id')
                ->paginate((int) $request->query('per_page', 20));

            return response()->json([
                'success' => true,
                'tag' => $tag,
                'data' => $rows->items(),
                'total' => $rows->total(),
                'max_pages' => $rows->lastPage(),
            ]);
        })->name('api.support.topic.tag.show');
    });

    Route::prefix('ticket')->middleware('auth:sanctum')->group(function () {
        Route::get('/', function (Request $request) {
            $params = [
                's' => $request->query('s'),
                'catId' => $request->query('catId'),
            ];

            $user = Auth::user();
            $isAgent = $user->hasPermission('support_ticket_reply');
            $isAdmin = $user->hasPermission('support_ticket_manage');
            $supportTicketViewType = setting_item('support_ticket_view_type');

            if ($isAgent || $isAdmin) {
                if (!$isAdmin && $supportTicketViewType !== 'all') {
                    $params['agentId'] = $user->id;
                }
            } else {
                $params['customerId'] = $user->id;
            }

            $query = Ticket::query()->with(['cat', 'last_reply'])->where(function ($q) use ($params) {
                if (!empty($params['s'])) {
                    $q->where('title', 'like', '%' . $params['s'] . '%');
                }
                if (!empty($params['catId'])) {
                    $q->where('cat_id', $params['catId']);
                }
                if (!empty($params['customerId'])) {
                    $q->where('customer_id', $params['customerId']);
                }
                if (!empty($params['agentId'])) {
                    $q->where('agent_id', $params['agentId']);
                }
            });

            $rows = $query->orderByDesc('id')->paginate((int) $request->query('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $rows->items(),
                'total' => $rows->total(),
                'max_pages' => $rows->lastPage(),
            ]);
        })->name('api.support.ticket.index');

        Route::get('/{id}', function (int $id) {
            $user = Auth::user();
            $isAgent = $user->hasPermission('support_ticket_reply');
            $isAdmin = $user->hasPermission('support_ticket_manage');

            $query = Ticket::query()->where('id', $id)->with(['cat', 'replies.user', 'customer', 'agent']);
            if (!$isAdmin) {
                if ($isAgent) {
                    $query->where('agent_id', $user->id);
                } else {
                    $query->where('customer_id', $user->id);
                }
            }
            $ticket = $query->first();
            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
            }

            return response()->json(['success' => true, 'data' => $ticket]);
        })->name('api.support.ticket.show');

        Route::post('/', function (Request $request) {
            $user = Auth::user();
            if (!$user->hasPermission('support_ticket_create')) {
                return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
            }

            $validated = $request->validate([
                'title' => 'required|max:255',
                'content' => 'required|max:6500',
                'cat_id' => 'required|integer',
            ]);

            $ticket = new Ticket();
            $ticket->customer_id = $user->id;
            $ticket->last_reply_by = $user->id;
            $ticket->last_reply_at = now();
            $ticket->status = 'open';
            $ticket->title = $validated['title'];
            $ticket->content = strip_tags($validated['content']);
            $ticket->cat_id = $validated['cat_id'];
            $ticket->save();

            return response()->json(['success' => true, 'message' => 'Ticket created', 'data' => $ticket], 201);
        })->name('api.support.ticket.store');

        Route::post('/{id}/reply', function (Request $request, int $id) {
            $validated = $request->validate([
                'content' => 'required|max:6500',
            ]);

            $user = Auth::user();
            $isAgent = $user->hasPermission('support_ticket_reply');
            $isAdmin = $user->hasPermission('support_ticket_manage');

            $query = Ticket::query()->where('id', $id);
            if (!$isAdmin) {
                if ($isAgent) {
                    $query->where('agent_id', $user->id);
                } else {
                    $query->where('customer_id', $user->id);
                }
            }
            $ticket = $query->first();
            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
            }
            if ($ticket->status === 'closed') {
                return response()->json(['success' => false, 'message' => 'Ticket closed'], 422);
            }

            $reply = new TicketReply();
            $reply->content = clean($validated['content']);
            $reply->user_id = $user->id;
            $reply->ticket_id = $ticket->id;
            $reply->save();

            $ticket->last_reply_by = $user->id;
            $ticket->last_reply_at = now();
            $ticket->save();

            return response()->json(['success' => true, 'message' => 'Reply created', 'data' => $reply], 201);
        })->name('api.support.ticket.reply');

        Route::post('/{id}/action', function (Request $request, int $id) {
            $user = Auth::user();
            if (!$user->hasPermission('support_ticket_reply') || !$user->hasPermission('support_ticket_manage')) {
                return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
            }

            $validated = $request->validate([
                'action' => 'required|string',
                'status' => 'nullable|string',
                'note_content' => 'nullable|string',
            ]);

            $ticket = Ticket::query()->where('id', $id)->first();
            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
            }

            if ($validated['action'] === 'status' && !empty($validated['status'])) {
                if ($ticket->status !== $validated['status']) {
                    $ticket->status = $validated['status'];
                    if ($validated['status'] === 'closed') {
                        $ticket->closed_at = now();
                        $ticket->closed_by = $user->id;
                    }
                    $ticket->save();
                }
            }

            return response()->json(['success' => true, 'message' => 'Saved', 'data' => $ticket]);
        })->name('api.support.ticket.action');
    });

    Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
        Route::prefix('topic')->group(function () {
            Route::get('/', function (Request $request) {
                if (!Auth::user()->hasPermission('support_topic_view')) {
                    return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
                }

                $query = Topic::query()->with(['cat', 'tags']);
                if ($request->filled('s')) {
                    $query->where('title', 'like', '%' . $request->query('s') . '%');
                }
                $rows = $query->orderByDesc('id')->paginate((int) $request->query('per_page', 20));

                return response()->json([
                    'success' => true,
                    'data' => $rows->items(),
                    'total' => $rows->total(),
                    'max_pages' => $rows->lastPage(),
                ]);
            })->name('api.support.admin.topic.index');

            Route::get('/categories', function () {
                if (!Auth::user()->hasPermission('support_topic_category')) {
                    return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
                }
                return response()->json(['success' => true, 'data' => TopicCat::query()->orderByDesc('id')->get()]);
            })->name('api.support.admin.topic.categories');

            Route::get('/tags', function () {
                if (!Auth::user()->hasPermission('support_topic_create')) {
                    return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
                }
                return response()->json(['success' => true, 'data' => Tag::query()->orderByDesc('id')->get()]);
            })->name('api.support.admin.topic.tags');
        });

        Route::prefix('ticket')->group(function () {
            Route::get('/', function (Request $request) {
                if (!Auth::user()->hasPermission('support_ticket_manage')) {
                    return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
                }

                $query = Ticket::query()->with(['cat', 'last_reply', 'customer', 'agent']);
                if ($request->filled('s')) {
                    $query->where('title', 'like', '%' . $request->query('s') . '%');
                }
                if ($request->filled('catId')) {
                    $query->where('cat_id', $request->query('catId'));
                }
                $rows = $query->orderByDesc('id')->paginate((int) $request->query('per_page', 20));

                return response()->json([
                    'success' => true,
                    'data' => $rows->items(),
                    'total' => $rows->total(),
                    'max_pages' => $rows->lastPage(),
                ]);
            })->name('api.support.admin.ticket.index');

            Route::get('/categories', function () {
                if (!Auth::user()->hasPermission('support_ticket_manage')) {
                    return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
                }
                return response()->json(['success' => true, 'data' => TicketCat::query()->orderByDesc('id')->get()]);
            })->name('api.support.admin.ticket.categories');
        });
    });
});
