if(!RightNow)
    var RightNow = {};

var prevButton = document.getElementById('prev_btn');
var submitButton = document.getElementById('submit_btn');

if (submitButton)
{
    var ensureSubmitButtonIsEnabled = function(){
        submitButton.disabled = false;
        if (typeof RightNow.MarketingFeedback !== 'undefined')
            submitButton.value = RightNow.MarketingFeedback.getSubmitButtonText();

        if (prevButton)
            prevButton.disabled = false;

        if (typeof(window.mobileOnPageShow) === 'function')
            window.mobileOnPageShow();
    }

    var appendToEvent = function(el, event, eventFunction) {
        if (!el.addEventListener)
            el.attachEvent("on" + event, eventFunction);
        else
            el.addEventListener(event, eventFunction, false);
    }
    appendToEvent(window, "load", ensureSubmitButtonIsEnabled);
    appendToEvent(window, "pageshow", ensureSubmitButtonIsEnabled);
    appendToEvent(document.body, "pageshow", ensureSubmitButtonIsEnabled);
}

if (prevButton)
{
    if(typeof onBeforeUnloadMessage === 'undefined')
        var onBeforeUnloadMessage = '';

    window.onbeforeunload = function() {
        return onBeforeUnloadMessage;
    }

    var cancelOnBeforeUnload = function () {
        window.onbeforeunload = function () {};
    }

    var appendToOnClickEvent = function(el, eventFunction) {
        if (!el.addEventListener)
            el.attachEvent("onclick", eventFunction);
        else
            el.addEventListener("click", eventFunction, false);
    }

    if (prevButton)
        appendToOnClickEvent(prevButton, cancelOnBeforeUnload);
    if (submitButton)
        appendToOnClickEvent(submitButton, cancelOnBeforeUnload);

    var links = document.getElementsByTagName('a');
    for (var i = 0; i < links.length; i++)
        appendToOnClickEvent(links[i], cancelOnBeforeUnload);
}

/**
 * RightNow.MarketingFeedback
 *
 * @namespace Contains functions specific to marketing/feedback code that lives in CP, should not be used for polling widget
 */
