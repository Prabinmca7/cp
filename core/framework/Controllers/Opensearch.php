<?php

namespace RightNow\Controllers;

use RightNow\Utils\Url,
    RightNow\ActionCapture;

/**
 * Provides an OpenSearch feed that allows for various filters such as keyword, product, category, etc. ({@link http://en.wikipedia.org/wiki/OpenSearch})
 */
final class Opensearch extends Base
{
    private $rssgen;
    private $feedTitle, $feedImageUrl, $feedDescription;
    private $q;
    private $params;
    private $reportID;
    private $headers;
    private $homeLink;

    public function __construct()
    {
        parent::__construct();

        parent::_setClickstreamMapping(array(
            "index" => "opensearch_service",
            "feed" => "opensearch_service",
            "extdocs" => "opensearch_service"
            ));
        require_once CPCORE . "Libraries/ThirdParty/OpenSearchWriter.php";

        if (!\RightNow\Utils\Config::getConfig(EU_SYNDICATION_ENABLE))
            \RightNow\Libraries\ThirdParty\rss_error(\RightNow\Utils\Config::getMessage(END_SYNDICATION_ENABLED_MSG));

        $this->feedTitle = \RightNow\Utils\Config::getConfig(EU_SYNDICATION_TITLE);
        $this->feedDescription = \RightNow\Utils\Config::getConfig(EU_SYNDICATION_DESCRIPTION);
        $this->feedImageUrl = \RightNow\Utils\Config::getConfig(EU_SYNDICATION_IMAGE_URL);
    }

    /**
     * Handle users who don't specify a method
     * @internal
     */
    public static function index()
    {
        // without a function name, the parameter parsing
        // will break... so don't support it.
        \RightNow\Libraries\ThirdParty\rss_error(\RightNow\Utils\Config::getMessage(NO_FUNCTION_SPECIFIED_LBL));
    }

    /**
     * Provides an XML feed of data that can be given to RSS readers. Typical CP report filters can be tacked onto the end
     * of this endpoint, i.e. /ci/opensearch/feed/kw/{search_term}
     */
    public function feed()
    {
        $this->_process();
    }

    /**
     * Returns XML description of opensearch feed
     */
    public function desc()
    {
        $desc = '
        <?xml version="1.0" encoding="UTF-8"?>
        <OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
            <ShortName>' . $this->feedTitle . '</ShortName>
            <Description>' . $this->feedDescription . '</Description>
            <Language>' . LANG_DIR . '</Language>
            <SyndicationRight>open</SyndicationRight>
            <OutputEncoding>UTF-8</OutputEncoding>
            <InputEncoding>UTF-8</InputEncoding>
            <Url xmlns:parameters="http://a9.com/-/spec/opensearch/extensions/parameters/1.0/"
                 template="' . Url::getShortEufBaseUrl('sameAsCurrentPage') . '/ci/opensearch/feed/"
                 type="text/xml"
                 parameters:method="GET">
                <parameters:Parameter name="q" value="{searchTerms}"/>
                <parameters:Parameter name="count" value="{itemsPerPage}" minimum="0"/>
                <parameters:Parameter name="startIndex" value="{startIndex}" minimum="0"/>
                <parameters:Parameter name="startPage" value="{startPage}"/>
            </Url>
            <Url xmlns:parameters="http://a9.com/-/spec/opensearch/extensions/parameters/1.0/"
                 template="' . Url::getShortEufBaseUrl('sameAsCurrentPage') . '/ci/opensearch/feed/"
                 type="text/html"
                 parameters:method="GET">
                <parameters:Parameter name="q" value="{searchTerms}"/>
                <parameters:Parameter name="count" value="{itemsPerPage}" minimum="0"/>
                <parameters:Parameter name="startIndex" value="{startIndex}" minimum="0"/>
                <parameters:Parameter name="startPage" value="{startPage}"/>
            </Url>
        </OpenSearchDescription>';

        header('Content-Type: application/opensearchdescription+xml; charset="utf-8"');
        echo $desc;
        exit;
    }

