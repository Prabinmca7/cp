<?php

namespace RightNow\Controllers;

use RightNow\Utils\Framework,
    RightNow\Utils\Config,
    RightNow\Utils\Okcs,
    RightNow\Utils\Text,
    RightNow\Libraries\AbuseDetection;

/**
* Generic controller endpoint for standard OKCS widgets to make requests to retrieve data. Nearly all of the
* methods in this controller echo out their data in JSON so that it can be received by the calling JavaScript.
*/
final class OkcsAnswerPreview extends Base
{
    public function __construct()
    {
        parent::__construct();
        require_once CPCORE . 'Utils/Okcs.php';
        parent::_setMethodsExemptFromContactLoginRequired(array(
            'answerPreview'
        ));
    }

    /**
    * Method to fetch data through OKCS APIs
    * @internal
    */
    public function answerPreview() {
        $answerId = $this->input->post('answerId');
        if (!empty($answerId) && is_numeric($answerId) && $answerId > 0) {
            $this->processPrevieRequest($answerId);
        }
        else {
            Framework::setLocationHeader("/app/error/error_id/1");
            return;
        }
    }

    /**
    * Method to add subscription for the IM Answer content.
    * @param string $answerId Answer Id to preview
    */
    private function processPrevieRequest($answerId) {
        AbuseDetection::check();

        $this->session->setSessionData(array('integrationToken' => $this->input->post('integrationToken')));
        $this->session->setSessionData(array('answerVersionId' => $this->input->post('versionId')));
        $this->session->setSessionData(array('userGroups' => $this->input->post('userGroups')));
        $this->session->setSessionData(array('answerViewType' => 'okcsAnswerPreview'));

        Framework::setLocationHeader("/app/" . Config::getConfig(CP_ANSWERS_DETAIL_URL) . "/a_id/" . $answerId);
    }

}
