<?php
namespace Modules\Boat\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\AdminController;
use Modules\Boat\Models\Boat;
use Modules\Core\Models\Attributes;
use Modules\Core\Models\AttributesTranslation;
use Modules\Core\Models\Terms;
use Modules\Core\Models\TermsTranslation;
use Illuminate\Support\Facades\DB;

class AttributeController extends AdminController
{
    protected $termsClass;
    private Attributes $attributesClass;
    private AttributesTranslation $attributesTranslation;
    private TermsTranslation $termsTranslation;

    public function __construct(Attributes $attributesClass, Terms $termsClass,AttributesTranslation $attributesTranslation, TermsTranslation $termsTranslation)
    {
        $this->setActiveMenu('admin/module/boat');
        $this->termsClass = $termsClass;
        $this->attributesClass = $attributesClass;
        $this->attributesTranslation = $attributesTranslation;
        $this->termsTranslation = $termsTranslation;
    }

    public function callAction($method, $parameters)
    {
        if (! Boat::isEnable()) {
            $request = request();
            if ($request && ($request->wantsJson() || $request->is('api-admin/*'))) {
                return response()->json(['message' => __('Boat module is disabled')], 503);
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
        $this->checkPermission('boat_manage_attributes');
        $listAttr = $this->attributesClass::where("service", 'boat');
        if (!empty($search = $request->query('s'))) {
            $listAttr->where('name', 'LIKE', '%' . $search . '%');
        }
        $listAttr->orderBy('created_at', 'desc');
        $data = [
            'rows'        => $listAttr->get(),
            'row'         => new $this->attributesClass(),
            'translation'    => new $this->attributesTranslation,
            'breadcrumbs' => [
                [
                    'name' => __('Boat'),
                    'url'  => route('boat.admin.index')
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

        return view('Boat::admin.attribute.index', $data);
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
        $this->checkPermission('boat_manage_attributes');
        $data = [
            'translation'    => $translation,
            'enable_multi_lang'=>true,
            'rows'        => $this->attributesClass::where("service", 'boat')->get(),
            'row'         => $row,
            'breadcrumbs' => [
                [
                    'name' => __('Boat'),
                    'url'  => route('boat.admin.index')
                ],
                [
                    'name' => __('Attributes'),
                    'url'  => route('boat.admin.attribute.index')
                ],
                [
                    'name'  => __('Attribute: :name', ['name' => $row->name]),
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

        return view('Boat::admin.attribute.detail', $data);
    }

    public function store(Request $request)
    {
        $this->checkPermission('boat_manage_attributes');
        $this->validate($request, [
            'name' => 'required'
        ]);
        $id = $request->input('id');
        if ($id) {
            $row = $this->attributesClass::find($id);
            if (empty($row)) {
                if ($this->isApiRequest($request)) {
                    return response()->json(['message' => __('Attributes not found!')], 404);
                }

                return redirect()->back()->with('error', __('Attributes not found!'));
            }
        } else {
            $row = new $this->attributesClass($request->input());
            $row->service = 'boat';
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
        $this->checkPermission('boat_manage_attributes');
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
        $this->checkPermission('boat_manage_attributes');
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
            'translation'    => new $this->termsTranslation,
            'breadcrumbs' => [
                [
                    'name' => __('Boat'),
                    'url'  => route('boat.admin.index')
                ],
                [
                    'name' => __('Attributes'),
                    'url'  => route('boat.admin.attribute.index')
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

        return view('Boat::admin.terms.index', $data);
    }

    public function term_edit(Request $request, $id)
    {
        $this->checkPermission('boat_manage_attributes');
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
                    'name' => __('Boat'),
                    'url'  => route('boat.admin.index')
                ],
                [
                    'name' => __('Attributes'),
                    'url'  => route('boat.admin.attribute.index')
                ],
                [
                    'name' => $attr->name,
                    'url'  => route('boat.admin.attribute.term.index',['id'=>$row->attr_id])
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

        return view('Boat::admin.terms.detail', $data);
    }

    public function term_store(Request $request)
    {
        $this->checkPermission('boat_manage_attributes');
        $this->validate($request, [
            'name' => 'required'
        ]);
        $id = $request->input('id');
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
        $row->image_id = $request->input('image_id');
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
        $this->checkPermission('boat_manage_attributes');
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

    public function getForSelect2(Request $request)
    {
        $pre_selected = $request->query('pre_selected');
        $selected = $request->query('selected');

        if($pre_selected && $selected){
            if(is_array($selected))
            {
                $query = $this->termsClass::getForSelect2Query('boat');
                $items = $query->whereIn('bc_terms.id',$selected)->take(50)->get();
                return response()->json([
                    'items'=>$items
                ]);
            }else{
                $items = $this->termsClass->find($selected);
            }

            return response()->json([
                'results'=>$items
            ]);
        }
        $q = $request->query('q');
        $query = $this->termsClass::getForSelect2Query('boat', $q);
        $res = $query->orderBy('bc_terms.id', 'desc')->limit(20)->get();
        return response()->json([
            'results' => $res
        ]);
    }
}
