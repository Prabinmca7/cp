# FilteredTopAnswers widget sample code README

## Overview

This widget provides an example of how to display results from a report which are runtime filtered by the product and/or category ID provided by widget attributes.

## Concepts illustrated

* Widget creation
* Report Data Interaction and Display

### Explanation

Provided a report ID, this widget builds up the appropriate product and/or category filters so that the results returned only apply to that product or category. This
is useful for displaying a 'Top Answers' section that only applies to a specific product, category, or both. By default, product or category parameters in the URL will
have no effect on the results displayed. However, the 'allow_url_filter_modification' attribute can be toggled to allow for product or category URL parameters to override the
values specified for the widget. The report ID provided is checked to ensure that a product or category filter is one of the defined filters on the report, if a value is specified.

## Installation and Setup

1. Extract the widget into /cp/customer/development/widgets/custom/reports.
2. Move the FilteredTopAnswers.css file to /cp/customer/assets/themes/standard/widgetCss.
3. Go to https://yoursite.com/ci/admin/versions/manage, select this widget and click "Start using this version."
4. Place the widget on a page (e.g. <rn:widget path="reports/FilteredTopAnswers"/>) and specify the relevant attributes.

### Tags and related doc topics

:   widget, php, view, reports

### Framework versions tested with

:   3.0

## Starting from Scratch

If you were to build this widget yourself, you'd start from the Widgets → Create a new Widget menu on the Customer Portal administration pages.

1. Create a new widget from scratch
2. Components
    * PHP Controller
    * View
3. Attributes
    * Add five attributes:
        * product_filter_id:
            * type: int
            * description: ID of the product to filter results on.
        * category_filter_id:
            * type: int
            * description: ID of the category to filter results on.
        * allow_url_filter_modification:
            * type: boolean
            * description: Denotes if this widget will allow the product and category parameters provided in the URL to modify the result list. If true, a product or category value in the URL will overwrite the '*_filter_id' attributes value. If false, the '*_filter_id' attribute will always be used and no modification will occur.
            * default: false
        * report_id:
            * type: int
            * description: ID of the report used to display data.
            * default: 176
            * required: true
        * limit:
            * type: int
            * description: Maximum number of results to display
            * default: 5

