<?php
namespace RightNow\Widgets;

use RightNow\Utils\Url;

class OkcsRelatedAnswers extends \RightNow\Widgets\RelatedAnswers {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->classList = $this->classList->remove("rn_RelatedAnswers");
        if (!$answerID = Url::getParameter('a_id')) {
            return false;
        }

        $this->data['appendedParameters'] = Url::getParametersFromList($this->data['attrs']['add_params_to_url']) . '/related/1' . Url::sessionParameter();

        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        $this->data['js']['answerId'] = $answerID;
        $this->data['js']['cpAnswerView'] = \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL);
    }
}
