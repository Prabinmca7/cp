/**
 * The RightNow.Field module provides common functionality for all field widgets (FormInput) and
 * an EventProvider object that can be used for custom input widgets. By extending from this module
 * the following events are provided to the widget through the parentForm() functions.
 *
 * @example
 * In addition to the above events exposed through the parent form, field widgets themselves expose the following events:
 *      'change' - Triggered when a field element's value is changed. The provided value is the instance of the widget being
 *          changed. This instance can be used to dynamically alter requiredness and visibility of other fields in the
 *          same form.
 *
 *      'constraintChange' - Triggered after a call to setConstraints. This event is used to update a widget when a constraint is altered.
 *          The event is fired in two different forms. The first, `constraintChange` is fired with an object containing the
 *          following structure: { <updated_constraint_name>: <updated_constraint_value> }, the second
 *          takes the form `constraintChange:<constraint_name>` and sends an object with the format: { constraint: <updated_constraint_value>}.
 *
 * @example
 * Use this.parentForm().on('eventName', handlerFunction) in the extending widget to subscribe to these events:
 *      'submit' - Triggered when a form submission is performed by the FormSubmit widget. This event should be used
 *          with a validation callback to ensure the widget extending from RightNow.Field has passed validation.
 *          The validation function should return false when the validation fails and an EventObject containing the
 *          data to be sent to the server when it succeeds. If all of the subscribed widgets pass validation,
 *          a validation:pass event is fired, otherwise a validation:fail event is fired.
 *
 *      'validation:fail' - Triggered when a form has failed validation. The submission is canceled and each field must be
 *          revalidated before submitting the form.
 *
 *      'validation:pass' - Triggered when a form is successfully validated. The collected data is submitted to the server
 *          and the response event is fired once received.
 *
 *      'send' - The send event is triggered immediately before the form is submitted to the server and allow subscribers
 *          to halt the form submission by returning false.
 *
 *      'response' - Triggered once the AJAX request has successfully completed. The page is typically redirected to the
 *          FormSubmit widget's on_success_url.
 *
 *      'collect' -  Fired during form submission to gather the fields which will be submitted with the form. The return value
 *          of this event determines whether or not a value will be submitted with the form. When false is returned, the
 *          field is excluded from the submission. When the field name is returned it is included.
 *
 *     'formUpdated' - Fired when a field is added or removed from the form or when a constraint value on a field is altered.
 *
 * @requires RightNow.Form
 * @constructor
 */
