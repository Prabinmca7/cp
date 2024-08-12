/**
 * Synopsis:
 * - Logged-in social user.
 */
UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionComments_0',
    preloadFiles: [
        '/euf/core/thirdParty/js/ORTL/ortl.js'],
    subInstanceIDs: ['RichTextInput_24'],
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionComments - New comment functionality tests"
    });

    suite.add(new Y.Test.Case({
        name: "New comments",

        commentResponse: '<div class="rn_Comments"> \
            <div class="rn_CommentContainer" data-commentid="432">Jack of diamonds</div> \
            <div class="rn_CommentContainer" data-commentid="434">Queen of hearts</div> \
            </div>',

        setUp: function() {
            this.eventObject = new RightNow.Event.EventObject(widget, { data: {}});
            this.makeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = RightNow.Event.createDelegate(this, function() {
                this.request = Array.prototype.slice.call(arguments);
            });
        },

        tearDown: function() {
            RightNow.Ajax.makeRequest = this.makeRequest;
            this.request = null;
        },
        numberOfComments: function() {
            return Y.all('.rn_CommentContainer[data-commentid]').size();
        },

        "New comments from server response are injected": function() {
            var originalNumberOfComments = this.numberOfComments();

            this.wait(function(){
                widget._newCommentResponse(this.commentResponse, this.eventObject);
                this.wait(function(){
                    var insertedCommentIDs = Y.one(baseSelector).all('[data-commentid]').getAttribute('data-commentid');

                    Y.Assert.areSame(2, insertedCommentIDs.length);
                    Y.Assert.areSame('432', insertedCommentIDs[0]);
                    Y.Assert.areSame('434', insertedCommentIDs[1]);
                }, 3000);
            }, 3000);
        },

        "Error message is not injected": function() {
            var originalNumberOfComments = this.numberOfComments();

            var callback = widget._ajaxResponse(widget._events('new').name, widget._newCommentResponse);
            callback.call(widget, { responseText: 'chainsaw' }, this.eventObject);

            Y.Assert.areSame(originalNumberOfComments, this.numberOfComments());
            Y.Assert.areSame(-1, Y.one(baseSelector).get('text').indexOf('chainsaw'));
        },

        "Existing new comment field is cleared after getting server response": function() {
            widget.newCommentEditor._getCommentEditorWidget().reload('soft blur');
            this.wait(function() {
                widget._newCommentResponse(this.commentResponse, this.eventObject);
                this.wait(function() {
                    Y.assert(!Y.Lang.trim(widget.newCommentEditor.getHTML()));
                }, 3000);
            }, 3000);

        },

        "Banner isn't displayed if its label isn't specified": function() {
            var alertCount = Y.all('.rn_Alert').size();
            widget.data.attrs.label_new_comment_banner = '';
            widget._newCommentResponse(this.commentResponse, this.eventObject);
            Y.assert(alertCount === Y.all('.rn_Alert').size());
        },

        "Banner is displayed if its label is specified": function() {
            this.wait(function() {
                var alertCount = Y.all('.rn_Alert').size();
                widget.data.attrs.label_new_comment_banner = 'Nevermind';
                widget._newCommentResponse(this.commentResponse, this.eventObject);
                Y.assert(alertCount + 1 === Y.all('.rn_Alert').size());
                Y.assert(Y.all('.rn_Alert').slice(-1).get('text').toString().indexOf('Nevermind') > -1);
            }, 3000);
        },

        "Comment is sent to widget endpoint": function() {
            this.wait(function() {
                widget.newCommentEditor._getCommentEditorWidget().reload('bananas');
                this.wait(function() {
                    var submitButton = Y.one(baseSelector + ' .rn_NewComment [type="submit"]');
                    submitButton.simulate('click');
                    widget.newCommentEditor.on('instanceReady', function(evt) {
                        Y.assert(submitButton.get('disabled'));
                        Y.Assert.areSame(widget.data.attrs.new_comment_ajax, this.request[0]);
                        Y.Assert.areSame('<p>bananas</p>', Y.Lang.trim(this.request[1].commentBody));
                        Y.assert(!this.request[1].commentID);
                        widget.newCommentEditor.reload();
                    }, this);
                }, 3000);
            }, 3000);
        }
    }));

    return suite;
}).run();
