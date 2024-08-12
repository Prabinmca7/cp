<?php
/**
 * File: AjaxSiteLocation.php
 * Abstract: Ajax Controller for use with the SiteInfo widget
 * Version: 1.0
 */

namespace Custom\Controllers;

use RightNow\Libraries\AbuseDetection,
    RightNow\Utils\Config;

class AjaxSiteLocation extends \RightNow\Controllers\Base
{
    /**
     * This function is an end point to create an incident and add a Site$Location
     * custom object item that references the incident.
     * @return void
     */
    function submitAsk()
    {
        AbuseDetection::check($this->input->post('f_tok'));
        $data = json_decode($this->input->post('form'));
        if(!$data)
        {
            header("HTTP/1.1 400 Bad Request");
            // Pad the error message with spaces so IE will actually display it instead of a misleading, but pretty, error message.
            Framework::writeContentWithLengthAndExit(json_encode(Config::getMessage(END_REQS_BODY_REQUESTS_FORMATTED_MSG)) . str_repeat("\n", 512));
        }
        $incidentID = $this->input->post('i_id');
        $smartAssistant = $this->input->post('smrt_asst');

        // Find site location in the form data
        foreach ($data as $index => $field) {
            if ($field->name === 'Site$Location') {
                $siteLocation = $field;
                $siteLocationIndex = $index;
            }
        }

        if ($siteLocationIndex) {
            // Pull site location out of the form data to handle elsewhere
            array_splice($data, $siteLocationIndex, 1);
        }

        // Attempt to create the incident
        $incidentResponse = $this->model('Field')->sendForm($data, intval($incidentID), ($smartAssistant === 'true'));

        // If a site location was sent in and an incident was successfully created
        if ($siteLocation->value && $incidentResponse->result['transaction']['incident']['key'] === "i_id") {
            // Create a site location entry
            $addLocationResponse = $this->model('custom/SiteInfo')->addLocation($incidentResponse->result['transaction']['incident']['value'], $siteLocation->value);

            if(count($addLocationResponse->errors) > 0) {
                echo json_encode($addLocationResponse->errors);
            }
            else {
                // As long as nothing went wrong, just echo the incident response
                echo $incidentResponse->toJson();
            }
        }
        else {
            echo $incidentResponse->toJson();
        }
    }
}

