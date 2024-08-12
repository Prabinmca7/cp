UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'Accordion_0'
}, function(Y, widget, baseSelector){
    var accordionTests = new Y.Test.Suite({
        name: "standard/navigation/Accordion",
        
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.toggle = widget.data.attrs.toggle;
                    this.toggleItem = Y.one("#" + widget.data.attrs.item_to_toggle);
                }
            };
            
            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }            
        }
    });
        
    accordionTests.add(new Y.Test.Case(
    {
        name: "Focus item has focus when focus_item_on_open_selector attribute is set",
        
        /**
         * Test to confirm focus has not been changed
         */
        testItemFocus: function() {
            var currentActiveElement = Y.one(document.activeElement);

            this.initValues();

            Y.Assert.areSame(widget.data.attrs.focus_item_on_open_selector, "");

            var toggle = Y.one("#" + this.toggle);
            toggle.simulate('click');
            Y.Assert.isTrue(currentActiveElement.compareTo(document.activeElement));
        }
    }));
    
    return accordionTests;
});
UnitTest.run();
