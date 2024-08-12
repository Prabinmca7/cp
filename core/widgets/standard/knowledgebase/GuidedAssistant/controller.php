<?php
namespace RightNow\Widgets;
use RightNow\Utils\Url;

class GuidedAssistant extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'guide_request_ajax' => 'getGuideAsArray',
        ));
    }

    function getData() {
        if ($this->data['attrs']['static_guide_id']) {
            $guideID = $this->data['attrs']['static_guide_id'];
        }
        else if ($guideID = Url::getParameter('g_id')) {
            $guideID = intval($guideID);
        }
        else if (($answerID = Url::getParameter('a_id')) && ($answer = $this->CI->model('Answer')->get($answerID)->result)) {
            $guideID = $answer->GuidedAssistance ? $answer->GuidedAssistance->ID : null;
        }

        if($guideID) {
            if($this->data['attrs']['popup_window_url']) {
                $this->data['attrs']['popup_window_url'] = \RightNow\Utils\Url::addParameter($this->data['attrs']['popup_window_url'], 'g_id', $guideID);
                return;
            }
            $langID = Url::getParameter('lang');
            $guidedAssistant = $this->CI->model('Guidedassistance')->get($guideID, $langID)->result;
            if($guidedAssistant) {
                $this->data['firstQuestion'] = $guidedAssistant->questions[0];
                $this->data['guideID'] = $guidedAssistant->guideID;
                $this->data['js'] = array(
                    'guidedAssistant' => $guidedAssistant->toArray(),
                    'types' =>
                        array('QUESTION_RESPONSE' => GA_QUESTION_RESPONSE,
                                'GUIDE_RESPONSE' => GA_GUIDE_RESPONSE,
                                'ANSWER_RESPONSE' => GA_ANSWER_RESPONSE,
                                'TEXT_RESPONSE' => GA_TEXT_RESPONSE,
                                'URL_POST' => GA_URL_POST,
                                'URL_GET' => GA_URL_GET,
                                'BUTTON_QUESTION' => GA_BUTTON_QUESTION,
                                'MENU_QUESTION' => GA_MENU_QUESTION,
                                'LIST_QUESTION' => GA_LIST_QUESTION,
                                'LINK_QUESTION' => GA_LINK_QUESTION,
                                'IMAGE_QUESTION' => GA_IMAGE_QUESTION,
                                'TEXT_QUESTION' => GA_TEXT_QUESTION,
                                'RADIO_QUESTION' => GA_RADIO_QUESTION),
                    'session' => $this->CI->session->getSessionData('sessionID'),
                    'f_tok'    => \RightNow\Utils\Framework::createTokenWithExpiration(0, false, false),
                    'channel' => GUIDE_USAGE_CHANNEL_CUSTOMER);
                if($langID) {
                    $this->data['js']['langID'] = $langID;
                }
                if($mobileBrowser = $this->CI->agent->supportedMobileBrowser()){
                    $this->data['js']['mobile'] = $mobileBrowser;
                }

                $this->data['js']['isSpider'] = $this->CI->rnow->isSpider();
                $metaInfo = $this->CI->_getMetaInformation();
                if(isset($metaInfo['account_session_required']) && strtolower($metaInfo['account_session_required']) === 'true') {
                    $this->processAgentMode();
                }
                return;
            }
        }
        //didn't retrieve the needed data: don't output widget
        return false;
    }

    /**
     * AJAX request handler for sub-guide retrieval. Echos out JSON encoded result
     * @param array|null $params Post parameters
     */
    function getGuideAsArray($params) {
        $this->renderJSON(($guide = $this->CI->model('Guidedassistance')->get(intval($params['guideID']), (isset($params['langID'])) ? intval($params['langID']) : null)->result) ? $guide->toArray() : array());
    }

    /**
     * Sets various flags according to the agent viewing mode.
     */
    protected function processAgentMode() {
        //if session is required (and we've reached this point) then the user's an agent
        if($consoleMode = Url::getParameter('preview')) {
            //preview is either one of two values:
            //agent (don't log stats, don't display answers; display agent text)
            //enduser (don't log stats, don't display agent text; display answers)
            $this->data['js']['agentMode'] = ($consoleMode === 'agent') ? 'agentPreview' : 'enduserPreview';
        }
        else {
            //live agent runtime (log stats, display agent text, don't display answers)
            $this->data['js']['agentMode'] = 'agent';
        }
        $this->data['js']['accountID'] = intval(Url::getParameter('account_id'));
        $this->addJavaScriptInclude(\RightNow\Utils\Url::getCoreAssetPath('debug-js/RightNow.Agent.js'));
        if(Url::getParameter('chat')) {
            $this->data['js']['isChatAgent'] = true;
            $this->data['js']['channel'] = GUIDE_USAGE_CHANNEL_CHAT;
        }
        else {
            $this->data['js']['channel'] = GUIDE_USAGE_CHANNEL_AGENT;
        }
    }
}

