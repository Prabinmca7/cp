<?php
/**
 * File: controller.php
 * Abstract: Controller for ClickCounterWithAjax widget
 * Version: 1.0
 */

namespace Custom\Widgets\sample;

class ClickCounterWithAjax extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'update_message_endpoint' => array(
                'method'      => 'handleUpdateMessageEndpoint',
                'clickstream' => 'custom_action',
            ),
        ));
    }

    function getData() {
        $this->data['spanClass'] = 'border0';
        return parent::getData();
    }

    /**
     * Handles the update_message_endpoint AJAX request
     * @param array $params Get / Post parameters
     * @return void
     */
    function handleUpdateMessageEndpoint(array $params) {
        $numTimes = $params['numTimes'];
        // The CSS only handles classes border0, border1, and border2.
        $spanClass = "border" . ($numTimes % 3);
        echo json_encode(array(
            'message' => sprintf($this->data['attrs']['label_updated_message'], $numTimes),
            'spanClass' => $spanClass));
    }
}