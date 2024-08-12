<?php
/**
 * File: controller.php
 * Abstract: Basic controller for the SiteInfo widget
 * Version: 1.0
 */

namespace Custom\Widgets\input;

class SiteInfo extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        // Set the form field name. This is used to create the data object 
        // to send over to the form endpoint when the form is submitted. To 
        // see how this is used you can check out RightNow.Field.js
        $this->data['js']['name'] = 'Site$Location';
        // Set up a constraints array. This is referenced in the Field
        // JavaScript widget helper module. This is where you would set min
        // or max lengths if needed.
        $this->data['js']['constraints'] = array();
    }
}