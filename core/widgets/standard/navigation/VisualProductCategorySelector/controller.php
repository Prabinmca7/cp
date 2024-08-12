<?php
namespace RightNow\Widgets;

use RightNow\Utils\Url;

class VisualProductCategorySelector extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $hierarchyType = $this->data['attrs']['type'];
        $itemID = $this->data['attrs']['show_sub_items_for'] ?: null;

        if (!$items = $this->CI->model('Prodcat')->getDirectDescendants($hierarchyType, $itemID)->result) return false;

        if ($items = $this->limitItems($items, $this->data['attrs']['top_level_items'], $this->data['attrs']['maximum_items'])) {
            $this->data['js'] = array(
                'items'              => $items,
                'appendedParameters' => Url::getParametersFromList($this->data['attrs']['add_params_to_url']) . Url::sessionParameter(),
            );
            
            if($item = $this->CI->model('Prodcat')->get($itemID)->result) {
                $this->data['attrs']['label_breadcrumb'] = $item->Name;
            }
            
            if($this->data['attrs']['prefetch_sub_items_non_ajax'] && !$this->data['attrs']['prefetch_sub_items']) {
                $linking = $this->CI->model('Prodcat')->getLinkingMode();
                foreach($items as $item) {
                    if ($item['hasChildren']) {
                        $id = $item['id'];
                        $this->data['js']['subItems'][$id] = $this->getSubItems($id, $hierarchyType, $linking)->result[0] ?: array();
                    }
                }
            }
        }
        else {
            return false;
        }
    }

    /**
     * Filters the given items.
     * @param array $items Result of Prodcat model's #getDirectDescendants
     * @param string $limitTo Comma-separated list of item ids to restrict to
     * @param integer $maxLimit Maximum number of items to include
     * @return array filtered items
     */
    function limitItems($items, $limitTo, $maxLimit) {
        if ($limitTo !== '') {
            $items = $this->filterItemIDs($items, $limitTo);
        }
        if (count($items) > $maxLimit) {
            $items = $this->limitToMaxSize($items, $maxLimit);
        }

        return $items;
    }

    /**
     * Filters the items to the comma-separated item ids.
     * @param  array $items Items to filter
     * @param  array $limitTo  Comma-separated list of item ids to restrict to
     * @return array filtered items
     */
    protected function filterItemIDs($items, $limitTo) {
        // Get the comma-separated integer ids specified.
        $limitTo = array_map('intval', explode(',', $limitTo));
        // Filter out any erroneous entries (intval converted them to zero)
        // and make the values the keys for faster lookups.
        $limitTo = array_flip(array_filter($limitTo));

        // Return a proper zero-indexed array with no holes (array_filter leaves holes).
        return array_values(array_filter($items, function($item) use ($limitTo) {
            return !is_null($limitTo[$item['id']]);
        }));
    }

    /**
     * Slices an array to the given size.
     * @param  array   $items Items to resize
     * @param  integer $maxLimit New size
     * @return array   size-limited array
     */
    protected function limitToMaxSize($items, $maxLimit) {
        return array_slice($items, 0, $maxLimit);
    }

    /**
     * Returns the child products or categories for parent $id
     * @param int|string $id The product or category ID
     * @param string $filter One of 'product' or 'category'
     * @param bool $linking If true, return linked categories
     * @return array An array of child products or categories for parent $id
     */
    function getSubItems($id, $filter, $linking)
    {
        $id = intval($id) ?: null;
        $results = $this->CI->model('Prodcat')->getDirectDescendants($filter, $id);
        $results->result = array($results->result ?: array());
        if ($linking && \RightNow\Utils\Text::beginsWithCaseInsensitive($filter, 'prod')) {
            $linkedCategories = ($id)
                ? $this->CI->model('Prodcat')->getLinkedCategories($id)->result
                // Product selection went back to 'All' -> retrieve all top-level categories
                : array($this->CI->model('Prodcat')->getDirectDescendants('Category')->result);

            $results->result += array('link_map' => $linkedCategories ?: array(array()));
        }

        return $results;
    }
}