    /**
     * Processes all search filters and executes the report
     */
    private function _process()
    {
        $params = $this->uri->uri_to_assoc(3);

        // grab any traditional GET parameters in the URL
        if (($pos = strpos($this->uri->uri_string(), '?')) !== false)
        {
            $getParams = substr($this->uri->uri_string(), $pos + 1);
            foreach(explode('&', $getParams) as $param)
            {
                list($key, $value) = explode('=', $param);
                $params[$key] = $value;
            }
        }

        // URL decode all of the parameters
        foreach ($params as $key => $value)
        {
            $params[$key] = urldecode($value);
        }

        // which report is going to be executed (do not use r_id param)
        if (isset($params['type']))
        {
            switch ($params['type']) {
                case "answers":
                    $this->reportID = 165; // default OpenSearch report
                    break;
                default:
                    \RightNow\Libraries\ThirdParty\rss_error(\RightNow\Utils\Config::getMessage(INVALID_OPENSEARCH_TYPE_SPECIFIED_LBL));
            }
        }
        else
        {
            $this->reportID = 165; // default OpenSearch report
        }

        // get the query
        if (isset($params['kw']))
            $this->q = $params['kw'];
        if (isset($params['q']))
            $this->q = $params['q'];

        if (isset($this->q))
        {
            $filters['keyword']->filters->data = $this->q;
            $filters['keyword']->filters->rnSearchType = 'keyword';
            $filters['keyword']->filters->reportID = $this->reportID;
            $filters['keyword']->type = 'keyword';
            $filters['search'] = true;
        }

        if (!$this->session->isNewSession())
        {
            // how many results
            if (isset($params['count']))
                $filters['per_page'] = $params['count'];

            // what is the startindex
            if (isset($params['startIndex']))
            {
                $filters['start_index'] = $params['startIndex'];
            }

            // It's only a "search" if there is no paging going on, and it's
            // not a first impression. Though this will be ignored if there
            // isn't actually a search term
            if (empty($filters['start_index']))
            {
                $filters['recordKeywordSearch'] = 1;
            }
            else
            {
                ActionCapture::record('opensearch', 'paging');
            }
        }
        if(isset($this->q) && $filters['recordKeywordSearch'] === 1){
            ActionCapture::record('opensearch', 'search', substr($this->q, 0, ActionCapture::OBJECT_MAX_LENGTH));
        }

        $reportFilter = $this->model('Report')->getFilterByName($this->reportID, 'os_search')->result;
        $filters['searchType']->filters->fltr_id = $reportFilter['fltr_id'];
        $filters['searchType']->filters->oper_id = $reportFilter['oper_id'];
        $filters['searchType']->filters->rnSearchType = 'searchType';
        $filters['searchType']->filters->reportID = $this->reportID;
        $filters['searchType']->type = 'searchType';

        if (isset($params['sort']))
        {
            $sort = explode(':', $params['sort']);

            $filters['sort_args']['filters']['sort_field0'] = array(
                'col_id'         => intval($sort[0]),
                'sort_direction' => intval($sort[1]),
                'sort_order'     => 1 );
        }

        if (isset($params['p']))
        {
            $prodFilter = $this->model('Report')->getFilterByName($this->reportID, 'os_prod')->result;

            $filters['map_prod_hierarchy']->filters->fltr_id = $prodFilter['fltr_id'];
            $filters['map_prod_hierarchy']->filters->oper_id = $prodFilter['oper_id'];
            $filters['map_prod_hierarchy']->filters->rnSearchType = 'menufilter';
            $filters['map_prod_hierarchy']->filters->reportID = $this->reportID;

            foreach(explode(';', $params['p']) as $product)
            {
                $filters['map_prod_hierarchy']->filters->data[] = explode(',', $product);
            }
        }

        if (isset($params['c']))
        {
            $catFilter = $this->model('Report')->getFilterByName($this->reportID, 'os_cat')->result;

            $filters['map_cat_hierarchy']->filters->fltr_id = $catFilter['fltr_id'];
            $filters['map_cat_hierarchy']->filters->oper_id = $catFilter['oper_id'];
            $filters['map_cat_hierarchy']->filters->rnSearchType = 'menufilter';
            $filters['map_cat_hierarchy']->filters->reportID = $this->reportID;

            foreach(explode(';', $params['c']) as $category)
            {
                $filters['map_cat_hierarchy']->filters->data[] = explode(',', $category);
            }
        }

        foreach ($params as $name => $value)
        {
            $filterNum = 0;

            // filter parameters are preceded by a 'f_'
            if (stristr(substr($name, 0, 2), 'f_'))
            {
                $tempFilter = $this->model('Report')->getFilterByName($this->reportID, substr($name, 2))->result;

                $filters["os_filter$filterNum"]->filters->fltr_id = $tempFilter['fltr_id'];
                $filters["os_filter$filterNum"]->filters->oper_id = $tempFilter['oper_id'];
                $filters["os_filter$filterNum"]->filters->rnSearchType = 'searchType';
                $filters["os_filter$filterNum"]->filters->reportID = $this->reportID;
                $filters["os_filter$filterNum"]->filters->data[]  = $value;
                $filterNum++;
            }
        }

        $format['raw_date'] = true;

        $reportToken = \RightNow\Utils\Framework::createToken($this->reportID);
        $results = $this->model('Report')->getDataHTML($this->reportID, $reportToken, $filters, $format)->result;

        if (!isset($results['headers']) || !isset($results['data']))
            \RightNow\Libraries\ThirdParty\rss_error(\RightNow\Utils\Config::getMessage(ERROR_EXECUTING_REPORT_LBL) . " " . $this->reportID);

        // create the header array for easy column extraction
        foreach ($results['headers'] as $header)
        {
            if ($header['heading'] == 'title' ||
                $header['heading'] == 'link' ||
                $header['heading'] == 'pubDate' ||
                $header['heading'] == 'score')
            {
                $this->headers[] = array('heading' => $header['heading'], 'std' => true);
            }
            else
            {
                $this->headers[] = array('heading' => $header['heading']);
            }
        }

        // assemble a link that points back to an analogue of
        // this search on the regular enduser pages
        $this->homeLink = Url::getShortEufAppUrl('sameAsCurrentPage', '/home');
        if (isset($this->q))
        {
            $this->homeLink .= '/kw/' . $this->q;
        }
        $this->homeLink .= Url::sessionParameter();

        $output = $this->_toRssXml($results);

        if($output)
        {
            header('Content-Type: text/xml; charset='.$this->rssgen->outputencoding);
            header('Content-Length: '.strval(strlen($output)));
            echo $output;
        }
        else
        {
            \RightNow\Libraries\ThirdParty\rss_error("Error: " . $this->rssgen->error);
        }
    }

