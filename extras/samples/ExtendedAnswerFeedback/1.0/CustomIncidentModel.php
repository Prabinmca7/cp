<?php
/**
 * File: CustomIncidentModel.php
 * Abstract: Saves values on several custom fields for the incident
 * supplied by the pre_feedback_submit hook.
 * Version: 1.0
 */
namespace Custom\Models;

class CustomIncidentModel extends \RightNow\Models\Base {
    /**
     * This method is triggered via a hook that fires prior
     * to site or answer feedback (incident creation via
     * the Incident Model's submitFeedback method).
     * @param array &$hookInfo Array containing key whose value is
     * the incident that's about to be saved
     * @return void
     */
    function preFeedbackSubmit(array &$hookInfo) {
        if ($this->CI->input->post('a_id')) {
            // Answer feedback was submitted (as opposed to site feedback).
            $incident = $hookInfo['data'];

            $typeOfFeedback = $this->CI->input->post('type');
            $sourceOfFeedback = $this->CI->input->post('source');

            if (ctype_digit($typeOfFeedback)) {
                // Since `type` is a Menu field that uses a Menu Only Custom Object (TypeOfFeedback),
                // we expect an int and fetch a TypeOfFeedback custom object instance corresponding to that ID value.
                $feedbackTypeObj = "\\" . CONNECT_NAMESPACE_PREFIX . "CO\TypeOfFeedback";
                $incident->CustomFields->CO->type = $feedbackTypeObj::fetch((int) $typeOfFeedback);
            }
            if ($sourceOfFeedback) {
                // `source` is a Text Area field.
                $incident->CustomFields->CO->source = $sourceOfFeedback;
            }
        }
    }
}
