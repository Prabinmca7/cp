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
                    this.focusItem = this.toggleItem.all(widget.data.attrs.focus_item_on_open_selector).item(0);
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
         * Tests that focus is set on correct item widget is opened
         */
        testItemFocus: function() {
            this.initValues();

            var toggle = Y.one("#" + this.toggle);
            toggle.simulate('click');
            Y.Assert.isTrue(this.focusItem.compareTo(document.activeElement));
        }
    }));
    
    return accordionTests;
});
UnitTest.run();
