UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionComments_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionComments"
    });

    suite.add(new Y.Test.Case({
        name: "Widget UI tests for non-logged-in user",

        "Login event is fired and URL is updated when login link is clicked": function() {
            var calledBy;
            RightNow.Event.on("evt_requireLogin", function(args) {
                calledBy = args[0].w_id;
            });

            var widget = RightNow.Widgets.QuestionComments.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                    },
                    _loginLinkClick: function() {
                        RightNow.Event.fire('evt_requireLogin');
                    }
                }
            });

            Y.one(baseSelector + '_Login').simulate('click');
            Y.Assert.areSame(widget.instanceID, calledBy);
        },

        "No loading state when social user error from response": function() {
            var callback = widget._ajaxResponse(widget._events('new').name, widget._newCommentResponse);
            widget.newCommentEditor = new RightNow.Widgets.QuestionComments.embeddedEditor(widget.data, widget.instanceID, Y);
            widget.newCommentEditor.setForm(Y.Node.create('<form><span class="rn_RichTextInput" id="foo"></form>'));
            callback.call(widget, { responseText: 'banana', errors: 'oops' }, this.eventObject);

            var commentsDiv = Y.one(baseSelector + ' .rn_Comments');
            Y.Assert.isFalse(commentsDiv.hasClass('rn_Loading'));
            Y.Assert.areSame('', commentsDiv.get('parentNode').getAttribute('aria-busy'));
        }
    }));

    return suite;
}).run();
