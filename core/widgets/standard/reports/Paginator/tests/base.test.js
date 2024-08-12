UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'Paginator_0',
}, function(Y, widget, baseSelector){
    var paginatorTests = new Y.Test.Suite({
        name: "standard/reports/Paginator",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'Paginator_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.currentPage = 0;
                    this.instance.searchSource()._filters = { 'allFilters':{} };
                },

                checkEventParameters: function(eventName, type, args)
                {
                    Y.Assert.areSame(eventName, type);
                    Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                    Y.Assert.isObject(args.filters);
                }
            };

            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    paginatorTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation",

        /**
         * Tests the widget's response to clicking on a page link
         */
        testPageLink: function() {
            this.initValues();

            var switchPageEventCalled = false;
            RightNow.Event.subscribe("evt_switchPagesRequest", function() {
                switchPageEventCalled = true;
            }, this);

            this.instance.searchSource().on("appendFilter", this.pageRequestEventHandler, this);

            if (this.widgetData.js.endPage - this.widgetData.js.startPage > 0)
                this.currentPage = 2;
            else
                this.currentPage = this.widgetData.js.endPage;
            var currentPage = document.getElementById('rn_' + this.instanceID + '_PageLink_' + this.currentPage);
            if (currentPage) {
                Y.one('#rn_' + this.instanceID + '_PageLink_' + this.currentPage).simulate('click');
                Y.Assert.isTrue(switchPageEventCalled);
            }
        },

        /**
         * Tests the widget's response to clicking on the forward link
         */
        testForwardClick: function() {
            this.initValues();

            this.currentPage = 2;
            var eo = new RightNow.Event.EventObject(this.instanceID, {
                data:
                    {"page": this.currentPage,
                       "total_pages": 3,
                       "truncated": false
                    },
                filters: {"report_id": this.widgetData.attrs.report_id}});
            this.instance.searchSource().fire("response", eo);

            var switchPageEventCalled = false;
            RightNow.Event.subscribe("evt_switchPagesRequest", function() {
                switchPageEventCalled = true;
            }, this);

            this.instance.searchSource().on("appendFilter", this.pageRequestEventHandler, this);
            this.currentPage++;
            Y.one('#rn_' + this.instanceID + ' .rn_NextPage a').simulate('click');

            Y.Assert.isTrue(switchPageEventCalled);
        },

        /**
         * Tests the widget's response to clicking on the back link
         */
        testBackClick: function() {
            this.initValues();

            this.currentPage = 2;
            var eo = new RightNow.Event.EventObject(this.instanceID, {
                data:
                    {"page": this.currentPage,
                       "total_pages": 3,
                       "truncated": false
                    },
                filters: {"report_id": this.widgetData.attrs.report_id}});
            this.instance.searchSource().fire("response", eo);

            var switchPageEventCalled = false;
            RightNow.Event.subscribe("evt_switchPagesRequest", function() {
                switchPageEventCalled = true;
            }, this);

            this.instance.searchSource().on("appendFilter", this.pageRequestEventHandler, this);
            this.currentPage--;
            Y.one('#rn_' + this.instanceID + ' .rn_PreviousPage a').simulate('click');

            Y.Assert.isTrue(switchPageEventCalled);
        },

        /**
         * Tests the widget's normal response to an evt_reportResponse event
         */
        testReportResponseCase1: function() {
            this.initValues();

            var eo = new RightNow.Event.EventObject(this.instanceID, {
                data: {"page": 2,
                       "total_pages": 3,
                       "truncated": false
                      },
                filters: {"report_id": this.widgetData.attrs.report_id}});

            this.instance.searchSource().fire("response", eo);

            var pages = document.getElementById('rn_' + this.instanceID + ' ul');
            Y.Assert.isFalse(Y.one('#rn_' + this.instanceID + ' .rn_NextPage').hasClass('rn_Hidden'));
            Y.Assert.isFalse(Y.one('#rn_' + this.instanceID + ' .rn_PreviousPage').hasClass('rn_Hidden'));
            Y.Assert.isFalse(Y.one('#rn_' + this.instanceID).hasClass('rn_Hidden'));
            Y.Assert.areSame("2", Y.one('#rn_' + this.instanceID + ' .rn_CurrentPage').get('text'));
        },

        /**
         * Tests that the back button is changed when the first page is selected as the current page.
         */
        testReportResponseCase2: function() {
            this.initValues();

            var eo = new RightNow.Event.EventObject(this.instanceID, {
                data: {"page": 1,
                       "total_pages": 3,
                       "truncated": false
                      },
                filters: {"report_id": this.widgetData.attrs.report_id}});

            this.instance.searchSource().fire("response", eo);

            Y.Assert.isTrue(Y.one('#rn_' + this.instanceID + ' .rn_PreviousPage').hasClass('rn_Hidden'));
            Y.Assert.isFalse(Y.one('#rn_' + this.instanceID + ' .rn_NextPage').hasClass('rn_Hidden'));
            Y.Assert.isFalse(Y.one('#rn_' + this.instanceID).hasClass('rn_Hidden'));
            Y.Assert.areSame("1", Y.one('#rn_' + this.instanceID + ' .rn_CurrentPage').get('text'));
        },

        /**
         * Tests that the forward button is hidden when the current page is changed to the same number as total_pages
         */
        testReportResponseCase3: function() {
            this.initValues();

            var eo = new RightNow.Event.EventObject(this.instanceID, {
                data: {"page": 3,
                       "total_pages": 3,
                       "truncated": false
                      },
                filters: {"report_id": this.widgetData.attrs.report_id}});

            this.instance.searchSource().fire("response", eo);

            Y.Assert.isFalse(Y.one('#rn_' + this.instanceID + ' .rn_PreviousPage').hasClass('rn_Hidden'));
            Y.Assert.isTrue(Y.one('#rn_' + this.instanceID + ' .rn_NextPage').hasClass('rn_Hidden'));
            Y.Assert.isFalse(Y.one('#rn_' + this.instanceID).hasClass('rn_Hidden'));
            Y.Assert.areSame("3", Y.one('#rn_' + this.instanceID + ' .rn_CurrentPage').get('text'));
        },

        /**
         * Tests if in response to an evt_reportResponse event where either the data.truncated value is
         * false or the total number of pages in data.total_pages < 2 that the instance of this widget
         * is hidden (assigned 'rn_Hidden' class)
         */
        testReportResponseCase4: function() {
            this.initValues();

            var eo = new RightNow.Event.EventObject(this.instanceID, {
                data: {"page": 1,
                       "total_pages": 1,
                       "truncated": false
                      },
                filters: {"report_id": this.widgetData.attrs.report_id}});

            Y.Assert.isFalse(Y.one('#rn_' + this.instanceID).hasClass('rn_Hidden'), "SubCase 1 failed");

            this.instance.searchSource().fire("response", eo);

            Y.Assert.isTrue(Y.one('#rn_' + this.instanceID).hasClass('rn_Hidden'), "SubCase 1 failed");

            eo.data = {"page": 3,
                       "total_pages": 3,
                       "truncated": true
                      };

            this.instance.searchSource().fire("response", eo);

            Y.Assert.isTrue(Y.one('#rn_' + this.instanceID).hasClass('rn_Hidden'), "SubCase 2 failed");
        },

        pageRequestEventHandler: function(type, args) {
                args = args[0];
                this.checkEventParameters("appendFilter", type, args);
                Y.Assert.areSame(this.currentPage, args.filters.page);
        }
    }));

    return paginatorTests;
});
UnitTest.run();
