# AdditionalResults widget sample code README

## Overview

This widget queries a third-party search API on CX knowledgebase searches and appends the results onto the end of report results.

## Concepts illustrated

* Widget extension
    * Controller (PHP) extension
    * Logic (JS) extension
    * View extension using rn:blocks
        * PHP Widget view
        * EJS Widget view
    * Including and inheriting CSS from the parent widget
* Widget controller AJAX handler
* Using widgets' `source_id` attribute to trigger searches on an additional search endpoint
* Making a simple HTTP request to a third-party API and using the PersistentReadThroughCache class to cache responses

### Explanation

This widget extends from the standard Multiline widget.

* The controller adds additional methods to process the widget's search AJAX request and retrieve and cache search results from a third-party API.
* The PHP and EJS views use an rn:block as an insertion point to append additional results after the standard report results.
* The logic sets the AJAX endpoint for the "ddg" `source_id` source and oversees the report and additional results responses and calls the parent widget's method to render the combined results.
* The CSS for the Multiline widget is included. The AdditionalResults.css file simply adds styling for the additional results.

## Installation and Setup

1. Extract the widget into /cp/customer/development/widgets/custom/search.
2. Move the AdditionalResults.css file to /cp/customer/assets/themes/standard/widgetCss.
3. Go to https://yoursite.com/ci/admin/versions/manage, select this widget and click "Start using this version."
4. Place the widget on a search results page and add the `source_id` attribute onto widgets that are to trigger and provide filter values for the search. See the included list.php page for an example.

## Notes

This widget assumes that the default Multiline report (id 176) is being used.

Note the presence of the `source_id` attribute on the included list.php page. The widgets that are to trigger the search, provide their filter state, or participate in the search transaction for the custom search endpoint must share the same `source_id` attribute value as the widget displaying the results and setting up the source's endpoint.

In this case, KeywordText is providing its search term, SearchButton is triggering the search, and AdditionalResults is defining the endpoint for the search's AJAX request and displaying the results.

*****

The endpoint for this custom search source is specified in the `constructor` function in logic.js. For every `source_id` used on the page, an endpoint must be specified--the endpoint is the route to a Customer Portal controller and method that will process the AJAX request. In this case, the widget's controller itself processes the AJAX request so the widget's `search_endpoint` defaults to "/ci/ajax/widget", which is the generic controller route to specify when the widget's controller method is handling the request. But `search_endpoint` could easily specify a custom controller route (e.g. "/cc/myController/myMethod") to process the request.

*****

Report and Search widgets such as SearchTruncation, Paginator, and ResultInfo aren't aware of this particular search source's results--even if their widget declarations are updated to share the same `source_id` value, their controller.php and logic.js code would need to be updated to look at the specific return structure of results from this particular source (specifically the `RelatedTopics` array in the returned object) in order to properly compute pagination, number of results returned, etc. Those widgets' standard functionality can be extended in a similar manner as this example in order to properly "know" about results returned from this search source.

*****

This example uses the Duck Duck Go search engine as the source for search results. Simple phrases and keywords will return results, but Duck Duck Go's [search API](http://api.duckduckgo.com/) will not return results for complex queries or questions.

*****

### Tags and related doc topics

:   widget, extend, search, ajax, js, php, view, blocks

### Framework versions tested with

:   3.0

## Starting from Scratch

If you were to build this widget yourself, you'd start from the Widgets → Create a new Widget menu on the Customer Portal administration pages.

1. Extend the functionality of an existing widget
2. Extend from reports/Multiline
    * Name: AdditionalResults
    * Parent folder: search
3. Components
    * PHP Controller
    * AJAX handler
    * View modification
        * Extend the view
    * Include the parent widget's CSS
    * JavaScript
    * JavaScript templates
4. Attributes
    * Include all inherited attributes
    * Add three attributes:
        * label_heading:
            * type: string
            * description: Heading for additional results
            * default: Additional results
        * search_endpoint:
            * type: ajax
            * description: Endpoint for searches
            * default: /ci/ajax/widget
        * source_id:
            * type: string
            * description: ID for an additional search source
            * default: ddg