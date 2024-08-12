UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionComments_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionComments - Share panel functionality"
    });
    
    function clickTrigger(index) {
            var expectedPostData = {
                commentID: Y.all('.rn_CommentContainer').item(index || 0).getAttribute('data-commentid'),
            };
            // mock AJAX request on Share click
            UnitTest.overrideMakeRequest(widget.data.attrs.check_comment_exists_ajax, expectedPostData);
            Y.all('a.rn_ShareAction').item(index || 0).simulate('click');
            widget.commentActions._onShareResponse({errors: null}, new RightNow.Event.EventObject({}, {
                data: expectedPostData
            }));
    };
    suite.add(new Y.Test.Case({
        name: "Share Panel",
        "Panel toggles when trigger is clicked": function () {
            clickTrigger();
            
            Y.Assert.isNotNull(Y.one('.rn_ShareBox'));

            clickTrigger();

            Y.Assert.isNull(Y.one('.rn_ShareBox'));
        },

        "Panel is inserted after the comment": function () {
            var firstComment = Y.one(baseSelector + '_Comments .rn_Comments').get('children').item(0);
            
            clickTrigger();

            Y.Assert.isNotNull(Y.one('.rn_ShareBox'));
            Y.Assert.isTrue(firstComment.next().hasClass('yui3-panel'));

            clickTrigger(1);
            
            Y.Assert.isNotNull(Y.one('.rn_ShareBox'));
            // Comment moved from after first comment...
            Y.Assert.isFalse(firstComment.next().hasClass('yui3-panel'));
            
            // ...To after second comment.
            var secondComment = Y.one(baseSelector + '_Comments .rn_Comments').get('children').item(1);
            Y.Assert.isTrue(secondComment.next().hasClass('yui3-panel'));

            Y.one(document.body).simulate('click');
        },

        "Share link doesn't include page url param": function () {
            clickTrigger();

            Y.Assert.areSame(-1, Y.one('.rn_ShareBox input').get('value').indexOf('/page/'));

            Y.one(document.body).simulate('click');
        },

        "An error message is displayed if the response is returned with errors": function () {
            var selector = '#rnDialog1';
            widget.commentActions._onShareResponse({result: false, errors: [{externalMessage: "Cannot find comment"}]});

            Y.assert(!Y.one('#rnDialog1').ancestor('.yui3-panel-hidden'));
            Y.assert(Y.one(selector));
            Y.assert(Y.one(selector).get('text').indexOf('Cannot find comment') > -1);
        }
    }));

    return suite;
}).run();
