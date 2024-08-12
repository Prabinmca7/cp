<?php
namespace RightNow\Widgets;

use RightNow\Utils\Connect;

class DataDisplay extends \RightNow\Libraries\Widget\Output
{
    function __construct($attrs) 
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        if(parent::getData() === false)
            return false;
    }
}
