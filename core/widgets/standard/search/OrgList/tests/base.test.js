(function () {
    // Set the dropdown's value to something other
    // than its initial state. This is simulate
    // IE.old's form field caching behavior.
    var dropdown = document.querySelector('select');
    dropdown.selectedIndex = dropdown.options.length - 1;
})();

UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'OrgList_0'
}, function(Y, widget, baseSelector){
    var orgListTests = new Y.Test.Suite({
        name: "standard/search/OrgList",

        setUp: function() {
            var testExtender = {
                initValues: function() {
                    this.defaultValue = 0;
                    this.dropdown = Y.one(baseSelector + '_Options');
                    this.defaultEventData = {fltr_id: 5, oper_id: 1, report_id: widget.data.attrs.report_id, rnSearchType: 'org', searchName: 'org', data: {selected: 2}};
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    orgListTests.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        tearDown: function () {
            this.dropdown.set('selectedIndex', this.defaultValue);
        },

        "Widget event object is returned on the 'search' event": function() {
            this.initValues();

            widget.searchSource()
            .on("send", function(type, args) {
                args = args[0].allFilters.org;
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.areSame(widget.instanceID, args.w_id);
                Y.Assert.isObject(args.filters);
            }, this)
            .on("send", function() { return false; /* Prevent the search from actually happening */ })
            .fire("search", new RightNow.Event.EventObject(this.instance, {filters: this.defaultEventData}));
        },

        "Widget's dropdown updates when triggered from the 'response' event": function() {
            this.initValues();

            widget.searchSource().on("response", function(type, args) {
                args = args[0];
                Y.Assert.isObject(args.filters);
                Y.Assert.areSame(widget.instanceID, args.w_id);
                Y.Assert.areSame(this.defaultEventData.data.selected, parseInt(this.dropdown.get('value'), 10));
            }, this)
            .fire("response", new RightNow.Event.EventObject(widget, {filters: this.defaultEventData}));
        },

        "Widget's dropdown updates when triggered from the 'reset' event": function() {
            this.initValues();

            widget.searchSource().on("reset", function(type, args) {
                Y.Assert.areSame("reset", type);
                args = args[0];
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.isObject(args.filters);
                Y.Assert.areSame(widget.instanceID, args.w_id);
                Y.Assert.areSame(this.defaultValue, parseInt(this.dropdown.get('value'), 10));
            }, this)
            .fire("reset", new RightNow.Event.EventObject(widget, {filters: this.defaultEventData, data:{name:'all'}}));
        },

        "Widget's dropdown updates when triggered from the 'orgChanged' event": function(){
            this.initValues();

            var defaultData = this.defaultEventData;
            defaultData.data.selected = 1;

            widget.searchSource().on("orgChanged", function(type, args){
                Y.Assert.areSame("orgChanged", type);
                args = args[0];
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.isObject(args.filters);
                Y.Assert.areSame(widget.instanceID, args.w_id);
                Y.Assert.areSame(defaultData.data.selected, parseInt(this.dropdown.get('value'), 10));
            }, this)
            .fire("orgChanged", new RightNow.Event.EventObject(widget, {filters: defaultData}));
        }
    }));

    return orgListTests;
}).run();
