<?php
namespace Modules\Template\Blocks;

use Modules\Media\Helpers\FileHelper;
use Modules\Template\Blocks\BaseBlock;

class WhyChooseUs extends BaseBlock
{
    public $title;
    public $sub_title;
    public $desc;
    public $text_link_more;
    public $link_more;
    public $list_item;
    public $image;
    public $background_image;
    public $title_list;
    public function getOptions(){
        return [
            'settings' => [
                [
                    'id'        => 'title',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Title'),
                    'adminLabel'     => 'true'
                ],
                [
                    'id'        => 'sub_title',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Sub Title')
                ],
                [
                    'id'        => 'desc',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Description')
                ],
                [
                    'id'        => 'text_link_more',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Text Link More')
                ],
                [
                    'id'        => 'link_more',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Link More')
                ],
                [
                    'id'    => 'image',
                    'type'  => 'uploader',
                    'label' => __('Image Feature')
                ],
                [
                    'id'    => 'background_image',
                    'type'  => 'uploader',
                    'label' => __('Background Image')
                ],
                [
                    'id'        => 'title_list',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Title List'),
                    'adminLabel'     => 'true'
                ],
                [
                    'id'          => 'list_item',
                    'type'        => 'listItem',
                    'label'       => __('List Image(s)'),
                    'title_field' => 'title',
                    'settings'    => [
                        [
                            'id'        => 'title',
                            'type'      => 'input',
                            'inputType' => 'text',
                            'label'     => __('Title'),
                            'adminLabel'     => 'true'
                        ],
                        [
                            'id'    => 'image',
                            'type'  => 'uploader',
                            'label' => __('Image')
                        ],
                    ]
                ]
            ],
            'category'=>__("Other Block")
        ];
    }

    public function getTitle()
    {
        return __('Why Choose Us');
    }

    public function render()
    {
        $model['title'] = $this->title;
        $model['sub_title'] = $this->sub_title;
        $model['desc'] = $this->desc;
        $model['text_link_more'] = $this->text_link_more;
        $model['link_more'] = $this->link_more;
        $model['list_item'] = $this->list_item;
        $model['title_list'] = $this->title_list;
        $model['image'] = $this->image;
        $model['background_image'] = $this->background_image;
        return $this->view('Template::frontend.blocks.why-choose-us.index', $model);
    }

}
