<?php
namespace RightNow\Widgets;

class AdvancedSearchDialog extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $reportID = (int) $this->data['attrs']['report_id'];
        if(strlen(trim($this->data['attrs']['additional_filters'])))
        {
            $this->data['menuFilters'] = $this->data['searchTypeFilters'] = array();
            $runtimeFilters = explode(',', trim($this->data['attrs']['additional_filters']));
            foreach($runtimeFilters as $filter)
            {
                $filter = trim($filter);
                $filterData = $this->CI->model('Report')->getFilterByName($reportID, $filter)->result;
                if($filterData === null)
                {
                    echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FLTR_NAME_PCT_S_FND_RPT_ID_PCT_S_MSG), $filter, $reportID));
                    continue;
                }
                if(!in_array($filterData['data_type'], array(VDT_MENU, VDT_INT, VDT_VARCHAR)))
                {
                    echo $this->reportError(sprintf(\RightNow\Utils\Config::getMessage(FLTR_PCT_S_SUPP_TYPE_MENU_INT_TXT_MSG), $filter));
                    continue;
                }

                if(($filterData['data_type'] === VDT_INT || $filterData['data_type'] === VDT_VARCHAR))
                {
                    $this->data['searchTypeFilters'][] = $filter;
                }
                else
                {
                    $this->data['menuFilters'][] = $filter;
                }
            }
            $this->data['searchTypeFilters'] = count($this->data['searchTypeFilters']) ? implode(',', $this->data['searchTypeFilters']) : '';
        }
        $this->data['webSearch'] = ($reportID === CP_NOV09_WIDX_DEFAULT || $reportID === CP_WIDX_REPORT_DEFAULT);
    }
}
