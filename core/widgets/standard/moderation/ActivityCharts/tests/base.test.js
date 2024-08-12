UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'ActivityCharts_0'
}, function (Y, widget) {
    var suite = new Y.Test.Suite({
        name: "standard/moderation/ActivityCharts",
        setUp: function(){
            for (var item in this.items) {
                Y.mix(this.items[item]);
            }
        },
        tearDown: function() {
        }
    });

    suite.add(new Y.Test.Case({
        name: "Activity charts data setter test",

        "Test if data for axes is set": function() {
            Y.Assert.isObject(widget.axesDefinition, 'Axes data not set');
            Y.Assert.areSame(true, widget.axesDefinition.totals.maximum >= 5, 'Maximum should not be less than 5');
            Y.Assert.areSame(widget.axesDefinition.totals.keys.length, widget.data.attrs.object_types.length, 'axesDefinition keys are not matching with supplied social object types');
        },

        "Test if data for legend is set": function() {
            Y.Assert.isObject( widget.legendDefinition, 'Legend data not set');
        },

        "Test if data for tooltip is set": function() {
            Y.Assert.isObject(widget.tooltipDefinition, 'Tooltip data not set');
        },

        "Test if data for seriesCollection is set": function() {
            Y.Assert.isTrue(widget.seriesCollectionDefinition.length > 0, 'seriesCollectionDefinition data not set');
        },

        "Test if data for chart is set": function() {
            Y.Assert.isArray(widget.chartData, 'chartData is not an array');
            Y.Assert.areSame(7, widget.chartData.length, 'chartData array does not have 7 elements');
        },
        "Test if data for chart's style is set": function() {
            Y.Assert.isObject(widget.chartStyleDefinition, 'chart style data is not set');
        }

    }));

    return suite;
}).run();
