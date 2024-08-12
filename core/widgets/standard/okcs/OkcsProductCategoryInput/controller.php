<?php
namespace RightNow\Widgets;
use \RightNow\Utils\Config;

class OkcsProductCategoryInput extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['js']['initial'] = array();
        $this->data['js']['moreLinkLabel'] = $this->data['attrs']['label_more_link'];
        $this->data['js']['limit'] = $this->data['attrs']['child_categories_to_display'];
        switch($this->data['attrs']['filter_type']) {            
            case 'Category':
                $this->data['attrs']['label_nothing_selected'] = Config::getMessage(SELECT_A_CATEGORY_LBL);
                $this->data['attrs']['label_input'] = Config::getMessage(CATEGORY_LBL);
                $this->data['attrs']['label_all_values'] = Config::getMessage(ALL_CATEGORIES_LBL);
                $this->data['js']['moreLinkLabel'] = $this->data['attrs']['label_more_link'];
                $this->data['js']['limit'] = $this->data['attrs']['child_categories_to_display'];
                break;
            case 'ContentType':
                $this->data['attrs']['label_nothing_selected'] = Config::getMessage(SELECT_CONTENT_TYPE_LBL);
                $this->data['attrs']['label_input'] = Config::getMessage(CONTENT_TYPE_LBL);
                $this->data['attrs']['label_all_values'] = Config::getMessage(NONE_LBL);
                break;
        }
    }
}
