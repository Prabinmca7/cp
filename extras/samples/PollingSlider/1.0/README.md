# PollingSlider Sample Code README

## Overview

This widget demonstrates how to extend a standard widget's logic.js and CSS. It removes a couple options from the standard widget (eg. showing the poll as a dialog), and adds some simple drawer functionality to display the poll at the bottom of the browser window.

## Concepts Illustrated

* Logic (JS) Extension
* Including and inheriting CSS from parent widget

## Installation And Setup

1. Create a new survey
    * Select the type to be Polling. 
    * Add a question to the survey. Note: this widget works best with single question surveys and radio button or checkbox type questions.
    * Save (grab the Survey ID from the Info dialog)
2. Extract the widget into /cp/customer/development/widgets/custom/surveys.
3. Move the PollingSlider.css file to /cp/customer/assets/themes/standard/widgetCss.
4. Go to https://yoursite.com/ci/admin/versions/manage, select this widget and click "Start using this version."
5. Place the widget on a CP page and add the `survey_id` attribute with the id of the survey you created in step 1. The location of the widget tag in the page is not super important because it will be pinned to the bottom of the browser window regardless of where you place it in the page.

## Notes

* For testing purposes - if you want to see the poll every time the page loads, even if you've taken it already, add cookie_duration="0" to your rn:widget tag.
* Two screenshots are included in the widget's preview directory.

### Tags and related doc topics

:   widget, extend, logic, override

### Framework versions tested with

:   3.0

## Starting From Scratch ##

To create a widget like this for yourself, use the "Create a new widget" wizard in the Customer Portal Administration Area. Choose "Extend the functionality of an existing widget." For this example, the surveys/Polling standard widget was extended, but you can extend any widget (custom or standard). Pick a name and location for your widget. There are then a series of questions about what the widget will be doing. For the PollingSlider widget, only "Does this widget have its own JavaScript?" was marked Yes. At the list of attributes (pulled from the Parent widget) there were a couple that needed to be disabled/removed. Click the 'x' in the upper right of "modal", "poll_logic", and "seconds." Click finish, and the files you will need will be automatically generated.

Opening the generated info.yml file you can see all of the settings that were set from the wizard questions. The only thing added to this file manually was "transition" under the YUI section. This will be used to open and close the polling slider.

Two CSS files are automatically generated for you, the base css and presentation css. Base css is right next to your info.yml file and the presentation css is placed in your themes widgetCss directory. Presentation css is where the majority of the css for this widget lives.

A logic JS file is also created for you and includes the basic template you need for extending the widget's JS functionality. Inside of the overrides object we have a constructor function which will override the parent widget's constructor (surveys/Polling). In this widget, we will just run the parent's constructor (this.parent()) and then add a couple additional tasks to do. In this overrides object you could override any function in the parent widget if you needed. In this widget our features are mostly additive, so we will leave all of the parent functions in place. Outside the overrides object, we have added a simple click handler function to toggle whether the polling slider is open or closed using the YUI Transition module. 
