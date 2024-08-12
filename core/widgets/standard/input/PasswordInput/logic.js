RightNow.Widgets.PasswordInput = RightNow.Field.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this.input = this.Y.one(this._inputSelector);
            if (!this.input) return;
            this.inputLabel = this.Y.one(this.baseSelector + "_Label");
            this.currentPasswordField = this.Y.one(this._inputSelector + "_CurrentPassword");

            this.input.on('change', function() {
                this.fire('change', this);
            }, this);
            this.on('constraintChange', this.constraintChange, this);

            if (this.data.attrs.require_validation) {
                this.validation = this.Y.one(this._inputSelector + "_Validate");
                this.validationLabel = this.Y.one(this._inputSelector + "_LabelValidate");

                this.Y.one(this._inputSelector + '_Validate').on('change', function() {
                    this.fire('change', this);
                }, this);
            }

            this.parentForm().on("submit", this.onValidate, this);

            if (this.data.js.requirements) {
                this.initEvents();
            }

            if (this.data.attrs.initial_focus) {
                if (this.currentPasswordField && this.currentPasswordField.focus)
                    this.currentPasswordField.focus();
                else if (this.input.focus)
                    this.input.focus();
            }
        },

        /**
         * Validates the password and verify password field (if present).
         * @param {array} errors error messages to append on to
         * @return {boolean} T if validation passed, F if validation failed
         */
        validate: function(errors) {
            var attrs = this.data.attrs;

            this._value = this.input.get('value');
            // Validate regex requirements
            this._checkData();
            if (this._errors) {
                errors = this._errors.slice();
            }

            if (attrs.required) {
                this.toggleErrorIndicator(false, this.input, this.inputLabel);
                if (!this.input.get('value')) {
                    errors.push(attrs.label_required);
                    this.toggleErrorIndicator(true, this.input, this.inputLabel);
                }
            }
            if (!errors.length && this.data.js.requirements && !this.validateInput(true)) {
                errors.push(RightNow.Interface.getMessage("PCT_S_REQUIREMENTS_MET_LBL"));
            }
            if (attrs.require_validation) {
                this.toggleErrorIndicator(false, this.validation, this.validationLabel);
                if (!this.validateValidation(true)) {
                    errors.push({text: RightNow.Text.sprintf(attrs.label_validation_incorrect,
                        RightNow.Text.sprintf(attrs.label_validation, attrs.label_input))});
                    this.toggleErrorIndicator(true, this.validation, this.validationLabel);
                }
            }
            return !errors.length;
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
        var templateObject = {
            label: label,
            instanceID: this.instanceID,
            fieldName: this._fieldName,
            required: requiredness,
            requiredMarkLabel: RightNow.Interface.getMessage("FIELD_REQUIRED_MARK_LBL"),
            requiredLabel: RightNow.Interface.getMessage("REQUIRED_LBL"),
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
        if(this.data.js.requirements || (constraint.required === this.data.attrs.required)) return;

        //Remove the highlight
        this.toggleErrorIndicator(false, this.input, this.inputLabel);
        if(this.data.attrs.require_validation) {
            this.toggleErrorIndicator(false, this.validation, this.validationLabel);
        }

        //Hide any old messages
        if(this.data.attrs.required && this.lastErrorLocation) {
            this.Y.one('#' + this.lastErrorLocation).all("[data-field='" + this._fieldName + "']").remove();
        }

        //Replace any old labels with new labels
        if(this.data.attrs.label_input) {
            this.swapLabel(this.Y.one(this.baseSelector + '_LabelContainer'), constraint.required, this.data.attrs.label_input, this.getStatic().templates.label);
            this.inputLabel = this.Y.one(this.baseSelector + "_Label");
        }

        if(this.data.attrs.require_validation) {
            var labelValidate = RightNow.Text.sprintf(this.data.attrs.label_validation, this.data.attrs.label_input);
            this.swapLabel(this.Y.one(this.baseSelector + '_LabelValidateContainer'), constraint.required, labelValidate, this.getStatic().templates.labelValidate);
            this.validationLabel = this.Y.one(this._inputSelector + "_LabelValidate");
        }

        this.data.attrs.required = constraint.required;
    },

    /**
     * Sets up event listening.
     */
    initEvents: function() {
        var input = this.input;
        input.on("focus", this.showOverlay, this, 'input');
        input.on("blur", this.blurValidation, this, 'input');
        input.on("keyup", this.validateInput, this);
        input.on('keydown', function(e) {
            if (e.keyCode === RightNow.UI.KeyMap.TAB) {
                this.blurValidation((e.shiftKey) ? false : e, 'input');
            }
        }, this);

        if (this.data.attrs.require_validation) {
            var validation = this.validation;
            validation.on("focus", function(e, type) {
                if (!this.inputOverlay || !this.inputOverlay.get("visible")) {
                    this.showOverlay(e, type);
                }
            }, this, 'validation');
            validation.on("blur", this.blurValidation, this, 'validation');
            validation.on("keyup", this.validateValidation, this);
            validation.on('keydown', function(e) {
                if (e.keyCode === RightNow.UI.KeyMap.TAB) {
                    this.blurValidation((e.shiftKey) ? false : e, 'validation');
                }
            }, this);
        }
    },

    /**
     * A convenience method for retrieving the correct verification field value.
     * @return The verify field value
     */
    getVerificationValue: function() {
        return this._getTextFieldValue(this.Y.one(this._inputSelector + '_Validate'));
    },

    /**
     * Event handler executed when form is being submitted.
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    onValidate: function(type, args) {
        var eventObject = this.createEventObject(),
            errors = [];

        if (!this.validate(errors)) {
            this.lastErrorLocation = args[0].data.error_location;
            this.displayError(errors, this.lastErrorLocation);
            RightNow.Event.fire("evt_formFieldValidateFailure", eventObject);
            return false;
        }

        if (this.data.attrs.require_current_password && this.currentPasswordField) {
            eventObject.data.currentValue = this.currentPasswordField.get('value');
        }

        RightNow.Event.fire("evt_formFieldValidatePass", eventObject);
        return eventObject;
    },

    /**
     * Adds error messages to the form's error div.
     * @param {array} errors Contains error messages
     * Each item should be an object with a `text` property
     * where the value is used for the error message; if an
     * `id` property is also specified, it'll be used for the
     * message's onclick focus behavior. If an item isn't
     * an object it's assumed to be a string containing a
     * '%s' placeholder for label_input (otherwise the message
     * is simply tacked onto label_input if there's no '%s's)
     * @param {string} errorLocation id of the form's error div
     */
    displayError: function(errors, errorLocation) {
        var commonErrorDiv = this.Y.one("#" + errorLocation),
            errorLength = errors.length;

        if (commonErrorDiv && errorLength) {
            for (var i = 0, errorString = "", message, id, defaultID = this.input.get("id"); i < errorLength; i++) {
                message = errors[i];
                id = defaultID;
                if (typeof message === 'object' && message.text) {
                    if (message.id) {
                        id = message.id;
                    }
                    message = message.text;
                }
                else {
                    message = (message.indexOf("%s") > -1) ? RightNow.Text.sprintf(message, this.data.attrs.label_input) : this.data.attrs.label_input + " " + message;
                }
                errorString += "<div data-field=\"" + this._fieldName + "\"><b><a href='javascript:void(0);' onclick='document.getElementById(\"" + id +
                    "\").focus(); return false;'>" + message + "</a></b></div> ";
            }
            commonErrorDiv.append(errorString);
        }
    },

    /**
     * Adds / removes the error indicators on the
     * field and label.
     * @param {Boolean} showOrHide T to add, F to remove
     * @param {Array} fieldToHighlight ID of field to highlight
     * @param {Array} labelToHighlight ID of label to highlight
     */
    toggleErrorIndicator: function(showOrHide, fieldToHighlight, labelToHighlight) {
        var method = ((showOrHide) ? "addClass" : "removeClass");
        fieldToHighlight[method]("rn_ErrorField");
        labelToHighlight[method]("rn_ErrorLabel");
    },

    /**
     * Shows the overlay. Creates the overlay if it doesn't already exist.
     * @param {Object} e Focus event
     * @param {String} type The element to attach the overlay onto (input|validation)
     */
    showOverlay: function(e, type) {
        var name = type + "Overlay";
        if (!this[name]) {
            var overlay,
                element = this[type],
                insertInto = this.Y.one(this.baseSelector + ((type === 'input') ? '_PasswordHelp' : '')),
                content;

            if (type === 'input') {
                content = this.Y.Node.create('<div class="rn_PasswordOverlay" />').setHTML(new EJS({text: this.getStatic().templates.overlay}).render({
                    title: this.data.attrs.label_validation_title,
                    instanceID: this.instanceID,
                    validations: this.data.js.requirements,
                    passwordRequirementsLabel: RightNow.Interface.getMessage("PASSWD_VALIDATION_REQS_READ_L_MSG")
                }));
            }
            else {
                // Un-hide the existing screen reader text and use it as the overlay text.
                content = this.Y.one(this.baseSelector + '_PasswordValidationHelp').addClass('rn_PasswordOverlay').removeClass('rn_ScreenReaderOnly');
            }

            if (this.Y.Overlay) {
                overlay = new this.Y.Overlay({
                    bodyContent: content,
                    visible: false,
                    zIndex: 1,
                    align: {
                        node: element,
                        points: [this.Y.WidgetPositionAlign.TL, this.Y.WidgetPositionAlign.TR]
                    }
                }).render(insertInto);
            }
            else {
                // Mobile
                insertInto.append(content);
                overlay = {
                    _body: content,
                    show: function() { this._body.removeClass('rn_Hidden'); },
                    hide: function() { this._body.addClass('rn_Hidden'); },
                    get: function(what) {
                        if (what === 'visible') {
                            return !this._body.hasClass('rn_Hidden');
                        }
                        if (what === 'bodyContent') {
                            return this._body;
                        }
                    }
                };
            }
            this[name] = overlay;
        }
        this[name].align && this[name].align();
        this[name].show();
    },

    /**
    * Performs validation on blur.
    * If validation succeeds then the overlay is hidden;
    * If validation fails, the appropriate class names
    * are added and the overlay remains open.
    * @param {Object} e Blur event
    * @param {String} type The element perform validation on (input|validation)
    */
    blurValidation: function(e, type) {
        var overlay = this[type + "Overlay"];

        if (!overlay || this._alreadyBlurring) return;

        if (e === false) {
            overlay.hide();
            return;
        }

        this.toggleErrorIndicator(false, this[type], this[type + "Label"]);
        if (this["validate" + type.charAt(0).toUpperCase() + type.slice(1)](true)) {
            overlay.hide();
        }
        else if (overlay.get('visible')) {
            this.toggleErrorIndicator(true, this[type], this[type + "Label"]);

            if (e.keyCode) {
                // Focus on the overlay if the blur is invoked via keypress (screen readers)
                var overlayBody = overlay.get('bodyContent');
                overlayBody = (overlayBody.item) ? overlayBody.item(0) : overlayBody.one('*');

                // Focusing on something else during a blur event handler triggers a different
                // blur event, but we don't want that event (which completes before this does)
                // to disrupt what we're doing here.
                this._alreadyBlurring = true;
                this.Y.Node.getDOMNode(overlayBody.setAttribute("tabIndex", "-1")).focus();
                this._alreadyBlurring = false;

                // Stop the tab event from skipping the validation overlay's focus
                e.halt();
            }
            else if (type === 'validation') {
                overlay.hide();
            }
        }
    },

    /**
    * Returns validation class names. Returns an object instead of
    * simply existing as an instance property because the message base may
    * not have initialized at the point that the widget is evaluated.
    * @return {Object}
    */
    validationClasses: function() {
        return this.validationClasses.classes || (this.validationClasses.classes = {
            // Added to checklist's list-items
            fail: 'rn_Fail',
            pass: 'rn_Pass',
            // Progress bar indicators
            progress: [
                {className: 'rn_NoValidations', label: RightNow.Interface.getMessage("PASSWORD_IS_TOO_SHORT_MSG")},
                {className: 'rn_AllValidations', label: RightNow.Interface.getMessage("PERFECT_LBL")},
                {className: 'rn_SomeValidations', label: RightNow.Interface.getMessage("PASSWORD_IS_TOO_INSECURE_MSG")}
            ]
        });
    },

    /**
     * Validates the password field.
     * @param {Object} e DOM event
     * @return {Boolean} Whether validation succeeded or failed
     */
    validateInput: function(e) {
        if (e.keyCode === RightNow.UI.KeyMap.TAB) return;

        var Y = this.Y,
            password = this.input.get("value"),
            passed = 0, total = 0,
            requirements = this.data.js.requirements,
            checks = this._getPasswordStats(password);

        Y.Object.each(checks, function(value, key, className, requirement) {
            if (!(requirement = requirements[key])) return;

            if ((requirement.bounds === 'max' && value > requirement.count) ||
                (requirement.bounds === 'min' && value < requirement.count)) {
                className = 'fail';
            }
            else {
                passed++;
                className = 'pass';
            }
            this.updatePasswordChecklist(key, className);
            total++;
        }, this);

        var UI = RightNow.UI,
            baseSelector = this.baseSelector,
            quotient = passed / total,
            index = Math.round(quotient);

        if (quotient !== index && quotient > 0.3) {
            index = 2;
        }
        index = this.validationClasses().progress[index];

        UI.show(baseSelector + " .rn_Strength");
        UI.hide(baseSelector + " .rn_Intro .rn_Text");
        var meter = Y.one(baseSelector + " .rn_Meter");
        if (meter) {
            meter.setHTML('<div class="' + index.className + '"></div>');
        }
        meter = Y.one(baseSelector + "_MeterLabel");
        if (meter) {
            //removed 'meter.setHTML(index.label);' because setHTML() was not working properly with IE9'
            meter.set("innerHTML", index.label);
        }

        return passed === total;
    },

    /**
     * Validates the password validation field.
     * @return {Boolean} Whether validation succeeded or failed
     */
    validateValidation: function() {
        var validationValue = this.validation.get('value'),
            addRemoveClassOrder = [this.validationClasses().fail, this.validationClasses().pass];
        if (validationValue === this.input.get("value")) {
            addRemoveClassOrder.reverse();
        }
        if (this.validationOverlay) {
            this.validationOverlay.get("bodyContent").addClass(addRemoveClassOrder[0]).removeClass(addRemoveClassOrder[1]);
        }

        return addRemoveClassOrder[0] === this.validationClasses().pass;
    },

    /**
     * Finds the corresponding list-item for the specified name
     * and updates its class name and screen reader text.
     * @param {string} name of the item to lookup via its
     *      data-validate attribute
     * @param {string} name Either 'pass' or 'fail'
     */
    updatePasswordChecklist: function(name, action) {
        if (!(this._passwordChecklist || (this._passwordChecklist = this.Y.one(this.baseSelector + " ul.rn_Requirements")))) return;

        var className = this.validationClasses()[action],
            label = ((action === 'pass')
                ? RightNow.Interface.getMessage("COMPLETE_LBL")
                : RightNow.Interface.getMessage("INCOMPLETE_LBL"));

        this._passwordChecklist.one("li[data-validate='" + name + "']")
            .set('className', className).one(".rn_ScreenReaderOnly").set("innerHTML", label);
    },


    /**
     * Computes various items for the given password text.
     * @param {string} password Password text
     * @return {object} The stats recorded for the text:
     *  - repetitions: max number of character repetitions
     *  - occurrences: max number of character occurrences
     *  - uppercase: number of uppercase characters
     *  - lowercase: number of lowercase characters
     *  - special: number of special characters
     *  - specialAndDigits: number of special characters
     *      and digits
     *  - length: length of the string
     */
    _getPasswordStats: function(password) {
        var checks = {
                repetitions: 0,
                occurrences: 0,
                uppercase: 0,
                lowercase: 0,
                special: 0,
                specialAndDigits: 0,
                length: 0
            },
            len = password.length,
            lastChar = '',
            i, j, char, occur, reps,
            lc, uc;

        for (i = 0; i < len; i++) {
            occur = 0;
            char = password[i] || password.substr(i, 1);

            if (char === lastChar) {
                reps++;
                if (reps > checks.repetitions) {
                    checks.repetitions = reps;
                }
            }
            else {
                lastChar = char;
                reps = 1;
            }

            for (j = 0; j < len; j++) {
                if ((password[j] || password.substr(j, 1)) === char) {
                    occur++;
                }
            }
            if (occur > checks.occurrences) {
                checks.occurrences = occur;
            }

            uc = char.toLocaleUpperCase();
            lc = char.toLocaleLowerCase();

            if (uc === lc && lc === char) {
                // Special char or digit
                if (!isNaN(parseInt(char, 10))) {
                    checks.specialAndDigits++;
                }
                else {
                    checks.specialAndDigits++;
                    checks.special++;
                }
            }
            else if (lc === char) {
                checks.lowercase++;
            }
            else if (uc === char) {
                checks.uppercase++;
            }
        }
        checks.length = len;

        return checks;
    }
});
