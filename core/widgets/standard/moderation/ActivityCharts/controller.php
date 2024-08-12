<?php
namespace RightNow\Widgets;
use RightNow\Utils\Framework;

class ActivityCharts extends \RightNow\Libraries\Widget\Base {
    
    protected $yAxisMaximum = 5;
    
    function __construct($attrs) {
        parent::__construct($attrs);
    }
    
    function getData() {
        if (!Framework::isSocialUser()) {
            return false;
        }
        $isUserModerationActionAllowed = $this->CI->model('CommunityUser')->isModerateActionAllowed(true);
        if ($isUserModerationActionAllowed !== true && count($this->data['attrs']['object_types']) === 1 && current($this->data['attrs']['object_types']) === 'CommunityUser') {
            return false;
        }
        
        $chartData = array();
        $socialObjectLabelsOrdered = array();
        $socialObjectLabels = array(
            'CommunityQuestion' => $this->data['attrs']['label_social_question'],
            'CommunityComment' => $this->data['attrs']['label_social_comment']
        );
        if ($isUserModerationActionAllowed === true) {
            $socialObjectLabels['CommunityUser'] = $this->data['attrs']['label_social_user'];
        }
        
        foreach($this->data['attrs']['object_types'] as $socialObject) {
            if ($socialObject === 'CommunityUser' && $isUserModerationActionAllowed !== true) {
                continue;
            }
            //Match the order as listed in the object_types attribute
            $socialObjectLabelsOrdered[$socialObject] = $socialObjectLabels[$socialObject];
            
            $counts = $this->CI->model($socialObject)->getRecentSocialObjectCountsByDateTime($socialObject, $this->data['attrs']['interval_unit'], $this->data['attrs']['number_of_intervals'])->result;            
            if ($counts) {
                //set the maximum to at least 5 if none of the counts have more than 5, else set the maximum from counts.
                $this->yAxisMaximum = max($counts) < $this->yAxisMaximum ? $this->yAxisMaximum : max($counts);
                
                foreach($counts as $date => $count) {
                    if (!isset($chartData[$date]['date']) || !$chartData[$date]['date']) {
                        $chartData[$date] = array('date' => $this->data['attrs']['interval_unit'] == 'month' ? date('M Y', strtotime($date)) : $date);
                    }
                    $chartData[$date][$socialObjectLabelsOrdered[$socialObject]] = $counts[$date];
                }
            }
        }
        
        //round the maximum to next available number which is multiple of 10s (e.g, convert 26, 32 to 30, 40 respectively), this helps to plot the graph's Y-axis number correctly without many floating numbers
        $this->yAxisMaximum = ($this->yAxisMaximum > 10 && ($this->yAxisMaximum % 10) > 0) ? $this->yAxisMaximum + (10 - ($this->yAxisMaximum % 10)) : $this->yAxisMaximum;
        $this->data['js']['chart']['axes']['maximum'] = $this->yAxisMaximum;
        
        //store the lables only for social object types passed in attribute
        $this->data['js']['chart']['keys'] = array_intersect_key($socialObjectLabelsOrdered, array_flip($this->data['attrs']['object_types']));
        $this->data['js']['chart']['data'] = array_values($chartData);
    }
}
