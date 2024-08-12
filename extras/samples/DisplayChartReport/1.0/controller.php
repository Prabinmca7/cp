<?php
/**
 * File: controller.php
 * Abstract: Controller for retrieving chart report data
 * Version: 1.0
 */

namespace Custom\Widgets\Sample;

class DisplayChartReport extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);

        //This function registers the AJAX handler getChartData so that subsequent
        //requests for data are correctly routed to this controller instance.
        $this->setAjaxHandlers(array(
            'get_chart_data_ajax' => array(
                'method' => 'getChartData',
                'clickstream' => 'custom_action'
            )
        ));
    }

    /**
     * This function is executed during the widget creation process when a new page request
     * is made to the server. The results are used when rendering the view and executing
     * the JavaScript on the client side.
     * @return null|false
     */
    function getData() {
        if($this->data['attrs']['report_id'] === -1) {
            echo $this->reportError('The report_id is unset. Please check the readme document to determine how to setup the report.');
            return false;
        }

        //Retrieve our initial set of data results
        $reportToken = \RightNow\Utils\Framework::createToken($this->data['attrs']['report_id']);
        $filtersArray = array();
        $results = $this->CI->model('Report')->getDataHTML($this->data['attrs']['report_id'], $reportToken, $filtersArray, array())->result;
        if($results['error'] !== null) {
            echo $this->reportError($results['error']);
            return false;
        }

        //Make sure that the report has two columns with valid headers
        if(count($results['headers']) !== 2) {
            echo $this->reportError(sprintf("The report '%s' does not use exactly two columns and cannot be displayed with this widget.", $this->data['attrs']['report_id']));
            return false;
        }

        //Add the results to the data sent to the client so that they can be rendered
        $this->data['js'] = array(
            'reportData' => $this->addRandomizedData($results, true),
            'categoryLabel' => ($this->data['attrs']['category_axis_label']) ?: $results['headers'][0]['heading'],
            'valueLabel' => ($this->data['attrs']['value_axis_label']) ?: $results['headers'][1]['heading'],
            'r_tok' => $reportToken
        );
    }

    /**
     * This function is used as an AJAX endpoint for requests for newly updated chart data.
     * @param array $parameters Get or post parameters
     * @return void
     */
    function getChartData(array $parameters) {
        //Look up the new results
        $filtersArray = array();
        $results = $this->CI->model('Report')->getDataHTML($parameters['report_id'], $parameters['r_tok'], $filtersArray, array())->result;
        if($results['error'] !== null) {
            echo json_encode(getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG));
            return;
        }

        //Seed in some random data so the chart looks different with each refresh
        echo json_encode($this->addRandomizedData($results));
    }

    /**
     * Used in case the report doesn't have much interesting data. It will add randomized results
     * so that it is easy to see how the charts will display different data sets. If your site has a report
     * with appropriate data, this function and its calls can be removed.
     * @param array $results Results from executing report
     * @param bool $isInitialRequest Whether this request is the first
     * @return array Randomized data
     */
    function addRandomizedData(array $results, $isInitialRequest = false) {
        //If the report data is empty, just add in some random data so a chart can be displayed
        $data = (empty($results['data'])) ? array(array(6, 4), array(7, 3), array(8, 16), array(9, 10), array(10, 5)) : $results['data'];

        //Only randomize the data on requests after the initial
        if(!$isInitialRequest) {
            foreach($data as &$item) {
                $randomOffset = rand(0, 20);
                if($randomOffset % 5) {
                    $item[1] -= $randomOffset;
                }
                else {
                    $item[1] += $randomOffset;
                }
                if($item[1] < 0) {
                    $item[1] = abs($item[1]);
                }
            }
        }
        return $data;
    }
}