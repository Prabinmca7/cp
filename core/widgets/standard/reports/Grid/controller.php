<?php

namespace RightNow\Widgets;

use RightNow\Utils\Config;

class Grid extends \RightNow\Libraries\Widget\Base
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        $this->data['attrs']['sanitize_data'] = $this->parseSanitizeDataToArray($this->data['attrs']['sanitize_data']);
        $format = array(
            'truncate_size' => isset($this->data['attrs']['truncate_size']) ? $this->data['attrs']['truncate_size'] : null,
            'max_wordbreak_trunc' => isset($this->data['attrs']['max_wordbreak_trunc']) ? $this->data['attrs']['max_wordbreak_trunc'] : null,
            'emphasisHighlight' => isset($this->data['attrs']['highlight']) ? $this->data['attrs']['highlight'] : null,
            'recordKeywordSearch' => true,
            'dateFormat' => isset($this->data['attrs']['date_format']) ? $this->data['attrs']['date_format'] : null,
            'urlParms' => isset($this->data['attrs']['add_params_to_url']) ? \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url']) : null,
            'hiddenColumns' => true,
            'sanitizeData' => $this->data['attrs']['sanitize_data']
        );

        $filters = array('recordKeywordSearch' => true);
        \RightNow\Utils\Url::setFiltersFromAttributesAndUrl($this->data['attrs'], $filters);
        if (!$this->helper('Social')->validateModerationMaxDateRangeInterval($this->data['attrs']['max_date_range_interval'])) {
            echo $this->reportError(Config::getMessage(MAX_FMT_YEAR_T_S_EX_90_S_5_YEAR_ETC_MSG));
            return false;
        }        
        $filters = $this->CI->model('Report')->cleanFilterValues($filters, $this->helper('Social')->getModerationDateRangeValidationFunctions($this->data['attrs']['max_date_range_interval']));
        $reportToken = \RightNow\Utils\Framework::createToken($this->data['attrs']['report_id']);
        $results = $this->CI->model('Report')->getDataHTML($this->data['attrs']['report_id'], $reportToken, $filters, $format)->result;
        if ($results['error'] !== null)
        {
            echo $this->reportError($results['error']);
            return false;
        }

        $this->data['tableData'] = $results;
        if(count($this->data['tableData']['data']) === 0 && $this->data['attrs']['hide_when_no_results'])
        {
            $this->classList->add('rn_Hidden');
        }

        $filters['page'] = $results['page'];
        $this->data['js'] = array(
            'filters'       => $filters,
            'columnID'      => isset($filters['sort_args']) ? (int) $filters['sort_args']['filters']['col_id'] : null,
            'sortDirection' => isset($filters['sort_args']) ? (int) $filters['sort_args']['filters']['sort_direction'] : null,
            'format'        => $format,
            'token'         => $reportToken,
            'headers'       => $this->data['tableData']['headers'],
            'rowNumber'     => $this->data['tableData']['row_num'],
            'searchName'    => 'sort_args',
            'dataTypes'     => array('date' => VDT_DATE, 'datetime' => VDT_DATETIME, 'number' => VDT_INT)
        );

        //Columns to exclude from sorting
        $this->data['attrs']['exclude_from_sorting'] = array_map('trim', explode(",", $this->data['attrs']['exclude_from_sorting']));
    }

    /**
     * This method will parse sanitize_data input to generate a well formed array
     * @param string $data String mentioned as widget attribute containing list of columns to sanitize along with type of sanitization 
     * or column number to fetch type of sanitization. For example,
     * '1|text/x-markdown, 3|text/x-markdown, 5|text/html, 2|16'
     * @return array Well formed array to be used for sanitization at model layer. For example,
     * array(
     * 1 => 'text/x-markdown',
     * 3 => 'text/x-markdown',
     * 5 => 'text/html'
     * 2 => 16
     * )
     */
    private function parseSanitizeDataToArray($data) {
        $returnData = array();
        if (is_string($data) && preg_match_all("/[\d]+|([a-z\/-]+)/", $data, $matches)) {
            $length = count($matches[0]);
            for($i = 0; $i < $length; $i += 2) {
                $returnData[(int)$matches[0][$i]] = $matches[0][$i + 1];
            }
        }

        return $returnData;
    }
}
