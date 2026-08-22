<?php
namespace Modules\Location\Models;

use App\BaseModel;
use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocationCategory extends BaseModel
{
    use SoftDeletes;
    use NodeTrait;
    protected $table = 'location_category';
    protected $fillable = [
        'name',
        'content',
        'slug',
        'icon_class',
        'status',
        'parent_id'
    ];
    protected $slugField     = 'slug';
    protected $slugFromField = 'name';

    public static function getModelName()
    {
        return __("Location Category");
    }

    public static function searchForMenu($q = false)
    {
        $query = static::select('id', 'name');
        if (strlen($q)) {
            $query->where('name', 'like', "%" . $q . "%");
        }
        $a = $query->orderBy('id', 'desc')->limit(10)->get();
        return $a;
    }
    public function getDetailUrl(){
        return url(app_get_locale(false, false, '/') . config('tour.tour_route_prefix').'?cat_id[]='.$this->id);
    }

    public function dataForApi(){
        $translation = $this->translate();
        return [
            'id'=>$this->id,
            'name'=>$translation->name,
            'slug'=>$this->slug,
        ];
    }

    public function toApiPayload(): array
    {
        $createdAt = $this->created_at ? $this->created_at->toIso8601String() : now()->toIso8601String();
        $updatedAt = $this->updated_at ? $this->updated_at->toIso8601String() : $createdAt;

        $children = [];
        if ($this->relationLoaded('children') && !empty($this->children)) {
            foreach ($this->children as $child) {
                $children[] = $child instanceof LocationCategory ? $child->toApiPayload() : $child;
            }
        }

        return [
            'id'             => (int) $this->id,
            'name'           => (string) ($this->name ?? ''),
            'icon_class'     => (string) ($this->icon_class ?? ''),
            'content'        => (string) ($this->content ?? ''),
            'slug'           => (string) ($this->slug ?: \Illuminate\Support\Str::slug($this->name ?? '')),
            'status'         => (string) ($this->status ?? 'publish'),
            'parent_id'      => $this->parent_id ? (int) $this->parent_id : null,
            'created_at'     => $createdAt,
            'updated_at'     => $updatedAt,
            'formatted_date' => display_date($this->updated_at ?: $this->created_at ?: now()),
            'children'       => $children,
        ];
    }

    public function location_category_translations(){
        return $this->hasOne(LocationCategoryTranslation::class,'origin_id')->where('locale',app()->getLocale());
    }
}
