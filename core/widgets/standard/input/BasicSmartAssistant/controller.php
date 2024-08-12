<?php
namespace RightNow\Widgets;

class BasicSmartAssistant extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['disableSmartAssistant'] = $this->CI->input->post('smart_assistant') === 'false';
        if (!$this->data['disableSmartAssistant']) {
            require_once CPCORE . 'Libraries/PostRequest.php';
            $this->data['smartAssistantResults'] = $smartAssistantResults = \RightNow\Libraries\PostRequest::getSmartAssistantResults();
            $this->data['showHeader'] = $this->showHeader($smartAssistantResults);
        }
    }

    /**
     * Determines whether or not to show the link and button in the header
     * @param mixed $smartAssistantResults Smart assistant results
     * @return bool Whether to display the link and button in the header
     */
    protected function showHeader($smartAssistantResults) {
        if ($smartAssistantResults && is_array($suggestions = $smartAssistantResults['suggestions']) && count($suggestions)) {
            foreach ($suggestions as $suggestion) {
                if ($suggestion['type'] !== 'AnswerSummary' && $suggestion['type'] !== 'QuestionSummary')
                    return true;
            }
        }
        return false;
    }
}
