UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SearchButton_0'
}, function(Y, widget, baseSelector){
    var searchButtonTests = new Y.Test.Suite("standard/search/SearchButton");

    searchButtonTests.add(new Y.Test.Case({
        name: "Event Test",

        setUp: function() {
            this.errors = [];
            Y.Assert = Y.Assert;
            this.instanceID = 'SearchButton_0';
            this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
            this.instance.searchSource().on('send', function() {
                return false;
            });
        },

        "Clicking the search button fires the search event": function() {
            this.instance.searchSource().once("search", function(name, args) {
                Y.Assert.areSame("search", name);
                Y.Assert.areSame(this.instanceID, args[0].w_id);
            }, this);
            Y.one(baseSelector + "_SubmitButton").simulate('click');
        },

        "Clicking the search button disables the search button": function() {
            this.instance.searchSource().once("search", function(name, args) {
                this.eventShouldntHaveFired = true;
            }, this);
            Y.one(baseSelector + "_SubmitButton").simulate('click');
            this.wait(function() {
                if (this.eventShouldntHaveFired) {
                    Y.Assert.fail("An event was fired when it shouldn't have");
                }
            }, 1000);
        },

        "Clicking the search button disables the search button even if a response had previously happened": function() {
            this.instance.searchSource().on("search", function(name, args) {
                Y.Assert.areSame("search", name);
                Y.Assert.areSame(this.instanceID, args[0].w_id);
            }, this);
            this.instance.searchSource().fire("response", "doesn't matter");
            Y.one(baseSelector + "_SubmitButton").simulate('click');
            this.instance.searchSource().fire("response", "re-enable");
        },

        "`newPage` member for 'search' event object should be properly set": function() {
            var searchButton = Y.one(baseSelector + '_SubmitButton'),
                newPage = false;

            this.instance.searchSource().on('search', function(evt, args) {
                newPage = args[0].filters.newPage;
            });

            this.instance.data.attrs.report_page_url = '';
            searchButton.simulate('click');
            Y.assert(!newPage);
            this.instance.searchSource().fire("response", "re-enable");

            this.instance.data.attrs.report_page_url = '/app/answers/list';
            searchButton.simulate('click');
            Y.assert(newPage);
            this.instance.searchSource().fire("response", "re-enable");

            this.instance.data.attrs.report_page_url = '{current_page}';
            searchButton.simulate('click');
            Y.assert(!newPage);
            this.instance.searchSource().fire("response", "re-enable");

            this.instance.data.attrs.force_page_flip = true;
            searchButton.simulate('click');
            Y.assert(newPage);
            this.instance.searchSource().fire("response", "re-enable");
        },

        "When the widget is configured to open search result on a different tab/popup, then the search button should not be disabled": function() {
            var searchButton = Y.one(baseSelector + '_SubmitButton'),
                searchDone = false;

            this.instance.searchSource().on('search', function(evt, args) {
                searchDone = true;
            });

            this.instance.data.attrs.report_page_url = "https://someabsoluteurl";
            this.instance.data.attrs.target = "_blank";
            searchButton.simulate('click');
            Y.assert(searchDone);

            //resets value of searchDone to false
            searchDone = false;

            searchButton.simulate('click');
            Y.assert(searchDone);

            //resets value of searchDone to false
            searchDone = false;

            this.instance.data.attrs.popup_window = true;
            searchButton.simulate('click');
            Y.assert(searchDone);
        },

        "When the widget is configured to open search result in the same page, then the search button should be disabled before the reponse event": function() {
            var searchButton = Y.one(baseSelector + '_SubmitButton'),
                searchDone = false;

            this.instance.searchSource().on('search', function(evt, args) {
                searchDone = true;
            });

            this.instance.data.attrs.report_page_url = "/app/answers/list";
            this.instance.data.attrs.target = "_self";
            this.instance.data.attrs.popup_window = false;
            searchButton.simulate('click');
            Y.assert(searchDone);

            //resets value of searchDone to false
            searchDone = false;

            this.instance.searchSource().fire("response", "re-enable");
            searchButton.simulate('click');
            Y.assert(searchDone);
        }
    }));

    return searchButtonTests;
});
UnitTest.run();
