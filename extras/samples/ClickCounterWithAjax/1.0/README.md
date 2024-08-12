# ClickCounterWithAjax widget sample code README

## Overview

This widget provides a simple example of an AJAX request being processed by a widget's controller and the returned data being rendered within the
widget. The widget will initially display with the message "There is currently no data to display" in white text with a red background above a
"Click Me!" button. Clicking the button will cause an AJAX request to be made and the text will update to "I've now been clicked this many times: 1"
in white text with a black background. Click one more time and the text will update again and change to black on a white background. Continuing to
click will update the message and cycle the text through the three color changes (white text and red background, white text and black background,
black text and white background). The initial message, the button text, and the updated message are all configurable via attributes on the widget,
while the color changes are configurable within the widget's CSS.

## Concepts illustrated

* Widget creation
* Widget controller AJAX handler
* Widget JavaScript views

### Explanation

This widget is able to make AJAX requests without any additional custom controllers.

* The controller adds an additional method to process the widget's AJAX request.
* The PHP and EJS views use a CSS class to make it more obvious when the AJAX request has returned and the message has been updated.
* The logic calls the AJAX endpoint and processes the response with the EJS view.

## Installation and Setup

1. Extract the widget into /cp/customer/development/widgets/custom/sample.
2. Move the ClickCounterWithAjax.css file to /cp/customer/assets/themes/standard/widgetCss.
3. Go to https://yoursite.com/ci/admin/versions/manage, select this widget and click "Start using this version."
4. Place the widget on a page (e.g. <rn:widget path="sample/ClickCounterWithAjax"/>).

### Tags and related doc topics

:   widget, ajax, js, php, view

### Framework versions tested with

:   3.0

## Starting from Scratch

If you were to build this widget yourself, you'd start from the Widgets → Create a new Widget menu on the Customer Portal administration pages.

1. Create a new widget from scratch
2. Components
    * PHP Controller
        * AJAX-handling
    * View
        * Extend the view
    * JavaScript
        * JavaScript templates
4. Attributes
    * Rename provided default_ajax_endpoint to update_message_endpoint
    * Add three new attributes:
        * label_button
            * type: string
            * description: The label for the button
            * default: Click Me!
        * label_message
            * type: string
            * description: The label for the initial message
            * default: There is currently no data to display
        * label_updated_message
            * type: string
            * description: The label for the updated message
            * default: "I've now been clicked this many times: %d"
