UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SiteFeedback_0'
}, function(Y, widget, baseSelector){
    var siteFeedbackTests = new Y.Test.Suite({
            name: "standard/feedback/SiteFeedback",
            setUp: function(){
                var testExtender = {
                    initValues : function() {
                        this.baseID = baseSelector + '_';
                    },

                    _getDialogButton: function(index)
                    {
                        return Y.all('#rnDialog1 .yui3-widget-ft button').item(index);
                    },

                    cancelButton: function()
                    {
                        return this._getDialogButton(1);
                    },

                    submitButton: function()
                    {
                        return this._getDialogButton(0);
                    },

                    checkEventParameters: function(eventName, type, args)
                    {
                        Y.Assert.areSame(eventName, type);
                        Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                        Y.Assert.isObject(args.filters);
                        Y.Assert.areSame(widget.instanceID, args.w_id);
                    },

                    getResponse: function(response, id)
                    {
                        RightNow.Event.subscribe('evt_siteFeedbackSubmitResponse', this.responseEventHandler, this);

                        widget._onResponseReceived(response, new RightNow.Event.EventObject(widget, {
                            data: {
                                rate: 0,
                                email: this.email,
                                message: this.message,
                                a_id: null
                            }
                        }));

                        Y.assert(this.responseEventHandler.called);

                        var confirmationDialogContainer = Y.one('#rnDialog' + id);
                        if (!confirmationDialogContainer) {
                            return null;
                        }

                        var dialogMessage = confirmationDialogContainer.one('#rn_Dialog_' + id + '_Message').getHTML();
                        confirmationDialogContainer.all('button').item(1).simulate('click');
                        Y.assert(confirmationDialogContainer.ancestor('.yui3-panel-hidden'));
                        return dialogMessage;
                    }
                };

                for(var item in this.items)
                {
                    Y.mix(this.items[item], testExtender);
                }
            }
        });

    siteFeedbackTests.add(new Y.Test.Case(
    {
        name : "Open and Close Dialog Test",

        /**
         * Opens dialog, ensures it gets created, and closes dialog using both the
         * cancel button and the 'X' button.
         */
        testSimpleOpenAndClose: function()
        {
            this.initValues();

            Y.one("#rn_SiteFeedback_0_FeedbackLink").simulate('click');

            //Verify that the dialog is visible
            var dialogContainer = Y.one('#rnDialog1');
            Y.assert(!dialogContainer.ancestor('.yui3-panel-hidden'));

            //Close dialog using 'X' link
            dialogContainer.one('button').simulate('click');
            Y.assert(dialogContainer.ancestor('.yui3-panel-hidden'));

            //Re-open dialog
            Y.one("#rn_SiteFeedback_0_FeedbackLink").simulate('click');
            Y.assert(!dialogContainer.ancestor('.yui3-panel-hidden'));

            //Close dialog with 'Cancel' button
            this.cancelButton().simulate('click');
            Y.assert(dialogContainer.ancestor('.yui3-panel-hidden'));
        }
    }));

    siteFeedbackTests.add(new Y.Test.Case(
    {
        name: "Error Validation",
        /**
         * Submits form with invalid data and ensures correct number validation errors appear.
         */
        testValidationErrors: function()
        {
            this.initValues();

            //Clear out any existing values (can be set from cookie)
            var emailInput = Y.one(this.baseID + "EmailInput").set('value', ""),
                feedbackInput = Y.one(this.baseID + "FeedbackTextarea").set('value', "");

            Y.one(this.baseID + "FeedbackLink").simulate('click');

            var errorMessageContainer = Y.one(this.baseID + 'ErrorMessage');
            Y.Assert.areSame(widget.data.attrs.label_dialog_title, Y.one('#rn_Dialog_1_Title').getHTML());
            Y.Assert.areSame(widget.data.attrs.label_send_button, this.submitButton().getHTML());
            Y.Assert.areSame(widget.data.attrs.label_cancel_button, this.cancelButton().getHTML());
            Y.Assert.areSame(0, errorMessageContainer.all('*').size());

            //Submit with no fields filled in
            this.submitButton().simulate('click');
            Y.Assert.areSame(4, errorMessageContainer.get('children').size());

            //Submit with invalid email filled in
            emailInput.set('value', "a");
            this.submitButton().simulate('click');
            Y.Assert.areSame(4, errorMessageContainer.get('children').size());

            //Submit with just "whitespace" in feedback field
            feedbackInput.set('value', " \n");
            this.submitButton().simulate('click');
            Y.Assert.areSame(4, errorMessageContainer.get('children').size());

            //Submit with valid email
            emailInput.set('value', "a@example.com");
            this.submitButton().simulate('click');
            Y.Assert.areSame(2, errorMessageContainer.get('children').size());

            //Submit with feedback and no email
            emailInput.set('value', "");
            feedbackInput.set('value', "feedback");
            this.submitButton().simulate('click');
            Y.Assert.areSame(2, errorMessageContainer.get('children').size());
            feedbackInput.set('value', "");
            this.cancelButton().simulate('click');
        }
    }));

    siteFeedbackTests.add(new Y.Test.Case(
    {
        name: "Feedback Submission",

        setUp: function()
        {
            this._origMakeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = Y.bind(this.makeRequestMock, this);

            this.email = "a@example.com",
            this.message = "Unit Test Feedback";
        },

        tearDown: function()
        {
            RightNow.Ajax.makeRequest = this._origMakeRequest;
            this._origMakeRequest = null;
            this.makeRequestMock.calledWith = null;

            this.email = null;
            this.message = null;
        },

        "User input is sent to the server": function()
        {
            this.initValues();

            Y.one(this.baseID + "FeedbackLink").simulate('click');

            Y.one(this.baseID + "EmailInput").set('value', this.email);
            Y.one(this.baseID + "FeedbackTextarea").set('value', this.message);

            RightNow.Event.subscribe('evt_siteFeedbackRequest', this.requestEventHandler, this);

            this.submitButton().simulate('click');

            Y.assert(this.requestEventHandler.called);

            var request = this.makeRequestMock.calledWith;
            Y.Assert.areSame(widget.data.attrs.submit_site_feedback_ajax, request[0]);
            Y.assert(request[1].f_tok);
            Y.Assert.isNull(request[1].a_id);
            Y.Assert.areSame(this.email, request[1].email);
            Y.Assert.areSame(this.message, request[1].message);
            Y.Assert.areSame(0, request[1].rate);
            Y.assert(this.submitButton().get('disabled'));
            Y.assert(this.cancelButton().get('disabled'));
        },

        "Confirm confirmation dialog is displayed upon server response": function()
        {
            Y.Assert.areSame(widget.data.attrs.label_feedback_confirmation, this.getResponse({ID: 123}, 2));
            Y.assert(Y.one('#rnDialog1').ancestor('.yui3-panel-hidden'));
        },

        "Confirm a response error is displayed": function()
        {
            var error = 'Something bad happended';
            Y.Assert.areSame(error, this.getResponse({error: error}, 3));
        },

        "Confirm error dialog is not displayed when response is undefined": function()
        {
            Y.Assert.isNull(this.getResponse(null, 4));

        },

        makeRequestMock: function ()
        {
            this.makeRequestMock.calledWith = Array.prototype.slice.call(arguments);
        },

        requestEventHandler: function(type, args)
        {
            args = args[0];
            Y.Assert.areSame("evt_siteFeedbackRequest", type);
            Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
            Y.Assert.isObject(args.filters);
            Y.Assert.areSame(widget.instanceID, args.w_id);
            Y.Assert.isNull(args.data.a_id);
            Y.Assert.areSame(this.email, args.data.email);
            Y.Assert.areSame(this.message, args.data.message);
            Y.Assert.areSame(0, args.data.rate);
            this.requestEventHandler.called = true;
        },

        responseEventHandler: function(type, args)
        {
            args = args[0];
            Y.Assert.areSame("evt_siteFeedbackSubmitResponse", type);
            Y.Assert.isObject(args.data.filters);
            Y.Assert.areSame(widget.instanceID, args.data.w_id);
            Y.Assert.isNull(args.data.data.a_id);
            Y.Assert.areSame(this.email, args.data.data.email);
            Y.Assert.areSame(this.message, args.data.data.message);
            Y.Assert.areSame(0, args.data.data.rate);
            this.responseEventHandler.called = true;
        }
    }));
    return siteFeedbackTests;
});
UnitTest.run();