    /**
     * Converts data into XML feed
     * @param array $results Results from report execution
     */
    private function _toRssXml(array $results)
    {
        $this->rssgen = new \RightNow\Libraries\ThirdParty\rss_writer_class;

        // channel properties
        $this->rssgen->specification = '2.0';
        $this->rssgen->allownoitems = true;
        $this->rssgen->about = Url::getShortEufBaseUrl('sameAsCurrentPage', '/app/about');
        $this->rssgen->rssnamespaces['opensearch'] = 'http://a9.com/-/spec/opensearch/1.1/';
        $this->rssgen->rssnamespaces['relevance'] = 'http://a9.com/-/opensearch/extensions/relevance/1.0/';
        $this->rssgen->rssnamespaces['atom'] = 'http://www.w3.org/2005/Atom';
        $this->rssgen->rssnamespaces['related'] = 'http://labs.rightnow.com/opensearchextensions/related/1.0/';

        // general feed info
        $properties = array(
            'title' => $this->feedTitle,
            'link' => $this->homeLink,
            'description' => $this->feedDescription,
        );
        $this->rssgen->addchannel($properties);

        // feed image info
        if (strlen($this->feedImageUrl))
        {
            $properties = array(
                'url' => $this->feedImageUrl,
                'title' => $this->feedTitle,
                'link' => $this->homeLink,
                'description' => $this->feedDescription,
            );
            $this->rssgen->addimage($properties);
        }

        $this->rssgen->addchanneltag('opensearch:totalResults', array(), $results["total_num"]);
        $this->rssgen->addchanneltag('opensearch:startIndex', array(), $results["start_num"]);
        $this->rssgen->addchanneltag('opensearch:itemsPerPage', array(), $results["per_page"]);
        $this->rssgen->addchanneltag('opensearch:Query', array("role" => "request", "searchTerms" => "$this->q"), '');
        $session = explode('/', Url::sessionParameter());
        $this->rssgen->addchanneltag('related:session', array(), array_pop($session));

        if ($results["spelling"])
            $this->rssgen->addchanneltag('opensearch:Query', array("role" => "correction", "searchTerms" => $results["spelling"]), '');

        if ($results["ss_data"])
            foreach ($results["ss_data"] as $key => $suggestedSearchTerm)
                $this->rssgen->addchanneltag('opensearch:Query', array("role" => "related", "searchTerms" => $suggestedSearchTerm), '');

        if ($results["topic_words"])
        {
            $catLabel = \RightNow\Utils\Config::getMessage(RECOMMENDED_DOCS_LBL);

            foreach ($results["topic_words"] as $key => $topicWord)
            {
                $related = array('link' => $topicWord['url'],
                                                'title' => $topicWord['title'],
                                                'description' => $topicWord['text'],
                                                'category' => $topicWord);
                $this->rssgen->addrelated($related);
            }
        }

        foreach ($results["data"] as $row)
        {
            $item = array();
            $related = array();

            foreach ($this->headers as $col => $info)
            {
                switch ($info['heading']) {
                    case "title":
                        // prevent opensearch writer from doubly-encoding
                        $item['title'] = htmlspecialchars_decode($row[$col]);
                        break;
                    case "link":
                        $url = $row[$col];
                        preg_match('@href=["|\'](.*)["|\'].*@', $url, $matches);
                        $item['link'] = Url::getShortEufBaseUrl('sameAsCurrentPage', $matches[1]);
                        break;
                    case "pubDate":
                        $item['pubDate'] = date(DATE_W3C, $row[$col]);
                        break;
                    case "score":
                        $item['relevance:score'] = $row[$col];
                        break;
                    default:
                        $item['related:field'][] = array('attributes' => array('name' => $info['heading']), "value" => $row[$col]);
                }
            }
            $this->rssgen->additem($item);
        }

        $this->rssgen->writerss($output);
        return $output;
    }
}
