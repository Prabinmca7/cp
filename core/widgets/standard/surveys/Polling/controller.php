<?php
namespace RightNow\Widgets;

class Polling extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'submit_poll_ajax' => 'submitPoll',
        ));
    }

    function getData()
    {
        $this->data['js']['cookied_questionID'] = isset($_COOKIE[$this->instanceID]) ? intval($_COOKIE[$this->instanceID]) : 0;
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        if ($this->data['attrs']['modal'] === true && $this->data['js']['cookied_questionID'] > 0)
        {
            // we aren't going to pop the poll, don't even bother collecting data
            return false;
        }
        else
        {
            $this->data['ma_js_location'] = \RightNow\Utils\Url::getCoreAssetPath('js/' . MOD_BUILD_SP . '.' . MOD_BUILD_NUM . '/min/RightNow.MarketingFeedback.js');
            if (isset($this->data['attrs']['admin_console']) && $this->data['attrs']['admin_console'])
                return $this->getDataAdminPreview();
            else if ($this->data['js']['cookied_questionID'] > 0)
                return $this->getDataPollResults($this->data['js']['cookied_questionID']);
            else
                return $this->getDataServePoll();
        }
    }


    /**
     * Sets up the data object to do an admin preview
     */
    protected function getDataAdminPreview()
    {
        $questionID = $_REQUEST['question_id'];
        $surveyID = $_REQUEST['survey_id'];

        if ($questionID < 1 || $surveyID < 1)
            return false;

        $surveyData = $this->CI->model('Polling')->getPollQuestion($surveyID, $questionID, false)->result;

        $this->data['question'] = $surveyData['question'];
        $this->data['answer_area'] = $surveyData['answer_area'];
        $this->data['total_votes_label'] = $surveyData['total_votes_label'];
        $this->data['view_results_label'] = $surveyData['view_results_label'];
        $this->data['show_results_link'] = $surveyData['show_results_link'];

        if ($_REQUEST['modal'] === "true")
            $this->data['attrs']['modal'] = true;
        else
            $this->data['attrs']['modal'] = false;

        $this->data['js']['flow_id'] = $surveyData['flow_id'];
        $this->data['js']['doc_id'] = $surveyData['doc_id'];
        $this->data['js']['show_chart'] = $surveyData['show_chart'];
        $this->data['js']['question_type'] = $surveyData['question_type'];
        $this->data['js']['question_id'] = $questionID;

        if ($this->data['attrs']['modal'])
        {
            $this->data['js']['title'] = $surveyData['title'];
            $this->data['js']['submit_button_label'] = $surveyData['submit_button_label'];
        }
        else
        {
            $this->data['title'] = $surveyData['title'];
            $this->data['submit_button_label'] = $surveyData['submit_button_label'];
        }
    }

    /**
     * Sets up the data object to serve the poll question
     */
    protected function getDataServePoll()
    {
        $questionID = -1;
        $surveyID = $this->data['attrs']['survey_id'];

        if ($this->data['attrs']['test'])
            $data = $this->CI->model('Polling')->getPreviewQuestion($surveyID, $questionID, false)->result;
        else
            $data = $this->CI->model('Polling')->getPollQuestion($surveyID, $questionID, false)->result;

        if (!isset($data['flow_id']) || !$data['flow_id'])
        {
            if (!$this->data['attrs']['modal'])
                echo $this->reportError(\RightNow\Utils\Config::getMessage(FLOW_FND_SURVEY_ID_PLS_SURVEY_ID_MSG));
            $this->data['js']['disabled_expired'] = true;
            return false;
        }

        if ($data['survey_disabled'] === 1)
        {
            if (!$this->data['attrs']['modal'])
                echo \RightNow\Utils\Config::getMessage(POLL_DISABLED_LBL);
            $this->data['js']['disabled_expired'] = true;
            return false;
        }

        if ($data['max_responses_met'] || (intval($data['expiration_date']) > 0 && intval($data['expiration_date']) < time()))
        {
            if (!$this->data['attrs']['modal'])
            {
                if (strlen($data['expire_msg']) > 0)
                    echo $data['expire_msg'];
                else
                    echo \RightNow\Utils\Config::getMessage(POLL_EXPIRED_LBL);
            }
            $this->data['js']['disabled_expired'] = true;
            return false;
        }

        $this->data['question'] = isset($data['question']) ? $data['question'] : null;
        $this->data['answer_area'] = $data['answer_area'];
        $this->data['total_votes_label'] = $data['total_votes_label'];
        $this->data['view_results_label'] = $data['view_results_label'];
        $this->data['show_results_link'] = $data['show_results_link'];

        $this->data['js']['flow_id'] = $data['flow_id'];
        $this->data['js']['doc_id'] = $data['doc_id'];
        $this->data['js']['question_id'] = $data['question_id'];
        $this->data['js']['validation_script'] = isset($data['validation_script']) ? $data['validation_script'] : null;
        $this->data['js']['turn_text'] = $data['turn_text'];
        $this->data['js']['show_total_votes']  = $data['show_total_votes'];
        $this->data['js']['show_chart'] = $data['show_chart'];
        $this->data['js']['question_type'] = isset($data['question_type']) ? $data['question_type'] : null;
        $this->data['js']['element_type'] = isset($data['element_type']) ? $data['element_type'] : null;
        $this->data['js']['dialog_description'] = $data['title'] . ' ' . \RightNow\Utils\Config::getMessage(DIALOG_LBL);

        if ($this->data['attrs']['modal'] === true)
        {
            $this->data['js']['title'] = $data['title'];
            $this->data['js']['submit_button_label'] = $data['submit_button_label'];
            $this->data['js']['ok_button_label'] = $data['ok_button_label'];
        }
        else
        {
            $this->data['title'] = $data['title'];
            $this->data['submit_button_label'] = $data['submit_button_label'];
        }
    }

    /**
     * Sets up the data object to serve the results of a poll question
     * @param int $questionID The primary key for the question
     */
    protected function getDataPollResults($questionID)
    {
        $surveyID = $this->data['attrs']['survey_id'];

        $data = $this->CI->model('Polling')->getPollQuestion($surveyID, $questionID, false)->result;

        $this->data['question'] = isset($data['question']) ? $data['question'] : null; 
        $this->data['total_votes_label'] = $data['total_votes_label'];
        $this->data['title'] = $data['title'];

        $this->data['js']['show_total_votes'] = $data['show_total_votes'];
        $this->data['js']['question_type'] = isset($data['question_type']) ? $data['question_type'] : null;
        $this->data['js']['show_chart'] = $data['show_chart'];
        $this->data['js']['turn_text'] = $data['turn_text'];

        $resultsData = $this->CI->model('Polling')->getPollResults(isset($data['flow_id']) ? $data['flow_id'] : 0, $questionID, $this->data['attrs']['test'])->result;
        $this->data['js']['total'] = $resultsData['total'];
        $this->data['js']['question_results'] = $resultsData['question_results'];
    }

    /**
     * Submits a poll and returns the result data
     * @param array|null $parameters The parameters used to get the results of a poll
     */
    function submitPoll($parameters)
    {
        $flowID = $parameters['flow_id'];
        $questionID = $parameters['question_id'];
        $testMode = $parameters['test'] === true || $parameters['test'] === 'true';

        if (isset($parameters['results_only']) && $parameters['results_only'])
        {
            $this->renderJSON($this->CI->model('Polling')->getPollResults($flowID, $questionID, $testMode)->result);
        }
        else
        {
            \RightNow\Libraries\AbuseDetection::check();

            $docID = $parameters['doc_id'];
            $responses = $parameters['responses'];
            $chartType = $parameters['chart_type'];
            $includeResults = ($parameters['include_results'] === 'true');
            $src = 0;
            $tbl = 0;

            if (isset($parameters['i_id']) && intval($parameters['i_id']) > 0) {
                $tbl = VTBL_INCIDENTS;
                $src = $parameters['i_id'];
            }
            else if (intval($parameters['chat_id']) > 0){
                $tbl = TBL_CHATS;
                $src = $parameters['chat_id'];
            }

            if (!$testMode)
                $this->CI->model('Polling')->submitPoll($flowID, $docID, $questionID, $responses, isset($questionType) ? $questionType : null, false, $src, $tbl);

            if ($includeResults)
                $this->renderJSON($this->CI->model('Polling')->getPollResults($flowID, $questionID, $testMode)->result);
        }
    }
}
