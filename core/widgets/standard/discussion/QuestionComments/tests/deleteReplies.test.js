/**
 * Synopsis:
 * - Logged-in social user.
 * - Delete comment and refresh remaining comments.
 */
UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionComments_0',
    subInstanceIDs: ['FileListDisplay_36'],
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionComments - Deleting functionality tests"
    });

    suite.add(new Y.Test.Case({
        name: "Deleting",

        newComments:
            '<div class="rn_Comments"> \
                <div id="rn_QuestionComments_0_434" class="rn_CommentContainer" data-commentid="434">Jack of diamonds</div> \
                <div id="rn_QuestionComments_0_435" class="rn_CommentContainer" data-commentid="435">Apple</div> \
                <div id="rn_QuestionComments_0_436" class="rn_CommentContainer" data-commentid="436"> \
                    <div class="rn_CommentContent"> \
                        <div class="rn_CommentText" itemprop="text"><p>Comment 1 for testing</p></div> \
                        <div id="rn_FileListDisplay_36" class="rn_FileListDisplay rn_Output"></div> \
                        <div class="rn_CommentFooter"> \
                            <div class="rn_BestAnswerActions"></div> \
                            <div class="rn_CommentToolbar"> \
                                <ul role="menubar"> \
                                    <li role="menuitem" class="rn_CommentAction"> \
                                        <a href="javascript:void(0);" class="rn_EditCommentAction" data-commentid="1308"> \
                                            <span class="rn_ActionLabel">Edit</span> \
                                        </a> \
                                    </li> \
                                </ul> \
                            </div> \
                        </div> \
                    </div> \
                </div> \
                <div id="rn_QuestionComments_0_437" class="rn_CommentContainer" data-commentid="435">Apple</div> \
                <div id="rn_QuestionComments_0_Replies_437" data-commentid="437" class="rn_Replies rn_Group"> \
                    <a class="rn_ReplyTitle" href="javascript:void(0);" data-toggle-parent="rn_Collapsed"> Replies </a> \
                </div> \
                <div id="rn_QuestionComments_0_438" data-commentid="438" data-contenttype="text/x-markdown" class="rn_CommentContainer" itemprop="suggestedAnswer" itemscope="" itemtype="http://schema.org/Answer"> Reply 1 </div> \
                <div id="rn_QuestionComments_0_438" class="rn_CommentContainer" data-commentid="438">Pinaapple</div> \
            </div>',

        deleteResponse: '{"result":{"ID":1,"LookupName":null,"CreatedTime":null,"UpdatedTime":null,"Body":null}}',

        setUp: function() {
            this.eventObject = new RightNow.Event.EventObject(widget, { data: {"commentID": 436}});
        },

        commentCount: function() {
            return Y.all('.rn_CommentContainer[data-commentid]').size();
        },

        "Click edit and delete comment": function() {
            widget._newCommentResponse(this.newComments, this.eventObject);
            Y.Assert.areSame(6, this.commentCount());
            this.wait(function() {
                Y.one('#rn_QuestionComments_0_436 .rn_EditCommentAction').simulate('click');
                //Simulate successfull comment delete
                widget._deleteCommentResponse(this.deleteResponse, this.eventObject);
            }, 10000);
            Y.Assert.areSame(5, this.commentCount());

            widget._newCommentResponse(this.newComments, this.eventObject);
            Y.Assert.areSame(6, this.commentCount());
            //Simulate successfull comment reply delete
            this.wait(function() {
                widget._deleteCommentResponse(this.deleteResponse, new RightNow.Event.EventObject(widget, { data: {"commentID": 437}}));
            }, 10000);
            Y.Assert.areSame(5, this.commentCount());
        }
    }));

    return suite;
}).run();
