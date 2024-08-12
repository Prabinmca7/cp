RightNow.Widgets.DateInput = RightNow.Field.extend({
    overrides: {
        constructor: function() {
            if (this.data.js.readOnly) return;

            this.parent();

            var widgetContainer = this.Y.one(this.baseSelector);
            if(!widgetContainer) return;

            this.input = widgetContainer.all('select');
            if(!this.input) return;

            if(this.data.attrs.hint && !this.data.attrs.hide_hint && !this.data.attrs.always_show_hint) {
                this._initializeHint();
            }
            if(this.data.attrs.initial_focus && this.input.item(0) && this.input.item(0).focus) {
                this.input.item(0).focus();
            }
            if(this.data.attrs.validate_on_blur) {
                this.input.item(this.input.size() - 1).on('blur', this.blurValidate, this);
            }

            this.input.on('change', function() {
                RightNow.Event.fire("evt_formInputDataChanged", null);
                this.fire('change', this);
            }, this);
            this.on("constraintChange", this.constraintChange, this);

            this.parentForm().on('submit', this.onValidate, this);
        }
    },

    /**
     * Used by Dynamic Forms to switch between a required and a non-required label
     * @param  {Object} container    The DOM node containing the label
     * @param  {Boolean} requiredness True or false
     * @param  {String} label        The label text to be inserted
     * @param  {String} template     The template text
     */
    swapLabel: function(container, requiredness, label, template) {
        this.Y.augment(this, RightNow.RequiredLabel);
        var templateObject = {
            label: label,
            instanceID: this.instanceID,
            fieldName: this._fieldName,
            required: requiredness,
            hint: this.data.attrs.hint
        };

        container.setHTML('');
        container.append(new EJS({text: template}).render(templateObject));
    },

    /**
     * Triggered whenever a constraint is changed.
     * @param  {String} evt        The event name
     * @param  {Object} constraint A list of constraints being changed
     */
    constraintChange: function(evt, constraint) {
        constraint = constraint[0];
        if(constraint.required === this.data.attrs.required) return;

        //If the requiredness changed and the form has already validated, clear the messages and highlights
        this.toggleErrorIndicator(false);
        if(this.data.attrs.required && this.lastErrorLocation) {
            this.Y.one('#' + this.lastErrorLocation).all("[data-field='" + this._fieldName + "']").remove();
        }

        //Replace any old labels with new labels
        if(this.data.attrs.label_input) {
            this.swapLabel(this.Y.one(this.baseSelector + '_Legend'), constraint.required, this.data.attrs.label_input, this.getStatic().templates.legend);
        }

        this.data.attrs.required = constraint.required;
    },

    /**
     * Event handler for when form is being submitted
     * @param {String} type Event name
     * @param {Array} args Event arguments
     */
    onValidate: function(type, args) {
        var eo = this.createEventObject(this),
            errors = [];

        this.toggleErrorIndicator(false);

        if (!this.validateValue(errors) || !this.validate(errors)) {
            this.lastErrorLocation = args[0].data.error_location;
            this.displayError(errors, this.lastErrorLocation);
            RightNow.Event.fire("evt_formFieldValidateFailure", eo);
            return false;
        }

        RightNow.Event.fire("evt_formFieldValidatePass", eo);
        return eo;
    },

    /**
     * Displays an error message in the form's common error element and highlights the
     * offending field and label.
     * @param {Array} errors List of error messages to display
     * @param {String} errorLocation ID of the element to display the errors in
     */
    displayError: function(errors, errorLocation) {
        var commonErrorDiv = this.Y.one("#" + errorLocation),
            fieldsToHighlight;
        if(commonErrorDiv) {
            for(var id = ((this.input instanceof this.Y.NodeList) ? this.input.item(0).get("id") : this.input.get("id")),
                    i = 0, errorString = "", message; i < errors.length; i++) {
                message = errors[i];
                if (typeof message === "object" && message.ids && message.message) {
                    id = message.ids[0];
                    fieldsToHighlight = message.ids;
                    message = message.message;
                }
                message = (message.indexOf("%s") > -1) ? RightNow.Text.sprintf(message, this.data.attrs.label_input) : this.data.attrs.label_input + " " + message;
                errorString += "<div data-field=\"" + this._fieldName + "\"><b><a href='javascript:void(0);' onclick='document.getElementById(\"" + id +
                                "\").focus(); return false;'>" + message + "</a></b></div> ";
            }
            commonErrorDiv.append(errorString);
        }
        this.toggleErrorIndicator(true, fieldsToHighlight);
    },


    /**
     * Adds / removes the error indicators on the
     * field and label.
     * @param {Boolean} showOrHide T to add, F to remove
     * @param {Array} fieldsToHighlight IDs of fields to highlight
     */
    toggleErrorIndicator: function(showOrHide, fieldsToHighlight) {
        var method = ((showOrHide) ? "addClass" : "removeClass");
        if (fieldsToHighlight) {
            this.input.each(function(field) {
                if (this.Array.indexOf(fieldsToHighlight, field.get("id")) > -1) {
                    field[method]("rn_ErrorField");
                }
            }, this.Y);
        }
        else {
            this.input[method]("rn_ErrorField");
        }
        this.Y.one(this.baseSelector + "_Legend")[method]("rn_ErrorLabel");
    },

    /**
     * Toggles the error indicators based on whether the field value is legit.
     */
    blurValidate: function() {
        this.toggleErrorIndicator((this.data.attrs.required && !this.getValue()) || !this.validateValue());
    },

    /**
     * Validation routine to check if date is fully filled-out and the value is valid.
     * @param {Array} errors List of error messages to display
     * @return {Boolean} denoting if value is acceptable
     */
    validateValue: function(errors) {
        // check if all of the date fields have been set (only all or none is allowed)
        var errorNodes = [];
        this.input.each(function(input) {
            if (input.get("value") === "")
                errorNodes.push(input.get("id"));
        });
        if (errorNodes.length && errorNodes.length !== this.input.size()) {
            // only partially filled-in; highlight the offending dropdowns
            errors.push({ids: errorNodes, message: RightNow.Interface.getMessage("PCT_S_IS_NOT_COMPLETELY_FILLED_IN_MSG")});
            return false;
        }
        else if (errorNodes.length === this.input.size()) {
            // non-required field that hasn't been filled out at all
            return true;
        }

        var year = this._getDateFieldValue('Year'),
            month = this._getDateFieldValue('Month'),
            day = this._getDateFieldValue('Day');

        if (new Date(year, month - 1, day).getDate() !== day) {
            errors.push(RightNow.Interface.getMessage("PCT_S_IS_NOT_A_VALID_DATE_MSG"));
            return false;
        }
        if (this.data.attrs.min_year && year <= this.data.attrs.min_year) {
            var min = this.data.js.min,
                hour = this._getDateFieldValue('Hour');
            if (year < min.year || month < min.month || (month === min.month && day < min.day) || (hour !== null && (hour < min.hour && day <= min.day))) {
                errors.push(RightNow.Text.sprintf(RightNow.Interface.getMessage("VALUE_MIN_VALUE_PCT_S_MSG"), this.data.js.min_val));
                return false;
            }
        }
        return true;
    },

    /**
     * Return value of one of the date fields.
     * @fieldName {string} Year|Month|Day|Hour
     * @return {?number} integer if the specified field has a selection, otherwise null.
     */
    _getDateFieldValue: function(fieldName) {
        var element = this.Y.one(this._inputSelector + '_' + fieldName);
        return (element) ? parseInt(element.get('value'), 10) : null;
    }
});
