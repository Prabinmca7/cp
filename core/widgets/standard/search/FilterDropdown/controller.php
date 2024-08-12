<?php

namespace RightNow\Widgets;

class FilterDropdown extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $list = $allFilters = array();
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $allFilters);
        $filters = $this->CI->model('Report')->getFilterByName($this->data['attrs']['report_id'], $this->data['attrs']['filter_name'])->result;
        if(!$filters){
            echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FILTER_PCT_S_EXIST_REPORT_PCT_S_LBL), $this->data['attrs']['filter_name'], $this->data['attrs']['report_id']));
            return false;
        }
        $list = \RightNow\Utils\Framework::getOptlist($filters['optlist_id']);
        $optlist = array();
        $optionsToIgnore = explode(',', $this->data['attrs']['options_to_ignore']);

        foreach ($list as $key => $value)
        { 
            if (is_int($key) && !in_array($key, $optionsToIgnore))
                $optlist[] = array('id' => $key, 'label' => $value);
        }
        $defaultValue = (isset($allFilters[$this->data['attrs']['filter_name']])) ? $allFilters[$this->data['attrs']['filter_name']]->filters->data[0] : null;
        $this->data['js'] = array('filters' => $filters,
                                  'name' => $filters['prompt'],
                                  'list' => $optlist,
                                  'defaultValue' => $defaultValue ?: $filters['default_value'],
                                  'rnSearchType' => 'filterDropdown',
                                  'searchName' => $this->data['attrs']['filter_name']
                                  );
    }
}
