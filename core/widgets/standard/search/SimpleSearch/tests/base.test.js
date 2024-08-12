UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SimpleSearch_0'
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/search/SimpleSearch"
    });

    tests.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        setUp: function() {
            this.searchField = Y.one(baseSelector + '_SearchField').set('value', '');
            this.submitButton = Y.one(baseSelector + '_Submit');

            this.navigatedTo = null;

            RightNow.Url.navigate = Y.bind(function(url) {
                this.navigatedTo = url;
            }, this);
        },

        "Widget navigates to search page with empty keyword input": function() {
            this.submitButton.simulate('click');
            Y.Assert.isNotNull(Y.one('.rn_BannerAlert'));

        },

        "Widget navigates to search page with keyword input as keyword url parameter": function() {
            this.searchField.set('value', 'Test');
            this.submitButton.simulate('click');

            Y.Assert.areSame(RightNow.Url.addParameter(widget.data.attrs.report_page_url + '/kw/Test', 'session', RightNow.Url.getSession()) + '/search/1', this.navigatedTo);
        },

        "Widget doesn't add trailing spaces inserted at front/back of keyword input in navigated url": function() {
            this.searchField.set('value', '  Test  ');
            this.submitButton.simulate('click');

            Y.Assert.areSame(RightNow.Url.addParameter(widget.data.attrs.report_page_url + '/kw/Test', 'session', RightNow.Url.getSession()) + '/search/1', this.navigatedTo);
        },

        "Widget doesn't add search param if url already contains `search/1`": function() {
            this.searchField.set('value', 'Test');
            widget.data.attrs.report_page_url = '/app/search/1';
            var urlWithSearchTerm = widget.data.attrs.report_page_url + '/kw/Test';
            this.submitButton.simulate('click');

            Y.Assert.areSame(RightNow.Url.addParameter(urlWithSearchTerm, 'session', RightNow.Url.getSession()), this.navigatedTo);
        },

        "URL parameters are properly constructed": function() {
            var result = widget._urlParameters();
            Y.Assert.areSame('', result);

            result = widget._urlParameters({kw: 'phone', session: '123abc', author: 456});
            Y.Assert.areSame('/kw/phone/session/123abc/author/456', result);
        }
    }));

    return tests;
}).run();
