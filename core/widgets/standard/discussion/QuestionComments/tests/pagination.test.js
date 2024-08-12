UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionComments_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionComments - Pagination tests"
    });

    var commentContent = Y.one(baseSelector + '_Comments').getHTML();

    suite.add(new Y.Test.Case({
        name: "Pagination",

        setUp: function() {
            this.origMakeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = function () {
                RightNow.Ajax.makeRequest.called || (RightNow.Ajax.makeRequest.called = 0);
                RightNow.Ajax.makeRequest.called++;
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
            };
        },

        tearDown: function() {
            RightNow.Ajax.makeRequest = this.origMakeRequest;
        },
        

        "The correct page is requested when clicking a pagination link": function() {
            var pageLink = Y.one(baseSelector + ' .rn_PageLinks a');

            pageLink.simulate('click');
            Y.Assert.areSame(widget.data.attrs.paginate_comments_ajax, RightNow.Ajax.makeRequest.calledWith[0]);
            Y.Assert.areSame(parseInt(pageLink.getAttribute('data-pageID'), 10), RightNow.Ajax.makeRequest.calledWith[1].pageID);

            Y.Assert.areSame('true', Y.one(baseSelector + '_Comments').getAttribute('aria-busy'));
            Y.Assert.isTrue(Y.one(baseSelector + '_Comments .rn_Comments').hasClass('rn_Loading'));

            var callbackOptions = RightNow.Ajax.makeRequest.calledWith[2];
            callbackOptions.successHandler.call(callbackOptions.scope, { responseText: commentContent }, callbackOptions.data);

            Y.Assert.areSame("", Y.one(baseSelector + '_Comments').getAttribute('aria-busy'));
            Y.Assert.areEqual(RightNow.Url.getParameter('page'), RightNow.Ajax.makeRequest.calledWith[1].pageID);
        },

        "Another page cannot be requested while one is currently loading": function() {
            var pageLink = Y.one(baseSelector + ' .rn_PageLinks a');

            pageLink.simulate('click');

            Y.Assert.areSame(1, RightNow.Ajax.makeRequest.called);

            pageLink.simulate('click');

            Y.Assert.areSame(1, RightNow.Ajax.makeRequest.called);
        }
    }));

    return suite;
}).run();
