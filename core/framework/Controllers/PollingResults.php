<?php

namespace RightNow\Controllers;

/**
 * Endpoint for viewing results from a marketing poll.
 */
final class PollingResults extends Base
{
    /**
     * Takes a question and survey ID and renders a page showing the results for the poll.
     */
    public function index()
    {
        $parameters = $this->uri->uri_to_assoc(3);
        $this->questionID = $parameters['questionID'];
        $this->surveyID = $parameters['surveyID'];

        if (!isset($this->questionID) || ($this->questionID <= 0 && $this->questionID !== 'all') || !isset($this->surveyID) || $this->surveyID <= 0)
            exit();

        $surveyData = $this->model('Polling')->getSurveyData($this->surveyID)->result;

        if ($surveyData['flow_id'] > 0)
        {
            $this->questionIDNameList = $this->model('Polling')->getResultsPageQuestionList($surveyData['flow_id'])->result;

            $this->title = $surveyData['title'];
            $this->results = $this->model('Polling')->getPollResults($surveyData['flow_id'], $this->questionID, false, false)->result;
            $this->load->view("Admin/ma/polling/index");
        }
    }
}