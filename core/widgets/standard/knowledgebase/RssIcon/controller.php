<?php
namespace RightNow\Widgets;

class RssIcon extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        //The RSS feed doesn't work if login is required so avoid displaying the widget
        if(\RightNow\Utils\Config::getConfig(CP_CONTACT_LOGIN_REQUIRED)){
            return false;
        }
        $this->data['feedParams'] = '';

        if ($this->data['attrs']['object_type'] === 'CommunityQuestion') {
            $this->data['href'] = "/ci/cache/socialrss";
            if ($this->data['attrs']['prodcat_type'] === 'Product') {
                $this->data['feedParams'] = "/p/";
                if ($p = \RightNow\Utils\Url::getParameter('p'))
                    $this->data['feedParams'] .= $this->getProductCategoryFormattedChain($p, "Product");
            }
            else if ($this->data['attrs']['prodcat_type'] === 'Category') {
                $this->data['feedParams'] = "/c/";
                if ($c = \RightNow\Utils\Url::getParameter('c'))
                    $this->data['feedParams'] .= $this->getProductCategoryFormattedChain($c, "Category");
            }
        }
        else {
            $this->data['href'] = "/ci/cache/rss";
            if ($p = \RightNow\Utils\Url::getParameter('p'))
                $this->data['feedParams'] .= "/p/" . $this->getProductCategoryFormattedChain($p, "Product");

            if ($c = \RightNow\Utils\Url::getParameter('c'))
                $this->data['feedParams'] .= "/c/" . $this->getProductCategoryFormattedChain($c, "Category");
        }
    }

    /**
     * Function to return the product or category hirerachy chain
     * @param Integer $prodCatID Product or Category ID
     * @param String $productOrCategory Product or Category
     * @return String
     */
    private function getProductCategoryFormattedChain ($prodCatID, $productOrCategory) {
        $responseChain = $this->CI->model("Prodcat")->getFormattedChain($productOrCategory, $prodCatID)->result;
        if (!empty($responseChain)) {
            foreach ($responseChain as $chainData) {
                $prodCatHierArray[] = $chainData['id'];
            }
            return implode(',', $prodCatHierArray);
        }
    }
}
