# Site Info Custom Object Sample Code README

## Overview

This sample code demonstrates how to change a form endpoint to point at a custom controller and model. Then in the model we use ConnectPHP to create a new custom object item. 

## Concepts Illustrated

* Logic (JS) Extension
* Custom Model
* Custom Controller
* Changing a form endpoint
* Accessing custom objects via Connect PHP

## Installation And Setup
 
1. Create the new custom object. Import the included custom object zip file using the custom object importer in the admin console.
2. Add a report to show custom object entries. Import the included report xml file using the analytics importer in the admin console.
3. Extract the widget into /cp/customer/development/widgets/custom/input.
4. Place the included controller in /cp/customer/development/controllers/ and the model in /cp/customer/development/models/custom/.
5. Go to https://yoursite.com/ci/admin/versions/manage, select the input/SiteInfo widget and click "Start using this version."
6. Place the SiteInfo widget between the `<form>` tags on your ask.php page. Change the form action to point at your custom controller function (eg. change ci/AjaxRequest/sendForm to cc/AjaxSiteLocation/submitAsk).

## Notes

* Screenshot included in the widget's preview directory.

### Tags and related doc topics

:   widget, extend, logic, override, custom object, model, controller, ConnectPHP

### Framework versions tested with

:   3.1

## Starting From Scratch ##

If you were to build this widget yourself, you'd start from the Widgets → Create a new Widget menu on the Customer Portal administration pages.

1. Create a brand new widget from scratch
    * Name: SiteInfo
    * Parent folder: input
3. Components - turn on
    * PHP Controller - no ajax handling needed
    * View
    * JavaScript - no templates needed
4. Attributes - add the following attributes
    * label_site_url
        - Type: String
        - Description: Label for the site URL field
        - Default: Site URL
        - Required: False
    * label_required
        - Type: String
        - Description: Label to display as the requirement message
        - Default: %s is required
        - Required: False
    * required
        - Type: Boolean
        - Description: If set to true, the field must contain a value.
        - Default: False
        - Required: False
5. Finish widget builder
6. Extend the logic JS file from the RightNow.Field widget helper. This has several helpful functions that make processing form requests easier. Also, refer to the standard/input/TextInput widget for good examples of how to handle things like validation and error processing. 
7. Create a SiteInfo model based off of the sample model, and add a function that uses ConnectPHP to create a new custom object item
8. Add a new function to a custom controller that processes all of your new incident form data in the same way as the standard sendForm function, but at the end calls your custom model to create the custom object item.
