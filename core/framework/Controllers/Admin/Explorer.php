<?php

namespace RightNow\Controllers\Admin;

use RightNow\Utils\Config,
    RightNow\Utils\Text,
    RightNow\Internal\Libraries\ConnectExplorer;

require_once CPCORE . 'Internal/Libraries/ConnectExplorer.php';

class Explorer extends Base {
    function __construct() {
        parent::__construct(true, '_verifyLoginWithCPEditPermission');
    }

    /**
     * Renders the Connect Object Explorer ROQL query window
     */
    function index() {
        $documentationVersion = sprintf("//documentation.custhelp.com/euf/assets/devdocs/%s/Connect_PHP/Default.htm",
            \RightNow\Utils\Url::getProductVersionForLinks());
        $options = array(
            'css' => array(
                'explorer/explorer',
                'thirdParty/codemirror/lib/codemirror.css',
            ),
            'js' => array(
                'explorer/explorer',
                'thirdParty/codemirror/lib/codemirror-min.js',
            ));
        return $this->_render('explorer/index', array('documentationVersion' => $documentationVersion), Config::getMessage(CONNECT_OBJECT_EXPLORER_LBL), $options);
    }

    /**
     * Endpoint for submitting ROQL queries
     */
    function query() {
        header('content-type: application/javascript');
        $callback = urlencode($this->input->get('callback'));
        $query = str_replace(array("&#039;", "&quot;"), array("'", '"'), html_entity_decode($this->input->get('query')));
        $query = strip_tags($query);
        $returnJson = function($data) use ($callback) {
            return $callback . "(" . json_encode($data) . ")";
        };
        echo $returnJson(ConnectExplorer::query(
            html_entity_decode($query),
            urldecode($this->input->get('limit')),
            urldecode($this->input->get('page'))
        ));
    }

    /**
     * Endpoint for displaying meta details for the specified $fieldName.
     * @param string $fieldName The name of the Connect object/field being inspected, in dot notation.
     * @param int|null $objectID Specific ID of object to retrieve
     * @param boolean $displayInPage If True, display meta details in a Panel from the explorer page.
     */
    function inspect($fieldName = 'showObjects', $objectID = null, $displayInPage = true) {
        $objectID = (int) $objectID;
        $options = array(
            'css' => array(
                'explorer/explorer',
                'thirdParty/codemirror/lib/codemirror.css',
            ),
            'js' => array(
                'explorer/explorer',
                'thirdParty/codemirror/lib/codemirror-min.js',
            ));
        if ($displayInPage) {
            return $this->_render('explorer/index', array('fieldName' => $fieldName, 'objectID' => $objectID), Config::getMessage(CONNECT_OBJECT_EXPLORER_LBL), $options);
        }

        $connectNamespace = CONNECT_NAMESPACE_PREFIX . '\\';
        $fields = array();
        if (!$fieldName || $fieldName === 'showObjects') {
            $rows = array();
            foreach (ConnectExplorer::getPrimaryClasses(true) as $classname) {
                $rows[] = array('field' => $classname, 'type' => "{$connectNamespace}{$classname}", 'link' => $classname);
            }
            $results = array('rows' => $rows);
        }
        else {
            try {
                $results = ConnectExplorer::getMeta($fieldName, $objectID);
                if ($results) {
                    $fields = array_merge(array($results['objectName']), $results['fields']);
                }
            }
            catch (\Exception $e) {
                $error = Config::getMessage(ERR_INV_CONN_FLD_ID_SPEC_COLON_LBL) . ": $fieldName" . ($objectID ? "/$objectID" : '');
                $results = array();
            }
        }

        $this->_renderJSONAndExit(array('html' => $this->load->view('Admin/explorer/inspect', array(
            'error' => isset($error) ? $error : null,
            'rows' => isset($results['rows']) ? $results['rows'] : null,
            'fields' => $fields,
            'objectID' => $objectID,
            'adminUrl' => '/ci/admin/explorer/inspect',
            'connectNamespace' => $connectNamespace), true)));
    }

    /**
     * Endpoint for exporting ROQL query result to a CSV file.
     */
    function export() {
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=ROQLQueryResults.csv");
        header("Cache-Control: no-cache, no-store");
        header("Pragma: no-cache");
        header("Expires: 0");

        $query = urldecode(\RightNow\Utils\Url::getParameter('query'));
        $originalQuery = html_entity_decode($query);
        $query = str_replace(array("&#039;", "&quot;"), array("'", '"'), html_entity_decode($query));
        $query = strip_tags($query);

        $results = ConnectExplorer::query($query);
        if (isset($results['error']) && $results['error']) {
            echo nl2br("Query: $originalQuery\n");
            echo "Error: " . $results['error'];
            return;
        }

        $columns = array();
        foreach ($results['columns'] as $column) {
            $columns[] = $column['key'];
        }

        $fh = fopen('php://output', 'w');
        fputcsv($fh, array("Query: {$query}"));
        fputcsv($fh, array("{$results['total']} results returned in {$results['duration']} seconds"));
        fputcsv($fh, array());
        fputcsv($fh, $columns);

        $keys = array_flip($columns);
        foreach ($results['results'] as $result) {
            $contents = $this->sanitizeContent(array_values(array_intersect_key($result, $keys)));
            fputcsv($fh, $contents);
        }
    }

    /**
     * Sanitizes content to avaoid csv formula injection
     * @param array $content Content array to sanitize
     * @return array $sanitizedContent Sanitized content array
     */
    function sanitizeContent($content) {
        foreach($content as $currentVal) {
            $sanitizedContent[] = isset($currentVal) && $currentVal && preg_match('/^[=|+|\-|@]/', $currentVal) ? '\'' . $currentVal : $currentVal;
        }
        return $sanitizedContent;
    }
}
