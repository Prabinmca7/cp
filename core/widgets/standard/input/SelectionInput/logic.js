RightNow.Widgets.SelectionInput = RightNow.Field.extend({
    overrides: {
        constructor: function(){
            if (this.data.js.readOnly) return;

            this.parent();

            var attrs = this.data.attrs;

            this.input = (this.data.js.type === "Boolean" && !attrs.display_as_checkbox)
                ? this.Y.all(this._inputSelector + "_1, " + this._inputSelector + "_0")
                : this.Y.one(this._inputSelector);

            if(!this.input) return;

            if(attrs.hint && !attrs.hide_hint && !attrs.always_show_hint) {
                this._initializeHint();
            }

            if(attrs.initial_focus && this.input) {
                if(this.data.js.type === "Boolean" && this.input[0] && this.input[0].focus)
                    this.input[0].focus();
                else if(this.input.focus)
                    this.input.focus();
            }

            if (attrs.validate_on_blur && attrs.required) {
                this.Y.Event.attach('blur', this.blurValidate, this.input instanceof this.Y.NodeList ? this.input.item(1) : this.input, this);
            }

            this.input.on('change', function() {
                RightNow.Event.fire("evt_formInputDataChanged", null);
                this.fire('change', this);
            }, this);
            this.on("constraintChange", this.constraintChange, this);

            //specific events for specific fields:
            var fieldName = this.data.js.name;
            //province changing
            if(fieldName === "Contact.Address.Country") {
                this.input.on("change", this.countryChanged, this);
                if(this.input.get('value'))
                    this.countryChanged();
            }
            else if(fieldName === "Contact.Address.StateOrProvince") {
                this.currentState = this.input.get('value');
                RightNow.Event.on("evt_provinceResponse", this.onProvinceResponse, this);
            }
            //If this is the Status field, subscribe to the change event so we can toggle the Thread field requiredness
            else if(fieldName === 'Incident.StatusWithType.Status'){
                this.on('change', this.onStatusChanged, this);
            }

            this.parentForm().on("submit", this.onValidate, this);
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
            if(this.data.js.type === 'Boolean' && !this.data.attrs.display_as_checkbox) {
                this.swapLabel(this.Y.one(this.baseSelector + '_Label'), constraint.required, this.data.attrs.label_input, this.getStatic().templates.legend);
            }
            else {
                this.swapLabel(this.Y.one(this.baseSelector + '_LabelContainer'), constraint.required, this.data.attrs.label_input, this.getStatic().templates.label);
            }
        }

        this.data.attrs.required = constraint.required;
    },

    /**
     * Event handler executed when form is being submitted.
     * @param {String} type Event name
     * @param {Array} args Event arguments
     */
    onValidate: function(type, args) {
        var eo = this.createEventObject(),
            errors = [];

        this.toggleErrorIndicator(false);

        if(!this.validate(errors)) {
            this.lastErrorLocation = args[0].data.error_location;
            this.displayError(errors, this.lastErrorLocation);
            RightNow.Event.fire("evt_formFieldValidationFailure", eo);
            return false;
        }

        RightNow.Event.fire("evt_formFieldValidationPass", eo);
        return eo;
    },

    /**
     * Adds error messages to the common error element and adds
     * error indicators to the widget field and label.
     * @param {Array} errors Error messages
     * @param {String} errorLocation ID of the common error element
     */
    displayError: function(errors, errorLocation) {
        var commonErrorDiv = this.Y.one("#" + errorLocation);
        if(commonErrorDiv) {
            for(var id = ((this.input instanceof this.Y.NodeList) ? this.input.item(0).get("id") : this.input.get("id")),
                    i = 0, message; i < errors.length; i++) {
                message = errors[i];
                message = (message.indexOf("%s") > -1) ? RightNow.Text.sprintf(message, this.data.attrs.label_input) : this.data.attrs.label_input + " " + message;
                commonErrorDiv.append("<div data-field=\"" + this._fieldName + "\"><b><a href='javascript:void(0);' onclick='document.getElementById(\"" + id +
                                "\").focus(); return false;'>" + message + "</a></b></div>");
            }
        }
        this.toggleErrorIndicator(true);
    },

    /**
     * Adds / removes the error indicators on the
     * field and label.
     * @param {Boolean} showOrHide T to add, F to remove
     */
    toggleErrorIndicator: function(showOrHide) {
        var method = ((showOrHide) ? "addClass" : "removeClass");
        this.input[method]("rn_ErrorField");
        this.Y.one(this.baseSelector + "_Label")[method]("rn_ErrorLabel");
    },

    /**
     * Validates the field's requiredness state.
     * Asserts that attrs.required is true.
     */
    blurValidate: function() {
        this.toggleErrorIndicator(!this.getValue());
    },

    /**
     * Event handler executed when country dropdown is changed.
     * Should only be called for the 'contacts.country_id' select field.
     */
    countryChanged: function() {
        var value = this.input.get("value"),
            fireResponse = function(response, eventObject) {
                RightNow.Event.fire("evt_provinceResponse", response, eventObject);
            };
        if(value) {
            var eventObject = new RightNow.Event.EventObject(this, {data: {country_id: value}});
            if (RightNow.Event.fire("evt_provinceRequest", eventObject)) {
                this._provinces = this._provinces || {};
                if (this._provinces[value]) {
                    return fireResponse(this._provinces[value], eventObject);
                }
                RightNow.Ajax.makeRequest("/ci/ajaxRequestMin/getCountryValues", eventObject.data, {
                    successHandler: function(response) {
                        this._provinces[value] = response;
                        fireResponse(response, eventObject);
                    },
                    scope: this,
                    json: true,
                    type: "GETPOST"
                });
            }
        }
        else {
            fireResponse({ProvincesLength: 0, Provinces: {}});
        }
    },

    /**
     * Event handler executed when province/state data is returned from the server.
     * Should only be subscribed to by the 'contacts.prov_id' field.
     * @param type String Event name
     * @param args Object Event arguments
     */
    onProvinceResponse: function(type, args) {
        var response = args[0],
            options = '',
            provinces = response.Provinces,
            i, length;
        this.input.set("innerHTML", "");
        if (provinces) {
            if (!this.Y.Lang.isArray(provinces)) {
                // TK - remove when PHP toJSON converts array-like objects into arrays
                var temp = [];
                this.Y.Object.each(provinces, function(val) {
                    temp.push(val);
                });
                provinces = temp;
            }

            if (!provinces[0] || (provinces[0].Name !== "--" && !this.data.hideEmptyOption)) {
                provinces.unshift({Name: "--", ID: ""});
            }
            for (i = 0, length = provinces.length; i < length; i++) {
                options += "<option value='" + provinces[i].ID + "'>" + provinces[i].Name + "</option>";
            }
            this.input.append(options);
            this.input.set('value', this.currentState);
        }
        //Any subsequent province requests should go back to the initial value '--'
        this.currentState = '';
    },

    /**
     * Event handler for when input field changes and field is Incident.StatusWithType.Status. Tries to
     * find the Incident.Theads field to toggle it's requiredness.
     */
    onStatusChanged: function(){
        var threadInput = this.parentForm().findField('Incident.Threads');
        if(threadInput){
            threadInput.setConstraints({required: this.input.get('value') === '0'});
        }
    }
});
