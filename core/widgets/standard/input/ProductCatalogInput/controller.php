<?php

namespace RightNow\Widgets;

use RightNow\Utils\Url;

class ProductCatalogInput extends \RightNow\Libraries\Widget\Input
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $this->data['js']['name'] = $this->data['attrs']['name'] = "Asset.Product";
        if (parent::getData() === false) return false;

        $this->data['attrs']['hint'] = trim($this->data['attrs']['hint']);

        $defaultChain = $this->getDefaultChain();
        $defaultHierMap = array();
        if(count($defaultChain) > 0 ) {
            $defaultHierMap = $this->CI->model('ProductCatalog')->getFormattedTree($defaultChain)->result;
        }
        else {
            $defaultHierMap = array($this->CI->model('ProductCatalog')->getDirectDescendants()->result);
        }

        //Add in the all values label
        array_unshift($defaultHierMap[0], array('id' => 0, 'label' => $this->data['attrs']['label_all_values']));
        $this->data['js']['hierData'] = $defaultHierMap;
    }

    /**
    * Retrieves defaults
    * 1. URL parameter saproduct_id
    * 2. default_value attribute
    * @return Array The default chain chosen from the above options. If no chain is found, returns an empty array.
    */
    protected function getDefaultChain() {
        $defaultValue = Url::getParameter('product_id');
        // look for a value in the widget attributes
        if ($defaultValue === false || $defaultValue === null) {
            $defaultFromAttribute = (isset($this->data['attrs']['default_value'])) ? $this->data['attrs']['default_value'] : false;
            if ($defaultFromAttribute !== false) {
                $defaultValue = $defaultFromAttribute;
            }
        }

        return $defaultValue ? $this->CI->model('ProductCatalog')->getFormattedChain($defaultValue, true)->result : array();
    }
}
