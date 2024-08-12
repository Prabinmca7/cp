<?php
namespace RightNow\Widgets;

class SimpleSearch extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if($this->data['attrs']['report_page_url'] === '')
            $this->data['attrs']['report_page_url'] = '/app/' . $this->CI->page;
        if($this->CI->agent->browser() === 'Internet Explorer')
            $this->data['isIE'] = true;

        $filterData = $this->getDataFromFilter();
        $this->data['js']['url_parameters'] = $this->getUrlParameters($this->data['attrs']['add_params_to_url'], $filterData);
        $this->data['js']['placeholder'] = $this->getPlaceholderText($filterData);
    }

    /**
     * Builds an array of url parameters
     * @param string $parameterString The value from `add_params_to_url`
     * @param array $filterData An array specifying which filter_type ('p' or 'c') is being used and the corresponding prodcat object.
     * @return array An array containing the url parameters and corresponding values.
     */
    protected function getUrlParameters($parameterString, array $filterData) {
        $parameters = $this->getParametersFromHelper($parameterString);
        if ($filterData) {
            $parameters[$filterData['parameter']] = $filterData['object']->ID;
        }

        return $parameters;
    }

    /**
     * Returns the text to use as the search input's placeholder attribute.
     * @param array $filterData An array specifying which filter_type ('p' or 'c') is being used and the corresponding prodcat object.
     * @return string The text to use as the search input's placeholder.
     */
    protected function getPlaceholderText(array $filterData) {
        if ($filterData) {
            if ($placeholderLabel = $this->data['attrs']['label_filter_type_placeholder']) {
                return \RightNow\Utils\Text::stringContains($placeholderLabel, '%s')
                    ? sprintf($placeholderLabel, $filterData['object']->LookupName)
                    : $placeholderLabel;
            }
            return '';
        }

        return $this->data['attrs']['label_placeholder'];
    }

    /**
     * Returns either the product or category (determined by 'filter_type') if present in the corresponding url parameter ('p' or 'c').
     * @return array An array having 'parameter' and 'object' as keys when a product or category is specified, else an empty array.
     */
    protected function getDataFromFilter() {
        $filterData = array();
        if (($filterType = $this->data['attrs']['filter_type']) !== 'none') {
            $parameter = $filterType === 'product' ? 'p' : 'c';
            $model = $this->CI->Model('Prodcat');
            if (($filterValue = (int) \RightNow\Utils\Url::getParameter($parameter))
                && ($object = $model->get($filterValue)->result)
                && $model->isEnduserVisible($object)) {
                $filterData = array('parameter' => $parameter, 'object' => $object);
            }
        }

        return $filterData;
    }
}
