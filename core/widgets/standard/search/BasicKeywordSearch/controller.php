<?php

namespace RightNow\Widgets;

class BasicKeywordSearch extends \RightNow\Widgets\KeywordText
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        if (parent::getData() === false)
            return false;

        $this->data['appendedParameters'] = \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url'], array('kw'));

        if ($this->data['js']['initialValue']) {
            // make sure the posted kw value has html friendly quotes
            $this->data['js']['initialValue'] = str_replace(array("'", '"'), array('&#039;', '&quot;'), $this->data['js']['initialValue']);
        }

        if ($this->data['attrs']['report_page_url'] === '')
            $this->data['attrs']['report_page_url'] = "/app/{$this->CI->page}";
    }
}
