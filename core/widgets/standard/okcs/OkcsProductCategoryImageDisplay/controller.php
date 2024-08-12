<?php
namespace RightNow\Widgets;

use RightNow\Utils\Url,
    RightNow\Utils\Text;

class OkcsProductCategoryImageDisplay extends \RightNow\Libraries\Widget\Base {
    private $productCategoryApiVersion = 'v1';

    function getData() {
        if (!$prodCatID = Url::getParameter('categoryRecordID')) return false;

        if ($prodCat = $this->CI->model('Okcs')->getProductCategoryDetails($prodCatID, $this->productCategoryApiVersion)) {
            $this->data['js']['slug'] = Text::slugify($prodCat->name);
            $this->data['title'] = $prodCat->name;
        }
    }
}
