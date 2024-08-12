<?php

namespace RightNow\Widgets;

use RightNow\Utils\Url;

class ProductCatalogSearchFilter extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $filters = array();

        $filterType = $this->data['attrs']['filter_type'] = 'pc';
        Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        if(!$filters[$filterType]->filters->optlist_id) {
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FILTER_PCT_S_EXIST_REPORT_PCT_D_LBL), $this->data['attrs']['label_input'], $this->data['attrs']['report_id']));
            return false;
        }

        $this->data['js'] = array(
            'oper_id'     => $filters[$filterType]->filters->oper_id,
            'fltr_id'     => $filters[$filterType]->filters->fltr_id,
            'report_def'  => $filters[$filterType]->report_default,
            'searchName'  => $filterType,
        );

        $defaultChain = null;
        if(is_array($filters[$filterType]->filters->data)) {
            $defaultChain = $filters[$filterType]->filters->data[0];
        }
        else if(is_string($filters[$filterType]->filters->data)) {
            $defaultChain = $filters[$filterType]->filters->data;
        }

        $defaultChain = $this->data['js']['initial'] = ($defaultChain) ? explode(',', $defaultChain) : array();
        $defaultHierMap = array();
        if(count($defaultChain) > 0 ) {
            $defaultHierMap = $this->CI->model('ProductCatalog')->getFormattedTree($defaultChain, true)->result;
        }
        else {
            $defaultHierMap = array($this->CI->model('ProductCatalog')->getDirectDescendants(null, 0, true)->result);
        }

        array_unshift($defaultHierMap[0], array('id' => 0, 'label' => $this->data['attrs']['label_all_values']));
        $this->data['js']['hierData'] = $defaultHierMap;
    }
}