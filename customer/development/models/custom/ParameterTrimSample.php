<?php 
    namespace Custom\Models;

    use RightNow\Models\Base;

    /**
     * This is an example of a custom model that trims urlParameters stored in the SessionData.
     * Trims the remembered urlParameters. 
     * Class ParameterTrimSample 
     * @package Custom\Models 
     * 
     * To include this model, open the cp/customer/development/config/hooks.php 
     * file via WebDAV and edit it so that it contains:
     * 
     * $rnHooks['pre_page_render'] = array(
     *        'class' => ‘ParameterTrimSample',
     *        'function' => 'trimUrlParameters',
     *        'filepath' => ''
     *    );
     */

class ParameterTrimSample extends Base {
    /**
     * The maximum number of url parameters stored in the session. 
     * You can choose the max length of the array, it defaults to 15. 
     * It is not recommended to go to zero since the list is used for 
     * RecentlyViewed widgets. 
     */

    /* Used for category, product and role. */
    const PARAMETER_LIMIT = 15;

    /* Limits the number of url parameters stored in the session to PARAMETER_LIMIT */
    const SMALLER_PARAMETER_LIMIT = 5;

    /**
     * This function allows you to take the data from 
     * SessionData->urlParameters, sort it by $key, appending a counter which 
     * is critical in returning the original order post trim. You then pass 
     * your sorted array to the trimUrl function. Once you have trimmed all 
     * necessary content, you then need to return the data to the original 
     * order and commit it back to the SessionData->urlParameters with 
     * the values that are present in your $trimmed array.
     *
     * We have no return here due to you committing it back to the SessionData.
     */
    public function trimUrlParameters() {
        /*
         * Example of how data is saved and looks like in urlParameters array
         *  $urlParameters = array(array("kw" => "Alphabet"), array("page" => "1"), array("search" => "1") , array("kw" => "Bears"), array("st" => "5"), array("kw" => "Chicken"), array("page" => "1"),array("st" => "5"), array("kw" => "Dalmation"), array("page" => "3") , array("search" => "1"), array("kw" => "Elephant"), array("page" => "1"), array("st" => "5"),array("kw" => "Fox"), array("page" => "1")); 
         */
        $urlParameters = $this->CI->session->getSessionData('urlParameters');

        $trimmed = array();
        $sortArray = array();
        $counter = 0;

        foreach($urlParameters as $urlParameter) {
            foreach($urlParameter as $key => $value) {
                if(!isset($sortArray[$key])) {
                    $sortArray[$key] = array();
                }
                $sortArray[$key][] = array($counter++,$value);
            }
        }

        foreach(array_keys($sortArray) as $key) {
            $sortArray[$key] = trimUrl($key, $sortArray[$key]);
        }

        foreach($sortArray as $key => $values) {
            foreach($values as $value) {
                $trimmed[$value[0]] = array($key => $value[1]);
            }
        }
        ksort($trimmed);
        $trimmed = array_values($trimmed);

        // Trim the parameter list. The -1 tells array_slice() to preserve the end of the list 
        $this->CI->session->setSessionData(array('urlParameters' => $trimmed)); 
    }

    /**
     * This function does most of the work for trimming. By passing in your 
     * $key, $values pair you are looking to trim this data down. Once data 
     * is removed you cannot retrieve it. It's important to remember that 
     * if you have widgets such as the discussion/RecentlyViewedContent that 
     * if you trim the content associated values that the content_count can still
     * only show the number of values which still exist within the SessionData.
     *
     * It should also be noted that this is a non-exhaustive list of 
     * potential urlParameter's.
     *
     * @param string $key Array key of urlParameter
     * @param array $values Array Values associated with key for specific urlParameter
     * @return array Array Values after trimming
     */
    private function trimUrl($key, $values) {
        switch ($key) {
            //Keep lots of these 
            case 'a_id':
            case 'qid':
                return array_slice($values, self::PARAMETER_LIMIT * -1);

            // Don't keep any of these
            case 'supporthub':
            case 'overview': 
                return array();

            // Keep some of these
            case 'role':
            case 'p':
            case 'c':
            case 'page':
            case 'kw':
                return array_slice($values, self::SMALLER_PARAMETER_LIMIT * -1);

            // Keep all of $key, $value pairs that do not match specific case    
            default:
                return $values;
        }
    }
}
