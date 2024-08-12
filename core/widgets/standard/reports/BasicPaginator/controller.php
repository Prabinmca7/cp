<?php

namespace RightNow\Widgets;

class BasicPaginator extends \RightNow\Widgets\Paginator
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        if (parent::getData() === false)
            return false;

        $this->data['appendedParameters'] = \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url']);
    }
}
