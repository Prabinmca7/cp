/**
 * Synopsis:
 * - Logged-in social user who is the author of the question.
 */

UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionDetail_0',
    jsFiles: [
	'/euf/core/thirdParty/js/ORTL/ortl.js'],
    subInstanceIDs: ['FileListDisplay_8']
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionDetail - Base functionality tests"
    });

    function clickEdit() {
        Y.one('.rn_EditQuestionLink').simulate('click');
    }

    function exitEditMode() {
        var cancelEdit = Y.one('.rn_CancelEdit a');
        if (!cancelEdit.hasClass('rn_Hidden')) cancelEdit.simulate('click');
    }

    suite.add(new Y.Test.Case({
        name: "Editing",

        tearDown: exitEditMode,

       "Form is shown when edit button is clicked": function() {
            var form = Y.one(baseSelector + ' form');
            Y.assert(form.ancestor('.rn_Hidden'));
            clickEdit();
            Y.assert(!form.ancestor('.rn_Hidden'));
        },

        "QuestionHeader, body and InfoOptions are hidden when edit button is clicked": function() {
            var group = Y.all('.rn_QuestionHeader,.rn_QuestionBody,.rn_QuestionInfoOptions');
            Y.Assert.areSame('false', Y.Array.dedupe(group.hasClass('rn_Hidden')).toString());
            clickEdit();
            Y.Assert.areSame('true', Y.Array.dedupe(group.hasClass('rn_Hidden')).toString());
        },

        "Input fields contain the correct data": function() {
            clickEdit();
            this.wait(function () {
                Y.Assert.areSame('<p>' + Y.Lang.trim(Y.one('.rn_QuestionBody').get('text')) + '</p>', Y.one('.rn_RichTextInput iframe').get('contentDocument').one('body').getHTML());
                Y.Assert.areSame(Y.Lang.trim(Y.one('.rn_QuestionTitle').get('text')), Y.one('[name="Communityquestion\\.Subject"]').get('value'));
           }, 3000);
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Deleting",

        deleteButton: Y.one('.rn_DeleteQuestion'),

        setUp: function() {
            RightNow.Event.on('evt_deleteQuestionRequest', this.requestListener, this);
            RightNow.Event.on('evt_deleteQuestionResponse', this.responseListener, this);
            this._makeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = Y.bind(this.makeRequest, this);
            clickEdit();
        },

        tearDown: function() {
            this.haltRequest = false;
            this.haltResponse = false;
            this.responseListenerCalledWith = null;
            this.requestListenerCalledWith = null;
            this.makeRequestCalledWith = null;
            RightNow.Event.unsubscribe('evt_deleteQuestionRequest', this.requestListener, this);
            RightNow.Event.unsubscribe('evt_deleteQuestionResponse', this.responseListener, this);
            RightNow.Ajax.makeRequest = this._makeRequest;
        },

        makeRequest: function() {
            this.makeRequestCalledWith = Array.prototype.slice.call(arguments);
        },

        requestListener: function() {
            this.requestListenerCalledWith = Array.prototype.slice.call(arguments);
            return !!!this.haltRequest;
        },

        responseListener: function() {
            this.responseListenerCalledWith = Array.prototype.slice.call(arguments);
            return !!!this.haltResponse;
        },

        clickDeleteConfirm: function(selection) {
            Y.all('.rn_Dialog .yui3-widget-ft button').item(selection === 'yes' ? 0 : 1).simulate('click');
        },

        "Clicking no from the delete confirmation dialog cancels the deletion": function() {
            this.deleteButton.simulate('click');
            this.clickDeleteConfirm('no');
            Y.Assert.areSame(undefined, this.requestListenerCalledWith);
            var dialogButtons = Y.all('.rn_Dialog .yui3-widget-ft button');
            //Dialog yes button
            Y.Assert.areSame(dialogButtons.item(0).get('text'), dialogButtons.item(0).one('span.rn_ScreenReaderOnly').get('text') + widget.data.attrs.label_confirm_delete_button);
            //Dialog no button
            Y.Assert.areSame(dialogButtons.item(1).get('text'), widget.data.attrs.label_cancel_delete_button);
        },

        "Button disablement and ajax request are conditional on global event": function() {
            this.haltRequest = true;
            this.deleteButton.simulate('click');
            this.clickDeleteConfirm('yes');
            Y.assert(!this.deleteButton.get('disabled'), 'Delete button not disabled');
            Y.Assert.areSame(parseInt(this.deleteButton.getAttribute('data-questionid'), 10),
                this.requestListenerCalledWith[1][0].data.questionID);
        },

        "Button is disabled and ajax request is made": function() {
            this.deleteButton.simulate('click');
            this.clickDeleteConfirm('yes');
            Y.assert(this.deleteButton.get('disabled'));
            Y.Assert.areSame(widget.data.attrs.delete_question_ajax, this.makeRequestCalledWith[0]);
            Y.Assert.areSame(parseInt(this.deleteButton.getAttribute('data-questionid'), 10),
                this.makeRequestCalledWith[1].questionID);
        },

        "Button re-enablement and window reload are conditional on global event": function() {
            this.haltResponse = true;
            widget._deleteQuestionResponse({ bananas: true }, [{ holland: 'cold' }]);
            Y.assert(this.responseListenerCalledWith[1][0].bananas);
            Y.Assert.areSame('cold', this.responseListenerCalledWith[1][1].holland);
        },

        "Page redirects after deleting question": function() {
            var onQuestionDeletedCallbackArgs = [null, {
                    href: window.location.href
                }];

            widget._deleteQuestionResponse({ bananas: true }, onQuestionDeletedCallbackArgs);

            //display banner default timeout is 4000, therefore putting a wait time little longer than default timeout of banner.
            this.wait(function() {
                Y.Assert.areNotSame(window.location.href, onQuestionDeletedCallbackArgs[1].href);
            }, 4500);
        },

        "Page does not redirect after deleting question if there was an error": function() {
            var onQuestionDeletedCallbackArgs = [null, {
                    href: window.location.href
                }];

            widget._deleteQuestionResponse({ errors: 'so many' }, onQuestionDeletedCallbackArgs);
 
            Y.Assert.areSame(window.location.href, onQuestionDeletedCallbackArgs[1].href);
        },

        "A response indicating an error displays custom error message": function() {
            var alertCount = Y.all('.rn_Alert').size();
            widget._deleteQuestionResponse({result: false, errors: [{externalMessage: "Cannot find question"}]});

            Y.assert(alertCount + 1 === Y.all('.rn_Alert').size());
            Y.assert(Y.all('.rn_Alert').slice(-1).get('text').toString().indexOf('Cannot find question') > -1);
            Y.Assert.isFalse(this.deleteButton.get('disabled'), "Button remains disabled");
        }
    }));

    return suite;
}).run();
