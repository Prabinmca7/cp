UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'Accordion_0',
}, function(Y, widget, baseSelector){
    var accordionTests = new Y.Test.Suite({
        name: "standard/navigation/Accordion",
        
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'Accordion_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    if (this.instance !== null) {
                        this.widgetData = this.instance.data;
                        this.toggle = this.widgetData.attrs.toggle;
                        this.toggleItem = document.getElementById(this.widgetData.attrs.item_to_toggle);
                        this.collapsedCSS = this.widgetData.attrs.collapsed_css_class;
                        this.expandedCSS = this.widgetData.attrs.expanded_css_class;
                    
                        if (!this.toggleItem) {
                            this.toggleItem = document.getElementById(this.toggle).nextSibling;
                            while(this.toggleItem) {
                                if(this.toggleItem.nodeType === 1)
                                    break;
                                else
                                    this.toggleItem = this.toggleItem.nextSibling;
                            }
                        }
                    }
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
        name: "Event Handling and Operation",
        
        /**
         * Tests the widgets response to a click event on the element which is specified as the toggle
         * element.
         */
        testToggle: function() {
            this.initValues();

            var toggle = Y.one("#" + this.toggle);
            toggle.simulate('click');
            Y.Assert.isTrue(toggle.hasClass(this.expandedCSS));
            Y.Assert.isFalse(toggle.hasClass(this.collapsedCSS));
            Y.Assert.areEqual(this.toggleItem.style.display, "block");

            toggle.simulate('click');
            Y.Assert.isTrue(toggle.hasClass(this.collapsedCSS));
            Y.Assert.isFalse(toggle.hasClass(this.expandedCSS));
            Y.Assert.areEqual(this.toggleItem.style.display, "none");
        }
    }));
    
    return accordionTests;
});
UnitTest.run();
