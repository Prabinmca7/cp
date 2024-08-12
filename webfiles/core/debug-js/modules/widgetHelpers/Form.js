(function() {
var FormConductor = (function() {
    /* keep track of form submit instances on the page */
var _instances = {},
    /* deferred object to return to subscribers before a parent form may have been instantiated */
    _deferredObjects = {},
    /* list of fields added before the form widget is instantiated */
    _deferredFields = {};

return {
    /**
     * Returns a deferred object to subscribers
     * @param {String} parentFormID ID of the parent form
     * @param {String} instanceID instance id of the caller
     * @return {Object} Object representing the widget's this.parentForm()
     */
    getDeferred: function(parentFormID, instanceID) {
        if (_instances[parentFormID]) {
            return _instances[parentFormID];
        }

        _deferredObjects[instanceID] = _deferredObjects[instanceID] || {events: []};
        _deferredObjects[instanceID].form = parentFormID;
        return {
            on: function(event, handler, context) {
                if (handler && typeof handler === "function") {
                    _deferredObjects[instanceID].events.push({name: event, handler: handler, context: context});
                    return this; // chainable
                }
                throw Error("Handler specified isn't a callable function");
            },
            addField: function(fieldName, instance) {
                if(fieldName && instance && typeof instance === "object") {
                    if(!_deferredFields[parentFormID]) {
                        _deferredFields[parentFormID] = {};
                    }

                    _deferredFields[parentFormID][fieldName] = instance;
                    return;
                }

                throw Error("A non-object field cannot be added to a form");
            }
        };
    },

    /**
     * Returns an existing Form instance
     * @param {String} parentFormID ID of the parent form
     * @return {Object?} The form instance or null if there is none for the given id
     */
    getInstance: function(parentFormID) {
        return _instances[parentFormID];
    },

    /**
     * Adds a Form instance.
     * @param {String} parentFormID ID of the parent form
     */
    newInstance: function(parentFormID, instance) {
        if (instance instanceof RightNow.Form) {
            _instances[parentFormID] = instance;
        }
    },

    /**
     * Returns a collection of events that were created via _deferred.
     * @private
     * @param {String} parentFormID ID of the parent form
     * @return {Object} Events
     *      the names of each event as keys, with arrays of subscriber object:
     *       { handler: function, context: context }
     */
     getDeferredEvents: function(parentFormID) {
        var i, subscriber, events,
            eventList = {};
        for (i in _deferredObjects) {
            subscriber = _deferredObjects[i];
            if (_deferredObjects.hasOwnProperty(i) && subscriber.form === parentFormID && subscriber.events.length) {
                events = subscriber.events;
                for (var j = 0; j < events.length; j++) {
                    eventList[events[j].name] = eventList[events[j].name] || [];
                    eventList[events[j].name].push(events[j]);
                }
            }
        }
        return eventList;
    },

    /**
     * Return a list of deferred fields. These fields are added to the FormSubmission instance for usage
     * with `findField`.
     * @param {String} parentFormID ID of the parent form
     * @return {Object} A list of fields with structure {fieldName: fieldInstance}
     */
    getDeferredFields: function(parentFormID) {
        return _deferredFields[parentFormID];
    }
};
})();

var FormSubmission = (function() {
    var allFields = {},
        validatedFields = {};

    /**
    * Submits a GET form to a CP controller by converting
    * the form's fields to segments and navigating to the controller
    * via a hyperlink (in order to properly replicate the form's target).
    * @param {Object} form YUI form node
    * @param {String} action The form's action / URL to navigate to
    * @param {String} parametersToAdd any additonal url parameters to add
    * @param {Object} scope context to use for YUI instance
    * @private
    * assert - The form's method is GET and the form's default submittal
    * event has already been canceled.
    */
    function _submitCPGetForm(form, action, parametersToAdd, scope) {
        var fields = {},
            target = form.getAttribute("target");

        form.all('input, select, textarea').each(function(element) {
            var type = element.get("type"),
                name = element.get("name");
            if (name !== "" && type !== "submit" && type !== "image") {
                fields[element.get("name")] = element.get("value");
            }
        });

        var url = action + RightNow.Url.convertToSegment(fields) + parametersToAdd;
        if (RightNow.Url.getSession()) {
            url = RightNow.Url.addParameter(url, "session", RightNow.Url.getSession());
        }

        var link = scope.Y.Node.create("<a class='rn_Hidden'>form</a>").setAttribute("href", url);
        if (target) {
            link.setAttribute("target", target);
        }
        scope.Y.one(document.body).append(link);
        link = scope.Y.Node.getDOMNode(link);

        if (document.createEvent) {
            var click = document.createEvent("MouseEvents");
            click.initEvent("click", false, false);
            link.dispatchEvent(click);
        }
        else { /* IE */
            link.fireEvent("onclick");
        }
    }

    /**
     * Callback for the ajax request. Expects its scope to be a Form instance.
     * @private
     * @param {Object} responseObject Response from the server
     */
    function _serverResponse(responseObject) {
        this.fire("response", new RightNow.Event.EventObject(this, {data: responseObject}));
    }

    /**
     * Callback when the ajax request fails (non-200 response code).
     * @param  {Object} responseObject Ajax response data
     */
    function _serverErrorResponse(responseObject) {
        this.fire("responseError", new RightNow.Event.EventObject(this, {data: responseObject}));
    }

    function _sendFormRequest(url, scope, fields) {
        if(RightNow.Event.noSessionCookies()) {
            // Attempt to set a test login cookie if the form contains the contact login field.
            for(var i = 0, length = fields.length; i < length; i++) {
                if(fields[i].name === 'Contact.Login') {
                    document.cookie = "cp_login_start=1;path=/;secure";
                    break;
                }
            }
        }

        fields = RightNow.JSON.stringify(fields);
            var postData = {
                form: fields,
                updateIDs: RightNow.JSON.stringify({
                    "asset_id": RightNow.Url.getParameter("asset_id"),
                    "qid": RightNow.Url.getParameter("qid"),
                    "product_id": RightNow.Url.getParameter("product_id"),
                    "serial_no": RightNow.Url.getParameter("serial_no"),
                    "i_id": RightNow.Url.getParameter("i_id"),
                    "user_id": RightNow.Url.getParameter("user")
                })
            },
            requestOptions = {
                scope: scope,
                json: true,
                successHandler: _serverResponse,
                failureHandler: _serverErrorResponse
            },
            Form = RightNow.UI.Form;

        if (Form.smartAssistant != null) {
            postData.smrt_asst = Form.smartAssistant;
        }
        if (Form.smartAssistantToken !== null) {
            postData.saToken = Form.smartAssistantToken;
        }
        if (scope.data.attrs.flash_message) {
            postData.flash_message = scope.data.attrs.flash_message;
        }

        if (scope._originalEventObject) {
            if (scope._originalEventObject.data.timeout) {
                requestOptions.timeout = scope._originalEventObject.data.timeout;
            }

            requestOptions =
            (function(target, source) {
                var members = ["challengeHandler", "challengeHandlerContext"],
                    i, m;
                    for (i = 0; i < members.length; ++i) {
                        m = members[i];
                        target[m] = source[m] || undefined;
                    }
                return target;
            })(requestOptions, scope._originalEventObject);
        }
        RightNow.Form.formToken.onNewToken(function(token) {
            postData.f_tok = token;
            RightNow.Ajax.makeRequest(url, postData, requestOptions);
        });
    }
return {
     submitForm: function(formID, parametersToAdd, scope) {
        var form = scope.Y.one("#" + formID),
            method = form.getAttribute("method"),
            action = form.getAttribute("action"),
            fields = validatedFields[formID];

        parametersToAdd = parametersToAdd || "";

        if (action && !scope.data.attrs.on_success_url) {
            // Traditional form with "get" method, page flip with data in the URL
            if (method.toLowerCase() !== "post" && (RightNow.Text.beginsWith(action, "/") || !RightNow.Url.isExternalUrl(action))) {
                _submitCPGetForm(form, action, parametersToAdd, scope);
                return;
            }

            // Traditional form with "post", page flip with post data
            if (parametersToAdd) {
                form.set("action", action + parametersToAdd);
            }

            for(var i = 0, field, existingElement; i < fields.length; i++) {
                field = fields[i];
                if(!field || !field.value) continue;

                // element could be either an input element or a textarea element, so look for both
                existingElement = form.one('input[name="' + field.name + '"]') || form.one('textarea[name="' + field.name + '"]');

                // If the value is already on the form that we're going to submit, just ensure the field's value is the same as what we've validated (shouldn't ever differ)
                if(existingElement) {
                    existingElement.set('value', field.value);
                }
                else {
                    // escape the value if we're adding a hidden element
                    form.append(scope.Y.Node.create('<input type="hidden" name="' + field.name + '" value="' + scope.Y.Escape.html(field.value) + '"/>'));
                }
            }

            form.submit();
            return;
        }

        // Ajax post to the action specified on the form or the default endpoint
        _sendFormRequest((action || "/ci/ajaxRequest/sendForm") + parametersToAdd, scope, fields);
    },

    resetValidatedFields: function(formID) {
        validatedFields[formID] = [];
    },

    addValidatedField: function(formID, eventObject) {
        if (!validatedFields[formID]) {
            this.resetFormFields(formID);
        }
        delete eventObject.data.form;
        validatedFields[formID].push(eventObject.data);
    },

    getValidatedFields: function(formID) {
        return RightNow.Lang.cloneObject(validatedFields[formID]);
    },

    addField: function(formID, name, instance) {
        if(!allFields[formID]) {
            allFields[formID] = {};
        }

        allFields[formID][name] = instance;
    },

    findField: function(formID, name) {
        return allFields[formID][name];
    },

    getAllFields: function(formID) {
        return allFields[formID] || {};
    }
};
})();

/**
 * The RightNow.Form module is used by the FormSubmit widget to provide a generic interface for form submission events.
 * An instance of this module is provided to any widget's which extend from RightNow.Field via the parentForm() method.
 * The FormSubmit widget uses this module to handle AbuseDetection, form submission and form validation.
 * RightNow.Form publishes the following events. They can be subscribed via the `on` method and triggered via the `fire` method.
 *     'submit' - Triggered when a form submission is performed by the FormSubmit widget. This event should be used
 *                with a validation callback to ensure the widget extending from RightNow.Field has passed validation.
 *                The validation function should return false when the validation fails and an EventObject containing the
 *                data to be sent to the server when it succeeds. If all of the subscribed widgets pass validation,
 *                a validation:pass event is fired, otherwise a validation:fail event is fired.
 *     'validation:fail' - Triggered when a form has failed validation. The submission is canceled and each field must be
 *                         revalidated before submitting the form.
 *     'validation:pass' - Triggered when a form is successfully validated. The collected data is submitted to the server
 *                         and the response event is fired once received.
 *     'send' - The send event is triggered immediately before the form is submitted to the server and allow subscribers
 *              to halt the form submission by returning false.
 *     'response' - Triggered once the AJAX request has successfully completed. The page is typically redirected to the
 *                  FormSubmit widget's on_success_url.
 *     'responseError' - Triggered when a non-"200 OK" status is returned in response to the AJAX request.
 *     'collect' -  Fired during form submission to gather the fields which will be submitted with the form. The return value
 *                  of this event determines whether or not a value will be submitted with the form. When false is returned, the
 *                  field is excluded from the submission. When the field name is returned it is included.
 *     'formUpdated' - Fired when a field is added or removed from the form or when a constraint value on a field is altered.
 *
 * @requires RightNow.EventProvider
 * @constructor
 */
RightNow.Form = RightNow.EventProvider.extend(/**@lends RightNow.Form*/{
    overrides:/**@ignore*/{
        constructor: function() {
            this.parent();

            this._challengeDivID = this.data.attrs.challenge_location;

            this._parentForm = RightNow.UI.findParentForm(this.baseSelector);
            this.Y.one("#" + this._parentForm).on("submit", function(e) {
                e.halt(); // cancel the form's default behavior
            });

            this._errorMessageDiv = this.Y.one("#" + this.data.attrs.error_location);

            this._formButton = this.Y.one(this.baseSelector + "_Button");
            if(!this._errorMessageDiv) {
                this._errorMessageDiv = this.Y.Node.create("<div id='rn_" + this.instanceID + "_ErrorLocation'>");
                this._formButton.insert(this._errorMessageDiv, "before");
            }

            //Switch the deferred subscriber over to the actual FormSubmission instance
            if(this._parentForm) {
                if (FormConductor.getInstance(this._parentForm)) {
                    throw new Error("Can't have two different FormSubmits for a single form");
                }
                FormConductor.newInstance(this._parentForm, this);
            }
            else {
                RightNow.UI.addDevelopmentHeaderError(RightNow.Interface.getMessage('FORMSUBMIT_PLACED_FORM_UNIQUE_ID_MSG'));
                throw new Error("An inappropriate form was specified");
            }

            //Add deferred event subscribers to the EventProvider base class
            this._events = FormConductor.getDeferredEvents(this._parentForm);

            //Add deferred fields to the FormConductor
            this.Y.Object.each(FormConductor.getDeferredFields(this._parentForm), function(instance, fieldName) {
                FormSubmission.addField(this._parentForm, fieldName, instance);
            }, this);

            this._widgetInstantiationComplete = RightNow.Widgets.isWidgetInstantiationComplete();
            if(!this._widgetInstantiationComplete) {
                RightNow.Event.on('evt_WidgetInstantiationComplete', function() {
                    this._widgetInstantiationComplete = true;
                }, this);
            }

            this._addSubscribersFilter("submit", this._filterSubmittedWidgets, this);
            this._addEventHandler("submit", {
                pre: function(eo) {
                    RightNow.Form.formToken.requestNewToken();
                    FormSubmission.resetValidatedFields(this._parentForm);
                    if (this._challengeDivID) {
                        eo.challengeHandler = RightNow.Event.createDelegate(this, this._challengeHandler);
                    }
                    this._originalEventObject = eo;
                },
                during: function(eo) {
                    if (eo === false) {
                        this._eventCanceled = true;
                    }
                    else if (eo instanceof RightNow.Event.EventObject) {
                        FormSubmission.addValidatedField(this._parentForm, eo);
                    }
                },
                post: function(eo) {
                    var whichOne = (this._eventCanceled ? "fail" : "pass");
                    this._eventCanceled = false;
                    this.fire("validation:" + whichOne, eo);
                }
            })._addEventHandler("send", {
                post: function(eo) {
                    if (!this._eventCanceled) {
                        FormSubmission.submitForm(this._parentForm, this.data.attrs.add_params_to_url, this);
                    }
                    this._eventCanceled = false;
                },
                during: function(eo) {
                    if (eo === false) {
                        this._eventCanceled = true;
                    }
                }
            })._addEventHandler("collect", {
                pre: function() {
                    this._collectedFields = [];
                },
                during: function(fieldName) {
                    if(fieldName) {
                        this._collectedFields.push(fieldName);
                    }
                },
                post: function(eo) {
                    this.fire("submit", eo);
                }
            })._addEventHandler("formUpdated");

            if(this.data.js.challengeProvider) {
                try {
                    this._challengeProvider = eval(this.data.js.challengeProvider);
                }
                catch (ex) {
                    throw "Failed while trying to parse a challenge provider.  " + ex;
                }
                this._createChallengeDiv();
                this._challengeProvider.create(this._challengeDivID, RightNow.UI.AbuseDetection.options);
                this.on("submit", this._onValidateChallengeResponse, this);
            }

            if (!this.data.js.f_tok) throw new Error("This widget is required to have a `f_tok` form submission token");

            RightNow.Form.formToken.init(this.data.js.f_tok, this.data.js.formExpiration);
        }
    },

    /**
     * Used to filter the list of subscribed widgets for the submit event when a widget is
     * hidden using the hide / show field functionality.
     * @param {Array} subscribers A list of the subscribed event objects
     * @return {Array} A filtered subscriber list
     */
    _filterSubmittedWidgets: function(subscribers) {
        var allFields = FormSubmission.getAllFields(this._parentForm),
            excludedIndices = [];

        //Exclude all of the field-backed subscribers that are not collected
        this.Y.Object.each(allFields, function(instance, fieldName) {
            this.Y.Array.each(subscribers, function(subscriber, index) {
                if(subscriber.context === instance && this.Y.Array.indexOf(this._collectedFields, fieldName) === -1) {
                    excludedIndices.push(index);
                }
            }, this);
        }, this);

        return excludedIndices;
    },

    /**
    * Returns all fields in the current form.
    * @return {Array} All fields to be submitted
    */
    getValidatedFields: function() {
        return FormSubmission.getValidatedFields(this._parentForm);
    },

    /**
     * Add a field to the form
     * @param {String} name The name of the field (e.g. Incident.CustomFields.c.int1)
     * @param {Object} widgetInstance The widget instance to add to the form
     */
    addField: function(name, widgetInstance) {
        FormSubmission.addField(this._parentForm, name, widgetInstance);
    },

    /**
     * Find a field within the form
     * @param {String} name The name of the field (e.g. Incident.Subject)
     * @return {Object} The RightNow.Field instance
     */
    findField: function(name) {
        if(!this._widgetInstantiationComplete) {
            throw new Error("Widget instantiation has not completed. This method can only be used after all widgets are constructed");
        }

        return FormSubmission.findField(this._parentForm, name);
    },

    /**
     * Hide the form on the page.
     */
    hide: function() {
        RightNow.UI.hide(this._formButton);
    },

    /**
     * Show the form on the page.
     */
    show: function() {
        RightNow.UI.show(this._formButton);
    },

    /**
     * Disable the form button.
     */
    disable: function() {
        this._formButton.set('disabled', true);
    },

    /**
     * Enable the form button.
     */
    enable: function() {
        this._formButton.set('disabled', false);
    },

    /**
     * Ensure that a div exists to display the abuse challenge in.
     * @private
     */
    _createChallengeDiv: function() {
        this.Y.one("#" + this._challengeDivID) || this._formButton.insert(this.Y.Node.create("<div id='" + this._challengeDivID + "' tabindex='-1'>"), 'before');
    },

    /**
     * Report an incorrect or absent abuse challenge response.
     * @private
     * @param {String=} errorMessage Error message to display (optional)
     */
    _reportChallengeError: function(errorMessage) {
        errorMessage = errorMessage || RightNow.Interface.getMessage("PLS_VERIFY_REQ_ENTERING_TEXT_IMG_MSG");
        var errorLinkAnchorID = "rn_ChallengeErrorLink";

        this._errorMessageDiv.append("<div><b><a id ='" + errorLinkAnchorID + "' href='javascript:void(0);'>" + errorMessage + "</a></b></div>");
        this.Y.one("#" + errorLinkAnchorID).on("click", RightNow.Event.createDelegate(this, function() {
            this._challengeProvider.focus(this._challengeDivID);
            return false;
        }));
    },

    /**
     * Called back by the RightNow.Ajax layer when it determines that the server responded that a challenge is required.
     * @private
     * @param abuseResponse An object returned by the server containing the challenge provider script.
     * @param requestObject The original request object
     * @param isRetry A boolean indicating if the the server said that the request contained an incorrect challenge response.
     */
    _challengeHandler: function(abuseResponse, requestObject, isRetry) {
        this._createChallengeDiv();

        if (!this._challengeProvider) {
            this._challengeProvider = RightNow.UI.AbuseDetection.getChallengeProvider(abuseResponse);
            this.on("submit", this._onValidateChallengeResponse, this);
            this._challengeProvider.create(this._challengeDivID, RightNow.UI.AbuseDetection.options);
        }

        this._reportChallengeError(RightNow.UI.AbuseDetection.getDialogCaption(abuseResponse));
        this.fire("validation:fail");
    },

    /**
     * Event handler for form validation.
     * @private
     */
    _onValidateChallengeResponse: function() {
        var eo = new RightNow.Event.EventObject(this, {data: {form: false}});
        //Challenges have no data to be passed with the form
        var inputs = this._challengeProvider.getInputs(this._challengeDivID);
        if (inputs.abuse_challenge_response) {
            for (var key in inputs)  {
                if (inputs.hasOwnProperty(key)) {
                    RightNow.Ajax.addRequestData(key, inputs[key]);
                }
            }
        }
    }
}, /**@lends RightNow.Form*/{
    /**
     * Static function on RightNow.Form that finds a particular form instance.
     * @param {String} elementID DOM id of the element to use to find parent form
     * @param {String} instanceID Instance id of the widget subscribing
     * @param {boolean} returnFormID Denotes if form ID should be returned instead of FormConductor object
     * @return {Object} A form object instance to subscribe or fire events on
     */
    find: function(elementID, instanceID, returnFormID) {
        var parentForm = RightNow.UI.findParentForm(elementID);
        if (parentForm) {
            return returnFormID ? parentForm : FormConductor.getDeferred(parentForm, instanceID);
        }
        throw new Error("You're using a form that doesn't have a proper form submit button or an id");
    },

    /**
     * Provides the interface for handling the form token.
     * @return {Object} Object literal containing functions
     */
    formToken: (function() {
        var token, expireInterval, expireTime, subscribers = [], subscribedToTokenResponse;

        /**
         * Calls any awaiting subscribers with the new token
         * as the first argument. Empties out the subscriber
         * queue.
         * @ignore
         */
        function notifySubscribers() {
            for (var i = 0, len = subscribers.length, subscriber; i < len; i++) {
                subscriber = subscribers[i];
                subscriber.callback.call(subscriber.context, token);
            }
            subscribers = [];
        }

        /**
         * Sets the timestamp when the current token
         * will expire.
         * @param {Number} interval Number of milliseconds
         * until the token expires
         * @ignore
         */
        function setExpireTime(interval) {
            expireTime = new Date().getTime() + interval;
        }

        /**
         * RightNow.Event doesn't have a subscribe once mechanism.
         * So keep track and only subscribe to the token response
         * event once if #init is called multiple times.
         */
        function subscribeToTokenResponse() {
            if (!subscribedToTokenResponse) {
                RightNow.Event.on('evt_formTokenUpdate', reUp);
                subscribedToTokenResponse = true;
            }
        }

        /**
         * Fires the token request event with the
         * current token.
         * @ignore
         */
        function demandReUp() {
            RightNow.Event.fire('evt_formTokenRequest',
                new RightNow.Event.EventObject(
                    { instanceID: 'RightNow.Form' },
                    { data: { formToken: token }}
                )
            );
        }

        /**
         * Callback when a refreshed token arrives.
         * @param  {String} name Event name
         * @param  {Array} args Event objects
         * @ignore
         */
        function reUp(name, args) {
            var eventObject = args[0];

            if (eventObject.data.newToken && (!eventObject.w_id || eventObject.w_id === 'RightNow.Form')) {
                token = eventObject.data.newToken;
                setExpireTime(expireInterval);

                notifySubscribers();
            }
        }

        /**
         * Determines whether the current token
         * has expired.
         * @return {boolean} T if expired, F if not
         * @ignore
         */
        function expired() {
            return new Date().getTime() >= expireTime;
        }

        return {
            /**
             * Should be called during Form instance construction.
             * @param  {String} newToken   Form token
             * @param  {Number} expiration Number of milliseconds the token's
             *                             good for
             */
            init: function(newToken, expiration) {
                token = newToken;
                expireInterval = expiration;
                setExpireTime(expireInterval);
                subscribeToTokenResponse();
            },

            /**
             * Initiates the request for a new token,
             * if the current token is expired.
             */
            requestNewToken: function() {
                if (expired()) demandReUp();
            },

            /**
             * Adds a one-time subscriber to be notified when a new token
             * arrives.
             * @param  {Function} callback Function to call when
             *                             a new token arrives;
             *                             if the token hasn't expired,
             *                             this is called asynchronously
             * @param {Object=} context `this` context for callback; defaults
             *                          to global context if not specified
             */
            onNewToken: function(callback, context) {
                if (expired()) {
                    subscribers.push({ callback: callback, context: context });
                }
                else {
                    setTimeout(function() { callback.call(context, token); }, 1);
                }
            }
        };
    })()
});
})();
