<?php
namespace RightNow\Widgets;

/**
 * Use this controller if you don't need any data in your widget
 * name and instanceID will be available in your view for creating unique html names
 */
class Blank extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
    }
}
