<?php

namespace RightNow\Controllers;
use RightNow\Utils\Framework;

/**
 * Endpoints for minor data actions that aren't recorded as part of clickstreams
 */
final class AjaxRequestMin extends Base
{
    /**
     * Retrieve the children of the specified product or category.
     * Required GET or POST parameters: id, filter
     * Optional GET or POST parameters: linking (specify false if product-category linking is enabled on the site but this
     * request should ignore linking). Returns JSON-encoded results. Is always an array and results appear as the first item
     * in the array; link_map is present as a key if product-category linking is enabled.
     *
     *     [
     *         0        => array of results,
     *         link_map => array of linked categories (optional)
     *     ]
     */
    public function getHierValues()
    {
        Framework::sendCachedContentExpiresHeader();
        $this->_echoJSON($this->_getHierValues(
            $this->input->request('id'),
            $this->input->request('filter'),
            $this->_linkingFromInput())->toJson());
    }

    /**
     * Retrieve the children of the specified products or categories.
     * Required GET or POST parameters: items, filter
     * Optional GET or POST parameters: linking (specify false if product-category linking is enabled on the site but this request should ignore linking).
     * Returns a JSON-encoded array where 'result' is an array having the parent product/category ID as key and the results of `getHierValues` as the value.
     */
    public function getBatchHierValues()
    {
        $filter = $this->input->request('filter');
        $linking = $this->_linkingFromInput();
        $values = array();
        foreach (explode(',', $this->input->request('items')) as $item) {
            if ($id = intval($item)) {
                $values[$id] = $this->_getHierValues($id, $filter, $linking)->result ?: array();
            }
        }
        Framework::sendCachedContentExpiresHeader();
        $this->_renderJSON(array('result' => $values));
    }

    /**
     * Retrieve the children of the specified sales product. Requires an id and level GET or POST parameters.
     */
    public function getHierValuesForProductCatalog()
    {
        $results = $this->model('ProductCatalog')->getDirectDescendants($this->input->request('id'), $this->input->request('level'), $this->input->request('isSearchRequest'));
        $results->result = $results->result ?: array();
        Framework::sendCachedContentExpiresHeader();
        $this->_echoJSON($results->toJson());
    }

    /**
     * Retrieves the full hierarchy of products or categories to display for accessible purposes.
     */
    public function getAccessibleTreeView()
    {
        $hmType = intval($this->input->request('hm_type'));
        $linkingOn = $this->input->request('linking_on');
        $results = $this->model('Prodcat')->getHierPopup($hmType, $linkingOn);
        Framework::sendCachedContentExpiresHeader();
        $this->_echoJSON($results->toJson());
    }

    /**
     * Retrieves the full hierarchy of the product catalog to display for accessible purposes.
     */
    public function getAccessibleProductCatalogTreeView()
    {
        $isSearchRequest = $this->input->request('isSearchRequest');
        $results = $this->model('ProductCatalog')->getHierPopup($isSearchRequest);
        Framework::sendCachedContentExpiresHeader();
        $this->_echoJSON($results->toJson());
    }

    /**
     * Provided the country ID, gets the list of provinces/states
     */
    public function getCountryValues()
    {
        $id = $this->input->request('country_id');
        $results = $this->model('Country')->get($id)->result;
        Framework::sendCachedContentExpiresHeader();
        if ($results) {
            // Kick the lazy loader for fields client needs
            $results->ProvincesLength = count($results->Provinces);
            if(\RightNow\Utils\Connect::isArray($results->Provinces)){
                foreach ($results->Provinces as $province) {
                    $province->ID; $province->DisplayOrder; $province->Name;
                }
            }
            $results->PostalMask; $results->PhoneMask;
        }
        $this->_renderJSON($results);
    }

    /**
     * Returns whether linking was specified via input
     * @return boolean True if linking was specified from input
     */
    private function _linkingFromInput()
    {
        $linking = $this->input->request('linking');
        return ($linking !== 'false' && $linking !== '0');
    }

    /**
     * Returns the child products or categoires for parent $id
     * @param int|string $id The product or category ID
     * @param string $filter One of 'product' or 'category'
     * @param bool $linking If true, return linked categories
     * @return array An aray of child products or categories for parent $id
     */
    private function _getHierValues($id, $filter, $linking)
    {
        $id = intval($id) ?: null;
        $results = $this->model('Prodcat')->getDirectDescendants($filter, $id);
        $results->result = array($results->result ?: array());
        if ($linking && \RightNow\Utils\Text::beginsWithCaseInsensitive($filter, 'prod') && $this->model('Prodcat')->getLinkingMode()) {
            $linkedCategories = ($id)
                ? $this->model('Prodcat')->getLinkedCategories($id)->result
                // Product selection went back to 'All' -> retrieve all top-level categories
                : array($this->model('Prodcat')->getDirectDescendants('Category')->result);

            $results->result += array('link_map' => $linkedCategories ?: array(array())); // Don't ask.
        }

        return $results;
    }
}
