UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'FormSubmit_0'
}, function(Y, widget, baseSelector){
    var formSubmitTests = new Y.Test.Suite({
        name: "standard/input/FormSubmit"
    });

    formSubmitTests.add(new Y.Test.Case({
        name: "Operation",
        'Validate that a native request with "get" and an action works as expected': function() {
            var that = this,
                unitTestForm = Y.one('#unitTestID');
            Y.one("#unitTestID").append(Y.Node.create("<textarea name='myText'></textarea>").set("value", "some Text or whatever"));

            //Remove these two lines when the rendering test starts working
            Y.one('#unitTestID').set('action', window.location + '#');
            Y.one('#unitTestID').set('target', '');

            if(!Y.UA.gecko) {
                Y.one(baseSelector + "_Button").simulate('click');
                this.wait(function() {
                    if(window.location.hash !== '') {
                        Y.Assert.isTrue(window.location.hash.indexOf('banana/jones') > -1);
                        Y.Assert.isTrue(window.location.hash.indexOf('myText/' + encodeURIComponent('some Text or whatever')) > -1);
                        that.validateSubmission();
                        window.location.hash = '';
                    }
                }, 1000);
            }
            //Old versions of FF, in particular 3.6 used on the CC server, don't support custom click events on URLs.
            //Use this as a placeholder until the server is updated.
            else {
                Y.one(baseSelector + "_Button").simulate('click');
                this.wait(function() {
                    var redirectURL = Y.one(document.body).get('lastChild').get('href');
                    Y.Assert.isTrue(redirectURL.indexOf('banana/jones') > -1);
                    Y.Assert.isTrue(redirectURL.indexOf('myText/' + encodeURIComponent('some Text or whatever')) > -1);
                    that.validateSubmission();
                }, 1000);
            }

        },

        validateSubmission: function() {
            var button = Y.one(baseSelector + "_Button");
            Y.Assert.isTrue(button.get("disabled"));
            Y.Assert.isTrue(button.hasClass("rn_Loading"));
            Y.Assert.areSame(widget.data.attrs.label_submitting_message, Y.Lang.trim(button.get("innerHTML")));
        }
    }));
    return formSubmitTests;
});
UnitTest.run();
