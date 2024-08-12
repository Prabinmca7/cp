UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'AdvancedSearchDialog_0'
}, function(Y, widget, baseSelector){
    var advancedSearchDialogTests = new Y.Test.Suite({
        name: "standard/search/AdvancedSearchDialog",

        setUp: function(){
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'AdvancedSearchDialog_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.widgetData.attrs.report_page_url = "";
                }
            };

            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    advancedSearchDialogTests.add(new Y.Test.Case({
        name : "Event Handling and Operation",

        /**
         * Test to ensure that the widget functions correctly by resetting its fields
         * when either the cancel button or close link are clicked.
         */
        testResetFilter: function() {
            this.initValues();

            Y.one("#" + this.instance.baseDomID + "_TriggerLink").simulate('click');

            var dialog = Y.one("#rnDialog1"),
                closeLink = dialog.one('button'),
                cancelBtn = dialog.all('button').item(2);

            Y.assert(!dialog.ancestor('.yui3-panel-hidden'));

            this.instance.searchSource().on("reset", function(type, args) {
                Y.Assert.areSame("reset", type);
                args = args[0];
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.isObject(args.filters);
                Y.Assert.areSame(this.instanceID, args.w_id);

                Y.Assert.areSame(this.widgetData.attrs.report_id, args.filters.report_id);
                Y.Assert.areSame("all", args.data.name);
            }, this);

            Y.one(cancelBtn).simulate('click');

            Y.one("#" + this.instance.baseDomID + "_TriggerLink").simulate('click');
            Y.Assert.isNull(dialog.ancestor('.yui3-panel-hidden'));
            closeLink.simulate('click');
        },

        /**
         * Test to ensure the widget function's correctly when the search button is
         * clicked.
         */
        testSearchRequest: function() {
            this.initValues();

            RightNow.Url.navigate = function(url, external) {
                Y.Assert.areSame(this.widgetData.attrs.report_page_url, url);
            };

            Y.one("#" + this.instance.baseDomID + "_TriggerLink").simulate('click');

            var dialog = Y.one("#rnDialog1"),
                searchBtn = dialog.all('button').item(2);

            Y.assert(!dialog.ancestor('.yui3-panel-hidden'));

            this.instance.searchSource().on("search", function(type, args) {
                Y.Assert.areSame("search", type);
                args = args[0];
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.isObject(args.filters);
                Y.Assert.areSame(this.instanceID, args.w_id);

                Y.Assert.areSame(this.widgetData.attrs.report_id, args.filters.report_id);
                Y.Assert.areSame(this.widgetData.attrs.report_page_url, args.filters.reportPage);
                Y.assert(dialog.ancestor('.yui3-panel-hidden'));
            }, this)
                .on("send", function() { return false; });

            Y.one(searchBtn).simulate('click');
        }
    }));

    advancedSearchDialogTests.add(new Y.Test.Case({
        name: "UI functional tests",
        /**
         * Tests to verify that the UI components operate correctly.
         * Tests consist of determining if the attributes set for UI components
         * are displaying correctly
         */
        testUI: function() {
            this.initValues();

            RightNow.Url.navigate = function(url, external) {
                Y.Assert.areSame(this.widgetData.attrs.report_page_url, url);
            };
            var trigger = document.getElementById("rn_" + this.instanceID + "_TriggerLink");
            if (Y.UA.ie) {
                Y.Assert.areSame(this.widgetData.attrs.label_link + this.widgetData.attrs.label_opens_new_dialog, trigger.innerText);
            }
            else {
                Y.Assert.areSame(this.widgetData.attrs.label_link + this.widgetData.attrs.label_opens_new_dialog, trigger.textContent);
            }

            Y.one(trigger).simulate('click');

            var dialog = Y.one("#rnDialog1"),
                buttons = dialog.all('.yui3-widget-buttons button'),
                closeLink = dialog.one('button'),
                cancelBtn = buttons.item(buttons.size() - 1),
                searchBtn = buttons.item(buttons.size() - 2);

            Y.assert(!dialog.ancestor('.yui3-panel-hidden'));
            Y.one(closeLink).simulate('click');
            Y.assert(dialog.ancestor('.yui3-panel-hidden'));

            Y.one(trigger).simulate('click');
            Y.assert(!dialog.ancestor('.yui3-panel-hidden'));
            Y.one(cancelBtn).simulate('click');
            Y.assert(dialog.ancestor('.yui3-panel-hidden'));

            Y.one(trigger).simulate('click');
            Y.assert(!dialog.ancestor('.yui3-panel-hidden'));
            Y.one(searchBtn).simulate('click');
            Y.assert(dialog.ancestor('.yui3-panel-hidden'));
        }
    }));

    return advancedSearchDialogTests;
});
UnitTest.run();
