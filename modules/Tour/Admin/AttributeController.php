<?php
namespace Modules\Tour\Admin;

use Illuminate\Http\Request;
use Modules\AdminController;
use Modules\Core\Models\Attributes;
use Modules\Core\Models\AttributesTranslation;
use Modules\Core\Models\Terms;
use Modules\Core\Models\TermsTranslation;
use Modules\Tour\Models\Tour;

class AttributeController extends AdminController
{
    protected $attributesClass;
    protected $termsClass;

    public function __construct()
    {
        $this->setActiveMenu(route('tour.admin.index'));
        $this->attributesClass = Attributes::class;
        $this->termsClass = Terms::class;
    }

    public function callAction($method, $parameters)
    {
        if (! Tour::isEnable()) {
            $request = request();
            if ($request && ($request->wantsJson() || $request->is('api-admin/*'))) {
                return response()->json(['message' => __('Tour module is disabled')], 503);
            }
            return redirect('/');
        }
        return parent::callAction($method, $parameters);
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->wantsJson() || $request->is('api-admin/*');
    }

    public function index(Request $request)
    {
        $this->checkPermission('tour_manage_attributes');
        $listAttr = $this->attributesClass::where("service", 'tour');
        if (!empty($search = $request->query('s'))) {
            $listAttr->where('name', 'LIKE', '%' . $search . '%');
        }
        $listAttr->orderBy('created_at', 'desc');
        $data = [
            'rows'        => $listAttr->get(),
            'row'         => new $this->attributesClass(),
            'translation'    => new AttributesTranslation(),
            'breadcrumbs' => [
                [
                    'name' => __('Tour'),
                    'url'  => route('tour.admin.index')
                ],
                [
                    'name'  => __('Attributes'),
                    'class' => 'active'
                ],
            ]
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => $data['rows'],
            ]);
        }
        return view('Tour::admin.attribute.index', $data);
    }

    public function edit(Request $request, $id)
    {
        $row = $this->attributesClass::find($id);
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Attributes not found!')], 404);
            }
            return redirect()->back()->with('error', __('Attributes not found!'));
        }
        $translation = $row->translate($request->query('lang',get_main_lang()));
        $this->checkPermission('tour_manage_attributes');
        $data = [
            'translation'    => $translation,
            'enable_multi_lang'=>true,
            'rows'        => $this->attributesClass::where("service", 'tour')->get(),
            'row'         => $row,
            'breadcrumbs' => [
                [
                    'name' => __('Tour'),
                    'url'  => route('tour.admin.index')
                ],
                [
                    'name' => __('Attributes'),
                    'url'  => route('tour.admin.attribute.index')
                ],
                [
                    'name'  => __('Attributes: :name', ['name' => $row->name]),
                    'class' => 'active'
                ],
            ]
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => [
                    'row' => $data['row'],
                    'translation' => $data['translation'],
                    'attributes' => $data['rows'],
                    'enable_multi_lang' => $data['enable_multi_lang'],
                ],
            ]);
        }
        return view('Tour::admin.attribute.detail', $data);
    }

    public function store(Request $request, $id)
    {
        $this->checkPermission('tour_manage_attributes');
        $this->validate($request, [
            'name' => 'required'
        ]);
        $bodyId = $request->input('id');
        if ($bodyId) {
            $row = $this->attributesClass::find($bodyId);
            if (empty($row)) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Attributes not found!')], 404);
                }
                return redirect()->back()->with('error', __('Attributes not found!'));
            }
        } else {
            $row = new $this->attributesClass($request->input());
            $row->service = 'tour';
        }
        $row->fill($request->input());
        $res = $row->saveOriginOrTranslation($request->input('lang'));
        if ($res) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Attribute saved'), 'data' => $row]);
            }
            return redirect()->back()->with('success', __('Attribute saved'));
        }
    }

    public function editAttrBulk(Request $request)
    {
        $this->checkPermission('tour_manage_attributes');
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
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = $this->attributesClass::where("id", $id);
                $query->first();
                if(!empty($query)){
                    $query->delete();
                }
            }
        }
        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Updated success!')]);
        }
        return redirect()->back()->with('success', __('Updated success!'));
    }

    public function terms(Request $request, $attr_id)
    {
        $this->checkPermission('tour_manage_attributes');
        $row = $this->attributesClass::find($attr_id);
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Term not found')], 404);
            }
            return redirect()->back()->with('error', __('Term not found'));
        }
        $listTerms = $this->termsClass::where("attr_id", $attr_id);
        if (!empty($search = $request->query('s'))) {
            $listTerms->where('name', 'LIKE', '%' . $search . '%');
        }
        $listTerms->orderBy('created_at', 'desc');
        $data = [
            'rows'        => $listTerms->paginate(20),
            'attr'        => $row,
            "row"         => new $this->termsClass(),
            'translation'    => new TermsTranslation(),
            'breadcrumbs' => [
                [
                    'name' => __('Tour'),
                    'url'  => route('tour.admin.index')
                ],
                [
                    'name' => __('Attributes'),
                    'url'  => route('tour.admin.attribute.index')
                ],
                [
                    'name'  => __('Attribute: :name', ['name' => $row->name]),
                    'class' => 'active'
                ],
            ]
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => $data['rows']->items(),
                'meta' => [
                    'current_page' => $data['rows']->currentPage(),
                    'per_page'     => $data['rows']->perPage(),
                    'total'        => $data['rows']->total(),
                    'last_page'    => $data['rows']->lastPage(),
                ],
                'attribute' => $row,
            ]);
        }
        return view('Tour::admin.terms.index', $data);
    }

    public function term_edit(Request $request, $id)
    {
        $this->checkPermission('tour_manage_attributes');
        $row = $this->termsClass::find($id);
        if (empty($row)) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Term not found')], 404);
            }
            return redirect()->back()->with('error', __('Term not found'));
        }
        $translation = $row->translate($request->query('lang',get_main_lang()));
        $attr = $this->attributesClass::find($row->attr_id);
        $data = [
            'row'         => $row,
            'translation'    => $translation,
            'enable_multi_lang'=>true,
            'breadcrumbs' => [
                [
                    'name' => __('Tour'),
                    'url'  => route('tour.admin.index')
                ],
                [
                    'name' => __('Attributes'),
                    'url'  => route('tour.admin.attribute.index')
                ],
                [
                    'name' => $attr->name,
                    'url'  => route('tour.admin.attribute.term.index',['attr_id'=>$row->attr_id])
                ],
                [
                    'name'  => __('Term: :name', ['name' => $row->name]),
                    'class' => 'active'
                ],
            ]
        ];
        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => [
                    'row' => $row,
                    'translation' => $translation,
                    'attribute' => $attr,
                    'enable_multi_lang' => true,
                ],
            ]);
        }
        return view('Tour::admin.terms.detail', $data);
    }

    public function term_store(Request $request, $routeId)
    {
        $this->checkPermission('tour_manage_attributes');
        $this->validate($request, [
            'name' => 'required'
        ]);
        $id = $request->input('id') ?: (($routeId !== '-1' && $routeId !== -1) ? $routeId : null);
        if ($id) {
            $row = $this->termsClass::find($id);
            if (empty($row)) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Term not found')], 404);
                }
                return redirect()->back()->with('error', __('Term not found'));
            }
        } else {
            $row = new $this->termsClass($request->input());
            $row->attr_id = $request->input('attr_id');
        }
        $row->fill($request->input());
        $res = $row->saveOriginOrTranslation($request->input('lang'));
        if ($res) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => __('Term saved'), 'data' => $row]);
            }
            return redirect()->back()->with('success', __('Term saved'));
        }
    }

    public function editTermBulk(Request $request)
    {
        $this->checkPermission('tour_manage_attributes');
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
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = $this->termsClass::where("id", $id);
                $query->first();
                if(!empty($query)){
                    $query->delete();
                }
            }
        }
        if ($this->isApiRequest($request)) {
            return response()->json(['message' => __('Updated success!')]);
        }
        return redirect()->back()->with('success', __('Updated success!'));
    }
}
