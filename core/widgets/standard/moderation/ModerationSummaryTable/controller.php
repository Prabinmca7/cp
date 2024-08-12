<?php
namespace RightNow\Widgets;
use RightNow\Utils\Config;

class ModerationSummaryTable extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $this->data['tableData'] = $this->getSummaryTableData();
        $this->data['noteLabels'] = $this->helper('Social')->formatListAttribute($this->data['attrs']['label_date_filter_option_note']);
    }

    /**
     * Method to build URL for moderation page with filters if $statusTypeID is supplied
     * @param string $socialObjectName Name of the social object for which URL need to be build.
     * @param int|null $statusTypeID Status Type ID
     * @return string Moderation URL with filters if $statusTypeID is supplied
     */
    protected function buildModerationURL($socialObjectName, $statusTypeID = null) {
        $urlData = array(
            'CommunityQuestion' => array(
                'link' => $this->data['attrs']['question_moderation_url'],
                'status_filter' => $this->data['attrs']['question_report_status_filter_name']
            ),
            'CommunityComment' => array(
                'link' => $this->data['attrs']['comment_moderation_url'],
                'status_filter' => $this->data['attrs']['comment_report_status_filter_name']
            ),
            'CommunityUser' => array(
                'link' => $this->data['attrs']['user_moderation_url'],
                'status_filter' => $this->data['attrs']['user_report_status_filter_name']
            )
        );

        if (!$statusTypeID) {
            return $urlData[$socialObjectName]['link'];
        }

        $statusTypeStatusMap = $this->CI->model($socialObjectName)->getMappedSocialObjectStatuses()->result;
        if (!$statusTypeStatusMap[$statusTypeID]) {
            return $urlData[$socialObjectName]['link'];
        }
        return $urlData[$socialObjectName]['link'] . '/' . $urlData[$socialObjectName]['status_filter']. '/' . implode(',', array_keys($statusTypeStatusMap[$statusTypeID]));
    }

    /**
     * Get the interval and unit for a given date filters to use in RQL
     * @param string $selectedDateFilter Supplied date filter
     * @return array Interval and unit for a given date filter
     */
    protected function getDateFilterOption($selectedDateFilter) {
        $dateFilterOptions = array(
            'last_24_hours'	=> array('interval' => 'hour','unit' => -24),
            'last_7_days'	=> array('interval' => 'day', 'unit' => -7),
            'last_30_days'	=> array('interval' => 'day', 'unit' => -30),
            'last_90_days'	=> array('interval' => 'day', 'unit' => -90),
            'last_365_days'	=> array('interval' => 'day', 'unit' => -365)
        );
        return $dateFilterOptions[$selectedDateFilter];
    }

    /**
     * Method to get the data for summary table.
     * @return array Different counts as an array
     */
    protected function getSummaryTableData() {
        $data = array();
        $dateFilterData = $this->getDateFilterOption($this->data['attrs']['date_filter_options']);
        if ($questionCounts = $this->getSocialObjectCountsOrderedByStatusType('CommunityQuestion', $dateFilterData['interval'], $dateFilterData['unit'])) {
            $data[] = $questionCounts;
        }
        if ($commentCounts = $this->getSocialObjectCountsOrderedByStatusType('CommunityComment', $dateFilterData['interval'], $dateFilterData['unit'])) {
            $data[] = $commentCounts;
        }
        if ($this->CI->model('CommunityUser')->isModerateActionAllowed(true) === true && $userCounts = $this->getSocialObjectCountsOrderedByStatusType('CommunityUser')) {
            $data[] = $userCounts;
        }
        $headers = array(
            array('heading' => $this->data['attrs']['label_type_heading']),
            array('heading' => $this->data['attrs']['label_status_suspended']),
            array('heading' => $this->data['attrs']['label_status_active']),
            array('heading' => $this->data['attrs']['label_status_archived']),
            array('heading' => $this->data['attrs']['label_total'])
        );

        return array(
            "data" => $data,
            "headers" => $headers
        );
    }

    /**
     * ROQL default order by LookupName (langauge dependent) or StatusTypeID (IDs are not arranged in same order for all social object) may not be consistent.
     * Summary table needs the data to be displayed in following order - suspended and active and also populate the counts of other status types and total count
     * @param string $socialObjectName Name of the social object for which count is needed.
     * @param string $interval Group by interval either day or hour
     * @param integer $unit Number of intervals. Pass negative number to get the counts for past date.
     * @return array Array of count data for different status types
     */
    private function getSocialObjectCountsOrderedByStatusType($socialObjectName, $interval = null, $unit = null) {
        $counts = $this->CI->model($socialObjectName)->getSocialObjectCountsByStatusType($socialObjectName, $interval, $unit)->result;
        $statusTypes = $this->CI->model($socialObjectName)->getSocialObjectMetadataMapping($socialObjectName, 'status_type_ids')->result;
        $orderByStatus = array('suspended', 'active', 'archive');

        $orderedCounts = array();

        if ($counts) {
            $typeLabels = array(
                'CommunityQuestion' => array('label' => $this->data['attrs']['label_type_question']),
                'CommunityComment' => array('label' => $this->data['attrs']['label_type_comment']),
                'CommunityUser' => array('label' => $this->data['attrs']['label_type_user'])
            );

            //Label columns
            $orderedCounts[] = array(
                'value' => $typeLabels[$socialObjectName]['label']
            );

            //Dynamic columns
            foreach($orderByStatus as $status) {
                if ('CommunityUser' !== $socialObjectName && 'archive' === $status) {
                    $orderedCounts[] = array('value' => 0);
                    continue;
                }
                $orderedCounts[] = array(
                    'value' => isset($counts[$statusTypes[$status]]) ? $counts[$statusTypes[$status]] : 0,
                    'link' => isset($counts[$statusTypes[$status]]) ? $this->buildModerationURL($socialObjectName, $statusTypes[$status]) : null
                );
            }

            //Total count columns
            array_push($orderedCounts, array('value' => array_sum($counts), 'link' => $this->buildModerationURL($socialObjectName)));
        }
        return $orderedCounts;
    }
}
