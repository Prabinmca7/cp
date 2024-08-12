<?php
namespace RightNow\Widgets;

use \RightNow\Utils\Url;

class TopicWords extends \RightNow\Libraries\Widget\Base {

    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getdata() {
        if (!isset($this->data['attrs']['source_id']) || !$this->data['attrs']['source_id']) {
            $this->data['attrs']['source_id'] = $this->data['attrs']['default_search_source'];
        }

        $this->data['appendedParameters'] = Url::getParametersFromList($this->data['attrs']['add_params_to_url']) . Url::sessionParameter();
        $this->data['topicWords'] = $this->CI->model('Report')->getTopicWords(Url::getParameter('kw'))->result;

        for($i = 0; $i < count($this->data['topicWords']); $i++) {
            if (!(Url::isExternalUrl($this->data['topicWords'][$i]['url']))) {
                $this->data['topicWords'][$i]['url'] .= $this->data['appendedParameters'];
            }
        }
        
        if(count($this->data['topicWords']) === 0)
            $this->classList->add('rn_Hidden');
        
    }

}
