UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'AnswerList_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/AnswerList",

        setUp: function() {
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'AnswerList_0';
                    this.viewType = widget.data.js.viewType;
                    if (this.viewType === 'table') {
                        this.table = Y.one('#rn_' + this.instanceID + '_Content table');
                    }
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Verify sorting available for title column ": function() {
            this.initValues();
            var sorted = false,
                sortTargetNode = Y.one('th.yui3-datatable-col-c0');
            if (widget.isSortable(sortTargetNode)) {
                Y.Assert.areSame('documentid', widget._columns[sortTargetNode._node.cellIndex].value.toLowerCase());
            }
        },

        "Verify sorting available for version column ": function() {
            this.initValues();
            var sorted = false,
                sortTargetNode = Y.one('th.yui3-datatable-col-c2');
            if (!widget.isSortable(sortTargetNode)) {
                Y.Assert.areSame('version', widget._columns[sortTargetNode._node.cellIndex].value.toLowerCase());
            }
        }
    }));

    return suite;
}).run();