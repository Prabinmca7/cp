<?php

namespace Custom\Helpers;

class SampleHelper extends \RightNow\Libraries\Widget\Helper {
    /**
     * Widget helper functionality
     * ---------------------------
     *
     * Helpers can be added onto a widget instance:
     *
     * From a widget's controller or view:
     *
     *      $this->loadHelper('Sample')
     *
     * This will load and instantiate the helper class and assign it onto
     * the widget's `helper` property.
     *
     * Using a helper method:
     *
     *      $this->helper('Sample')->helperMethod()
     */
    function helperMethod () {}
}
