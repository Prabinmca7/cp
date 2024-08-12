<?php
/**
 * File: controller.php
 * Abstract: Extending controller for ExtendingAnswerFeedback widget
 * Version: 1.0
 */

namespace Custom\Widgets\feedback;

use RightNow\Utils\Connect;

/**
 * ExtendingAnswerFeedback
 *
 * @uses RightNow\Widgets\AnswerFeedback
 */
class ExtendedAnswerFeedback extends \RightNow\Widgets\AnswerFeedback {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        // If a widget's getData method returns false then the widget isn't rendered.
        // This check ensures that we won't do additional work if the parent indicates
        // that the widget shouldn't be rendered.
        if (parent::getData() === false) return false;

        // Retrieve an empty Incident object and get the metadata for
        // its CustomFields.
        $incident = $this->CI->model('Incident')->getBlank()->result;
        $customFields = $incident->CustomFields;
        $customFieldsMeta = $customFields::getMetadata();

        if ($customFieldsMeta->CO) {
            $co = new $customFieldsMeta->CO->type_name;
            $meta = $co::getMetadata();

            // Check the existence and type of the two fields.
            if ($meta->type && $meta->type->named_values) {
                $this->data['js']['typeLabel'] = $meta->type->label;
                $this->data['feedbackTypes'] = $meta->type->named_values;
            }
            else {
                echo $this->reportError("Expecting a Menu field named type on Incident.CustomFields.CO");
                return false;
            }

            if ($meta->source && $meta->source->type_name === 'string') {
                $this->data['js']['sourceLabel'] = $meta->source->label;
            }
            else {
                echo $this->reportError("Expecting a String field named source on Incident.CustomFields.CO");
                return false;
            }
        }
        else {
            echo $this->reportError("Expecting a CO package to exist on the Incident object");
            return false;
        }
    }
}
