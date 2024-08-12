UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SimpleSearch_0'
}, function(Y, widget, baseSelector){
    var testSuite = new Y.Test.Suite({
        name: "standard/search/SimpleSearch",
    });

    testSuite.add(new Y.Test.Case({
        name: "Initial focus and label hint testing",

        "Search field has initial focus": function() {
            Y.Assert.areEqual('#' + document.activeElement.getAttribute('id'), baseSelector + '_SearchField');
        }
    }));

    return testSuite;
});
UnitTest.run();
