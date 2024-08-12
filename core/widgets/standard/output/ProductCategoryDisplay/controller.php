<?php
namespace RightNow\Widgets;

class ProductCategoryDisplay extends \RightNow\Libraries\Widget\Output
{
    function __construct($attrs)
    {
        parent::__construct($attrs);
    }

    function getData()
    {
        if(parent::getData() === false)
            return false;

        if(!$type = \RightNow\Utils\Connect::getProductCategoryType($this->data['value'])){
            echo $this->reportError(\RightNow\Utils\Config::getMessage(PRODUCTCATEGORYDISPLAY_DISP_MSG));
            return false;
        }

        //If it's not an array, then just create the flat result list
        if(is_object($this->data['value']) && !\RightNow\Utils\Connect::isArray($this->data['value'])) {
            if(!$this->data['value']->ID || (!$chain = $this->CI->model('Prodcat')->getFormattedChain($type, $this->data['value']->ID)->result))
                return false;

            $depth = 0;
            $this->data['value'] = array();
            foreach($chain as $item) {
                $this->data['value'][] = $this->createResultItem($item['id'], $item['label'], $depth++);
            }
        }
        //If we have an array of products or categories we need to generate the tree
        else {
            if(count($this->data['value']) === 0 || !$result = $this->generateTree($type))
                return false;
            $this->data['value'] = $result;
        }

        // Set the filter key for the search url.  Should be 'p' or 'c'.
        if($this->data['attrs']['report_page_url'] !== '')
        {
            $this->data['filterKey'] = ($type === 'product') ? 'p' : 'c';
            $this->data['attrs']['url'] = rtrim($this->data['attrs']['url'], '/');
            $this->data['appendedParameters'] = \RightNow\Utils\Url::getParametersFromList($this->data['attrs']['add_params_to_url']) . \RightNow\Utils\Url::sessionParameter();
        }
        $this->data['wrapClass'] = ($this->data['attrs']['left_justify']) ? ' rn_LeftJustify' : '';
    }

    /**
     * Due to the fact that any answer ID can have multiple products or categories associated with it we need to generate
     * the minimum tree which covers all the leaves. This relatively complicated function is used to merge all the hierarchies
     * and create a flattened result list for the view.
     * @param string $type The type of tree being generated 'product' or 'category'
     * @return array A two-dimensional associative array containing the Product or Category hierarchy
     */
    protected function generateTree($type) {
        //Build up a tree of all end-user-visible product or categories
        $dataTree = array();
        $prodcat = $this->CI->model('Prodcat');
        foreach($this->data['value'] as $leaf) {
            if(!$chain = $prodcat->getFormattedChain($type, $leaf->ID)->result) {
                continue;
            }

            $depth = 0;
            foreach($chain as $item) {
                $parentID = (!$depth) ? 0 : $chain[$depth - 1]['id'];
                $dataTree[$parentID][$prodcat->get($item['id'])->result->DisplayOrder] = $this->createResultItem($item['id'], $item['label'], $depth++);
            }
        }

        //Sort the data by display order
        foreach($dataTree as &$nodeList) {
            ksort($nodeList);
            $nodeList = array_values($nodeList);
        }

        //Create the ordered result list
        $iter = 0;
        $listCounts = $iterStacks = $resultList = array();
        while(true) {
            if($iter === null)
                break;
            if(!isset($listCounts[$iter]))
                $listCounts[$iter] = 0;
            if($listCounts[$iter] === count($dataTree[$iter])) {
                $iter = array_pop($iterStacks);
                continue;
            }
            $item = $dataTree[$iter][$listCounts[$iter]];
            array_push($resultList, $item);
            $listCounts[$iter]++;
            if(isset($dataTree[$item['ID']])) {
                array_push($iterStacks, $iter);
                $iter = $item['ID'];
            }
        }
        return $resultList;
    }

    /**
     * Given information about a product or category, produce the data structure expected by the view.
     * @param int $id The ID
     * @param string $label The label
     * @param int $depth The depth
     * @return array
     */
    protected function createResultItem($id, $label, $depth) {
        return array('ID' => $id, 'Name' => strip_tags($label), 'Depth' => $depth);
    }
}
