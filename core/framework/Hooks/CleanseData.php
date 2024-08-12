<?php

namespace RightNow\Hooks;

/**
 * Cleanse incoming data to the system, be it GET or POST parameters. This process detects any known variables
 * that have illegal values and triggers error conditions if they values are an unexpected format.
 *
 * Cleansing can do quite a bit to reduce the attack profile, but it is not complete. It can detect improper values by whitelisting
 * (providing the set of legal values) and it can test for legal lengths to avoid buffer overflow conditions, but it cannot test for logical
 * correctness. That must still be done where the variables are used.
 */
class CleanseData {
    private $CI;
    private $varlist;
    private $useCleanse;
    private $ajaxRequest = false;
    private $controllerClassName;

    function __construct() {
        $this->CI = get_instance();
        $this->varlist = $this->getValidationList();

        if($this->CI->isAjaxRequest()) {
            $this->ajaxRequest = true;
        }

        $controllerWhitelist = array('answerPreview', 'openlogin', 'redirect', 'rendering');
        $this->controllerClassName = $this->CI->router->fetch_class();
        $controllerFunctionName = $this->CI->router->fetch_method();
        $this->useCleanse = CUSTOM_CONTROLLER_REQUEST || (
            !IS_ADMIN && //whitelist all of the admin controllers...
            !in_array($this->controllerClassName, $controllerWhitelist) && // and some other controllers...
            !($this->controllerClassName === 'page' && $controllerFunctionName === 'render' && $this->CI->page === 'error' )); // and error pages.
    }

    /**
     * Main function for cleansing, called automatically by the CI Hooks class. It will
     * throw an error if cleanse fails (either redirect or HTTP header for ajax requests)
     * @return void
     */
    public function cleanse() {
        // Skip things we don't want to cleanse.
        if(!$this->useCleanse)
            return;

        //Analyze the URL and the POST parameters. If both are clean; continue loading the page.
        $this->processURI();
        if(!in_array($this->controllerClassName, array('answerPreview', 'fattach', 'redirect', 'pta', 'opensearch')) || CUSTOM_CONTROLLER_REQUEST) {
            $this->processPage();
        }
    }

