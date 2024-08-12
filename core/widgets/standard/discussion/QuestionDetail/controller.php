<?php
namespace Rightnow\Widgets;

use RightNow\Utils\Text;

class QuestionDetail extends \RightNow\Libraries\Widget\Base {
    private $questionStatus;

    function __construct($attrs) {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'delete_question_ajax' => array(
                'method' => 'delete',
                'clickstream' => 'question_delete',
                'login_required' => true,
            )
        ));
    }

    function getData() {
        if (!$question = $this->CI->model('CommunityQuestion')->get(\RightNow\Utils\Url::getParameter('qid'))->result) return false;

        if ($question->SocialPermissions->isDeleted()) {
            $this->classList->add('rn_DeletedQuestion');
        }
        $this->data['currentPage'] = \Rightnow\Utils\Url::deleteParameter($_SERVER['REQUEST_URI'], 'qid');
        $this->data['js']['userIsAuthor'] = $question->SocialPermissions->isAuthor();
        $this->data['question'] = $question;

        $statusTypeID = isset($question->CreatedByCommunityUser->StatusWithType->StatusType->ID) ? $question->CreatedByCommunityUser->StatusWithType->StatusType->ID : 0;
        if ($statusTypeID === STATUS_TYPE_SSS_USER_SUSPENDED || $statusTypeID === STATUS_TYPE_SSS_USER_DELETED) {
            $this->data['profileUrl'] = null;
            $this->data['author'] = \RightNow\Utils\Config::getMessage(INACTIVE_BRACKETS_LBL);
            $this->data['authorClassList'] = 'rn_DisplayName rn_DisplayNameDisabled';
        }
        else {
            $this->data['profileUrl'] = isset($question->CreatedByCommunityUser->ID) ? $this->helper('Social')->userProfileUrl($question->CreatedByCommunityUser->ID) : null;
            $this->data['author'] = isset($question->CreatedByCommunityUser->DisplayName) ? $question->CreatedByCommunityUser->DisplayName : null;
            $this->data['authorClassList'] = 'rn_DisplayName';
        }

        //Passing 0 disables the highlighting feature
        if($this->data['attrs']['author_roleset_callout'] !== "0") {
            $this->data['author_roleset_callout'] = $this->helper('Social')->filterValidRoleSetIDs($this->data['attrs']['author_roleset_callout']);
            $this->data['author_roleset_styling'] = $this->helper('Social')->generateRoleSetStyles($this->data['author_roleset_callout']);
        }
        
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }

    /**
     * Returns a formatted string for a given question
     * @param object $question Question object to pull the date from
     * @param string $dateField Name of the date field to pull the date from
     * @return string Formatted timestamp
     */
    function formatDate($question, $dateField = null) {
        if ($dateField) {
            return \RightNow\Libraries\Formatter::formatField($question->$dateField, $question::getMetadata()->$dateField, $this->data['attrs']['highlight']);
        }
        return date('Y-m-d', $question);
    }

    /**
     * Handles the delete question AJAX request
     * @param Array $params Get / Post parameters
     */
    function delete(array $params) {
        // get the first status with status type = deleted
        $statusResults = $this->CI->model('CommunityQuestion')->getSocialObjectStatuses(STATUS_TYPE_SSS_QUESTION_DELETED);
        if (!$statusResults->result) {
            echo $statusResults->toJson();
        }
        $deletedResult = $this->updateQuestion($params['questionID'], array(
            'CommunityQuestion.StatusWithType.Status.ID' => (object) array('value' => $statusResults->result[0]->Status->ID)
        ));
        if(count($deletedResult->errors) === 0) {
            $this->CI->session->setFlashData('info', $this->data['attrs']['successfully_deleted_question_banner']);
        }
        echo $deletedResult->toJson();
    }

    /**
     * Updates the given question with the given data.
     * @param Int $id Question id
     * @param Array $updateData Form data to update with
     * @return ResponseObject Result of operation
     */
    private function updateQuestion($id, $updateData) {
        return $this->CI->model('CommunityQuestion')->update($id, $updateData);
    }
}
