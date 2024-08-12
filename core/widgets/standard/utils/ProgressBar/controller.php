<?php
namespace RightNow\Widgets;

class ProgressBar extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $stepDescriptions = explode(',', $this->data['attrs']['step_descriptions']);
        $this->data['stepDescriptions'] = array();
        foreach($stepDescriptions as $stepDescription)
            $this->data['stepDescriptions'][] = trim($stepDescription);
        $totalSteps = count($this->data['stepDescriptions']);
        if ($totalSteps === 0)
            return false;
        array_unshift($this->data['stepDescriptions'], null);
        $this->data['totalSteps'] = $totalSteps;
    }
}
