UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ProdCatNotificationManager_0',
    subInstanceIDs: ['ProductCategoryInput_1', 'ProductCategoryInput_2']
}, function(Y, widget, baseSelector){
    var testSuite = new Y.Test.Suite({
        name: 'standard/notifications/ProdCatNotificationManager'
    });

    testSuite.add(new Y.Test.Case( {
        name: 'Confirm Button and Required Level Tests',
        'Clicking the set button does not submit when required level not met': function() {
            var dialog = Y.one(widget.baseSelector + '_Dialog'),
                dialogButtons = dialog.all('button'),
                addNotificationsButton = Y.one('.rn_AddButton'),
                troubleshootingCategory = Y.one('#ygtvlabelel15'),
                selectCategoryButton = dialogButtons.item(4),
                categorySetButton = dialogButtons.item(5),
                categoryOkButton = dialogButtons.item(6),
                errorSpan = Y.one('#rn_ProductCategoryInput_2_Label'),
                errorMessage = 'Please select an item under Troubleshooting';


            addNotificationsButton.simulate('click');
            selectCategoryButton.simulate('click');
            troubleshootingCategory.simulate('click');
            this.wait(function() { // Wait for tree to re-render following selection
                categoryOkButton.simulate('click');
                Y.Assert.isTrue((errorSpan.get('text').indexOf(errorMessage) > -1), 'Required level error not present after confirm');
                categorySetButton.simulate('click');
                // Submit did not happen. Dialog and error still exist
                Y.Assert.isTrue((errorSpan.get('text').indexOf(errorMessage) > -1), 'Required level error not present after set');
                widget._closeDialog();
            }, 2000);
        }
    }));

    return testSuite;
});
UnitTest.run();