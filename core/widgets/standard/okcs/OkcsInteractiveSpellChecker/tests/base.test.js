UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'OkcsInteractiveSpellChecker_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/OkcsInteractiveSpellChecker",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'OkcsInteractiveSpellChecker_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.spellCheckerContainer = Y.one('.rn_OkcsSpellCheckerContainer');
                    this.spellCheckerLink = Y.one('.rn_OkcsSpellCheckerLink');
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Click of suggested question(multiple mis-spelt word) fires search but returns no result": function() {
            this.initValues();
            this.instance.searchSource().on("search", function() { return false; });
            this.spellCheckerLink.simulate('click');

            var responseData = '{"query":null,"size":10,"total":0,"offset":0,"filters":{"query":{"value":"question","key":"kw","type":"query"},"locale":{"value":null,"key":"loc","type":"locale"},"facet":{"value":null,"key":"facet","type":"facet"},"collectFacet":{"value":null,"key":"collectFacet","type":"collectFacet"},"direction":{"value":"0","key":"dir","type":"direction"},"page":{"key":"page","type":"page","value":1},"truncate":{"value":200,"key":"truncate","type":"truncate"},"searchCacheId":{"value":null,"key":"searchCacheId","type":"searchCacheId"},"searchType":{"value":"PAGE","key":"searchType","type":"searchType"},"okcsSearchSession":{"value":"ZlVsdkRLZUVYRDk3QmwzOUVLQkFNcmJ6T0ZOa2NxOTBCN1o4SDlhaDhDbGpfQXVhdXd2dDVGaFBTdDR0X3k1dnNUQjVGM0F0QlE2NWFPYmlZbkQyZXJwUk9DUEFuQUR3MF83RU1pdHpqbGszMTF6QkxYa3VoWFZCS1ZFenVaUDRDNXFwQjhNQ0JETXJVIQ!!","key":"okcsSearchSession","type":"okcsSearchSession"},"priorTransactionID":{"value":835898389,"key":"priorTransactionID","type":"priorTransactionID"},"docIdRegEx":{"value":null,"key":"docIdRegEx","type":"docIdRegEx"},"transactionID":{"value":835898390,"key":"transactionID","type":"transactionID"},"okcsNewSearch":{"value":true,"key":"okcsNewSearch","type":"okcsNewSearch"},"prod":{"value":null,"key":"product","type":"prod"},"cat":{"value":null,"key":"category","type":"cat"},"querySource":{"value":null,"key":"querySource","type":"querySource"},"sort":{"value":null,"key":"sort","type":"sort"},"product":{"value":null,"key":"p","type":"product"},"category":{"value":null,"key":"c","type":"category"},"offset":{"value":0,"key":null,"type":"offset"},"limit":{"value":false},"sessionKey":"63619290f6572-cd37-4af6-85aa-aaf2a09e3dbe"},"results":[],"searchResults":{"page":0,"pageMore":0,"results":{"results":[],"facets":[]},"facet":[],"selectedLocale":null,"query":{"interactive":true,"original":"question","paraphrase":"question"}},"searchState":{"session":"ZlVKR2UzeTFyfmNaYWdTS3lUQmJ1cFhxSDF0RzF5cnZSeEd2TFAxaDB6fk5OZ00wYUlJcX5BOG5QNXl3RDlON2pJaXRQb3JMejhqWjh5X2o3NXhFZ2t2Q3pSVH53bHVzT3BQaEo1M3lrZDJEUU5IQ2V1Uk5WTEtCfndDWGFKT1VrdDNxRDFNdW9xbVpRIQ!!","transactionID":835898390,"priorTransactionID":835898390}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });
            this.instance.searchSource().fire("response", eo);
            Y.Assert.isTrue(this.spellCheckerContainer.hasClass('rn_Hidden'));
        },

        "Click of suggested question(multiple mis-spelt word) fires search but returns no": function() {
            this.initValues();
            this.instance.searchSource().on("search", function() { return false; });
            this.spellCheckerLink.simulate('click');

            var responseData = '{"query":null,"size":10,"total":0,"offset":0,"filters":{"query":{"value":"telephne","key":"kw","type":"query"},"locale":{"value":null,"key":"loc","type":"locale"},"facet":{"value":null,"key":"facet","type":"facet"},"collectFacet":{"value":null,"key":"collectFacet","type":"collectFacet"},"direction":{"value":"0","key":"dir","type":"direction"},"page":{"key":"page","type":"page","value":1},"truncate":{"value":200,"key":"truncate","type":"truncate"},"searchCacheId":{"value":null,"key":"searchCacheId","type":"searchCacheId"},"searchType":{"value":"PAGE","key":"searchType","type":"searchType"},"okcsSearchSession":{"value":"ZlVVdVhpX25HUVBqSzlPaDk1UGxfaVViQWJCdDRINTNrRGo1bUVVQXh0MkRabzRGYXZLN1g4R21Sb0Z_NzJzMmcxX25IWWN6azhTZl9Hak4xYUdYYW92MlZIbmlrT3puajBZaXN3T3JQX1pxaVVlUX5IdFdOenp1WFBEM2FwSExqX01qODl0WjBlNW1JIQ!!","key":"okcsSearchSession","type":"okcsSearchSession"},"priorTransactionID":{"value":389823239,"key":"priorTransactionID","type":"priorTransactionID"},"docIdRegEx":{"value":null,"key":"docIdRegEx","type":"docIdRegEx"},"transactionID":{"value":389823240,"key":"transactionID","type":"transactionID"},"okcsNewSearch":{"value":true,"key":"okcsNewSearch","type":"okcsNewSearch"},"prod":{"value":null,"key":"product","type":"prod"},"cat":{"value":null,"key":"category","type":"cat"},"querySource":{"value":null,"key":"querySource","type":"querySource"},"sort":{"value":null,"key":"sort","type":"sort"},"product":{"value":null,"key":"p","type":"product"},"category":{"value":null,"key":"c","type":"category"},"offset":{"value":0,"key":null,"type":"offset"},"limit":{"value":false},"sessionKey":"63619dd68a531-6de8-4157-8d5f-89e9f66a5c29"},"results":[],"searchResults":{"page":0,"pageMore":0,"results":{"results":[],"facets":[]},"facet":[],"selectedLocale":null,"query":{"interactive":true,"original":"telephne","paraphrase":"telephne","spellchecked":{"corrections":[{"word":"telephne","suggestions":[{"confidence":94,"value":"telephone"},{"confidence":90,"value":"telephones"},{"confidence":90,"value":"telephoner"}]}]}}},"searchState":{"session":"ZlVYMkh_MER3THpWZG11Z1FXdmo2Y3kwdkVtaE92TFYxUXYwd0hWNEpnRXlxT1RyS3FLNFpKUDFqX3FLfm1WZHhRdlpYd3BxUkhmSmgxZk1DeVpMdzVPRXc0bnlZOE84aHcxQlFXQVdZd2xVNkRGVW1NTElFOWw5c1VoY3J_U1JFV2xWZllMYlhlUG9zIQ!!","transactionID":389823240,"priorTransactionID":389823240}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });
            this.instance.searchSource().fire("response", eo);
            Y.Assert.isFalse(this.spellCheckerContainer.hasClass('rn_Hidden'));
            var responseData1 = '{"query":null,"size":0,"total":0,"offset":0,"filters":{"query":{"value":"telephne","key":"kw","type":"query"},"sort":{"value":null,"key":"sort","type":"sort"},"direction":{"value":null,"key":"dir","type":"direction"},"page":{"key":"page","type":"page","value":1},"product":{"value":null,"key":"p","type":"product"},"category":{"value":null,"key":"c","type":"category"},"offset":{"value":0,"key":null,"type":"offset"},"limit":{"value":"10"}},"results":[]}';
            var eo1 = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData1)
            });
            this.instance.searchSource().fire("response", eo1);
            Y.Assert.isFalse(this.spellCheckerContainer.hasClass('rn_Hidden'));
        }
    }));

    return suite;
}).run();
