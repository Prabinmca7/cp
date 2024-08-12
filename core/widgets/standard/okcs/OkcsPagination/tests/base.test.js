UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'OkcsPagination_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/OkcsPagination"
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

        "Clicking a page link triggers a search": function () {
            var pageNumber = 0;
            var calledCollect = false;

            widget.searchSource().once('collect', function () {
                calledCollect = true;
            }).once('search', function (evt, args) {
                pageNumber = args[0].data.page.value;
            });
            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: {
                filters: {
                    page: {
                        value: 1
                    },
                    limit: {
                        value: 10
                    },
                    resultCount: {
                        value: null
                    }
                },
                searchResults: {
                    page: 1,
                    pageMore: 1
                }
            }}));

            Y.one(baseSelector + ' a[rel="next"]').simulate('click');

            Y.Assert.areSame(2, pageNumber);
            
            Y.assert(calledCollect);
        },

        "Pagination Next/Prev links are generated properly for new results": function () {
            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: {
                filters: {
                    page: {
                        value: 2
                    },
                    limit: {
                        value: 10
                    },
                    resultCount: {
                        value: null
                    }
                },
                searchResults: {
                    page: 2,
                    pageMore: 0
                }
            }}));

            Y.assert(!Y.one('.rn_NextPage'));
            Y.Assert.areSame(1, Y.all('.rn_PreviousPage').size());
        },

        "Verify functionality on click of Next and Previous buttons": function () {
            var pageNumber = 0;
            var calledCollect = false;

            widget.searchSource().on('collect', function () {
                calledCollect = true;
            }).on('response', function (evt, args) {glbVar = args[0];
                pageNumber = args[0].data.searchResults.page;
            });

            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: {
                filters: {
                    page: {
                        value: 0
                    },
                    limit: {
                        value: 10
                    },
                    resultCount: {
                        value: null
                    }
                },
                searchResults: {
                    page: 1,
                    pageMore: 1
                }
            }}));

            //Verify only Next button on first page
            Y.Assert.isNotNull(Y.one(baseSelector + ' a[rel="next"]'));
            Y.Assert.isNotNull(Y.one(baseSelector + ' a[rel="previous"]'));

            //Verify click of Next on first page navigates to second page
            Y.one(baseSelector + ' a[rel="next"]').simulate('click');
            Y.Assert.areSame(1, pageNumber);                
            Y.assert(calledCollect);

            Y.one(baseSelector + ' a[rel="previous"]').simulate('click');
            
            calledCollect = false;

            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: {
                filters: {
                    page: {
                        value: 1,
                        direction: "backward"
                    },
                    limit: {
                        value: 10
                    },
                    resultCount: {
                        value: null
                    }
                },
                searchResults: {
                    page: 0,
                    pageMore: 1
                }
            }}));

            //Verify both Next and previous buttons on second page
            Y.Assert.isNotNull(Y.one(baseSelector + ' a[rel="next"]'));
            Y.Assert.isNull(Y.one(baseSelector + ' a[rel="previous"]'));                
            Y.Assert.areSame(0, pageNumber);
        },

        "Verify collect event fired on right click of Next button": function () {
            var pageNumber = 0;
            var calledCollect = false;

            widget.searchSource().on('collect', function () {
                calledCollect = true;
            }).on('response', function (evt, args) {glbVar = args[0];
                pageNumber = args[0].data.searchResults.page;
            });

            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: {
                filters: {
                    page: {
                        value: 0
                    },
                    limit: {
                        value: 10
                    },
                    resultCount: {
                        value: null
                    }
                },
                searchResults: {
                    page: 1,
                    pageMore: 1
                }
            }}));
            Y.one(baseSelector + ' a[rel="next"]').simulate('mouseup', {button: 3});
            Y.Assert.areSame(1, pageNumber);
            //Verify on right click of Next collect event is not fired
            Y.assert(!calledCollect);
            Y.Assert.isFalse(this.searchTriggered);
        }
    }));

    return suite;
}).run();
