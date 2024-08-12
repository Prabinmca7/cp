<?php
namespace RightNow\Widgets;
use RightNow\Utils\Url;

class ProductCatalogDisplay extends \RightNow\Libraries\Widget\Output
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        if(parent::getData() === false)
            return false;

        $salesProductID = null;
        $showNonVisibleProduct = false;
        if(($assetID = Url::getParameter('asset_id')) !== null) {
            $asset = $this->CI->model('Asset')->get(intval($assetID))->result;
            $salesProductID = $asset->Product->ID;
            $showNonVisibleProduct = true;
        }
        else if(($productID = Url::getParameter('product_id')) !== null) {
            $salesProductID = intval($productID);
        }

        if($salesProductID === null) {
            return false;
        }

        $this->data['value'] = $this->generateTree($salesProductID, $showNonVisibleProduct);
        $this->data['wrapClass'] = ($this->data['attrs']['left_justify']) ? ' rn_LeftJustify' : '';
    }

    /**
     * Create a flattened result list for the view.
     * @param int $salesProductID Sales Product Id
     * @param boolean $showNonVisibleProduct True if you want to display a non-visible product, false otherwise
     * @return array A two-dimensional associative array containing the Sales Product hierarchy
     */
    protected function generateTree($salesProductID, $showNonVisibleProduct = false)
    {
        $dataTree = array();
        $chain = $this->CI->model('ProductCatalog')->getFormattedChain($salesProductID, false, $showNonVisibleProduct)->result;
        $depth = 0;
        foreach($chain as $item) {
            $dataTree[$item['id']] = $this->createResultItem($item['id'], $item['label'], $depth++);
        }
        return $dataTree;
    }

    /**
     * Utility function to format heir menu item data
     * @param int $id The ID
     * @param string $label The label
     * @param int $depth The depth
     * @return array
     */
    protected function createResultItem($id, $label, $depth)
    {
        return array('ID' => $id, 'Name' => strip_tags($label), 'Depth' => $depth);
    }
}
