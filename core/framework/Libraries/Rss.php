<?php
namespace RightNow\Libraries;
use RightNow\Utils\Url,
    RightNow\Utils\Config;

/**
 * Generates XML output to generate an RSS feed
 */
class Rss
{
    public function __construct()
    {
        $this->CI = get_instance();
        require_once CPCORE . 'Libraries/ThirdParty/OpenSearchWriter.php';

        if (!Config::getConfig(EU_SYNDICATION_ENABLE))
            ThirdParty\rss_error(Config::getMessage(END_SYNDICATION_ENABLED_MSG));

        $this->title = Config::getConfig(EU_SYNDICATION_TITLE);
        $this->description = Config::getConfig(EU_SYNDICATION_DESCRIPTION);
        $this->link = Url::getShortEufAppUrl(false, Config::getConfig(CP_HOME_URL));

        $this->reportID = 10023;
        $this->ttl = (($time = Config::getConfig(CACHED_CONTENT_EXPIRE_TIME)) && $time > 0) ? $time : 5;
        $this->descriptionLength = 200;
    }

    /**
     * Creates an RSS feed from the current page parameters and outputs the result
     *
     * @return void
     */
    public function feed()
    {
        $params = $this->CI->uri->uri_to_assoc(3);

        // URL decode all of the parameters
        foreach ($params as $key => $value)
        {
            $params[$key] = urldecode($value);
        }

        $reportFilter = $this->CI->model('Report')->getFilterByName($this->reportID, 'os_search')->result;
        $filters['searchType'] = new \stdClass;
        $filters['searchType']->filters = new \stdClass;
        $filters['searchType']->filters->fltr_id = $reportFilter['fltr_id'];
        $filters['searchType']->filters->oper_id = $reportFilter['oper_id'];
        $filters['searchType']->filters->rnSearchType = 'searchType';
        $filters['searchType']->filters->report_id = $this->reportID;
        $filters['searchType']->type = 'searchType';

        if (isset($params['sort']) && $params['sort'] == 'created')
        {
            $filters['sort_args']['filters'] = array(
                'col_id'         => 6,  // created date (instead of modified)
                'sort_direction' => 2,  // descending
                'sort_order'     => 1); // constant
        }

        if (isset($params['p']))
        {
            $productFilter = $this->CI->model('Report')->getFilterByName($this->reportID, 'os_prod')->result;

            $filters['map_prod_hierarchy']->filters->fltr_id = $productFilter['fltr_id'];
            $filters['map_prod_hierarchy']->filters->oper_id = $productFilter['oper_id'];
            $filters['map_prod_hierarchy']->filters->rnSearchType = 'menufilter';
            $filters['map_prod_hierarchy']->filters->report_id = $this->reportID;

            foreach(explode(';', $params['p']) as $hierarchy)
            {
                $filters['map_prod_hierarchy']->filters->data[] = explode(',', $hierarchy);
            }

            $this->link .= '/p/' . $params['p'];
        }

        if (isset($params['c']))
        {
            $categoryFilter = $this->CI->model('Report')->getFilterByName($this->reportID, 'os_cat')->result;

            $filters['map_cat_hierarchy']->filters->fltr_id = $categoryFilter['fltr_id'];
            $filters['map_cat_hierarchy']->filters->oper_id = $categoryFilter['oper_id'];
            $filters['map_cat_hierarchy']->filters->rnSearchType = 'menufilter';
            $filters['map_cat_hierarchy']->filters->report_id = $this->reportID;

            foreach(explode(';', $params['c']) as $hierarchy)
            {
                $filters['map_cat_hierarchy']->filters->data[] = explode(',', $hierarchy);
            }

            $this->link .= '/c/' . $params['c'];
        }

        $format['raw_date'] = true;

        $reportToken = \RightNow\Utils\Framework::createToken($this->reportID);
        $results = $this->CI->model('Report')->getDataHTML($this->reportID, $reportToken, $filters, $format)->result;

        if (!isset($results['headers']) || !isset($results['data']))
            ThirdParty\rss_error(Config::getMessage(ERROR_EXECUTING_REPORT_LBL) . ' ' . $this->reportID);

        $output = $this->_toRssXml($results);

        if($output)
        {
            header('Content-Type: text/xml; charset="'.$this->rssgen->outputencoding.'"');
            header('Content-Length: '.strval(strlen($output)));
            echo $output;
        }
        else
        {
            ThirdParty\rss_error('Error: ' . $this->rssgen->error);
        }
    }

    /**
     * Converts RSS data into proper XML
     *
     * @param array $results List of RSS data
     * @return string RSS XML
     */
    private function _toRssXml(array $results)
    {
        $this->rssgen = new ThirdParty\rss_writer_class;

        // channel properties
        $this->rssgen->specification = '2.0';
        $this->rssgen->allownoitems = true;
        $this->rssgen->about = Url::getShortEufBaseUrl(false, '/app/about');
        $this->rssgen->rssnamespaces['atom'] = 'http://www.w3.org/2005/Atom';

        // general feed info
        $parameters = array(
            'title' => $this->title,
            'link' => $this->link,
            'description' => $this->description,
            'ttl' => $this->ttl,
        );
        $this->rssgen->addchannel($parameters);

        // feed image info
        if (strlen($imageLink = Config::getConfig(EU_SYNDICATION_IMAGE_URL)))
        {
            $this->rssgen->addimage($parameters = array(
                'url' => $imageLink,
                'title' => $this->title,
                'link' => $this->link,
                'description' => $this->description,
            ));
        }

        foreach ($results['data'] as $row)
        {
            $item = array();

            $item['title'] = $row['1'];
            preg_match('@href=["|\'](.*)["|\'].*@', $row['2'], $matches);
            $item['link'] = Url::getShortEufBaseUrl(false, $matches[1]);

            $item['description'] = \RightNow\Api::utf8_trunc_nchars($row['3'], $this->descriptionLength);

            $item['pubDate'] = date(DATE_RSS, $row['4']);

            $this->rssgen->additem($item);
        }

        $this->rssgen->writerss($output);
        return $output;
    }
}
