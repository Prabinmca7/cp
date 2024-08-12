<?php
namespace RightNow\Controllers;

use RightNow\Api,
    RightNow\Utils\Config,
    RightNow\Utils\Framework,
    RightNow\Utils\Text,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Connect\Knowledge\v1 as KnowledgeFoundation;

/**
 * Endpoint for rendering answer preview requests from the console. This is marked as internal since
 * the only way to hit this endpoint is via the console, so there really isn't any benefit in documenting it.
 *
 * @internal
 */
final class AnswerPreview extends Admin\Base {
    function __construct() {
        parent::__construct(true, '_verifyLoginBySessionId');
        //Since this controller isn't set as 'IS_ADMIN', overwrite the value Connect is using to force it
        //to be admin mode.
        // @codingStandardsIgnoreStart
        Connect\CustomerPortal::setCustomerPortalMode(Connect\CustomerPortalMode::Admin);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Display preview of specified $answerID
     *
     * @param int $answerID ID of answer to render
     */
    public function full($answerID) {
        if (Config::getConfig(OKCS_ENABLED)){
            $imContent = $this->model('Okcs')->getAnswerViewData($answerID, null, null, null, null, false, true);
            $imContent['published'] = $imContent['published'] ? Config::getMessage(PUBLISHED_LBL) : Config::getMessage(DRAFT_LBL);
            $data = array('data' => $imContent);
            $fullPreview = 'admin/okcs_answer_full_preview';
        }
        else {
            $accessLevels = $this->input->post('initial') === 'True'
                ? Api::contact_answer_access()
                : $this->input->post('accesslvls');

            $data = $this->_fullAnswerData(
                $answerID,
                $accessLevels ? explode(',', $accessLevels) : array()
            );

            if ($error = $data['error']) {
                exit($error);
            }

            if ($location = $data['location']) {
                Framework::setLocationHeader($location);
                exit;
            }
            $fullPreview = 'admin/answer_full_preview';
        }

        $this->_loadView($fullPreview, $data);
    }

    /**
     * Display preview of specified $versionID
     *
     * @param int $versionID ID of answer version to render
     */
    public function version($versionID) {
        $accessLevels = $this->input->post('initial') === 'True'
            ? Api::contact_answer_access()
            : $this->input->post('accesslvls');

        $data = $this->_fullAnswerVersionData(
            $versionID,
            $accessLevels ? explode(',', $accessLevels) : array()
        );

        if ($error = $data['error']) {
            exit($error);
        }

        if ($location = $data['location']) {
            Framework::setLocationHeader($location);
            exit;
        }
        $fullPreview = 'admin/answer_full_preview';
        
        $this->_loadView($fullPreview, $data);
    }

    /**
     * Display preview of specified answer elements. All data to display is
     * from POST data since the answer content has yet to be saved to the DB.
     */
    public function quick() {
        $this->_loadView('admin/answer_quick_preview', $this->_quickAnswerData(
            $this->input->post('summary'),
            $this->input->post('desc'),
            $this->input->post('soln')
        ));
    }

    /**
     * Displays headers and the specified view
     *
     * @param string $view Name of view to load
     * @param array $data Data to pass down to the view
     */
    private function _loadView($view, array $data) {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Expires: Mon, 19 May 1997 07:00:00 GMT'); // Date in the past
        $this->load->view($view, $data);
    }

    /**
     * Returns an array of data based on specified answer elements
     *
     * @param string $summary Summary of answer
     * @param string $description Description of answer
     * @param string $solution Solution of answer
     * @return array Expanded answer fields
     */
    private function _quickAnswerData($summary, $description, $solution) {
        return array(
            'summary' => Api::print_text2str(Text::expandAnswerTags($summary, true, true), OPT_ESCAPE_HTML | OPT_ESCAPE_SCRIPT),
            'description' => Text::expandAnswerTags($description, true, true),
            'solution' => Text::expandAnswerTags($solution, true, true),
            'baseurl' => \RightNow\Utils\Url::getShortEufBaseUrl(true),
        );
    }

    /**
     * Returns the answer summary, filtered by $accessLevels.
     * This was intentionally not put into the Answer model so as not to expose the ability to view answers by specified access levels.
     *
     * @param int $answerID ID of answer
     * @param array $accessLevels An array of numeric answer access levels
     * @return array Possible array keys:
     *     'location' - if a redirect is warranted
     *     'answer'   - upon success
     *     'error'    - upon error
     */
    private function _fullAnswerData($answerID, array $accessLevels) {
        try {
            $answer = KnowledgeFoundation\AnswerSummaryContent::fetch($answerID);
            $levels = new \RightNow\Connect\v1_2\AccessLevelTypeArray();
            foreach($accessLevels as $accessLevel) {
                $levelType = new Connect\AccessLevelType();
                $levelType->ID = intval($accessLevel);
                $levels[] = $levelType;
            }
            $content = $answer->GetContent(\RightNow\Models\Base::getKnowledgeApiSessionToken(), null, $levels);
            if ($content->AnswerType->ID === ANSWER_TYPE_URL) {
                return array('location' => $content->URL);
            }
            if ($content->AnswerType->ID === ANSWER_TYPE_ATTACHMENT) {
                return array('location' => Api::cgi_url(CALLED_BY_ADMIN) . '/admin/fattach_get.php?p_sid=' . $this->_getAgentSessionId() . '&p_tbl=' . TBL_ANSWERS . "&p_id=$answerID&p_created=" . $content->FileAttachments[0]->CreatedTime);
            }
            return array('answer' => $content);
        }
        catch (\Exception $e) {
            return array('error' => \RightNow\Utils\Config::getMessage(ANS_REQUESTED_IS_INVALID_MSG));
        }
    }

    /**
     * Returns the answer version summary, filtered by $accessLevels.
     * This was intentionally not put into the Answer model so as not to expose the ability to view answers by specified access levels.
     *
     * @param int $versionID ID of answer version
     * @param array $accessLevels An array of numeric answer access levels
     * @return array Possible array keys:
     *     'location' - if a redirect is warranted
     *     'answer'   - upon success
     *     'error'    - upon error
     */
    private function _fullAnswerVersionData($versionID, array $accessLevels) {
        try {
            $answer = KnowledgeFoundation\AnswerVersionSummaryContent::fetch($versionID);
            $levels = new \RightNow\Connect\v1_2\AccessLevelTypeArray();
            foreach($accessLevels as $accessLevel) {
                $levelType = new Connect\AccessLevelType();
                $levelType->ID = intval($accessLevel);
                $levels[] = $levelType;
            }
            $content = $answer->GetContent(\RightNow\Models\Base::getKnowledgeApiSessionToken(), null, $levels);
            if ($content->AnswerType->ID === ANSWER_TYPE_URL) {
                return array('location' => $content->URL);
            }
            if ($content->AnswerType->ID === ANSWER_TYPE_ATTACHMENT) {
                $rql = "SELECT AnswerVersion.Answer.ID FROM AnswerVersion WHERE AnswerVersion.ID = {$versionID}";
                $result = Connect\ROQL::query($rql)->next()->next();
                $answerID = $result['ID'];
                return array('location' => Api::cgi_url(CALLED_BY_ADMIN) . '/admin/fattach_get.php?p_sid=' . $this->_getAgentSessionId() . '&p_tbl=' . TBL_ANSWERS . "&p_id=$answerID&p_created=" . $content->FileAttachments[0]->CreatedTime);
            }
            return array('answer' => $content);
        }
        catch (\Exception $e) {
            return array('error' => \RightNow\Utils\Config::getMessage(ANS_REQUESTED_IS_INVALID_MSG));
        }
    }
}
