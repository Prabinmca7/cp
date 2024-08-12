UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'DateInput_0'
}, function(Y, widget, baseSelector){
    var dateInputTests = new Y.Test.Suite({
        name: 'standard/input/DateInput'
    });

    dateInputTests.add(new Y.Test.Case({
        name: 'Attribute min_year set prior to field constraint',

        // @@@ QA 120927-000089 - make sure that min_year may not be before field constraint min date
        testMinYearAttributes: function() {
            // make sure year was changed from 1902 to 1970
            Y.Assert.areSame(1902, parseInt(widget.data.attrs.min_year, 10));
        }
    }));
    return dateInputTests;
});
UnitTest.run();
