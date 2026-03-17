<?php
namespace Modules\Template\Blocks;

use Modules\Media\Helpers\FileHelper;
use Modules\Template\Blocks\BaseBlock;

class SoloBanner extends BaseBlock
{
    public $title;
    public $sub_title;
    public $link_title;
    public $link_url;
    public $bg_image;


    public function getTitle()
    {
        return __('Solo Banner');
    }

    public function getOptions()
    {
        return [
            'settings' => [
                [
                    'id'        => 'title',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Title')
                ],
                [
                    'id'        => 'sub_title',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Sub Title')
                ],
                [
                    'id'        => 'link_title',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Link Title')
                ],
                [
                    'id'        => 'link_url',
                    'type'      => 'input',
                    'inputType' => 'text',
                    'label'     => __('Link URL')
                ],
                [
                    'id'    => 'bg_image',
                    'type'  => 'uploader',
                    'label' => __('Background Uploader')
                ],
            ],
            'category'=>__("Other Block")
        ];
    }

    public function render()
    {
        $model = [
            'id' => time(),
            'title' => $this->title,
            'sub_title' => $this->sub_title,
            'link_title' => $this->link_title,
            'link_url' => $this->link_url,
            'bg_image' => $this->bg_image,
        ];
        return $this->view('Template::frontend.blocks.solo-banner.index', $model);
    }
}
