UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'OkcsManageRecommendations_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/OkcsManageRecommendations",

        setUp: function() {
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'OkcsManageRecommendations_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.viewType = widget.data.js.viewType;
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Accessibility Tests": function() {
            this.initValues();
            if (this.viewType === 'table') {
                var tableHeader = Y.one('thead');
                Y.Assert.isNotNull(tableHeader);

                var dataCell = Y.all("td");
                for (var i = 0; i < dataCell.size(); i++) {
                    Y.Assert.isNotNull(dataCell.item(i).getAttribute('headers'));
                }
            } else {
                Y.Assert.isNotNull(Y.one('ul'));
            }
        },

        "Validate if history source id is identical to widget source id": function() {
            this.initValues();
            Y.Assert.areSame(widget.data.attrs.source_id, widget.searchSource().options.history_source_id);
        },

        "Verify sort class is available for case number column": function() {
            this.initValues();
            if (this.viewType === 'table') {
                this.publishDateHeaderDOM = Y.one('th.yui3-datatable-col-c1');
                Y.Assert.isFalse(this.publishDateHeaderDOM.one('span').hasClass('rn_SortIndicator'));
                Y.Assert.isFalse(this.publishDateHeaderDOM.one('span.rn_SortIndicatorNewStyle').hasClass('rn_ArticlesSortDesc'));
                Y.Assert.isFalse(this.publishDateHeaderDOM.one('span.rn_SortIndicatorNewStyle').hasClass('rn_ArticlesSortAsc'));
            }
        }
    }));

    return suite;
}).run();