<?php

namespace RightNow\Widgets;

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Utils\Config;

class ModerationDateFilter extends \RightNow\Libraries\Widget\Base {

    function __construct ($attrs) {
        parent::__construct($attrs);
    }

    function getData () {
        $filterMetaData = $this->CI->model("Report")->getFilterByName($this->data['attrs']['report_id'], $this->data['attrs']['report_filter_name']);
        if ($filterMetaData->errors) {
            echo $this->reportError($filterMetaData->error);
            return false;
        }
        if ($this->data['attrs']['date_filter_options'] === array('none')) {
            return false;
        }
        if (!($maxDateRangeParts = $this->helper('Social')->validateModerationMaxDateRangeInterval($this->data['attrs']['max_date_range_interval']))) {
            echo $this->reportError(Config::getMessage(MAX_FMT_YEAR_T_S_EX_90_S_5_YEAR_ETC_MSG));
            return false;
        }
        $optionLabels = $this->helper('Social')->formatListAttribute($this->data['attrs']['date_filter_option_labels']);
        $dateFormat = Text::getDateFormatFromDateOrderConfig();
        $optionLabels["custom"] = sprintf($optionLabels["custom"], $dateFormat["label"]);
        $enabledOptions = array();
        foreach ($this->data['attrs']['date_filter_options'] as $dateFilterOption) {
            if (!empty($dateFilterOption)) {
                $enabledOptions[$dateFilterOption] = $optionLabels[$dateFilterOption];
            }
        }
        
        $urlDate = Url::getParameter($this->data['attrs']['report_filter_name']);
        $urlDate = ($urlDate !== null) ? trim($urlDate) : null;
        if (isset($optionLabels[$urlDate])) {
            $enabledOptions[$urlDate] = $optionLabels[$urlDate];
            $dateValue = $urlDate;
        }
        else if (!empty($urlDate) && $dateRange = Text::validateDateRange($urlDate, $dateFormat["short"], "|", false, $this->data['attrs']['max_date_range_interval'])) {
            $enabledOptions["custom"] = $optionLabels["custom"];
            $dateValue = $dateRange;
        }
        else {
            $defaultDateValue = $this->getDefaultFilterValue($filterMetaData, $optionLabels);
            if($optionLabels[$defaultDateValue]){
                $enabledOptions[$defaultDateValue] = $optionLabels[$defaultDateValue];
            } else {
                $enabledOptions["custom"] = $optionLabels["custom"];
            }
        }
        if (empty($enabledOptions)) {
            return false;
        }
        $dateUnitLabels = Text::getDateUnitLabels();
        $this->data['js'] = array('options' => $enabledOptions,
            'filter_id' => $filterMetaData->result['fltr_id'],
            'oper_id' => $filterMetaData->result['oper_id'],
            'urlDateValue' => isset($dateValue) ? $dateValue : null,
            'default_value' => isset($defaultDateValue) ? $defaultDateValue : null,
            'date_format' => $dateFormat,
            'max_data_range_label' => $maxDateRangeParts ? $maxDateRangeParts[0] . " " . $dateUnitLabels[$maxDateRangeParts[1]] : ""
        );
    }

    /**
     * Gets the default date filter value from the report
     *
     * @param \RightNow\Libraries\ResponseObject $filterMetaData The report filter metadata
     * @param array $optionLabels Array of allowed date options
     * @return string Default date value
     */
    protected function getDefaultFilterValue(\RightNow\Libraries\ResponseObject $filterMetaData, array $optionLabels) {
        $dateFormat = Text::getDateFormatFromDateOrderConfig();
        $parseOptions = array("allowedOptions" => $optionLabels, "dateFormat" => $dateFormat["short"]);
        return $this->helper('Social')->parseReportDefaultFilterValue($filterMetaData->result["default_value"], $filterMetaData->result["data_type"], $parseOptions);
    }
}