    /**
     * Handle a cleanse error on a page. Return will be different if we're
     * handling a normal request or an Ajax error
     * @param string $name Name of parameter that caused the cleanse error
     * @return void
     */
    private function cleanseError($name) {
        if($this->ajaxRequest) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 418 Parameter Error');
            echo "Cleanse Error";
            exit;
        }
        if(!IS_DEVELOPMENT) {
            \RightNow\Utils\Url::redirectToErrorPage(6);
        }
        else {
            \RightNow\Utils\Framework::setLocationHeader("/app/error/error_id/6/errorParameter/" . urlencode($name));
            exit();
        }
    }

    /**
     * Check all of the parameters POSTed to the page. There are two special cases, the filter array,
     * which contains search filter data and is handled by processFilters, and the form array, which
     * is handled by Connect PHP validation later in the process.
     * @return void
     */
    private function processPage() {
        if(count($_POST) > 0) {
            foreach($_POST as $key => $value) {
                if(!empty($value)) {
                    $this->cleanseItem($key, $value, $this->varlist);
                }
            }
        }
    }

    /**
     * Get a list of the segments picking them off in pairs and processing each pair. The pairs are validated
     * using the cleanseItem function.
     * @return void
     */
    private function processURI() {
        $urlSegments = $this->CI->uri->uri_to_assoc($this->CI->config->item('parm_segment'));

        // Starting at the last segment, peel off name-value pairs and test.
        foreach ($urlSegments as $key => $value)
        {
            $value = urldecode($value ? $value : '');
            $name = urldecode($key);

            // Handle possible XSS attacks here as well.  Don't try to escape because
            // scripting tags should never show up here.
            if(preg_match(NAME_NALLOW_PREG, $name) > 0) {
                $this->cleanseError($name);
            }
            $this->cleanseItem($key, $value, $this->varlist);
        }
    }

    /**
     * Cleanse a data item, be it a scalar, array or JSON string/array.
     * @param string $name The name of the item to cleanse
     * @param mixed $data The value of the item
     * @param array $validationList A list of parameters that we want to validate against
     * @return void
     * @throws \Exception If we can't parse the incoming data. Usually means the data isn't UTF-8.
     */
    private function cleanseItem($name, $data, array $validationList) {
        // Skip over items that don't have a definition
        if(!array_key_exists($name, $validationList)) {
            return;
        }

        // Handle complex types (array or json)
        $parameterDetails = $validationList[$name];
        if($parameterDetails['type'] === COMPLEX_TYPE) {
            $this->processArrayOrJson($name, $data, $parameterDetails['children']);
            return;
        }

        // Just a scalar value, validate it
        if(!empty($data)) {
            if(!($parameterDetails['mlen'] === 0)) {
                try {
                    if (\RightNow\Utils\Text::getMultibyteStringLength(htmlspecialchars_decode($data, ENT_QUOTES)) > $parameterDetails['mlen']) {
                        $this->cleanseError($name);
                    }
                }
                catch (\Exception $e) {
                    // invalid UTF-8. try cleansing first
                    $cleansedData = \RightNow\Api::utf8_cleanse($data);
                    try {
                        if(\RightNow\Utils\Text::getMultibyteStringLength(htmlspecialchars_decode($cleansedData, ENT_QUOTES)) > $parameterDetails['mlen']) {
                            $this->cleanseError($name);
                        }
                        $data = $cleansedData;
                    }
                    catch(\Exception $e) {
                        // this string is officially messed up. Bail with an exception
                        throw $e->getMessage();
                    }
                }
            }
            if(preg_match($parameterDetails['preg'], $data) === 0) {
                $this->cleanseError($name);
            }
        }
    }

    /**
     * Handle structured data. Iterate over all of the children and attempt to cleanse them.
     *
     * @param string $name The name of the item to cleanse
     * @param array|string $data Either a JSON encoded string or an array
     * @param array|null $validationList The list to validate against
     * @return int The number of errors found
     */
    private function processArrayOrJson($name, $data, $validationList) {
        if(empty($validationList) || (!is_array($data) && !is_string($data))) {
            return;
        }

        //It's a string and the parameter is supposed to be JSON but failed.
        if(is_string($data) && !($data = json_decode($data, true))) {
            $this->cleanseError($name);
        }
        foreach($data as $key => $value) {
            if(is_array($value)) {
                $this->processArrayOrJson($key, $value, $validationList);
            }
            else {
                $this->cleanseItem($key, $value, $validationList);
            }
        }
    }

    /**
     * Returns a list of URL Parameters and Post data variables that we need validated in a specific format.
     * @return array List of validation for each parameter
     */
    private function getValidationList() {
        return array(
            'action' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'a_id' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'account_id' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'answerID' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'asset_id' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'c' => array(
                'type' => SIMPLE_TYPE,
                'preg' => PRODCATLIST_PREG,
                'mlen' => INTLIST_LEN
            ),
            'c_id' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTLIST_PREG,
                'mlen' => INTLIST_LEN
            ),
            'cat' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'chain' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTLIST_PREG,
                'mlen' => INTLIST_LEN
            ),
            'comment' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXTLINES_PREG,
                'mlen' => LONGTEXT_LEN
            ),
            'commentID' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'contactToken' => array(
                'type' => SIMPLE_TYPE,
                'preg' => BASE64_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'count' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'country_id' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'dym' => array(
                'type' => SIMPLE_TYPE,
                'preg' => BOOLEAN_PREG,
                'mlen' => SHORTTEXT_LEN
            ),
            'email' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'emailAnswerToken' => array(
                'type' => SIMPLE_TYPE,
                'preg' => BASE64_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'error_id' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'f_tok' => array(
                'type' => SIMPLE_TYPE,
                'preg' => BASE64_PREG,
                'mlen' => TOKEN_LEN
            ),
            'formToken' => array(
                'type' => SIMPLE_TYPE,
                'preg' => BASE64_PREG,
                'mlen' => TOKEN_LEN
            ),
            'filter' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => SHORTTEXT_LEN
            ),
            'filter_type' => array(
                'type' => SIMPLE_TYPE,
                'preg' => ALPHANUM_PREG,
                'mlen' => SHORTTEXT_LEN
            ),
            'from' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => EMAIL_LEN
            ),
            'guideID' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'hm_type' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'id' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'i_id' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'keyword' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'kw' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => TABLENAME_LEN
            ),
            'lang' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'langID' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'linking' => array(
                'type' => SIMPLE_TYPE,
                'preg' => BOOLEAN_PREG,
                'mlen' => SHORTTEXT_LEN
            ),
            'linking_on' => array(
                'type' => SIMPLE_TYPE,
                'preg' => LINK_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'login' => array(
                'type' => SIMPLE_TYPE,
                'preg' => LOGIN_PREG,
                'mlen' => MEDTEXT_LEN
            ),
            'message' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXTLINES_PREG,
                'mlen' => TEXTLINES_LEN
            ),
            'name' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => SHORTTEXT_LEN
            ),
            'options_count' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'org' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'org_id' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'p' => array(
                'type' => SIMPLE_TYPE,
                'preg' => PRODCATLIST_PREG,
                'mlen' => INTLIST_LEN
            ),
            'page' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'password' => array(
                'type' => SIMPLE_TYPE,
                'preg' => PASSWORD_PREG,
                'mlen' => SHORTTEXT_LEN
            ),
            'postID' => array(
                'type' => SIMPLE_TYPE,
                'preg' => ALPHANUM_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'posts' => array(
                'type' => SIMPLE_TYPE,
                'preg' => ALPHANUM_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'postTypeID' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'prod' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'pw_reset' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            // TK - change this name to something better
            'qid' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'r_id' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'r_tok' => array(
                'type' => SIMPLE_TYPE,
                'preg' => BASE64_PREG,
                'mlen' => TOKEN_LEN
            ),
            'rate' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'refno' => array(
                'type' => SIMPLE_TYPE,
                'preg' => ALPHANUM_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'redirect' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => LONGTEXT_LEN
            ),
            'related' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'report_id' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'resourceHash' => array(
                'type' => SIMPLE_TYPE,
                'preg' => ALPHANUM_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'saResultToken' => array(
                'type' => SIMPLE_TYPE,
                'preg' => BASE64_PREG,
                'mlen' => TOKEN_LEN
            ),
            'saToken' => array(
                'type' => SIMPLE_TYPE,
                'preg' => BASE64_PREG,
                'mlen' => TOKEN_LEN
            ),
            'search' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'session' => array(
                'type' => SIMPLE_TYPE,
                'preg' => SESSION_PREG,
                'mlen' => MEDTEXT_LEN
            ),
            'session_id' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'sessionID' => array(
                'type' => SIMPLE_TYPE,
                'preg' => SESSION_PREG,
                'mlen' => MEDTEXT_LEN
            ),
            'smrt_asst' => array(
                'type' => SIMPLE_TYPE,
                'preg' => BOOLEAN_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'sort' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTLIST_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'st' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'status' => array(
                'type' => SIMPLE_TYPE,
                'preg' => ANY_OR_INT_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'subject' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'summary' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => LONGTEXT_LEN
            ),
            'threshold' => array(
                'type' => SIMPLE_TYPE,
                'preg' => INTEGER_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'to' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => EMAIL_LEN
            ),
            'token' => array(
                'type' => SIMPLE_TYPE,
                'preg' => BASE64_PREG,
                'mlen' => UNDEFINED_LEN
            ),
            'type' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => SHORTTEXT_LEN
            ),
            'url' => array(
                'type' => SIMPLE_TYPE,
                'preg' => URL_PREG,
                'mlen' => URL_LEN
            ),
            'w_id' => array(
                'type' => SIMPLE_TYPE,
                'preg' => TEXT_PREG,
                'mlen' => SHORTTEXT_LEN
            ),
            /* Complex Types and their children */
            'filters' => array(
                'type' => COMPLEX_TYPE,
                'preg' => ANYTHING_PREG,
                'mlen' => UNDEFINED_LEN,
                'children' => array(
                    'fltr_id' => array(
                        'type' => SIMPLE_TYPE,
                        'preg' => TEXT_PREG,
                        'mlen' => UNDEFINED_LEN
                    ),
                    'oper_id' => array(
                        'type' => SIMPLE_TYPE,
                        'preg' => INTEGER_PREG,
                        'mlen' => UNDEFINED_LEN
                    ),
                    'report_id' => array(
                        'type' => SIMPLE_TYPE,
                        'preg' => INTEGER_PREG,
                        'mlen' => UNDEFINED_LEN
                    ),
                )
            ),

        );
    }
}