UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SourceSearchButton_0'
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite("standard/searchsource/SourceSearchButton");

    tests.add(new Y.Test.Case({
        name: "Event Test",

        setUp: function() {
            this.searchButton = Y.one(baseSelector + "_SubmitButton");
            widget.searchField = true;
        },

        "Clicking the search button without search term": function() {
            // No search term
            widget.searchSource().filters.query = {};
            this.searchButton.simulate('click');
            Y.Assert.isNotNull(Y.one('.rn_BannerAlert'));
        },

        "Clicking the search button fires the search event": function() {
            UnitTest.overrideMakeRequest('/ci/ajaxRequest/search');

            // Assign search term
            widget.searchSource().filters.query = {key: "kw", type: "query", value: "iPhone"};
            widget.searchSource().filters.direction = {key: "dir", type: "direction", value: null};
            widget.searchSource().filters.sort = {key: "sort", type: "sort", value: null};

            Y.Assert.isFalse(widget.searchInProgress);
            this.searchButton.simulate('click');
            Y.Assert.isTrue(this.searchButton.get('disabled'));
            Y.Assert.isTrue(widget.searchInProgress);

            widget.searchSource().fire('response');
            Y.Assert.isFalse(this.searchButton.get('disabled'));
            Y.Assert.isFalse(widget.searchInProgress);

            Y.Assert.areSame(null, widget.searchSource().filters.direction.value);
            Y.Assert.areSame(null, widget.searchSource().filters.sort.value);
        },


        "Star search sets the direction and sort filters": function() {
            UnitTest.overrideMakeRequest('/ci/ajaxRequest/search');

            // Assign search term
            widget.searchSource().filters.query = {key: "kw", type: "query", value: "*"};
            widget.searchSource().filters.direction = {key: "dir", type: "direction", value: null};
            widget.searchSource().filters.sort = {key: "sort", type: "sort", value: null};

            this.searchButton.simulate('click');
            widget.searchSource().fire('response');

            Y.Assert.areSame(0, widget.filters.direction.value);
            Y.Assert.areSame(1, widget.filters.sort.value);
        },
    }));

    return tests;
}).run();
