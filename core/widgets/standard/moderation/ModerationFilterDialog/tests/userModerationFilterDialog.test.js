UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ModerationFilterDialog_0',
    subInstanceIDs: 'ModerationStatusFilter_1'
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

        validateSearchErrors: function(shouldPass, searchInvoked) {
            if(!shouldPass) {
                Y.Assert.areNotEqual("", this.Y.all(".rn_ErrorMessage").getHTML());
            }
            else {
                Y.Assert.isNotUndefined(Y.all(".rn_ErrorMessage")) ;
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

        testSearch: function() {
            var searchInvoked = false;

            widget.searchSource().on('search', function (evt, args) {
                return false;
            });
            widget.searchSource().on('response', function (evt, args) {
                searchInvoked = true;
            });
            this.triggerDialog();
            this.checkDialog();
            this.applyBtn.simulate('click');

            Y.assert(this.dialog.ancestor('.yui3-panel-hidden'));
            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: {
                filters: {
                    page: {
                        value: 0
                    },
                    limit: {
                        value: 10
                    }
                },
                searchResults: {
                    page: 1,
                    pageMore: 1
                }
            }}));

            this.triggerDialog();
            this.checkDialog();
            this.validateSearchErrors(true, searchInvoked);

        }
    }));
    return moderationFilterDialogTests;
}).run();
