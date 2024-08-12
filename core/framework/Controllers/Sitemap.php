<?php
namespace RightNow\Controllers;
require_once CPCORE . "Libraries/ThirdParty/SitemapWriter.php";

use RightNow\Utils\Url;

/**
 * Provides information to search engines for all public answers on the site. ({@link http://en.wikipedia.org/wiki/Site_map})
 */
final class Sitemap extends Base {
    /**
     * The page of the sitemap being requested.
     */
    private $pageNumber = 0;
    private $xmlGenerator;
    /**
     * Array containing all model names for which sitemap is to be displayed
     */
    private $sitemapArray = array();
    /**
    * Number of urls on each sitemap file. Max value 50,000
    */
    private $sitemapPageLimit = 50000;
    /**
    * Number of urls on a sitemap index file. Max value 50,000. Default value 1000
    */
    private $sitemapIndexLimit = 1000;
    private static $currentSitemapIndexUrlCount = 0;
    private $htmlMode = false;

    public function __construct() {
        parent::__construct();

        if (\RightNow\Utils\Config::getConfig(KB_SITEMAP_ENABLE)) {
            // key (answers) is consumed in url, value (AnswersSitemap) is model name
            $this->sitemapArray['answers'] = 'AnswersSitemap';
            if (\RightNow\Utils\Config::getConfig(OKCS_ENABLED) && !(\RightNow\Utils\Config::getConfig(MOD_RNANS_ENABLED) && IS_PRODUCTION)) {
                $this->sitemapArray['answers'] = 'OkcsAnswersSitemap';
            }

            if (\RightNow\Utils\Config::getConfig(SSS_DISCUSSION_SITEMAP_ENABLE)) {
                $this->sitemapArray['questions'] = 'QuestionsSitemap';
            }

            $this->xmlGenerator = new \RightNow\Libraries\ThirdParty\GsgXml("");
        }
    }

    /**
     * Returns the sitemap XML for all public answers
     */
    public function index() {
        $results = array();
        try {
            foreach($this->sitemapArray as $key => $value) {
                $results['totalPages'][$key] = ceil($this->model($value)->getTotalRows() / $this->sitemapPageLimit);
            }
            $this->_outputResults($results);
        } catch(\Exception $e) {
            $this->_errorOutput();
        }
    }

    /**
     * Returns sitemap for public questions based on page number
     */
    public function questions() {
        $this->_sitemapPagination('questions');
    }

    /**
     * Returns sitemap for public answers based on page number
     */
    public function answers() {
        $this->_sitemapPagination('answers');
    }

    /**
     * Returns the sitemap in HTML
     */
    public function html() {
        $this->htmlMode = true;
        $this->pageNumber = (int) $this->uri->segment(5, 0);
        $sitemapOf = $this->uri->segment(3, null);

        if (array_key_exists($sitemapOf, $this->sitemapArray)) {
            // if request contains blank page number then set it to 1
            $this->pageNumber = !empty($this->pageNumber) ? $this->pageNumber : 1;
            $this->$sitemapOf();
        } else {
            $this->index();
        }
    }

    /**
    * This method takes care of independent sitemap based on page number
    * @param string $sitemapOf Name of the sitemap to display
    */
    private function _sitemapPagination($sitemapOf) {
        try {
            $this->pageNumber = !empty($this->pageNumber) ? $this->pageNumber : (int) $this->uri->segment(4, 1);
            $results = $this->model(
                $this->sitemapArray[$sitemapOf])->getReportData(
                    array(
                        'sitemapPageLimit'      => $this->sitemapPageLimit,
                        'pageNumber'            => $this->pageNumber,
                        'sitemapOf'             => $sitemapOf
                    )
                );
            $this->_outputResults($results, $sitemapOf);
        } catch (\Exception $e) {
            $this->_errorOutput();
        }
    }

    /**
     * Calls respective output function based on htmlMode true/false
     * @param array &$results Results from report model
     * @param string $sitemapOf Name corresponding to key in sitemapArray
     */
    private function _outputResults(array &$results, $sitemapOf = null) {
        ($this->htmlMode) ? $this->_outputHtmlResults($results, $sitemapOf) : $this->_outputXmlResults($results, $sitemapOf);
    }

