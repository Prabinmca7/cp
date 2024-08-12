# Extended Answer Feedback widget sample code README

## Overview

This widget extends the standard AnswerFeedback widget in order to display and submit Incident custom attributes in the feedback form.

## Concepts illustrated

* Widget extension
    * Controller (PHP) extension
    * Logic (JS) extension
    * PHP view extension using rn:blocks
    * Including and inheriting CSS from the parent widget
    * Overriding inherited widget attributes in order to set different default values
* Using a Hook to add data onto an incident before it's saved

### Explanation

This widget extends from the standard AnswerFeedback widget.

* The controller fetches metadata from ConnectPHP for two Incident custom attributes (`source` and `type`) in order to retrieve their labels.
* The PHP view uses two rn:blocks to insert the Incident custom attributes into the widget's feedback form. The view also denotes that the `type` field is required in order to fill out the form.
* The logic ensures that a value is supplied for the `type` field, since it is required, and subscribes to an event to add the two custom attributes to the data that's sent to the server in the AJAX request.
* A custom model receives the incident that's about to be created for the feedback submission and sets the two custom attribute values to what was submitted to the server.

## Installation and Setup

1. This widget expects the existence of one Menu Only Custom Object and two Incident custom attributes:
    * TypeOfFeedback
        * Type: Menu Only Object
        * Package: CO
        * Menu Items: [Complaint, Compliment, Suggestion, Question/Concern]
    * type
        * Name: type
        * Label: Type of feedback
        * Package: CO
        * Object: Incident
        * Data Type: Menu
        * Menu: CO.TypeOfFeedback
    * source
        * Name: source
        * Package: CO
        * Label: How did you find this answer?
        * Field Usage: Long Text
2. Extract the widget into /cp/customer/development/widgets/custom/feedback.
3. Move the ExtendedAnswerFeedback.css file to /cp/customer/assets/themes/standard/widgetCss.
4. Go to https://yoursite.com/ci/admin/versions/manage, select this widget and click "Start using this version."
5. Replace the AnswerFeedback widget on the standard answers/detail.php page with ExtendedAnswerFeedback.
6. Place the included model in /cp/customer/development/models/custom.
7. Register the hook. In /cp/customer/development/config/hooks.php, add:

        $rnHooks['pre_feedback_submit'] = array(
            'class'    => 'CustomIncidentModel',
            'function' => 'preFeedbackSubmit',
        );

8. In order to view these new custom attribute values on newly-created Answer feedback incidents in the CX application, add the new Incident.Co.type and Incident.CO.source fields to the Incident workspace that's currently in use.

## Notes

This is but one of many solutions to this common customization scenario. A different implementation could send the AJAX request to a custom endpoint that would then send the POST parameters to a custom model that either extends the standard Incident model or re-implements its own feedback creation logic. That implementation is a more classical approach to the problem, although it involves more code (more code is more error-prone code).

The approach that this example takes--allowing the standard controller endpoint and model to process the feedback submittal normally, but using a hook model to add POST parameter values to the incident--isn't, strictly-speaking, the cleanest approach. Models, ideally, don't process POST parameters, but are given all needed parameters in method arguments. However, the advantage of this approach is that the standard controller and Incident model processing are left intact--features like auto abuse detection and enhancements and bug fixes in future framework updates will continue to happen automatically.

### Tags and related doc topics

:   widget, extend, feedback, hooks, js, php, view, blocks, custom attributes, models, events

### Framework versions tested with

:   3.0

## Starting from Scratch

If you were to build this widget yourself, you'd start from the Widgets → Create a new Widget menu on the Customer Portal administration pages.

1. Extend the functionality of an existing widget
2. Extend from feedback/AnswerFeedback
    * Name: ExtendedAnswerFeedback
    * Parent folder: feedback
3. Components
    * PHP Controller
    * View modification
        * Extend the view
    * Include the parent widget's CSS
    * JavaScript
4. Attributes
    * Include all inherited attributes
    * Modify two attributes' defaults:
        * dialog_threshold:
            * default: 3
        * options_count:
            * default: 5
