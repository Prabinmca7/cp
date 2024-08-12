<?php
namespace RightNow\Widgets;

class UserList extends \RightNow\Libraries\Widget\Base {
    
    function __construct($attrs) {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'content_load_ajax' => array(
                'method' => 'contentLoadAjax',
                'clickstream' => 'contentLoadAjax'
            )
        ));
    }

    function getData() {
        if($this->data['attrs']['content_load_mode'] === 'synchronous') {
            $this->data['user_data'] = $this->getUserList();
        }
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }
    
    /**
     * Get interval
     * @return string Interval
     */
    private function getInterval() {
        if (strpos($this->data['attrs']['activity_time_period'], "past_") === false) {
            $interval = null;
        }
        else {
            $interval = str_replace("past_", "", $this->data['attrs']['activity_time_period']);
        }
        return $interval;
    }
    
    /**
     * Get user list
     * @return array List of users
     */
    protected function getUserList() {
        $result = $this->CI->model('CommunityUser')->getListOfUsers($this->data['attrs']['content_type'], $this->data['attrs']['max_user_count'], $this->getInterval())->result;
        return $result;
    }
    
    /**
     * Ajax call for loading user list content
     */
    function contentLoadAjax() {
        $this->data['user_data'] = $this->getUserList();
        $content = $this->render($this->data['attrs']['content_display_type']);
        echo json_encode(array('result' => $content));
    }
}
