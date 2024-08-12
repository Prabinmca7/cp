<?php
namespace RightNow\Models;

use RightNow\Utils\Url;

require_once CPCORE . 'Models/SitemapBase.php';

/**
 * Sitemap for questions
 */
class QuestionsSitemap extends SitemapBase {

    public function __construct() {
        parent::__construct();

        $this->reportID = 15138;
    }

    /**
     * Processes report's row to extract specific details
     * @param array $row A row from reports data array
     * @return Array of various details
     */
    public function processData(array $row) {
        list($questionId, $title, $commentCount, $bestAnswerCount, $lastModified) = $row;
        $path = $this->processLink($questionId, $title);

        return array(
            $path,
            $lastModified,
            $title,
            $questionId,
            array(
                'bestAnswerCount' => $bestAnswerCount,
                'commentCount' => $commentCount
            )
        );
    }

    /**
     * This method is used to customize min-max priority range which will be used by xmlGenerator
     * This method can also be used to do prePriority calculations which is essiental in calculatePriority()
     * @param array $data An array contining list of following items
     * - upperLimitSocialQuestions Maximum priority a given social question/discussion can have
     * @return array An array containing list of following items
     * - minPriority Minimum priority to be used by xmlGenerator. Default: 0.5
     * - maxPriority Maximum priority to be used by xmlGenerator. Default: 1
     */
    public function prePriorityCalculation(array $data) {
        return array(
            'minPriority' => 0,
            'maxPriority' => !empty($data['upperLimitSocialQuestions']) ? $data['upperLimitSocialQuestions'] : parent::MAX_LIMIT_PRIORITY_DISCUSSIONS
        );
    }

    /**
     * Calculates priority of social questions/discussions
     * @param array $priorityData An array with values to be used in priority. List includes
     * - bestAnswerCount Total number of best answers for a given question/discussion
     * - commentCount Total number of comments for a given question/discussion
     * @param array $miscData An array containing additional details calculated prior to priority calculation. List includes
     * - totalPages Total number of pages for question/discussion
     * - pageNumber Current page number
     * - preHookData Array containing lower and uppper limits for priority scale
     * @return float $priority Priority
     */
    public function calculatePriority(array $priorityData, array $miscData) {
        $priority = 0.5;

        $miscData['totalPages'] = empty($miscData['totalPages']) ? 1 : $miscData['totalPages'];
        $bestAnswerScore = empty($priorityData['bestAnswerCount']) ? 0 : 1;
        $commentScore = empty($priorityData['commentCount']) ? 0 : 1;
        $normalScore = (0.1 * $bestAnswerScore) + (0.1 * $commentScore);
        $normalTime = ($miscData['totalPages'] - ($this->pageNumber - 1)) / $miscData['totalPages'];
        $priority = (0.8 * $normalTime) + $normalScore;
        $priority = round($miscData['preHookData']['upperLimitSocialQuestions'] * $priority, 1);

        return $priority;
    }

    /**
     * Processes questions details
     * @param int $id Questions id
     * @param string $summary String which contains summary of the question
     * @return string Url of question page
     */
    private function processLink($id, $summary) {
        return Url::defaultQuestionUrl($id) . "/~/" . \RightNow\Libraries\SEO::getAnswerSummarySlug($summary);
    }
}