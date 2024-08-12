# Dynamic Form Sample Code README

## Overview

This sample code demonstrates how to extend a standard widget and subscribe to the 'change' event to make a dynamic form by hiding, showing and setting constraints on other fields.

## Concepts Illustrated

* Logic (JS) Extension
* Implementing a 'change' event handler
* Overriding 'show' and 'hide' methods to produce a dynamic form with multiple levels of nesting
* Dynamically make fields required or not required based on selections of other fields

## Installation And Setup
 
1. Create the new custom object. Import the included custom object zip file using the custom object importer in the admin console.
2. Extract the widget into /cp/customer/development/widgets/custom/input.
3. Place the included view in /cp/customer/development/views/pages.
4. Go to https://yoursite.com/ci/admin/versions/manage, select the input/DynamicProductCategoryInput widget and click "Activate this version".
5. Update the IDs used in the `show_fields_for_ids` and `fields_required_for_ids` attributes in the included view to match IDs configured in your site.
6. Browse to https://yoursite.com/app/dynamic\_ask

## Notes

* Screenshot included in the widget's preview directory.
* This widget provides a basic solution for accessibility. In the logic JS file, a `span` that is not visible on the UI is added to the form with the correct ARIA attribute. This will let a screen reader know that a change to a field value caused a change in the fields being displayed within the form. To update this message, modify the `_notifyScreenReaders()` method in the logic JS file.

### Tags and related doc topics

:   widget, extend, logic, override, custom object

### Framework versions tested with

:   3.2

## Starting From Scratch ##

If you were to build this widget yourself, you'd start from the Widgets → Create a new Widget menu on the Customer Portal administration pages.

1. Extend the existing input/ProductCategoryInput widget
    * Name: DynamicProductCategoryInput
    * Parent folder: input
2. Components
    * PHP Controller - no ajax handling needed
    * JavaScript - no templates needed
    * CSS - Use parent's CSS
3. Attributes - add the following attributes
    * `show_fields_for_ids`
        - Type: String
        - Description: A list of IDs for the given data\_type and the fields that are associated with it. For instance: 2:Incident.Subject,Incident.Threads|5:Incident.Subject,Incident.Threads,Incident.FileAttachments
        - Required: False
    * `fields_required_for_ids`
        - Type: String
        - Description: A list of IDs for the given data\_type and the fields that are associated with it that should be required. For instance: 2:Incident.FileAttachments|5:Incident.FileAttachments,Incidents.CustomFields.CP.SerialNumber
        - Required: False
4. Finish widget builder
5. Edit the logic JS file to include a 'change' event handler. There are several methods that form input widgets include because they all extend from `RightNow.Field`, such as:
    * `hide()` - Hides a form widget
    * `show()` - Shows a form widget
    * `getValue()` - Returns the current value for an form widget
    * `getFieldName()` - Returns the field name. For instance, `Incident.Subject`, `Incident.Product`, `Incident.Category`, etc
    * `isVisible()` - Returns a boolean value indicating widgets visibility
    * `setConstraints()` - Provides a way to change constraints for a form widget
    * `parentForm()` - Used to locate the parent form. This can be used in conjunction with `findField()` which any of the above methods can be used against. For instance, `this.parentForm().findField('Incident.Subject').show()`. The `findField()` method will work with any read/write field found on https://yoursite.com/ci/admin/docs/framework/businessObjects, including custom fields and attributes.
6. Override the 'show' and 'hide' methods to support multiple levels in a dynamic form.
7. Add functionality to the widget controller to parse the widget attributes and pass them to the logic JS file via `$this->data['js']`.
8. Add the widget to a page and set the field mappings in the `show_fields_for_ids` and `fields_required_for_ids` attributes.
