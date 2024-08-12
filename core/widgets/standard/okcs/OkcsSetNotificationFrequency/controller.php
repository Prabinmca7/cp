<?php
namespace RightNow\Widgets;

class OkcsSetNotificationFrequency extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $value = $this->CI->model('Okcs')->getUserSubscriptionSchedule();
        $this->data['scheduleValue'] = $value;
        $this->data['js']['selectedValue'] = $value;
    }
}
