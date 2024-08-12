UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'MobileNavigationMenu_0',
}, function(Y, widget, baseSelector){
    var mobileNavigationMenuTests = new Y.Test.Suite({
        name: "standard/search/MobileNavigationMenu",
    });
        
    mobileNavigationMenuTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation",
        "Tests the response to an evt_navigationMenuShow event": function() {
            var button = Y.one(baseSelector + '_Link'),
                hasCalledEvent = false;

            Y.Assert.areSame(widget.data.attrs.label_button, button.get('textContent'));
            RightNow.Event.subscribe('evt_navigationMenuShow', function(type, args) {
                    hasCalledEvent = true;
                    args = args[0];
                    Y.Assert.areSame('evt_navigationMenuShow', type);
                    Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                    Y.Assert.isObject(args.filters);
                    Y.Assert.areSame(widget.instanceID, args.w_id);
            }, this); 
            button.simulate('click');
            Y.Assert.isTrue(hasCalledEvent);

            //Ensure that the menu is hidden 
        }
    }));
    
    return mobileNavigationMenuTests;
});
UnitTest.run();
