<?

namespace RightNow\Models;

/**
 * Search models for all search sources must extend from this class
 * and implement the defined abstract methods.
 */
abstract class SearchSourceBase extends Base {
    /**
     * This is the model's search-executing method.
     * @param array $filters Data structure containing filters to be
     *                         applied toward the search. Structure:
     *                         [
     *                             'filter type': [
     *                                 'type': 'filter type',
     *                                 'key': 'url parameter key' or null,
     *                                 'value': value,
     *                             ],
     *                             …
     *                         ]
     * @return \RightNow\Libraries\SearchResults
     */
    abstract function search(array $filters = array());

    /**
     * Some filter types, such as sorting, products, categories, or
     * other list-type filters need to retrieve data in order to
     * display to users (e.g. Sort dropdown). Widgets may call this
     * method on models in order to retrieve values for a particular
     * search type or the Search model may make delegate calls to this
     * method on behalf of widgets.
     * @param  string $filterType Type of filter for which to retrieve
     *                            data for (e.g. sort, product, customValue)
     * @return \Iterator             An array or an object with an iterable
     *                                  interface representing the possible
     *                                  values for the filter
     */
    abstract function getFilterValuesForFilterType($filterType);
}
