<?php

namespace RightNow\Models;

/**
 * Base class for all sitemap models. Provides a number of common methods.
 */
abstract class SitemapBase extends Base {

    private $totalRows;
    private $sitemapPageLimit;
    protected $pageNumber;
    protected $reportID;

    /**
     * Total number of urls allowed on a single sitemap page. Default value 50000. Max limit: 50000
     */
    const SITEMAP_MAX_PAGE_LIMIT = 50000;
    const PAGE_NUMBER_DEFAULT = 0;
    /**
     * Start point for range of priority scale (generally from 0 to 1) for KB answers. Default: 0.5
     */
    const MIN_LIMIT_PRIORITY_KB_ANSWERS = 0.5;
    /**
     * End point for range of priority scale (generally from 0 to 1) for social questions/discussions. Default: 0.8
     */
    const MAX_LIMIT_PRIORITY_DISCUSSIONS = 0.8;

    /**
     * Returns total number of rows in report
     * @return int Total rows
     */
    public function getTotalRows() {
        if (!isset($this->totalRows)) {
            // invoke report with minimal pages to get total_num section
            $results = $this->getReportData(array("sitemapPageLimit" => 1));
            $this->totalRows = $results['total_num'];

            $this->totalRows = is_null($this->totalRows) ? 0 : $this->totalRows;
        }

        return $this->totalRows;
    }

    /**
     * Executing report to retrieve rows
     * @param array $settings Various settings meant for report execution. List includes
     * - sitemapPageLimit Total number of urls allowed on a single sitemap page.
     * - pageNumber Page number for which sitemap is to be displayed.
     * @return array Report array containing data and headers
     */
    public function getReportData(array $settings) {
        $this->sitemapPageLimit = isset($settings['sitemapPageLimit']) ? $settings['sitemapPageLimit'] : self::SITEMAP_MAX_PAGE_LIMIT;
        $this->pageNumber = isset($settings['pageNumber']) ? $settings['pageNumber'] : self::PAGE_NUMBER_DEFAULT;
        $reportSettings = $this->getReportSettings();
        $results = $this->CI->model('Report')->getDataHTML($this->reportID,
            \RightNow\Utils\Framework::createToken($this->reportID), $reportSettings['filters'], $reportSettings['format'])->result;

        if (!isset($results['data']) || count($results['data']) === 0) {
            return array();
        }

        return $results;
    }

    /**
     * Modifies existing values meant for priority calculation using hooks
     * @param array &$preHookData Various data meant for priority algorithm calculation. List includes
     * - lowerLimitKBAnswers Starting point for KB answers priority scale to begin with.
     * - upperLimitSocialQuestions Max limit for questions/discussions priority scale to end at.
     */
    public function getPreHookData(array &$preHookData) {
        $preHookData = array(
                    'lowerLimitKBAnswers' => self::MIN_LIMIT_PRIORITY_KB_ANSWERS,
                    'upperLimitSocialQuestions' => self::MAX_LIMIT_PRIORITY_DISCUSSIONS
                    );
        \RightNow\Libraries\Hooks::callHook('pre_sitemap_priority_data', $preHookData);
    }

    /**
     * Default report format and filters.
     * @return array Contains filters and format keys
     */
    private function getReportSettings() {
        $format = array(
            'raw_date'   => true,
            'no_session' => true,
        );
        $filters = array(
            'sitemap'     => true,
            'no_truncate' => 1,
            'per_page'    => $this->sitemapPageLimit,
        );

        if ($this->pageNumber !== 0) {
            $filters['page'] = $this->pageNumber;
        }

        return array('filters' => $filters, 'format' => $format);
    }

    /**
     * Processes report's row to extract specific details
     * @param array $row A row from reports data array
     * @return array Array of various details
     */
    abstract public function processData(array $row);

    /**
     * Calculations and setting various variables that are to be used before priority calculation
     * @param array $data An array containing data required for pre priority calculation
     * @return array Array of various details
     */
    abstract public function prePriorityCalculation(array $data);

    /**
     * Calculate SEO priority
     * @param array $priorityData An array containing data obtained from reports to be used for priority.
     * @param array $miscData An array containing additional data obtained/calculated ad-hoc for priority calculation.
     * @return float Priority value.
     */
    abstract public function calculatePriority(array $priorityData, array $miscData);
}
