<?php

namespace RightNow\Widgets;

class KeywordText extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        $reportToken = \RightNow\Utils\Framework::createToken($this->data['attrs']['report_id']);
        $searchTerm = $this->CI->model('Report')->getSearchTerm($this->data['attrs']['report_id'], $reportToken, $filters)->result;
        $this->data['js'] = array(
            'initialValue' => $searchTerm ?: '',
            'rnSearchType' => 'keyword',
            'searchName' => 'keyword',
        );
    }
}
