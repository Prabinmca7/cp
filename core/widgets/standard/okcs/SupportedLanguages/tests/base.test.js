UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'SupportedLanguages_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/SupportedLanguages",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'SupportedLanguages_0'
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.languageContainer = Y.one("#rn_" + this.instanceID + "_Container");
                    this.link = Y.one("#rn_" + this.instanceID + "_Link");
                    this.inputCheckboxes = Y.all("fieldset");
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Language options should be hidden by default": function() {
            this.initValues();
            Y.Assert.isTrue(this.languageContainer.hasClass("rn_Hidden")); 
        },

        "Clicking the 'Specified Languages' link should display the language options": function() {
            this.initValues();
            this.link.simulate('click');
            Y.Assert.isFalse(this.languageContainer.hasClass("rn_Hidden")); 
        },

        "Verify 'for' attribute of label matches th 'id' of checkbox": function() {
            this.initValues();
            for (var i=0; i < this.inputCheckboxes.size(); i++) {
                var labelValue = this.inputCheckboxes.item(i).one('label').get('for'),
                    inputID = this.inputCheckboxes.item(i).one('input').get('id');
                Y.Assert.areSame(labelValue, inputID);
            }
        },
        
        "Verify selected locale filter": function() {
            var searchSource = this.instance.searchSource();
            searchSource.fire("collect");
            Y.Assert.isNotNull(searchSource.filters.loc);
            Y.Assert.isNotNull(searchSource.filters.loc.value.indexOf(this.widgetData.js.defaultLocale) > -1);
        },

        "Language options should be hidden on response change": function() {
            var searchSource = this.instance.searchSource();
            searchSource.fire("response");
            Y.Assert.isTrue(this.languageContainer.hasClass("rn_Hidden"));
        }
    }));

    return suite;
}).run();
