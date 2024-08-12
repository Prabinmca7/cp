<?php
namespace Custom\Models;

use RightNow\Libraries\SearchMappers\SocialSearchMapper;

require_once CPCORE . 'Models/SocialSearch.php';

/**
 * This is an example of a custom search model that extends a standard search model, specifically the SocialSearch model. To
 * automatically have this model be used in place of calls to the standard model, open the
 * cp/customer/development/config/search_sources.yml file via WebDAV and add an entry for 'SocialSearch' key so it contains:
 *
 *    SocialSearch:
 *        model: MySocialSearch
 *
 * You can also reference the standard mapping file (framework/Config/search_sources.yml) to get an idea about the file format.
 */
class MySocialSearch extends \RightNow\Models\SocialSearch {
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Since this model overrides and extends the standard SocialSearch model, any calls in the form
     *
     *     $this->CI->model('SocialSearch')->search(...) From a custom widget or model OR,
     *     $this->model('SocialSearch')->search(...) From a custom controller          OR,
     *     get_instance()->model('SocialSearch')->search(...) From any static method
     *
     * will call into this function since it is named the same. Be sure to call into the standard model by using
     * parent::search(...) and modify things either immediately before calling the parent or after getting
     * the result. Otherwise, you won't receive any critical bug fixes that may come in a later version.
     *
     * Searches KFAPI.
     * @param array $filters Search filters, each an array having at minimum a 'value' key specifying a
     *                       single value ('value' => 1), multiple values ('value' => '1,2,3'), or null.
     *                       - author: int
     *                       - category: int
     *                       - createdTime: datetime
     *                       - numberOfBestAnswers: int
     *                       - product: int
     *                       - status: int
     *                       - updatedTime: datetime
     *                       - query: int
     *                       - sort: int
     *                       - direction: int
     *                       - page: int
     *                       - offset: int
     *                       - limit: int
     * @return SearchResults instance
     */
    function search(array $filters = array()) {
        return parent::search($filters);
    }
}
