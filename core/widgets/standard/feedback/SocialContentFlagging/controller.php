<?php
namespace RightNow\Widgets;

class SocialContentFlagging extends \RightNow\Libraries\Widget\Base {

    private $question;
    private $comment;

    function __construct($attrs) {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array(
            'submit_flag_ajax' => array(
                'method' => 'submitFlagHandler',
                'clickstream' => 'social_content_flag',
                'login_required' => true,
            ),
        ));
    }

    function getData() {
        $questionID = $this->data['attrs']['question_id'];

        if(!$this->question = $this->CI->model('CommunityQuestion')->get($questionID)->result){
            return false;
        }

        if($this->data['attrs']['content_type'] === 'comment' && !$this->comment = $this->CI->model('CommunityComment')->getTabular($this->data['attrs']['comment_id'])->result){
            return false;
        }

        if(!$this->shouldShowFlaggingControls()){
            return false;
        }

        $this->data['userFlag'] = $this->getUserProvidedFlagOnContent();
        $this->data['js'] = array(
            'flags'           => $this->getFlagTypes($this->data['userFlag'], $this->helper('Social')->mapFlagTypeAttribute($this->data['attrs']['flag_types'])),
            'isFlagged'       => !!$this->getUserProvidedFlagOnContent(),
        );
        
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);

        $this->helper->flags = $this->data['js']['flags'];
        $this->helper->userFlag = $this->data['userFlag'];
        $this->helper->attrs = $this->data['attrs'];
    }

    /**
     * Ajax handler for submitting a flag
     * @param  array $params POST data sent with the request
     */
    function submitFlagHandler (array $params) {
        if($this->data['attrs']['content_type'] === 'question'){
            $flagResponse = $this->CI->model('CommunityQuestion')->flagQuestion($this->data['attrs']['question_id'], $params['flagID']);
        }
        else{
            $flagResponse = $this->CI->model('CommunityComment')->flagComment($this->data['attrs']['comment_id'], $params['flagID']);
        }
        if($flagResponse->result){
            echo json_encode(array('type' => $flagResponse->result->Type->ID));
        }
        else{
            echo $flagResponse->toJson();
        }
    }

    /**
     * Gets the flagging state for the object type we're checking
     * @return int ID of flagged content
     */
    protected function getUserProvidedFlagOnContent () {
        if($this->data['attrs']['content_type'] === 'question'){
            $questionFlags = $this->CI->model('CommunityQuestion')->getUserFlag($this->data['attrs']['question_id'])->result;
            return $questionFlags ? $questionFlags->Type->ID : 0;
        }
        return isset($this->comment->FlagType) ? $this->comment->FlagType : 0;
    }

    /**
     * Returns a list of flag types
     * @param int $userProvidedFlagID ID of the flag type set by the current user
     * @param array $flagsToInclude IDs of Flags to include
     * @return array List of flag types including their ID, name, and whether it's the one set by the user
     */
    protected function getFlagTypes ($userProvidedFlagID, array $flagsToInclude) {
        $objectInfo = \RightNow\Utils\Connect::retrieveMetaData('CommunityCommentFlg');

        return array_map(function ($namedValue) use ($userProvidedFlagID) {
            return (object) array(
                'ID'         => $namedValue->ID,
                'LookupName' => $namedValue->LookupName,
                'Selected'   => $namedValue->ID == $userProvidedFlagID, // User-provided flag ID is a string.
            );
        }, self::filterFlagsToSpecifiedTypes($objectInfo->Type->named_values, $flagsToInclude));
    }

    /**
     * Determines if the flagging controls should be shown to the user. Handles use cases where user isn't logged in
     * @return bool Whether or not flagging is allowed
     */
    protected function shouldShowFlaggingControls () {
        $isSocialUser = \RightNow\Utils\Framework::isSocialUser();
        //If the user isn't logged in or doesn't have a social profile and the questions is non-active/locked there isn't
        //much reason to show the UI to entice them to flag since for the majority of the time they won't be able to
        if(!$isSocialUser && ($this->question->SocialPermissions->isLocked() || !$this->question->SocialPermissions->isActive())){
            return false;
        }
        //Otherwise, show the UI if the user has permission, or they're not a social user (at which point we entice them to login/add social info)
        if($this->data['attrs']['content_type'] === 'question'){
            return $this->question->SocialPermissions->canFlag() || !$isSocialUser;
        }
        return $this->comment->SocialPermissions->canFlag() || !$isSocialUser;
    }

    /**
     * Filters a NamedIDValue array to include only values
     * for Flags with IDs specified in $flagsToInclude.
     * @param  array $allFlags       NamedIDValue array
     * @param  array $flagsToInclude Contains Flag IDs of Flags to inclue
     * @return array                  Filtered $allFlags
     */
    protected static function filterFlagsToSpecifiedTypes(array $allFlags, array $flagsToInclude) {
        $returnFlagsArray = array();
        foreach ($flagsToInclude as $flag) {
            foreach ($allFlags as $namedValue) {
                if ($namedValue->ID === $flag) {
                    $returnFlagsArray[] = $namedValue;
                }
            }
        }

        return $returnFlagsArray;
    }
}
