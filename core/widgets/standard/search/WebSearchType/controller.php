<?php
namespace RightNow\Widgets;

class WebSearchType extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        $searchMode = $filters['searchType']->filters->fltr_id;
        $this->data['js'] = array('report_id' => $this->data['attrs']['report_id'],
                                  'searchDefault' => ($searchMode) ? $searchMode : \RightNow\Utils\Config::getConfig(EU_WIDX_SEARCH_BY_DEFAULT, 'RNW_UI')
                                 );
        $this->data['searchOptions'] = $this->CI->model('Report')->getExternalDocumentSearchOptions()->result;
    }
}
