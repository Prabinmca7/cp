UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'SourcePagination_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/searchsource/SourcePagination"
    });


    suite.add(new Y.Test.Case({
        name: "Pagination behavior",

        setUp: function () {
            this.searchTriggered = false;

            widget.searchSource().on('search', Y.bind(function () {
                this.searchTriggered = true;

                return false; // Prevent the search from happening.
            }, this));
        },

        "Clicking a page link triggers a search event but not a collect event": function () {
            var pageNumber = 0;
            var calledCollect = false;

            widget.searchSource().once('collect', function () {
                calledCollect = true;
            }).once('search', function (evt, args) {
                pageNumber = args[0].data.page.value;
            });

            Y.one(baseSelector + ' a[data-rel="next"]').simulate('click');

            Y.Assert.areSame(2, pageNumber);
            Y.assert(!calledCollect);
        },

        "Pagination links are generated properly for new results": function () {
            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: {
                filters: {
                    page: {
                        value: 2
                    },
                    limit: {
                        value: 10
                    }
                },
                size: 3,
                offset: 10,
                total: 13
            }}));

            Y.assert(!Y.one('.rn_NextPage'));
            Y.Assert.areSame(1, Y.all('.rn_PreviousPage').size());
            Y.Assert.areSame(1, Y.all('.rn_CurrentPage').size());
            Y.Assert.areSame("", Y.one('.rn_CurrentPage').getAttribute('data-rel'));
        },

        "Internal page counter is updated so that previously-current page is now clickable": function () {
            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: {
                filters: {
                    page: {
                        value: 3
                    },
                    limit: {
                        value: 10
                    }
                },
                size: 3,
                offset: 0,
                total: 50
            }}));

            Y.one('a[data-rel="1"]').simulate('click');
            Y.Assert.isTrue(this.searchTriggered);

            this.searchTriggered = false;
            Y.one('a[data-rel="2"]').simulate('click');
        }
    }));

    return suite;
}).run();
