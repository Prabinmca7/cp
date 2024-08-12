<?php
namespace Custom\Models;

class Sample extends \RightNow\Models\Base
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * This function can be executed a few different ways depending on where it's being called:
     *
     * From a widget or another model: $this->CI->model('custom/Sample')->sampleFunction();
     *
     * From a custom controller: $this->model('custom/Sample')->sampleFunction();
     *
     * Everywhere else: $CI = get_instance();
     *                  $CI->model('custom/Sample')->sampleFunction();
     */
    function sampleFunction()
    {

    }
}