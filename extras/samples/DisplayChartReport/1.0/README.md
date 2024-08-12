# DisplayChartReport widget sample code README

## Overview

This widget provides an example of how to use the YUI charting library, a custom widget AJAX endpoint and a custom
report to create a chart display widget for two dimensional data.

## Concepts Illustrated

* Widget Creation
* Widget Controller AJAX Handler
* Additional YUI library dependency
* Report Data Interaction and Display

### Explanation

This widget retrieves the data from a report and sends it to the client side to be displayed. When
the data arrives at the client side, it is extracted from the data array and transformed into the appropriate
format for the YUI charting library. This transformed data can be used to render any 2D YUI chart including
bar, column, line and pie charts. These charts are displayed using SVG (Scalable Vector Graphics) to create
a simple interactive chart. The widget also contains a button to update the displayed results. The button
makes an AJAX request to the server to get the modified results (in this case, simple random data) which
is then displayed to the user. This widget will always expect the first two columns of the report data
to be the first two dimensions of the rendered chart. If you plan to use the widget as more than a sample
it is worth modifying the controller to more intelligently handle different formats of report data.

Disclaimer: This widget is intended as a sample and is not fully accessible. The contents of the YUI chart
will not be read to screen readers. It is recommended that if you want to implement this widget and also make
it accessible that you provide a table with the charted data that can be properly read and explored by a screen
reader or use an accessible charting library.

## Installation and Setup
1. Extract the widget into /cp/customer/development/widgets/custom/sample.
2. Move the DisplayChartReport.css file to /cp/webfiles/assets/themes/standard/widgetCss.
3. Import the report_def.xml file into the CX Reports Explorer and note the report ID.
4. Replace the default report_id attribute of -1 in the info.yml file with the report ID from the previous step.
5. Go to https://yoursite.com/ci/admin/versions/manage, select this widget and click "Start using this version."
6. Place the widget on a page (e.g. <rn:widget path="sample/DisplayChartReport"/>).

### Tags and related doc topics

: widget, ajax, js, php, view

### Framework versions tested with

: 3.0

## Starting from Scratch

If you were to build this widget yourself, you'd start from the Widgets → Create a new Widget menu on the Customer Portal administration pages.

1. Create a new widget from scratch
2. Components
    * PHP Controller
        * AJAX-handling
    * View
        * Add a view
    * JS
        * Add a logic file
4. Attributes
    * Rename provided default_ajax_endpoint to get_chart_data_ajax
    * Add seven new attributes:
        * label_loading
            * type: String
            * description: The label that appears while new results are loading into the display
            * default: Loading
        * label_result_link:
            * type: String
            * description: The label that appears on the button for loading updated results
            * default: Get Latest Results
        * chart_type:
            * type: Option
            * options: bar, column, line
            * default: bar
            * description: The type of chart to be displayed.
        * chart_header:
            * type: String
            * description: The title to display at the top of the chart
            * default: Number of page hits by Answer ID
        * report_id:
            * type: String
            * description: ID number of the report that contains charting data
            * default: -1
        * category_axis_label:
            * type: String
            * description: The label to display on the category axis. Uses the report header by default.
        * value_axis_label:
            * type: String
            * description: The label to display on the value axis. Uses the report header by default.
