<?php

namespace RightNow\Models;

use RightNow\Api;

/**
 * Methods for accessing surveys
 */
class Survey extends Base
{
    /**
     * Build a URL for accessing the specified survey
     *
     * @param int $surveyID The survey ID to use to build the link
     * @param int|null $contactID Contact to associate with the survey 
     * @param int|null $incidentID Incident to associate with the survey 
     * @param int|null $chatID Chat to associate with the survey 
     * @param int|null $opID Opportunity to associate with the survey 
     * @return string Survey link corresponding to surveyID
     */
    public function buildSurveyURL($surveyID, $contactID = null, $incidentID = null, $chatID = null, $opID = null)
    {
        return Api::build_survey_url($surveyID, $contactID, $incidentID, $chatID, $opID);
    }
}
