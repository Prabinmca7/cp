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

        "Click of suggested question(single mis-spelt word) fires search and returns result": function() {
            this.initValues();
            this.instance.searchSource().on("search", function() { return false; });
            this.spellCheckerLink.simulate('click');

            var responseData = '{"searchState":{"session":"Tenant2ayP8EmZl","transactionID":2,"priorTransactionID":2},"searchResults":{"page":1,"pageMore":0,"results":{"results":[{"name":"ANSWER","pageNumber":0,"pageMore":0,"pageStart":0,"score":0.78057998418808,"pageSize":2,"unshownResults":1,"totalResults":3,"resultItems":[{"type":"unstructured","fileType":"PDF","answerId":16777216,"docId":20971663,"score":0.99989998340607,"title":"Ruby Course","link":"http://slc01fmq.us.oracle.com:8080/content/AllFileTypes/FileCollection/English/PDF/English1.pdf","clickThroughLink":"?ui_mode=answer&prior_transaction_id=1576741719&iq_action=4&answer_id=16777216&turl=http%3A%2F%2Fslc01fmq.us.oracle.com%3A8080%2Fcontent%2FAllFileTypes%2FFileCollection%2FEnglish%2FPDF%2FEnglish1.pdf","similarResponseLink":"?ui_mode=answer&prior_transaction_id=1576741719&iq_action=12&answer_id=16777216&related_ids=","highlightedLink":"http://slc01fmq.us.oracle.com:8080/content/AllFileTypes/FileCollection/English/PDF/English1.pdf#xml=https://okcsdv2225-qp.dv.lan/srt///pdf/highlight.jsp?cid=48284c702fea3-284f-4090-ae66-dd7cb15c6219&ui_mode=answer&prior_transaction_id=1576741719&iq_action=6&answer_id=16777216&highlight_info=20971663,1169,1186&turl=http%3A%2F%2Fslc01fmq.us.oracle.com%3A8080%2Fcontent%2FAllFileTypes%2FFileCollection%2FEnglish%2FPDF%2FEnglish1.pdf","textElements":[{"url":"http://slc01fmq.us.oracle.com:8080/content/AllFileTypes/FileCollection/English/PDF/English1.pdf","type":"STRING","snippets":[{"text":"Editors: Theses Editors are available under windows and linux xemacs Good highlighting and ","level":1},{"text":"auto","level":3},{"text":"-indentation.","level":1}]},{"type":"STRING","snippets":[{"text":"Can be expanded to do everything.","level":0}]}],"clickThroughUrl":"ZlViaURKaVl3TFFtOEdlblg0aURYSE5PTHROVk8zWXI1azNIcGZmMDZfVkpjSW1reTQ0bjhGX2x4akQ3NWdGcFliU004cVVHcklEeHFsWXNsRzBIa25NS1R1Z1NaMGxTRDVmUjJpbjlaUTRra3g2ZDNETkRjdH5QVnVmR00xdEpkcmEyUmJBZ0p_ZnJFU2ZjUDhkMWRVUEhHVnl_eEU3dTJ5eFk3aE1uVFJFcUx2amtyeUR2Vmdxc2I2ZjNLS1lVaGsyd3ZGTXpDOUFmZ0dHa3k4c25GV3NpU0Ryd3Y4ZlRFaHpUTnA3dGk2MkVqN3pwTWplZH5lSnFTRzJrfkF6dUg2RFc3RTVIV2gzalNqN0NESDJxR0lYOXM0aDlnNG5ERGVucUVvYW5qVnQ2S1Q1YWNJSmVaNnpJdDlYOTFkM1lEelRkekVYbXZQS0NJWUsyRE9RSnk3cTFIVW90MEp0SWtFV2NjNTJ5TVl0NEF1WVRGaElqd1dTdyEh","href":"/ci/okcsFile/get/16777216/48284c702fea3-284f-4090-ae66-dd7cb15c6219/1576741719/PDF#__highlight","dataHref":"http://slc01fmq.us.oracle.com:8080/content/AllFileTypes/FileCollection/English/PDF/English1.pdf"}]}],"facets":[{"id":"DOC_TYPES","desc":"DocTypes","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"DOC_TYPES.HTML","desc":"HTML","count":3,"inEffect":true,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"DOC_TYPES.HTML.JavaScript","desc":"JavaScript","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]}]},{"id":"COLLECTIONS","desc":"Collections","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"COLLECTIONS.Loan","desc":"Loan","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]}]},"facet":null,"query":{"interactive":true,"original":"automatic","paraphrase":"automatic"}}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });
            this.instance.searchSource().fire("response", eo);
            Y.Assert.isTrue(this.spellCheckerContainer.hasClass('rn_Hidden'));
        }
    }));

    return suite;
}).run();
