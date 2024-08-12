<?php
namespace RightNow\Models;

require_once CPCORE . 'Models/AnswersSitemap.php';

use RightNow\Libraries\Hooks,
    RightNow\ActionCapture;

/**
 * Sitemap for OKCS Answers
 */
class OkcsAnswersSitemap extends AnswersSitemap {
     /**
     * Executing report to retrieve rows
     * @param array $settings Various settings meant for report execution. List includes
     * - pageNumber Page number for which sitemap is to be displayed.
     * @return array Report array containing data and headers
     */
    public function getReportData(array $settings) {
        $this->sitemapPageLimit = self::SITEMAP_MAX_PAGE_LIMIT;
        $this->pageNumber = isset($settings['pageNumber']) ? $settings['pageNumber'] : self::PAGE_NUMBER_DEFAULT;
        $hookData = array('pageNumber' => $this->pageNumber, 'sitemapPageLimit' => $this->sitemapPageLimit);
        if ((!is_string($hookError = Hooks::callHook('okcs_site_map_answers', $hookData))) ) {
            $results = $hookData['answers'];
        }
        else {
            ActionCapture::instrument('getReportData', 'Request', 'error', array('RequestOrigin' => 'getReportData'), $hookError);
        }

        if (!isset($results['data']) || count($results['data']) === 0) {
            return array();
        }

        return $results;
    }
}