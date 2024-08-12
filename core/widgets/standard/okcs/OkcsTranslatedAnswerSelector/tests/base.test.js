UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'OkcsTranslatedAnswerSelector_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/OkcsTranslatedAnswerSelector",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                     this.instanceID = 'OkcsTranslatedAnswerSelector_0';
                     this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Verify widget displays on page load": function() {
            this.initValues();
            widget.answerId = '1000044';
            widget.translationList = RightNow.JSON.parse('[{"answerId":1000044,"localeRecordId":"en_US"},{"answerId":1000003,"localeRecordId":"cs_CZ"},{"answerId":1000009,"localeRecordId":"it_IT"}]');
            widget.localeDescriptionList = RightNow.JSON.parse('{"cs_CZ":"Čeština Česká republika","en_US":"English United States ","it_IT":"Italiano Italia"}') ;
            var dropDownList = RightNow.JSON.parse('{"1000004":"Deutsch Deutschland ","1000000":"English United States ","1000005":"Italiano Italia"}') ;
            //widget._processResponse();Čeština Česká republika', $response['cs_CZ']);
            widget._constructLanguageDropdown(dropDownList);
            //Y.one(
        }
    }));

    return suite;
}).run();
