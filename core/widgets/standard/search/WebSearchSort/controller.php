<?php
namespace RightNow\Widgets;

class WebSearchSort extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        $sortMode = $filters['sort_args']['filters']['col_id'];
        $this->data['js'] = array('report_id' => $this->data['attrs']['report_id'],
                                  'sortDefault' => $sortMode ?: \RightNow\Utils\Config::getConfig(EU_WIDX_SORT_BY_DEFAULT, 'RNW_UI'),
                                  'configDefault' => \RightNow\Utils\Config::getConfig(EU_WIDX_SORT_BY_DEFAULT, 'RNW_UI')
                                 );
        $this->data['sortOptions'] = $this->CI->model('Report')->getExternalDocumentSortOptions()->result;
    }
}
