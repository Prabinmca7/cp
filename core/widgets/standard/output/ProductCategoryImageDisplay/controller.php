<?php
namespace RightNow\Widgets;

use RightNow\Utils\Url,
    RightNow\Utils\Text;

class ProductCategoryImageDisplay extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$prodCatID = Url::getParameter($this->data['attrs']['type'][0])) return false;

        if ($prodCat = $this->CI->model('Prodcat')->get($prodCatID)->result) {
            $this->data['js']['slug'] = Text::slugify($prodCat->LookupName);
        }
    }
}