RightNow.MarketingFeedback = (function(){
    /** Determines the type of submit being done (next/back)
    */
    var _submitType = 'submit';
    /** The text of the submit button - the flow designer can customize
     *  this so we can't use a message base string
    */
    var _submitButtonText = '';
    /** Determines whether a field is required or not
     * @constant*/
    var _requiredFlag = 1;
    /** If set, the value input must be greater than 0
     * @constant*/
    var _positiveValueRequiredFlag = 0x40;
    /** If set, the value input must be valid ascii
     * @constant*/
    var _asciiRequiredFlag = 2;
    /** If set, the value input must be a valid email address
     * @constant*/
    var _validEmailFlag = 4;
    /** If set, the value input must be furigana
     * @constant*/
    var _furiganaRequiredFlag = 0x10;
    /**If set, the value input cannot contain html
     * @constant*/
    var _htmlBlockFlag = 0x20;
    /**@constant*/
    var _questionTypeText = 1;
    /**@constant*/
    var _questionTypeChoice = 2;
    /**@constant*/
    var _questionTypeMatrix = 3;
    /**@constant*/
    var _fieldTypes = {"CDT_MENU":1, "CDT_BOOL":2, "CDT_INT":3, "CDT_DATETIME":4,
                       "CDT_VARCHAR":5, "CDT_MEMO":6, "CDT_DATE":7, "CDT_OPT_IN":8};
    /** Maximum survey text size in bytes supported by API
    * @constant*/
    var _maxTextSize = 4000;
    var _pollingMatrixCheckedColumns = [],
        _trimValue = function(value){
            try{
                return value.replace(/^\s+|\s+$/g, "");
            }
            catch(e){
                return value;
            }
        },
        _countBytes = function(str)
        {
            var i, sz, len = 0;
            for (i = 0, sz = str.length; i < sz; i++) 
                if (str.charCodeAt(i) < 0x0080)
                    len += 1;
                else if (str.charCodeAt(i) < 0x0800)
                    len += 2;
                else
                    len += 3;

            return(len);
        },
        _getCssClassRegex = function(cssClass){
            return new RegExp('(\\s|^)' + cssClass + '(\\s|$)');
        },
        _classExists = function(element, cssClass){
            return element.className.match(_getCssClassRegex(cssClass));
        },
        _addClass = function(element, cssClass){
            if(!_classExists(element, cssClass)){
                if(element.className){
                    element.className += " ";
                }
                element.className += cssClass;
            }
        },
        _removeClass = function(element, cssClass){
            if (_classExists(element, cssClass)) {
                element.className = element.className.replace(_getCssClassRegex(cssClass), '');
            }
        },
        /**@param {...*} string**/
        _sprintf = function(string)
        {
            var i = 1, args = arguments;
            return string.replace(/%[%sd]/g, function(match)
                {
                    if (match === "%%")
                        return "%";
                    return args[i++];
                }
            );
        };

    return {
         /**
         * Updates the real time character counter for text questions
         * @param {Node} fieldElement The input type=text element
         * @param {string} defaultColor The color of the counter when not in a warning state
         * @param {string} warningColor The color of the counter when in a warning state
         */
        updateCharCount: function(fieldElement, defaultColor, warningColor) {
            var counterElement = document.getElementById(fieldElement.name + "_count");
            // the validation info is always stored in a hidden input named "val_"
            var validationInfo = document.getElementById("val_" + fieldElement.name).value;
            var validationArray = validationInfo.split(",");
            var maxChars = validationArray[2];
            //Tabs are converted to four spaces on the server
            var charsUsed = fieldElement.value.replace(/\t/g, '    ').length;

            if (charsUsed <= maxChars)
                counterElement.style.color = defaultColor;
            else
                counterElement.style.color = warningColor;

            counterElement.innerHTML = maxChars - charsUsed;
        },

         /**
         * Checks and enforces "Force Ranking" matrix questions
         * @param {Node} radioInput The radio input element in the matrix
         * @param {Array} checkedColumnsArray List of which columns are checked
         * @param {Boolean} isPolling True if the ranking is for a polling widget
         * @param {Boolean} isMobile True if the survey is being rendered in mobile mode (always false for the polling widget)
         * @param {Object} peJavascriptObject An object passed in for survey runtime (always null for the polling widget)
         */
        checkRanking : function(radioInput, checkedColumnsArray, isPolling, isMobile, peJavascriptObject) {
            if (isPolling)
                checkedColumnsArray = _pollingMatrixCheckedColumns;
            if (typeof(checkedColumnsArray) === 'undefined')
                checkedColumnsArray = [];
            if (checkedColumnsArray[radioInput.value] && checkedColumnsArray[radioInput.value] !== radioInput.id) {
               var inputToUncheck = radioInput.form[checkedColumnsArray[radioInput.value]];
               if (isMobile && !isPolling)
                   peJavascriptObject.unCheckMatrixRadioButtonMobile(inputToUncheck);
               else
                   inputToUncheck.checked = false;
            }
            checkedColumnsArray[radioInput.value] = radioInput.id;

            if (isPolling)
                _pollingMatrixCheckedColumns = checkedColumnsArray;
        },

        /**
         * Validates the entire page
         * @param {Node} form The form element to run validation against
         * @param {Object} fieldData Object containing messages and data used to validate
         * @param {Array} fields List of all field objects in the form used for validation
         * @param {Node} errorDisplay Div to drop the errors into
         * @param {boolean} hasSurveyQuestions indicates if we should run validation on survey questions
         * @return {boolean} A value determining if validation passed or not
         */
        validate : function(form, fieldData, fields, surveyFields, errorDisplay, hasSurveyQuestions) {
            var surveyValidation = true;
            var formValidation = true;

            if (hasSurveyQuestions)
                surveyValidation = this.validateSurveyFields(form, fieldData, surveyFields, errorDisplay);

            formValidation = this.validateFormFields(form, fieldData, fields, errorDisplay);

            var result = (surveyValidation && formValidation)
            if (result)
               this.disableSubmitButton(fieldData.please_wait_message);
            return (result);
        },

         /**
         * Validates all survey questions in form
         * @param {Node} form The form element to run validation against
         * @param {Object} fieldData Object containing messages and data used to validate
         * @param {Array} fields List of all field objects in the form used for validation
         * @param {Node} errorDisplay Div to drop the errors into
         * @return {boolean} A value determining if validation passed or not
         */
        validateSurveyFields : function(form, fieldData, fields, errorDisplay) {
           var returnValue = true;
           var field, fieldLength, selectedCount, selectedArray, j, count, elementArray, firstInputElement, content, noOfLineBreaks;

           for (var i = 0; (i < fields.length) && fields[i].type; i++) {
              field = form.elements['q_' + fields[i].id];

              if ((fields[i].min > 0) || (fields[i].max > 0)) {
                 if (fields[i].type === _questionTypeText) {
                    if (fields[i].min > 0 || fields[i].max > 0) {
                       //Tabs are converted to four spaces on the server
                       var content = field.value.replace(/\t/g, '    ');
                       fieldLength = content.length;
                       if (fields[i].min > 0 && fieldLength < fields[i].min) {
                            this.addErrorMessage(fields[i].question_text + " " + fieldData.reqd_msg, field, errorDisplay, true);
                            returnValue = false;
                       }

                       if ((fields[i].max > 0 && fieldLength > fields[i].max) || (_countBytes(content) > _maxTextSize)){
                           this.addErrorMessage(fields[i].question_text + " " + fieldData.fld_too_mny_chars_msg, field, errorDisplay, true);
                           returnValue = false;
                       }
                    }
                 }
                 else {
                    selectedCount = 0;
                    // We have to go through and find all the elements associated
                    // with this question.  We'll drop them into an array so we
                    // can count them.
                    if (field) {
                       // Need to handle buttons & select elements differently
                       if(field.options) {
                          selectedArray = [];
                          count = 0;

                          for (j = 0; j < field.options.length; j++) {
                             if (field.options[j].selected && field.options[j].value !== '') {
                                selectedArray[count] = field.options[j].value;
                                count++;
                             }
                          }

                          selectedCount = selectedArray.length;
                       }
                       else {
                          // radio buttons. field is an array
                          for (j = 0; j < field.length; j++) {
                             if (field[j].checked)
                                selectedCount++;
                          }
                       }
                    }
                    else {
                       // This is a checkbox question
                       elementArray = fields[i].elements.replace(/'/g, "").split(":");

                       for (j = 0; j < elementArray.length; j++) {
                          field = form.elements[elementArray[j]];
                          if(field && field.checked)
                             selectedCount++;
                       }
                    }
                    if (form.elements['q_' + fields[i].id])
                        firstInputElement = form.elements['q_' + fields[i].id][0];
                    else
                        firstInputElement = form.elements[fields[i].elements.split(':')[0].replace("'","")];

                    if (firstInputElement.id === 'blankOption')
                        firstInputElement = firstInputElement.parentNode;

                    // Now check the values against the restrictions
                    if (fields[i].min > 0 && selectedCount < fields[i].min) {
                       this.addErrorMessage(fields[i].question_text + " " + fieldData.too_few_options_msg, firstInputElement, errorDisplay, false);
                       returnValue = false;
                    }
                    if (fields[i].max > 0 && selectedCount > fields[i].max) {
                       this.addErrorMessage(fields[i].question_text + " " + fieldData.too_many_options_msg, firstInputElement, errorDisplay, false);
                       returnValue = false;
                    }
                 }
              }
           }

           return returnValue;
        },

         /**
         * Validates all form fields with the exception of survey questions
         * @param {Node} form The form element to run validation against
         * @param {Object} fieldData Object containing messages and data used to validate
         * @param {Array} fields List of all field objects in the form used for validation
         * @param {Node} errorDisplay Div to drop the errors into
         * @return {boolean} A value determining if validation passed or not
         */
        validateFormFields : function(form, fieldData, fields, errorDisplay) {
            var field, i, j, numSet, str;
            var returnValue = true;

            for (i = 0; (i < fields.length) && fields[i].type; i++) {
                if ((fields[i].type != _fieldTypes.CDT_DATETIME) && (fields[i].type != _fieldTypes.CDT_DATE))
                    field = form.elements[fields[i].name];

                if (field && field.length > 0 && (fields[i].type === _fieldTypes.CDT_INT || fields[i].type === _fieldTypes.CDT_VARCHAR || fields[i].type === _fieldTypes.CDT_MEMO)) {
                    field = field[field.length - 1];
                }

                switch (fields[i].type) {
                    case _fieldTypes.CDT_MENU:
                        if (!this.validateMenuField(field, fieldData, fields[i], errorDisplay))
                            returnValue = false;
                        break;

                    case _fieldTypes.CDT_BOOL:
                    case _fieldTypes.CDT_OPT_IN:
                        if (!this.validateBoolField(field, fieldData, fields[i], errorDisplay))
                            returnValue = false;
                        break;

                    case _fieldTypes.CDT_INT:
                        if (!this.validateIntField(field, fieldData, fields[i], errorDisplay))
                            returnValue = false;
                        break;
                    case _fieldTypes.CDT_VARCHAR:
                    case _fieldTypes.CDT_MEMO:
                        if (!this.validateTextField(field, fieldData, fields[i], errorDisplay))
                            returnValue = false;
                        break;
                    case _fieldTypes.CDT_DATETIME:
                    case _fieldTypes.CDT_DATE:
                        if (!this.validateDateTimeField(form, fieldData, fields[i], errorDisplay))
                            returnValue = false;
                        break;
                }
            }

            return returnValue;
        },

         /**
         * Validates a menu field
         * @param {Node} htmlElement The element to run validation against
         * @param {Object} fieldData Object containing messages and data used to validate
         * @param {Object} menuField Field object containing data used for validation
         * @param {Node} errorDisplay Div to drop the errors into
         * @return {boolean} A value determining if validation passed or not
         */
        validateMenuField: function(htmlElement, fieldData, menuField, errorDisplay) {
            if ((menuField.flags & _requiredFlag) && (htmlElement.length > 1) && (htmlElement.selectedIndex < 1)) {
                this.addErrorMessage(menuField.label + ' ' + fieldData.reqd_msg, htmlElement, errorDisplay, true);
                return false;
            }
            return true;
        },

         /**
         * Validates a boolean field (yes/no)
         * @param {Node} htmlElement The element to run validation against
         * @param {Object} fieldData Object containing messages and data used to validate
         * @param {Object} boolField Field object containing data used for validation
         * @param {Node} errorDisplay Div to drop the errors into
         * @return {boolean} A value determining if validation passed or not
         */
        validateBoolField: function(htmlElement, fieldData, boolField, errorDisplay) {
            if ((boolField.flags & _requiredFlag) && !htmlElement[0].checked && !htmlElement[1].checked) {
                this.addErrorMessage(boolField.label + ' ' + fieldData.reqd_msg, htmlElement[0], errorDisplay, false);
                return false;
            }
            return true;
        },

         /**
         * Validates an int field
         * @param {Node} htmlElement The element to run validation against
         * @param {Object} fieldData Object containing messages and data used to validate
         * @param {Object} intField Field object containing data used for validation
         * @param {Node} errorDisplay Div to drop the errors into
         * @return {boolean} A value determining if validation passed or not
         */
        validateIntField: function(htmlElement, fieldData, intField, errorDisplay) {
            htmlElement.value = _trimValue(htmlElement.value);
            var returnValue = true;

            //if required and not set
            if ((intField.flags & _requiredFlag) && (htmlElement.value.length === 0)) {
                this.addErrorMessage(intField.label + ' ' + fieldData.reqd_msg, htmlElement, errorDisplay, true);
                returnValue = false;
            }
            if (htmlElement.value.length && isNaN(htmlElement.value)) {
                this.addErrorMessage(intField.label + ' ' + fieldData.int_msg, htmlElement, errorDisplay, true);
                returnValue = false;
            }
            if (intField.flags & _positiveValueRequiredFlag && htmlElement.value < 0) {
                this.addErrorMessage(intField.label + ' ' + fieldData.pos_int_msg, htmlElement, errorDisplay, true);
                returnValue = false;
            }
            if(parseInt(htmlElement.value, 10) > intField.maxval) {
                this.addErrorMessage(intField.label + ' ' + fieldData.val_ent_gt_lg_val_field_msg + ' \'' + intField.maxval + ' \'', htmlElement, errorDisplay, true);
                returnValue = false;
            }
            if(parseInt(htmlElement.value, 10) < intField.minval) {
                this.addErrorMessage(intField.label + ' ' + fieldData.val_ent_lt_sm_val_field_msg + ' \'' + intField.minval + '\'', htmlElement, errorDisplay, true);
                returnValue = false;
            }
            return returnValue;
        },

         /**
         * Validates a text field
         * @param {Node} textHtmlElement The element to run validation against
         * @param {Object} fieldData Object containing messages and data used to validate
         * @param {Object} textField Field object containing data used for validation
         * @param {Node} errorDisplay Div to drop the errors into
         * @return {boolean} A value determining if validation passed or not
         */
        validateTextField: function(textHtmlElement, fieldData, textField, errorDisplay) {
            var inputValue = _trimValue(textHtmlElement.value);
            var validAsciiExpression = new RegExp("^[\x20-\x7e]+$");
            var htmlStrippingExpression = new RegExp("[<>]");
            var numberExpression = new RegExp("%d");
            var returnValue = true;

            if (textField.type === _fieldTypes.CDT_VARCHAR) {
                if (textField.name === 'wf_2_100015' || textField.name === 'wf_2_100016' ||
                    textField.name === 'wf_2_100017' || textField.name === 'wf_2_100018' || textField.name === 'wf_2_100019') {
                    var validInput = new RegExp("^[-A-Za-z0-9,# +.()]+$");
                    if(inputValue && !validInput.test(inputValue)) {
                        this.addErrorMessage(fieldData.not_valid_phone_char_msg.replace("%s", textField.label), textHtmlElement, errorDisplay, true);
                        returnValue = false;
                    }
                }
                if (textField.maxlen && (textField.maxlen < inputValue.length)) {
                    var message = _sprintf(fieldData.oversz_msg, textField.label, textField.maxlen, inputValue.length - textField.maxlen);
                    this.addErrorMessage(message, textHtmlElement, errorDisplay);
                    returnValue = false;
                }
                if(textField.label.toUpperCase() === 'ALTERNATEEMAIL' && !fieldData.email_expr.test(inputValue)) {
                    this.addErrorMessage(textField.label + ' ' + fieldData.email_msg, textHtmlElement, errorDisplay, true);
                    returnValue = false;
                }
            } else if (textField.maxlen && (textField.maxlen < inputValue.length)) {
                var extraCharaters = inputValue.length - textField.maxlen;
                var message = _sprintf(fieldData.oversz_msg, textField.label, textField.maxlen, extraCharaters);
                this.addErrorMessage(message, textHtmlElement, errorDisplay);
                returnValue = false;
            }

            //if required and not set
            if ((textField.flags & _requiredFlag) && (inputValue.length === 0)) {
                this.addErrorMessage(textField.label + ' ' + fieldData.reqd_msg, textHtmlElement, errorDisplay, true);
                returnValue = false;
            }
            else if ((inputValue.length === 0)) {
                return true;
            }
            else if (textField.mask) {
                //if the value does not matches the required mask
                var maskError = this.checkMask(textField.mask, inputValue, textField.label, fieldData, textHtmlElement);
                if(maskError) {
                    this.addErrorMessage(maskError, textHtmlElement, errorDisplay, true);
                    returnValue = false;
                }
            }

            if ((textField.flags & _asciiRequiredFlag) && !validAsciiExpression.test(inputValue)) {
                this.addErrorMessage(textField.label + ' ' + fieldData.ascii_msg, textHtmlElement, errorDisplay, true);
                returnValue = false;
            }

            var emailExpression = new RegExp(fieldData.email_expr ? fieldData.email_expr : '.*');
            if (textField.flags & _validEmailFlag) {
                if (inputValue !== textHtmlElement.value) {
                    textHtmlElement.value = inputValue;
                }
                if (!emailExpression.test(inputValue)) {
                    this.addErrorMessage(textField.label + ' ' + fieldData.email_msg, textHtmlElement, errorDisplay, true);
                    returnValue = false;
                }
            }

            if ((textField.flags & _furiganaRequiredFlag) && !this.isFuriganaString(inputValue)) {
                this.addErrorMessage(textField.label + ' ' + fieldData.furigana_msg, textHtmlElement, errorDisplay, true);
                returnValue = false;
            }

            if (textField.flags & _htmlBlockFlag && htmlStrippingExpression.test(inputValue)) {
                this.addErrorMessage(textField.label + ' ' + fieldData.htmlStrippingExpression_msg, textHtmlElement, errorDisplay, true);
                returnValue = false;
            }

            return returnValue;
        },

         /**
         * Validates a Date or DateTime field
         * @param {Node} form The form element to run validation against
         * @param {Object} fieldData Object containing messages and data used to validate
         * @param {Object} dateTimeField Field object containing data used for validation
         * @param {Node} errorDisplay Div to drop the errors into
         * @return {boolean} A value determining if validation passed or not
         */
        validateDateTimeField: function(form, fieldData, dateTimeField, errorDisplay) {
            var field = [form.elements[dateTimeField.name + fieldData.dt_sfx[0]], form.elements[dateTimeField.name + fieldData.dt_sfx[1]], form.elements[dateTimeField.name + fieldData.dt_sfx[2]]];
            var returnValue = true;
            var dateTimeType = 4;

            if (dateTimeField.type === dateTimeType) {
                field[3] = form.elements[dateTimeField.name + '_hr'];
                field[4] = form.elements[dateTimeField.name + '_min'];
            }

            if (!(dateTimeField.flags & _requiredFlag)) { // not required
                for (var j = 0, numSet = 0; j < field.length; j++)
                    numSet += (field[j].selectedIndex > 0) ? 1 : 0;

                if ((numSet > 0) && (numSet !== field.length)) {
                    // field is only partially filled out
                    this.addErrorMessage(dateTimeField.label + ' ' + fieldData.not_complete_msg, field[0], errorDisplay, true);
                    returnValue = false;
                }
            }
            else {
                for (var j = 0; j < field.length; j++) {
                    if ((field[j].selectedIndex < 1)) {
                        this.addErrorMessage(dateTimeField.label + ' (' + fieldData.dt_lbl[j] + ')\' ' + fieldData.reqd_msg, field[j], errorDisplay, true);
                        returnValue = false;
                    }
                }
            }
            return returnValue;
        },

         /**
         * Determines if a string contains Furigana characters
         * @param {string} inputString The string to check for Furigana characters in
         * @return {boolean} A value indicating if the string is Furigana
         */
        isFuriganaString: function(inputString) {
            for (var i = 0; i < inputString.length; i++) {
                var charCode = inputString.charCodeAt(i);

                if ((charCode >= 0x3041 && charCode <= 0x309E) || // hiragana
                    (charCode >= 0x30A1 && charCode <= 0x30FE) || // full-width katakana
                    (charCode === 0x2212) || (charCode === 0x2025) || // full-width hyphens
                    (charCode === 0xFF0E) || (charCode === 0x0020) || // nakaguro, ' '
                    (charCode >= 0x0030 && charCode <= 0x0039) || // '0' - '9'
                    (charCode >= 0x0041 && charCode <= 0x005A) || // 'A' - 'Z'
                    (charCode >= 0x0061 && charCode <= 0x007A) || // 'a' - 'z'
                    (charCode === 0x0028) || (charCode === 0x0029) || // '('   ')'
                    (charCode === 0x002C) || (charCode === 0x002E) || // ','   '.'
                    (charCode === 0x0026) || (charCode === 0x002D) || // '&'   '-'
                    (charCode === 0xFF0D) || (charCode === 0xFF06) || // full-width hypen and ampersand
                    (charCode === 0xFF08) || (charCode === 0xFF09) || // full-width parenthesis
                    (charCode === 0x3000))                    // full-width space
                    continue;

                return false;
            }
            return true;
        },

        /**
         * Adds a clickable error message to errorDisplay and adds a CSS class to fields with errors
         * @param {string} message The error message
         * @param {Node} focusElement The element with an error
         * @param {Node} errorDisplay The div to drop the errors into
         * @param {boolean} highlightItem Indicates whether the field should have a "highlight" class added to it
         */
        addErrorMessage: function(message, focusElement, errorDisplay, highlightItem) {
            if (focusElement.nodeName.toLowerCase() === 'option')
                focusElement = focusElement.parentElement;

            _removeClass(errorDisplay, 'rn_Hidden');
            _addClass(errorDisplay, 'rn_MessageBox rn_ErrorMessage');
            if (highlightItem)
                 _addClass(focusElement, 'rn_ErrorField');
            var newMessage = '<a href="javascript:void(0);" onclick="document.getElementById(\'' + focusElement.id + '\').focus(); return false;">' + message + '</a>',
                oldMessage = errorDisplay.innerHTML;
            errorDisplay.innerHTML = (oldMessage) ? oldMessage + '<br>' + newMessage : newMessage;
            errorDisplay.firstChild.focus();
        },

        /**
         * Removes error messages and removes the error CSS classes from all fields
         * @param {Node} form The form to remove errors from
         * @param {Node} errorDisplay The div to drop the errors into
         */
        removeErrorsFromForm: function(form, errorDisplay) {
            _removeClass(errorDisplay, 'rn_MessageBox');
            _removeClass(errorDisplay, 'rn_ErrorMessage');
            _addClass(errorDisplay, 'rn_Hidden');
            errorDisplay.innerHTML = "";

            for (var i in form.elements) {
                if (form.elements[i] && form.elements[i].id && form.elements[i].nodeName)
                    _removeClass(form.elements[i], 'rn_ErrorField');
            }
        },

        /**
         * Get the style definition for a chart
         * @param {*} chartType The chart type
         * @returns {Object} The styling object for the chart
         */
        getChartStyleDefinition: function(chartType) {
            if (chartType === "pie")
                return {
                    legend: {
                        display: "right"
                    }
                };

            var xAxisDefinition = {
                majorTicks: {
                    size: 0
                },
                minorTicks: {
                    size: 0
                },
                majorGridLines: {
                    size: 0
                },
                minorGridLines: {
                    size: 0
                }
            };
            var yAxisDefinition = {
                majorTicks: {
                    size: 0
                },
                minorTicks: {
                    size: 0
                },
                majorGridLines: {
                    size: 0
                },
                minorGridLines: {
                    size: 0
                }
            };
            if (chartType === "horizontal_bar")
                xAxisDefinition.showLabels = false;
            else if (chartType === "vertical_bar")
                yAxisDefinition.showLabels = false;

            return {
                xAxis: xAxisDefinition,
                yAxis: yAxisDefinition
            };
        },

        /**
         * Get the style definition for a chart's series
         * @param {*} chartType The chart type
         * @returns {Object} The styling object for the series
         */
        getSeriesStyleDefinition: function(chartType) {
            if (chartType === "pie")
                return {
                    showLabels: false
                };

            return {
                color: "#0000FF"
            };
        },

         /**
         * Set the submitType to back
         */
        backClick : function() {
            _submitType = 'back';
        },

         /**
         * Get the submit type, either 'submit' or 'back'
         */
        getSubmitType : function() {
            return _submitType;
        },

         /**
         * Update a hidden variable letting the server know the question response has changed.
         * @param {Node} el The input/textarea element that has changed
         */
        setResponseModified : function(el) {
            el.form.elements.p_next_id.value = '';
        },

        getSubmitButtonText : function() {
            if (_submitButtonText.length === 0 && submitButton)
                _submitButtonText = submitButton.value;

            return _submitButtonText;
        },

        disableSubmitButton : function(pleaseWaitMessage) {
            if (submitButton && !submitButton.disabled) {
                _submitButtonText = submitButton.value;
                submitButton.disabled = true;
                submitButton.value = pleaseWaitMessage;
            }
            if (prevButton)
                prevButton.disabled = true;
        },

        checkMask : function(mask, value, fldlabel, fieldData, textHtmlElement) {
            var mtmp, ftmp, dtmp, message;
            mtmp = ftmp = dtmp = message = '';

            for (var i = 0; i < mask.length; i++)
            {
                ftmp += mask[i];

                if (mask[i] == 'F')
                    dtmp += mask[i + 1];
                else
                    dtmp += (mask[i + 1] == '#') ? '#' : '@';

                mtmp += mask[++i];
            }

            var ln = mtmp.length;

            if (ln < value.length)
            {
                message = fieldData.fld_too_mny_chars_msg;
            }
            else
            {
                for (var i = 0; i < ln; i++)
                {
                    if(textHtmlElement && this._fixCase(i, value, ftmp[i], mtmp[i], textHtmlElement)) {
                        value = textHtmlElement.value;
                    }
                    message = this._validateCharacterAt(i, value, ftmp[i], mtmp[i], fieldData);
                    if (message)
                    {
                        break;
                    }
                }
            }
            if (message)
                message = "'" + fldlabel + "': " + message + " " + fieldData.corr_fmt_is_msg + " " + dtmp;
            return message;
        },

        _fixCase : function(index, value, fchar, echar, textHtmlElement) {
            // ensure letters are propper case
            if (echar == 'L')
            {
                if (fchar == 'U' && (value.charAt(index) !== value.charAt(index).toUpperCase()))
                {
                    textHtmlElement.value = value.substring(0, index) + value.charAt(index).toUpperCase() + value.substring(index + 1);
                    return true;
                }
                else if (fchar == 'L' && (value.charAt(index) !== value.charAt(index).toLowerCase()))
                {
                    textHtmlElement.value = value.substring(0, index) + value.charAt(index).toLowerCase() + value.substring(index + 1);
                    return true;
                }
            }
            return false;
        },

        _validateCharacterAt : function(index, value, fchar, echar, fieldData) {
            var val = value[index];
            var code = value.charCodeAt(index); // get ascii value of character
            var message = null;

            if (fchar == 'F' && val != echar)
            {
                if (val == null)
                    message = fieldData.must_cont_valid_format_char_msg;
                else
                    message = "'" + val + "' " + fieldData.not_valid_format_char_msg;
            }
            else if ((echar == '#') && (!((code >= 48) && (code <= 57))))
            {
                if (val == null)
                    message = fieldData.must_cont_valid_num_msg;
                else
                    message = "'" + val + "' " + fieldData.not_valid_num_msg;
            }
            else if ((echar == 'A') && (!(((code >= 48) && (code <= 57)) || ((code >= 65) && (code <= 90)) || ((code >= 97) && (code <= 122)))))
            {
                if (val == null)
                    message = fieldData.must_cont_valid_alphanum_msg;
                else
                    message = "'" + val + "' " + fieldData.not_valid_alphanum_msg;
            }
            else if ((echar == 'L') && (!(((code >= 65) && (code <= 90)) || ((code >= 97) && (code <= 122)))))
            {
                if (val == null)
                    message = fieldData.must_cont_valid_letter_msg;
                else
                    message = "'" + val + "' " + fieldData.not_valid_letter_msg;
            }
            else if ((echar == 'C') && (!(((code >= 32) && (code <= 126)) || ((code >= 128) && (code <= 255)))))
            {
                if (val == null)
                    message = fieldData.must_cont_valid_char_msg;
                else
                    message = "'" + val + "' " + fieldData.not_valid_char_msg;
            }
            return message;
        }
    };
}());
