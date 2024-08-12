<?php
namespace RightNow\Models;

require_once CPCORE . 'Models/SitemapBase.php';

/**
 * Sitemap for answers
 */
class AnswersSitemap extends SitemapBase {

    /**
     * Maximum score for KB answers.
     * @var integer
     */
    private static $maxScore;

    public function __construct() {
        parent::__construct();

        $this->reportID = 10014;
    }

    /**
     * Processes report's row to extract specific details
     * @param array $row A row from reports data array
     * @return array Array of various details
     */
    public function processData(array $row) {
        list($answerUrl, $score, $lastModified, $title) = $row;
        list($path, $answerId) = $this->processAnswerLink($answerUrl, $title);

        if (!self::$maxScore) {
            // The report is sorted by score, so the
            // first result has the max score.
            self::$maxScore = $score > 0 ? $score : 100000;
        }

        return array(
            $path,
            $lastModified,
            $title,
            $answerId,
            array(
                'score' => $score
            )
        );
    }

    /**
     * This method is used to customize min-max priority range which will be used by xmlGenerator
     * This method can also be used to do prePriority calculations which is essiental in calculatePriority()
     * @param array $data An array contining list of following items
     * - lowerLimitKBAnswers Minimum priority a given KB answer can have
     * @return array An array containing list of following items
     * - minPriority Minimum priority to be used by xmlGenerator. Default: 0.5
     * - maxPriority Maximum priority to be used by xmlGenerator. Default: 1
     */
    public function prePriorityCalculation(array $data) {
        return array(
            'minPriority' => !empty($data['lowerLimitKBAnswers']) ? $data['lowerLimitKBAnswers'] : parent::MIN_LIMIT_PRIORITY_KB_ANSWERS,
            'maxPriority' => 1
        );
    }

    /**
     * Calculate priority for KB answers
     * @param array $priorityData An array with score values obtained from report to be used in priority
     * @param array $miscData An array containing additional details calculated prior to priority calculation. List includes
     * - totalPages Total number of pages for KB answers
     * - lowerLimit Starting point for priority scale
     * @return float $priority Calculated priority
     */
    public function calculatePriority(array $priorityData, array $miscData) {
        $priority = 0.5;
        $miscData['totalPages'] = empty($miscData['totalPages']) ? 1 : $miscData['totalPages'];

        $normalScore = $priorityData['score'] / self::$maxScore;
        $lowScore = ($miscData['totalPages'] - $this->pageNumber) / $miscData['totalPages'];
        $priority = ($normalScore / $miscData['totalPages']) + $lowScore;
        $priority = $miscData['preHookData']['lowerLimitKBAnswers'] + (1 - $miscData['preHookData']['lowerLimitKBAnswers']) * $priority;

        return $priority;
    }

    /**
     * Processes answer urls
     * @param string $link String which contains a href to an answer page
     * @param string $summary String which contains summary of the answer
     * @return array Array of [ /app/path/to/answer/a_id/id/~/answer-summary-slug, id ]
     */
    private function processAnswerLink($link, $summary) {
        if(!$summary || !preg_match('@href=["|\'](.*)["|\'][^>]*>(.*)<\/a>@', $link, $matches)) {
            return array();
        }

        return array(
            $matches[1] . "/~/" . \RightNow\Libraries\SEO::getAnswerSummarySlug($summary),
            $matches[2]
        );
    }

}