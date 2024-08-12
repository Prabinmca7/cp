/**
 * File: logic.js
 * Abstract: Extended (from RightNow.Widgets.ProductCategoryInput) logic file for the DynamicProductCategoryInput widget
 * Version: 1.0
 */

RightNow.namespace('Custom.Widgets.input.DynamicProductCategoryInput');
Custom.Widgets.input.DynamicProductCategoryInput = RightNow.Widgets.ProductCategoryInput.extend({ 
    /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
    overrides: {
        /**
         * Overrides RightNow.Widgets.ProductCategoryInput#constructor.
         */
        constructor: function() {
            // Call into parent's constructor
            this.parent();

            // Subscribe to the 'change' event. This event will fire when the field's value is changed.
            this.on('change', this.onChange, this);

            // Flag to keep track of whether or not we need to notify screen readers that the form changed
            this._shouldNotifyScreenReaders = false;
        },

        /**
         * Overrides RightNow.Field#show.
         */
        show: function() {
            // Call into the parent's show method
            this.parent();

            // If the current field was previously displayed, which resulted in other fields being made visible,
            // prior to the current field being hidden and re-displayed, the other fields should also be re-displayed.
            var value = this._getValue();
            if (value && this.data.js.fieldMapping[value]) {
                this._showFields(this.data.js.fieldMapping[value]);
                this._setFieldConstraints(value);
            }

            // If the old field is selected again then make sure constraints are set depending on the other field selection.
            // E.g If a product is selected back again then previously selected category is also selected and all the required constraints are set again.
            if (value && this.data.js.requiredFieldMapping[value]) {
                this._setFieldConstraints(value);
            }

            // Show any fields defined in the * (all) mapping
            if (this.data.js.fieldMapping['*']) {
                this._showFields(this.data.js.fieldMapping['*']);
            }
        },

        /**
         * Overrides RightNow.Field#hide.
         */
        hide: function() {
            // Call into the parent's hide method
            this.parent();

            // Get each id => [fields] mapping
            this.Y.Array.each(this.Y.Object.keys(this.data.js.fieldMapping), function(id) {
                if (id !== '*') {
                    // Hide all of the fields
                    this._hideFields(this.data.js.fieldMapping[id], new Array());
                }
            }, this);

            this._setFieldConstraints(null);
        }
    },

    /**
     * Event handler for the 'change' event.
     */
    onChange: function() {
        var value = this._getValue(),
            requiredFields = [],
            fieldsDisplayed = [];

        // Reset the screen reader flag
        this._shouldNotifyScreenReaders = false;

        // Show the fields defined in the value -> field mapping
        if (value && this.data.js.fieldMapping[value]) {
            fieldsDisplayed = this._showFields(this.data.js.fieldMapping[value]);
        }

        // Show any fields defined in the * (all) mapping
        if (this.data.js.fieldMapping['*']) {
            fieldsDisplayed = this.Y.Array.dedupe(fieldsDisplayed.concat(this._showFields(this.data.js.fieldMapping['*'])));
        }

        // Hide the remaining fields defined in the mapping that didn't match the current value
        this.Y.Array.each(this.Y.Object.keys(this.data.js.fieldMapping), function(id) {
            if (id !== value && id !== '*') {
                this._hideFields(this.data.js.fieldMapping[id], fieldsDisplayed);
            }
        }, this);

        // Make fields required as needed. First get a list of all possible fields we are controlling the required-ness of.
        if (value && this.data.js.requiredFieldMapping) {
            this._setFieldConstraints(value);
        }

        // If any fields were shown or hidden, notify screen readers that the form changed
        if (this._shouldNotifyScreenReaders) {
            this._notifyScreenReaders();
        }
    },

    /**
     * Pulls the currently selected value from the prodcat tree
     * @return Number id of the currently selected prodcat tree node
     */
    _getValue: function() {
        var chain = this.tree.get('valueChain');
        return chain[chain.length - 1];
    },

    /**
     * Sets the field constraints specified in 'fields_required_for_ids' attribute
     * @param Number value The current value of the Product or Category
     */
    _setFieldConstraints: function(value) {
        this.Y.Array.each(this.Y.Object.keys(this.data.js.requiredFieldMapping), function(id) {
            // Keep track of the fields we see, so we don't set the constraint multiple times
            var seenFields = [],
                field;

            this.Y.Array.each(this.data.js.requiredFieldMapping[id], function(fieldName) {
                // If the current field hasn't been processed yet, set its constraint
                if (this.Y.Array.indexOf(seenFields, fieldName) === -1) {

                    // Set the constraint on the field
                    if ((field = this.parentForm().findField(fieldName))) {
                        field.setConstraints(this._getRequiredConstraintForFieldName(value, fieldName));
                    }

                    // Add the field to the list of what we have seen
                    seenFields.push(fieldName);
                }
            }, this);
        }, this);
    },

    /**
     * Returns the correct required constraint name and value for the given field name.
     * @param Number value The current selected Product or Category value
     * @param String fieldName The name of the field the constraint is being returned for
     * @return Object The constraint object
     */
    _getRequiredConstraintForFieldName: function(value, fieldName) {
        var constraint = {};
        // If it's a field that is required for the current value, set the field to be required
        if (this.data.js.requiredFieldMapping[value] &&
            this.Y.Array.indexOf(this.data.js.requiredFieldMapping[value], fieldName) !== -1) {

            // Incident.FileAttachments, Incident.Product and Incident.Category are special because 
            // to make it required the minimum number of attachments has to be set. For other fields 
            // the constraint can be specified as 'required'.
            if (fieldName === 'Incident.FileAttachments') {
                constraint = {min_required_attachments: 1};
            }
            else if (fieldName === 'Incident.Product' || fieldName === 'Incident.Category') {
                constraint = {required_lvl: 1};
            }
            else {
                constraint = {required: true};
            }
        }
        else {
            if (fieldName === 'Incident.FileAttachments') {
                constraint = {min_required_attachments: 0};
            }
            else if (fieldName === 'Incident.Product' || fieldName === 'Incident.Category') {
                constraint = {required_lvl: 0};
            }
            else {
                constraint = {required: false};
            }
        }

        return constraint;
    },

    /**
     * Method to show fields
     * @param Array fields The fields to show
     * @return Array List of fields that were displayed
     */
    _showFields: function(fields) {
        var fieldsDisplayed = [],
            field;

        this.Y.Array.each(fields, function(fieldName) {
            // Keep track of the fields we are displaying
            fieldsDisplayed.push(fieldName);

            // Show the field
            if ((field = this.parentForm().findField(fieldName))) {
                field.show();
            }

            // Make sure we will notify screen readers that we altered the form
            this._shouldNotifyScreenReaders = true;
        }, this);

        return fieldsDisplayed;
    },

    /**
     * Method to hide fields
     * @param Array fields A list of fields to hide
     * @param Array fieldsDisplayed A list of fields that were just displayed and should not be hidden
     */
    _hideFields: function(fields, fieldsDisplayed) {
        var field;
        this.Y.Array.each(fields, function(fieldName) {
            // Make sure this isn't a field we just displayed
            if (this.Y.Array.indexOf(fieldsDisplayed, fieldName) === -1) {
                // Hide the field
                if ((field = this.parentForm().findField(fieldName))) {
                    field.hide();
                }
                
                // Make sure we notify screen readers that we altered the form
                this._shouldNotifyScreenReaders = true;
            }
        }, this);
    },

    /**
     * This will create (or re-create) a span that is not visible on the UI, but will be read by
     * screen readers. This is a basic example of how one might implement accessibility into their
     * dynamic form.
     */
    _notifyScreenReaders: function() {
        var parentForm = this.Y.one(this.parentForm().baseSelector), // convert parentForm to a YUI Node
            statusElement = parentForm.one('#rn_ScreenReaderStatus');

        // Destroy the element if it already exists
        if (statusElement) {
            statusElement.remove();
        }

        // Create the element to notify screen readers
        statusElement = this.Y.Node.create('<span role="aria-alert" id="rn_ScreenReaderStatus" class="rn_ScreenReaderOnly">A change to a field value caused a change in the fields being displayed.</span>');
        parentForm.insertBefore(statusElement, parentForm.siblings().get(0));
    }
});
