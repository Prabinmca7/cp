UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'AnswerFeedback_0'
}, function(Y, widget, baseSelector){
    var instanceCount = 0;

    var answerFeedbackTests = new Y.Test.Suite({
        name: "standard/feedback/AnswerFeedback",
        setUp: function(){
            //Inject some dialog CSS rules to make things display better
            var css = document.createElement("style");
            css.type = "text/css";
            css.innerHTML = ".yui3-panel {position: absolute} .yui3-panel-content {background: white} .yui3-panel-hidden {visibility: hidden}";
            document.head.appendChild(css);

            var testExtender = {
                createInstance : function() {
                    this.initValues();

                    // Generate a new instance if not the first instance, in order to circumvent purged buttons
                    if (instanceCount >= 1) {
                        this.widgetData = this.instance.data;
                        this.instance = new RightNow.Widgets.AnswerFeedback(this.widgetData, this.instanceID, Y);
                    }
                    ++instanceCount;
                },

                initValues : function() {
                    this.instanceID = 'AnswerFeedback_0';
                    this.baseID = 'rn_' + this.instanceID + '_';
                    this.ratingCell = this.baseID + 'RatingCell_';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.optionsCount = this.widgetData.attrs.options_count;
                    this.feedbackControls = Y.one('#' + this.baseID + ((this.widgetData.js.buttonView || this.widgetData.attrs.use_rank_labels) ? "RatingButtons" : "RatingMeter"));
                },

                _getDialogButton: function(index)
                {
                    return Y.all("#rnDialog" + (instanceCount) + " .yui3-widget-ft button").item(index);
                },

                submitButton: function()
                {
                    return this._getDialogButton(0);
                },

                cancelButton: function()
                {
                    return this._getDialogButton(1);
                },

                triggerDialog: function()
                {
                    var trigger = this.optionsCount === 2
                        ? baseSelector + "_RatingNoButton"
                        : baseSelector + "_RatingCell_" + this.widgetData.attrs.dialog_threshold;

                    Y.one(trigger).simulate('click');
                },

                resetControls: function(){
                    Y.one('.rn_AnswerFeedbackControl').append(this.feedbackControls);
                    this.instance._dialog.hide();
                }
            };

            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    answerFeedbackTests.add(new Y.Test.Case(
    {
        name : "Test: Correct Display for given Input",
        'Verify that the correct number of option buttons appear': function() {
            this.initValues();

            if (this.optionsCount === 2) {
                Y.Assert.isNotNull(Y.one(baseSelector + '_RatingYesButton'));
                Y.Assert.isNotNull(Y.one(baseSelector + '_RatingNoButton'));
            }
            else if (this.optionsCount > 2 && this.optionsCount <= 5) {
                for (var i = 1; i <= this.optionsCount; ++i) {
                    Y.Assert.isNotNull(document.getElementById(this.ratingCell + i));
                }
            }
            else {
                Y.Assert.fail("The options_count attribute is improperly set, or the rating cells/buttons did not appear");
            }
        },

        'Verify that the stars highlight on mouseover': function() {
            this.initValues();

            if (this.optionsCount <= 2) return;

            var className = 'rn_RatingCellOver',
                i, j, k;

            // Test from 0 to count and that classes are added or removed properly
            for (i = 1; i <= this.optionsCount; ++i) {
                Y.one('#' + this.ratingCell + i).simulate('mouseover');

                for (j = 1; j <= i; ++j) {
                    Y.Assert.isTrue(Y.one('#' + this.ratingCell + j).hasClass(className));
                }

                for (k = i + 1; k <= this.optionsCount; ++k) {
                    Y.Assert.isFalse(Y.one('#' + this.ratingCell + k).hasClass(className));
                }
            }

            // Test from count to zero and that classes are added and removed properly
            for (i = this.optionsCount; i > 0; --i) {
                Y.one('#' + this.ratingCell + i).simulate('mouseover');

                for (j = i; j > 0; --j) {
                    Y.Assert.isTrue(Y.one('#' + this.ratingCell + j).hasClass(className));
                }

                for (k = i + 1; k <= this.optionsCount; ++k) {
                    Y.Assert.isFalse(Y.one('#' + this.ratingCell + k).hasClass(className));
                }
            }
        }
    }));

    answerFeedbackTests.add(new Y.Test.Case(
    {
        name : "Open and Close the Dialog",

        'Verify that controls are hidden after user interacts with controls': function(){
            this.createInstance();
            Y.Assert.isNotNull(Y.one('#' + this.feedbackControls.get('id')));
            this.triggerDialog();
            Y.Assert.isNull(Y.one('#' + this.feedbackControls.get('id')));
            var thanksSpan = Y.one('.rn_ThanksLabel');
            Y.Assert.isObject(thanksSpan);
            Y.Assert.areSame(thanksSpan.get('innerHTML'), this.widgetData.attrs.label_feedback_thanks);
            this.resetControls();
        },

        'Verify that the dialog displays when a user clicks no or select below the threshold': function() {
            this.createInstance();

            this.triggerDialog();

            //Verify that the dialog is visible
            var dialogContainer = Y.one('#rnDialog' + instanceCount);
            Y.assert(!dialogContainer.ancestor('.yui3-panel-hidden'));

            //Close dialog using 'Cancel' button
            this.cancelButton().simulate('click');
            Y.assert(dialogContainer.ancestor('.yui3-panel-hidden'));

            this.resetControls();
            this.createInstance();
            this.triggerDialog();

            dialogContainer = Y.one('#rnDialog' + instanceCount);
            Y.assert(!dialogContainer.ancestor('.yui3-panel-hidden'));

            //Close dialog with 'X' link
            dialogContainer = Y.one('#rnDialog' + instanceCount);
            dialogContainer.one('button').simulate('click');
            Y.assert(dialogContainer.ancestor('.yui3-panel-hidden'));

            this.resetControls();
        }
    }));

    answerFeedbackTests.add(new Y.Test.Case(
    {
        name: "Error Validation",

        'Verify that validation errors occur when the dialog is populated incorrectly': function(){
            this.createInstance();
            this.triggerDialog();

            //Clear out any existing values (can be set from cookie)
            var emailInput = Y.one(baseSelector + "_EmailInput").set('value', '');

            var errorMessageContainer = Y.one(baseSelector + '_ErrorMessage');
            Y.Assert.areSame(0, errorMessageContainer.get('children').size());

            //Submit with no fields filled in
            this.submitButton().simulate('click');
            Y.Assert.areSame(4, errorMessageContainer.get('children').size());

            //Submit with invalid email filled in
            emailInput.set('value', "a");
            this.submitButton().simulate('click');
            Y.Assert.areSame(4, errorMessageContainer.get('children').size());

            //Submit with just "whitespace" in feedback field
            Y.one(baseSelector + "_FeedbackTextarea").set('value', " \n");
            this.submitButton().simulate('click');
            Y.Assert.areSame(4, errorMessageContainer.get('children').size());

            //Submit with valid email only a valid email
            emailInput.set('value', "a@example.com");
            this.submitButton().simulate('click');

            Y.Assert.areSame(2, errorMessageContainer.get('children').size());
            emailInput.set('value', "");
            this.submitButton().simulate('click');

            this.resetControls();
        },

        "Verify display of errors as links and text": function(){
            this.createInstance();

            this.triggerDialog();
            var errorDisplay = Y.one(widget.baseSelector + "_ErrorMessage");
            errorDisplay.set('innerHTML', '');

            widget._addErrorMessage('stuff');
            Y.Assert.areSame('<h2 role="alert">Error</h2>stuff', errorDisplay.get('innerHTML'));

            errorDisplay.set('innerHTML', '');

            widget._addErrorMessage('other stuff', widget.baseSelector + "_EmailInput");
            var childLink = errorDisplay.one('a');
            Y.Assert.isNotNull(childLink);
            Y.Assert.areSame('other stuff', childLink.get('innerHTML'));
            Y.Assert.areSame("document.getElementById('" + widget.baseSelector + "_EmailInput').focus(); return false;", childLink.getAttribute('onclick'));

            this.resetControls();
        }
    }));

    answerFeedbackTests.add(new Y.Test.Case(
    {
        name: "Feedback Submission",

        setUp: function()
        {
            this.email = "a@example.com",
            this.message = "Unit Test Feedback";
        },

        tearDown: function()
        {
            this.email = null;
            this.message = null;
        },

        'User input is sent to the server': function(){
            this.createInstance();
            var trigger = this.optionsCount === 2
                ? baseSelector + "_RatingNoButton"
                : "#" + this.ratingCell + this.widgetData.attrs.dialog_threshold;

            var expectedPostData = {
                a_id: null,
            };

            UnitTest.overrideMakeRequest(widget.data.attrs.submit_rating_ajax, expectedPostData);
            UnitTest.overrideMakeRequest('/ci/ajaxRequest/getNewFormToken');
            
            Y.one(trigger).simulate('click');

            Y.one(baseSelector + "_EmailInput").set('value', this.email);
            Y.one(baseSelector + "_FeedbackTextarea").set('value', this.message);

            expectedPostData = {
                a_id: null,
                f_tok: UnitTest.NO_VALUE,
                email: this.email,
                message: this.message,
                rate: 1
            };

            UnitTest.overrideMakeRequest(widget.data.attrs.submit_feedback_ajax, expectedPostData);
            UnitTest.overrideMakeRequest('/ci/ajaxRequest/getNewFormToken');
            
            this.submitButton().simulate('click');

            Y.assert(this.submitButton().get('disabled'));
            Y.assert(this.cancelButton().get('disabled'));

            this.resetControls();
        },

        "Confirm dialog is displayed upon server response": function(){
            RightNow.Event.subscribe('evt_answerFeedbackResponse', this.responseEventHandler, this);

            widget._incidentCreateFlag = true;

            widget._onResponseReceived({ ID: 'dance' }, new RightNow.Event.EventObject(widget, {
                data: {
                    rate: 0,
                    email: this.email,
                    message: this.message,
                    a_id: null
                }
            }));

            Y.Assert.isTrue(this.responseEventHandler.called);
            //Dialog should auto close on success response
            Y.Assert.isFalse(this.instance._dialog.get('visible'));
            this.resetControls();
        },

        "Confirm error message is diaplayed upon server failure response": function(){
            RightNow.Event.subscribe('evt_answerFeedbackResponse', this.responseEventHandler, this);

            widget._incidentCreateFlag = true;

            widget._onResponseReceived({ error: 'error message from server' }, new RightNow.Event.EventObject(widget, {
                data: {
                    rate: 0,
                    email: this.email,
                    message: this.message,
                    a_id: null
                }
            }));

            Y.Assert.isTrue(this.responseEventHandler.called);
            Y.Assert.areSame('<h2 role="alert">Error</h2>error message from server', Y.one(widget.baseSelector + "_ErrorMessage").get('innerHTML'));
        },

        requestEventHandler: function(type, args)
        {
            Y.Assert.areSame("evt_answerFeedbackRequest", type);
            args = args[0];
            Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
            Y.Assert.isObject(args.filters);
            Y.Assert.areSame(this.instanceID, args.w_id);
            Y.Assert.isNull(args.data.a_id);
            Y.Assert.areSame(this.email, args.data.email);
            Y.Assert.areSame(this.message, args.data.message);
            this.requestEventHandler.called = true;
        },

        responseEventHandler: function(type, args)
        {
            Y.Assert.areSame("evt_answerFeedbackResponse", type);
            Y.Assert.isObject(args[0]);
            args = args[1];
            Y.Assert.isObject(args.filters);
            Y.Assert.areSame(widget.instanceID, args.w_id);
            Y.Assert.isNull(args.data.a_id);
            Y.Assert.areSame(this.email, args.data.email);
            Y.Assert.areSame(this.message, args.data.message);
            this.responseEventHandler.called = true;
        }
    }));

    return answerFeedbackTests;
});
UnitTest.run();