    /**
     * Outputs XML results suitable for spider consumption.
     * @param array &$results Results from report model
     * @param string $sitemapOf Name of key present in sitemapArray representing corresponding model class
     * @throws \Exception If no data is obtained from reports
     */
    private function _outputXmlResults(array &$results, $sitemapOf = null) {
        if (($this->pageNumber === 0) && isset($results['totalPages'])) {
            // if the page was called with no page number and we have more
            // than one page, then create a sitemap index
            foreach ($results['totalPages'] as $section => $totalUrls) {
                $this->_sitemapIndexUrlLimitCheck($totalUrls);

                for ($i = 1; $i <= $totalUrls; $i++) {
                    $this->xmlGenerator->addSitemapUrl(Url::getShortEufBaseUrl('sameAsCurrentPage', "/ci/sitemap/{$section}/page/$i"));
                }
            }
        }
        else {
            if (!empty($results['data'])) {
                $preHookData = array();

                //call pre-hooks to get custom values of lowerLimitKBAnswers, upperLimitSocialQuestions, maxBestAnswerCount
                $this->model($this->sitemapArray[$sitemapOf])->getPreHookData($preHookData);

                //call to set priorityMin and priorityMax for xml generator based on preHookData settings
                $prePriorityResults = $this->model($this->sitemapArray[$sitemapOf])->prePriorityCalculation($preHookData);

                //set xmlGenerator based on pre priority calculation
                $this->xmlGenerator->priorityMin = $prePriorityResults['minPriority'];
                $this->xmlGenerator->priorityMax = $prePriorityResults['maxPriority'];

                foreach ($results['data'] as $row) {
                    list($path, $lastModified, $title, $id, $priorityData) = $this->model($this->sitemapArray[$sitemapOf])->processData($row);
                    $url = Url::getShortEufBaseUrl('sameAsCurrentPage', $path);
                    $priority = $this->model($this->sitemapArray[$sitemapOf])->calculatePriority(
                        $priorityData,
                        array(
                            'totalPages' => $results['total_pages'],
                            'preHookData' => $preHookData
                            )
                    );

                    /*
                     * patch to address PHP float precision affecting priority
                     * read: http://php.net/manual/en/language.types.float.php "Float point precision" warning for more details
                     * this addresses third party, xmlGenerator->addUrl() priority calculation flaw
                     */
                    if ($priority < $this->xmlGenerator->priorityMax) {
                        $priority += 0.000001;
                    }

                    $this->xmlGenerator->addUrl(
                        $url,
                        false, //path only,
                        $lastModified,
                        false, //Include time in timestamp
                        null, //change frequency
                        $priority);
                }
            } else {
                throw new \Exception("Requested data not found.");
            }
        }
        $this->xmlGenerator->output(false, false, true);
    }

    /**
     * Limit check on number of allowed sitemap urls on sitemap index file
     * @param int &$totalUrls Total number of urls. Each url points to a unique sitemap file.
     * @throws \Exception If total number of urls to perform limit check is 0
     */
    private function _sitemapIndexUrlLimitCheck(&$totalUrls) {
        if (isset($totalUrls)) {
            if ($totalUrls > $this->sitemapIndexLimit) {
                // we are overboard and have more # of urls than sitemap index page permits
                // clipping totalUrls to permissible value
                $totalUrls = $this->sitemapIndexLimit - self::$currentSitemapIndexUrlCount;
            }
            // increment current sitemap index url counter
            self::$currentSitemapIndexUrlCount += $totalUrls;
        }
        else {
            throw new \Exception("Parameter is empty.");
        }
    }

    /**
     * Outputs HTML links.
     * @param array &$results Results from report model
     * @param string $sitemapOf Name of key present in sitemapArray representing corresponding model class
     */
    private function _outputHtmlResults(array &$results, $sitemapOf) {
        $links = array();
        $url = null;
        $error = false;
        if (($this->pageNumber === 0) && isset($results['totalPages'])) {
            // if the page was called with no page number and we have more
            // than one page, then create a sitemap index
            foreach ($results['totalPages'] as $section => $totalUrls) {
                $this->_sitemapIndexUrlLimitCheck($totalUrls);

                for ($i = 1; $i <= $totalUrls; $i++) {
                    $url = Url::getShortEufBaseUrl('sameAsCurrentPage', "/ci/sitemap/html/{$section}/page/$i");
                    $links []= "<a href='$url'>$url</a>";
                }
            }
        } else {
            if (!empty($results['data'])) {
                foreach ($results['data'] as $row) {
                    list($path, $lastModified, $title, $id) = $this->model($this->sitemapArray[$sitemapOf])->processData($row);
                    $url = Url::getShortEufBaseUrl('sameAsCurrentPage', $path);
                    $links []= "<a href='$url'>{$id}</a>";
                }
            } else {
                $error = $this->_errorOutput();
            }
        }

        if ($error) {
            return;
        }
        $links = implode("<br>\n", $links);
        echo <<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title>HTML Sitemap</title>
        <meta name="ROBOTS" content="NOINDEX">
    </head>
    <body>
    {$links}
    </body>
</html>
HTML;
    }

    /**
     * Displays no sitemap info in case of any error
     * @return bool Returns true whenever this method is called.
     */
    private function _errorOutput() {
        echo <<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title>HTML Sitemap</title>
        <meta name="ROBOTS" content="NOINDEX">
    </head>
    <body>
    No sitemap available
    </body>
</html>
HTML;
        return true;
    }
}