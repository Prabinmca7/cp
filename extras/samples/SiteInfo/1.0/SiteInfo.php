<?php
/**
 * File: SiteInfo.php
 * Abstract: SiteInfo model for use with the SiteInfo widget
 * Version: 1.0
 */

namespace Custom\Models;

use RightNow\Connect\v1_4 as Connect;

class SiteInfo extends \RightNow\Models\Base
{
    /**
     * This function creates a Site$Location custom object and links it to an incident.
     * @param int $incidentID ID of the incident to link
     * @param string $location Location to add
     * @return null|Connect\Site\Location Instance of custom object or null on failure
     */
    function addLocation($incidentID, $location)
    {
        try {
            $locationObj = "\\" . CONNECT_NAMESPACE_PREFIX ."\Site\Location";
            $locationToAdd = new $locationObj();
            $locationToAdd->URL = strip_tags($location);
            $locationToAdd->Incident = $incidentID;
            $locationToAdd->save();
            return $this->getResponseObject($locationToAdd);
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        catch (\RightNow\Connect\v1_2\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }

    }
}