RightNow.Field = RightNow.Field || RightNow.EventProvider.extend(/**@lends RightNow.Field*/{
    /**
     * @private
     * @type {Object}
     */
    overrides: {
        /**
         * @constructor
         * @private
         */
        constructor: function() {
            this.parent();
            if (this.data.js.name) {
                this._fieldName = this.data.js.name;
                // Escape field selectors for YUI selector engine
                this._inputSelector = this.baseSelector + "_" + this._fieldName.replace(/\./g, "\\.");

                //Add this field to the form
                this.parentForm().addField(this._fieldName, this);

                //Create a change handler so forms can be modified dynamically
                this._addEventHandler("change");
                this._addEventHandler("constraintChange", {
                    post: function() {
                        this.parentForm().fire("formUpdated");
                    }
                });

                this.parentForm().on('collect', this.onCollect, this);
            }

            this.excludeFromValidation = this.data.attrs.hide_on_load || false;
        }
    },

    /**
     * Change a field's constraints dynamically. Changing a constraint fires an
     * event to update the form state.
     * @param {Object} constraints A list of constraints with the format {constraint-name: constraint-value}
     */
    setConstraints: function(constraints) {
        var constraintTypes = {
            required: function(value) { return (value === 'false' ? false : !!value); },
            required_lvl: function(value) { return parseInt(value, 10); },
            min_required_attachments: function(value) { return parseInt(value, 10); }
        };

        //Convert well known values
        this.Y.Object.each(constraintTypes, function(parser, type) {
            if(constraints[type]) {
                constraints[type] = parser(constraints[type]);
            }
        });

        //Fire one event with the entire object
        this.fire("constraintChange", constraints);

        //And individual events for each constraint
        this.Y.Object.each(constraints, function(value, name) {
            this.fire("constraintChange:" + name, {constraint: value});
        }, this);

        //Notify the form about the changes
        this.parentForm().fire("formUpdated");
    },

    /**
     * Triggered in preparation for a form submission.
     * @return {boolean|String} The field name when visible otherwise false
     */
    onCollect: function() {
        if(!this.isVisible()) {
            return false;
        }

        return this._fieldName;
    },

    /**
     * Whether or not the field is visible in the form
     * @return {boolean} visibility
     */
    isVisible: function() {
        return !this.excludeFromValidation;
    },

    /**
     * Hide this field from the form
     */
    hide: function() {
        RightNow.UI.hide(this.baseSelector);
        this.excludeFromValidation = true;

        //Remove messages from the error container
        var errorLocation = this.Y.one('#' + this.lastErrorLocation);
        if(this.lastErrorLocation && errorLocation) {
            errorLocation.all("[data-field='" + this._fieldName + "']").remove();
        }

        //Notify the form about the changes
        this.parentForm().fire('formUpdated');
    },

    /**
     * Show this field in the form
     */
    show: function() {
        RightNow.UI.show(this.baseSelector);
        this.excludeFromValidation = false;

        //Notify the form about the changes
        this.parentForm().fire('formUpdated');
    },

    /**
     * Get a field's dot separated field name
     * @return {String} The field name
     */
    getFieldName: function() {
        return this._fieldName;
    },

    /**
     * Retrieves the parent form instance.
     * @param formIDToUse {String} [optional] ID of form to use in case caller is not within a form element
     * @return {Object} RightNow.Form instance that provides the events given in the module description.
     */
    parentForm: function(formIDToUse) {
        var lookupNode = formIDToUse || this.baseDomID;
        return RightNow.Form.find(lookupNode, this.instanceID);
    },

    /**
     * Returns the ID of the parent form. Throws an exception if no parent form is found.
     * @return {String} ID of the parent form for this widget
     */
    getParentFormID: function(){
        return RightNow.Form.find(this.baseDomID, this.instanceID, true);
    },

    /**
     * Creates an EventObject populated with data as it is expected on the server.
     * @return {Object} EventObject containing data representing the widget's state
     */
    createEventObject: function() {
        return new RightNow.Event.EventObject(this, {data: {
            name: this.data.js.name,
            value: this.getValue(),
            required: this._isRequired(),
            form: this._parentForm
        }});
    },

    /**
     * Determines if field is of a certain data type.
     * @param {string} type The data type being checked for:
     *  'text', 'selection', 'date', 'email', 'url'
     * @return {boolean} Whether the field's data type corresponds
     */
    is: function(type) {
        var dataType = this.data.js.type;

        if (type === "text") {
            return dataType === 'Integer' || dataType === 'String' || dataType === 'Thread';
        }
        if (type === "selection") {
            return dataType === 'Boolean' || dataType === 'NamedIDLabel' || dataType === 'Country' || dataType === 'StateOrProvince';
        }
        if (type === "date") {
            return dataType === 'Date' || dataType === 'DateTime';
        }
        if (type === "email") {
            return (this.isCommonEmailType() || this.data.js.email === true);
        }
        if (type === "url") {
            return ((this.data.js.url) ? true : false);
        }
        if (type === "product") {
            return dataType === "ServiceProduct";
        }
        if (type === "category") {
            return dataType === "ServiceCategory";
        }
        if (type === 'attachment') {
            return dataType === 'FileAttachmentIncident';
        }
        if (type === 'password') {
            return this._fieldName === "Contact.NewPassword" || this._fieldName === "Contact.NewPassword_Validate";
        }
        return false;
    },

    /**
     * Checks if the current field is one of the primary or alternate contact email types
     * @return {boolean} Whether the field is one of a list of special email type fields
     */
    isCommonEmailType: function(){
        return  this._fieldName === 'Contact.Emails.PRIMARY.Address' ||
                this._fieldName === 'Contact.Emails.ALT1.Address' ||
                this._fieldName === 'Contact.Emails.ALT2.Address' ||
                this._fieldName === 'Incident.CustomFields.c.alternateemail';
    },

    /**
     * Returns true if input type is 'radio'
     * @return {boolean} Whether the field is of type 'radio'
     */
    _isRadio: function() {
        var inputType = this.input.get('type');
        return this.Y.Lang.isArray(inputType) && inputType[0] === 'radio';
    },

    /**
     * Adds an error message to an internal member array of error messages.
     * @private
     * @param {String} message The error message
     */
    _reportError: function(message) {
        this._errors = this._errors || [];
        this._errors.push(message);
    },

    /**
     * Validates the field's requiredness, bounds, and input format.
     * @param {Array} errors Array of error messages to populate
     * @param {String} value The field value. Uses the value of 'getValue' if not specified.
     * @return {boolean} T if the field passed validation, F otherwise
     */
    validate: function(errors, value) {
        this._errors = errors || [];
        this._value = typeof value !== 'undefined' ? value : this.getValue();

        // don't throw a less-than-min-length error if a required field is blank
        if (!this._checkRequired()) {
            this._checkValue();
        }

        if (this.is("email")) {
            if (!this._errors.length) {
                this._checkEmail();
            }
        }
        else if (this.is("url")) {
            if (!this._errors.length) {
                this._checkUrl();
            }
        }
        else{
            this._checkData();
        }

        return !this._errors.length;
    },

    /**
    * Checks that the value entered doesn't exceed its expected bounds
    * @private
    */
    _checkValue: function() {
        var value, max, min,
            length, minLength, maxLength;

        if(this.data.js.type === 'Integer') {
            //make sure it's a valid int, #getValue should have already verified it's an integer and not a float
            if (this._value !== "" && (typeof this._value !== 'number')) {
                return this._reportError(RightNow.Interface.getMessage('VALUE_MUST_BE_AN_INTEGER_MSG'));
            }
            value = parseInt(this._value, 10);
            max = parseInt(this.data.js.constraints.maxValue, 10);
            min = parseInt(this.data.js.constraints.minValue, 10);
            //make sure its value is in bounds
            if (this.Y.Lang.isNumber(max) && value > max) {
                return this._reportError(RightNow.Interface.getMessage('VALUE_IS_TOO_LARGE_MAX_VALUE_MSG') + max + ")");
            }
            if (this.Y.Lang.isNumber(min) && value < min) {
                return this._reportError(RightNow.Interface.getMessage('VALUE_IS_TOO_SMALL_MIN_VALUE_MSG') + min + ")");
            }
        }
        else if(this.data.js.name === "Contact.NewPassword" && this.data.js.passwordLength) {
            length = RightNow.Text.Encoding.utf8Length(this._value);
            minLength = this.data.js.passwordLength;
            if(length < minLength) {
                return this._reportError(RightNow.Text.sprintf(
                    (minLength - length === 1)
                        ? RightNow.Interface.getMessage("CONTAIN_1_CHARACTER_MSG")
                        : RightNow.Interface.getMessage("PCT_D_CHARACTERS_MSG"),
                minLength));
            }
        }

        if(this._value !== null && (this.data.js.constraints.maxLength || this.data.js.constraints.minLength)) {
            //make sure it's within the min/max field size
            length = this._value.length;
            maxLength = this.data.js.constraints.maxLength;
            minLength = this.data.js.constraints.minLength;
            if(maxLength && maxLength < length) {
                return this._reportError(RightNow.Text.sprintf((length - maxLength === 1)
                    ? RightNow.Interface.getMessage("EXCEEDS_SZ_LIMIT_PCT_D_CHARS_1_LBL")
                    : RightNow.Interface.getMessage("S_SZ_CHARS_CHARS_CHARS_XP_SBM_SZ_XCDD_MSG"),
                maxLength, (length - maxLength)));
            }
            else if(minLength && minLength > length){
                return this._reportError(RightNow.Text.sprintf(
                    (minLength - length === 1)
                        ? RightNow.Interface.getMessage("CONTAIN_1_CHARACTER_MSG")
                        : RightNow.Interface.getMessage("PCT_D_CHARACTERS_MSG"),
                minLength));
            }
        }
    },

    /**
    * Validation routine to check for valid strings in certain fields (i.e. channel, phone fields, and fields with constraints).
    * @private
    */
    _checkData: function() {
        if (this._value === null || this._value === "") return;

        var regexValidation = (this.data.js.constraints) ? this.data.js.constraints.regex : null;

        if(RightNow.Text.beginsWith(this._fieldName, 'Contact.Phones.') || this._fieldName === "Contact.Address.PostalCode") {
            if(!(/^[-A-Za-z0-9,# +.()]+$/.test(this._value))) {
                return this._reportError((this._fieldName === "Contact.Address.PostalCode")
                    ? RightNow.Interface.getMessage("PCT_S_IS_AN_INVALID_POSTAL_CODE_MSG")
                    : RightNow.Interface.getMessage("PCT_S_IS_AN_INVALID_PHONE_NUMBER_MSG"));
            }
        }
        else if (regexValidation && !(new RegExp(regexValidation).test(this._value))) {
            return this._reportError((this._fieldName === 'Contact.Login')
                ? RightNow.Interface.getMessage("PCT_S_CONT_SPACES_DOUBLE_QUOTES_LBL")
                : RightNow.Interface.getMessage("PCT_S_DIDNT_MATCH_EXPECTED_INPUT_LBL"));
        }
        //Check for space characters on channel fields
        else if(this.data.js.channelID && /\s/.test(this._value)) {
            return this._reportError(RightNow.Interface.getMessage('CONTAIN_SPACES_PLEASE_TRY_MSG'));
        }
    },

    /**
    * Validation routine to check for valid email addresses.
    * @private
    */
    _checkEmail: function() {
        if (!this._value) return;

        if (this._fieldName === 'Incident.CustomFields.c.alternateemail') {
            for (var i = 0, emailArray = this._value.split(/[,;]+/), value; i < emailArray.length; i++) {
                value = this.Y.Lang.trim(emailArray[i]);
                if(value && !RightNow.Text.isValidEmailAddress(value)) {
                   this._reportError(RightNow.Interface.getMessage("PCT_S_IS_INVALID_MSG"));
                }
            }
        }
        else if(!RightNow.Text.isValidEmailAddress(this._value)) {
            this._reportError(RightNow.Interface.getMessage("PCT_S_IS_INVALID_MSG"));
        }
    },

    /**
    * Validation routine to check for valid url.
    * @private
    */
    _checkUrl: function() {
        if(this._value !== null && this._value !== "" && !RightNow.Text.isValidUrl(this._value)) {
            this._reportError(RightNow.Interface.getMessage("IS_NOT_A_VALID_URL_MSG"));
        }
    },

    /**
     * Returns whether or not the field is required. Normalizes value for product/category, file attachment, and other field types.
     * @private
     * @return {boolean} Denotes if field must have a value
     */
    _isRequired: function(){
        if(this.is('product') || this.is('category')){
            return (this.data.attrs.required_lvl && this.data.attrs.required_lvl > 0) || false;
        }
        if(this.is('attachment')){
            return (this.data.attrs.min_required_attachments && this.data.attrs.min_required_attachments > 0) || false;
        }
        return this.data.attrs.required ? true : false;
    },

    /**
     * Validation routine to check if field is required, and if so, ensure it has a value.
     * @return {boolean} True if validation failed, False if validation passed
     */
    _checkRequired: function() {
        var error = false;
        if(this._isRequired()) {
            if (this.is("date")) {
                error = !(/\d/.test(this._value));
            }
            else if (this._value === "" || this._value === false || this._value === null) {
                error = true;
            }
        }
        if(error) {
            this._reportError(this.data.attrs.label_required);
        }
        return error;
    },

    /**
    * Returns the field's value.
    * @private
    * @return {String|Number} Field value
    */
    getValue: function() {
        var value;

        if (this.is("selection")) {
            if(this.input.size && this._isRadio()) {
                value = "";
                if(this.input.item(0).get("checked"))
                    value = this.input.item(0).get("value");
                else if(this.input.item(1).get("checked"))
                    value = this.input.item(1).get("value");
            }
            else if(this.input.get('type') === "checkbox") {
                value = this.input.get('checked');
            }
            else {
                //select value
                value = this.input.get("value");
            }
        }
        else if (this.is("date")) {
            // date
            value = "";
            var date = {
                day: 32,
                month: 13,
                year: 1900,
                hour: 0,
                minute: 0
            };
            this.input.each(function(input) {
                var inputName = input.get("name").toLowerCase();
                this.Y.Object.each(date, function(value, part) {
                    if (inputName.match(new RegExp(part + "$")) !== null) {
                        date[part] = parseInt(input.get("value"), 10);
                        return;
                    }
                });
            }, this);
            value = (date.year > 1900) ? date.year + "-" + date.month + "-" + date.day + " " + date.hour + ":" + date.minute + ":00" : "";
        }
        else if (this.is("product") || this.is("category")) {
            value = this._selectedNode
                ? (this._selectedNode.hierValue || 0)
                : (this._currentValue || 0);
        }
        else if (this.input && !this.is('attachment')) {
            value = this._getTextFieldValue(this.input);
        }
        return value;
    },

    /**
     * Focus on the specified field only if no alerts are being displayed.
     * @param {string} field Field to focus on
     */
    politeFocus: function(field) {
        if(!field) return;

        this.Y.all('[role=alert], [aria-live=assertive]').some(function(node) {
            return (!node.hasClass('rn_Hidden') && (node.get('textContent') || node.get('innerText')));
        }, this) || field.focus();
    },

    /**
     * Retrieve the value for a text backed field.
     * @param {Object} field The field to retrieve the value from
     * @return The value
     */
    _getTextFieldValue: function(field) {
        if(!field) return null;

        this._trimField(field);
        var value = field.get("value");

        if(this.data.js.type === 'Integer') {
            value = (value && !isNaN(Number(value)) && parseInt(value, 10) === parseFloat(value)) ? parseInt(value, 10) : value;
        }
        //Cast all empty strings to null, except for passwords, since null and empty string are technically different
        else if (this.is('text') && !this.is('password')) {
            // String or Thread. Integers are checked in the condition above
            if (value === '') value = null;
        }

        return value;
    },

    /**
    * Removes leading / trailing whitespace from the field value.
    * @param {string} field An optional target field. Defaults to the widget's input field.
    * @private
    */
    _trimField: function(field) {
        field = field || this.input;
        if(this.is("text") && !this.is("password")) {
            var value = field.get('value');
            if (!this.Y.Lang.isUndefined(value) && value !== "") {
              field.set('value', this.Y.Lang.trim(value));
            }
        }
    },

    /**
     * Shows hint for the input field.
     * @private
     */
    _initializeHint: function() {
        if(!this.data.attrs.always_show_hint) {
            if(this.Y.Overlay) {
                var overlay = this.Y.Node.create("<span id='" + this.baseDomID + "_Hint' aria-hidden='true'>" + this.data.attrs.hint + "</span>"),
                    align = {
                        node: this.baseSelector,
                        points: [this.Y.WidgetPositionAlign.TL, this.Y.WidgetPositionAlign.BL]
                    };

                if(this.data.info && this.data.info.class_name === 'RightNow.Widgets.SelectionInput' && this.data.js.type !== 'Boolean') {
                    overlay.addClass('rn_HintBoxRight');
                    align.node = this.Y.one(this.baseSelector + ' select');
                    align.points = [this.Y.WidgetPositionAlign.TL, this.Y.WidgetPositionAlign.TR];
                }
                else {
                    overlay.addClass('rn_HintBox');
                }

                overlay = new this.Y.Overlay({
                    bodyContent: overlay,
                    visible: false,
                    zIndex: 3,
                    align: align
                });

                if (this.Y.UA.webkit && (this.input.get('type') === 'checkbox' || this._isRadio())) {
                    var node = (this.input instanceof this.Y.NodeList) ? this.input.item(this.input.size() - 1) : this.input;
                    while (node.next()) {
                        node = node.next();
                    }
                    // Chrome fires 'click' instead of 'focus' for radio buttons and checkboxes and
                    // does not focus the element, which is needed for the blur event below.
                    this.input.on("click", function(){node.focus();overlay.show();});
                }
                else {
                    this.input.on("focus", function(){overlay.show();});
                }
                this.input.on("blur", function(){overlay.hide();});
                overlay.render(this.baseSelector);
            }
            else {
                //display hint inline if YUI container code isn't being included or the overlay is always being shown
                this.input.get("parentNode").append(this.Y.Node.create("<span class='rn_HintText' aria-hidden='true'>" + this.data.attrs.hint + "</span>"));
            }
        }
    }
}, {
    requires: {standard: ['overlay']}
});
