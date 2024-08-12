/**
 * Synopsis:
 * - Logged-in social user.
 * - Delete comment and refresh remaining comments.
 */
UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionComments_0',
    jsFiles: [
	'/euf/core/thirdParty/js/ORTL/ortl.js'],
    subInstanceIDs: ['RichTextInput_25']
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionComments - Deleting functionality tests"
    });

    suite.add(new Y.Test.Case({
        name: "Deleting",

        newComments:
            '<div class="rn_Comments"> \
                <div id="rn_QuestionComments_0_436" class="rn_CommentContainer" data-commentid="436"> \
                    <div class="rn_CommentContent"> \
                        <div class="rn_CommentText" itemprop="text"> \
                            <p>Comment 1 for testing</p> \
                        </div> \
                    </div> \
                </div> \
            </div>',

        deleteResponse: '{"result":{"ID":1,"LookupName":null,"CreatedTime":null,"UpdatedTime":null,"Body":null}}',

        setUp: function() {
            this.eventObject = new RightNow.Event.EventObject(widget, { data: {"commentID": 436}});
        },

        isHidden: function(el) {
            if(Y.instanceOf(el, Y.NodeList)) {
                return el.hasClass('rn_Hidden').every(function(item) { return item; });
            }

            return el.hasClass('rn_Hidden');
        },

        isVisible: function(el) {
            return !this.isHidden(el);
        },


        "Delete comment": function() {
            widget._newCommentResponse(this.newComments, this.eventObject);

            this.wait(function(){
                // Delete comment 436. Artificially create the delete dialog since the widget requires it.
                widget._deleteDialog = RightNow.UI.Dialog.actionDialog('ShrimpShoes', Y.Node.create('LoraxLoafers'));
                UnitTest.overrideMakeRequest(widget.data.attrs.delete_comment_ajax,
                    {w_id: widget.data.info.w_id, questionID: widget.data.js.questionID, commentID: 436});
                widget._deleteComment(436);
                this.wait(function(){
                    // Confirm deletion then remake comment 436
                    UnitTest.overrideMakeRequest(widget.data.attrs.fetch_page_with_comment_ajax,
                    {w_id: widget.data.info.w_id, questionID: widget.data.js.questionID, pageID: 1},
                    '_paginateCommentsResponse', widget, Y.Node.create('<div class="rn_CommentContainer" data-commentid="436">comment</div>'), this.eventObject);
                    widget._deleteCommentResponse(this.deleteResponse, this.eventObject);
                    this.wait(function(){
                        // Ensure the response from the server is rendered
                        Y.Assert.areSame(1, Y.all('.rn_CommentContainer[data-commentid]').size());
                    },3000);
                },3000);
            },3000);
        },

        "Deleting an already deleted comment": function() {
            Y.assert(this.isVisible(Y.one('.rn_NoNewCommentMessage')));
            Y.assert(this.isHidden(Y.one('.rn_PostNewComment')));

            // Delete comment 436 (which is already delated). Artificially create the delete dialog since the widget requires it.
            widget._deleteDialog = RightNow.UI.Dialog.actionDialog('ShrimpShoes', Y.Node.create('LoraxLoafers'));
            UnitTest.overrideMakeRequest(widget.data.attrs.delete_comment_ajax,
                {w_id: widget.data.info.w_id, questionID: widget.data.js.questionID, commentID: 436});
            widget._deleteComment(436);
            this.wait(function(){
                var alertCount = Y.all('.rn_Alert').size();
                widget.data.attrs.label_delete_comment_banner = 'Nevermind';
                widget._deleteCommentResponse({result: false, errors: [{externalMessage: "Cannot find comment"}]}, this.eventObject);
                Y.assert(alertCount + 1 === Y.all('.rn_Alert').size());
                // Ensure the response from the server is rendered
                this.wait(function(){
                    Y.assert(this.isHidden(Y.one('.rn_NoNewCommentMessage')));
                    Y.assert(this.isVisible(Y.one('.rn_PostNewComment')));
                },3000);
           },3000);
        }
    }));

    return suite;
});
setTimeout(function(){UnitTest.run();}, 3000);
