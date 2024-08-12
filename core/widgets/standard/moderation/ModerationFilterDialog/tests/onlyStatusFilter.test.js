UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ModerationFilterDialog_0',
    subInstanceIDs: ['ModerationStatusFilter_1']
}, function(Y, widget, baseSelector) {
    var moderationFilterDialogTests = new Y.Test.Suite({
        name: "standard/moderation/ModerationFilterDialog"
    });

    moderationFilterDialogTests.add(new Y.Test.Case({
        name: "Ensure widget functions correctly when either the cancel button or close link are clicked",
        /**
         * Test to ensure that the widget functions correctly
         * when either the cancel button or close link are clicked.
         */

        setUp: function() {
            this.errorMessage = '<b> <a href="javascript:void(0);" onclick="document.getElementById("rn_ModerationDateFilter_0_EditedOnFrom").focus(); return false;"> From Date should be in the format mm/dd/yyyy </a> </b>';
            this.closeBtn = this.findButtonByTextContent('close');
            this.applyBtn = this.findButtonByTextContent('apply');
        },

        triggerDialog: function() {
            var newNode = Y.Node.create('null');
            widget._openDialog({target: newNode});
        },

        fireEvent: function(eventName, includeErrors) {
            this.dataToInclude = {report_id: widget.data.attrs.report_id};
            if(includeErrors) {
                this.dataToInclude.errors = [this.errorMessage];
            }

            RightNow.Event.fire(eventName, new RightNow.Event.EventObject(widget, {data: this.dataToInclude}));
        },

        validateSearchErrors: function(shouldPass, searchInvoked) {
            if(!shouldPass) {
                Y.Assert.areNotEqual("", Y.all(".rn_ErrorMessage").getHTML());
            }
            else {
                Y.Assert.areEqual("", Y.all(".rn_ErrorMessage").getHTML());
            }

            Y.Assert.areSame(shouldPass, searchInvoked);
        },

        checkDialog: function() {
            this.dialog = Y.one("#rnDialog1");
            Y.assert(!this.dialog.ancestor('.yui3-panel-hidden'));
        },

        findButtonByTextContent: function(text) {
            var types = ['button', 'a', 'span', 'div'];

            Y.Array.some(types, function(type) {
                return Y.all(type).some(function(clickable) {
                    if(clickable.get('innerHTML') && (clickable.get('innerHTML').toLowerCase().indexOf(text.toLowerCase()) > -1)) {
                        this.returnVal = clickable;
                        return true;
                    }
                }, this);
            }, this);
            if(this.returnVal) return this.returnVal;

            Y.Assert.fail('Could not find text button');
        },

        testCloseLinkCancelButtons: function() {
            this.triggerDialog();
            this.checkDialog();

            widget.searchSource().once('reset', function(type, args) {
                Y.Assert.areSame("all", args[0].data.name);
            });
            widget._cancelFilters();

            this.triggerDialog();
            this.checkDialog();
            widget._closeDialog();
            Y.assert(this.dialog.ancestor('.yui3-panel-hidden'));
        },

        testModerationDateFilterValidationEventOnApply: function() {
            dateFilterValidateCallback = function(evt, args) {
                Y.Assert.areSame(args[0].data.report_id, widget.data.attrs.report_id);
            };

            this.fireEvent('evt_moderationCustomDateFilterEnabled', false);
            this.triggerDialog();
            this.checkDialog();

            RightNow.Event.subscribe('evt_validateModerationDateFilter', dateFilterValidateCallback, widget);
            this.applyBtn.simulate('click');

            Y.assert(this.dialog.ancestor('.yui3-panel-hidden'));
            RightNow.Event.unsubscribe('evt_validateModerationDateFilter', dateFilterValidateCallback, widget);
            this.closeBtn.simulate('click');
        },

        testDateFilterValidationErrorsDisplay: function() {
            var searchInvoked = false;
            widget.searchSource().once('search', function() {
                searchInvoked = true;
            }, widget);

            this.fireEvent("evt_moderationCustomDateFilterEnabled", false);
            this.triggerDialog();

            this.checkDialog();
            this.fireEvent("evt_moderationDateFilterValidated", true);
            this.validateSearchErrors(false, searchInvoked);
        },

        testSearchIfNoDateFilterErrors: function() {
            var searchInvoked = false;
            widget.searchSource().once('search', function() {
                searchInvoked = true;
            }, widget);

            this.fireEvent("evt_moderationCustomDateFilterEnabled", false);
            this.triggerDialog();

            this.checkDialog();
            this.validateSearchErrors(false, searchInvoked);
        },

        testSearchIfNoDateFilterEnabled: function() {
            var searchInvoked = false;
            widget.searchSource().once('search', function() {
                searchInvoked = true;
            }, widget);

            this.triggerDialog();
            this.checkDialog();
            this.applyBtn.simulate('click');

            this.triggerDialog();
            this.checkDialog();
            this.validateSearchErrors(true, searchInvoked);
        }
    }));

    return moderationFilterDialogTests;
}).run();
