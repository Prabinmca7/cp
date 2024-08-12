UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'Facet_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/Facet",
        
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'Facet_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    if(Y.one('[id="DOC_TYPES.HTML"]'))
                        Y.one('[id="DOC_TYPES.HTML"]').removeClass("rn_ActiveFacet");
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        setUp: function () {
            widget.searchSource().on('search', function() {
                // Prevent the search from happening.
                return false;
            });
        },

        "Validate data and DOM": function() {
            this.initValues();
            var content = Y.one(baseSelector + '_Content'),
                list = Y.all('li');
            Y.Assert.isNotNull(content);
            for (var i=0; i < list.size(); i++) {
                var content = list.item(i).get('children').item(0);
                if (content.get('nodeName') === 'A')
                    Y.Assert.isTrue(content.hasClass('rn_FacetLink'));
                else 
                    Y.Assert.areSame(content.get('nodeName'), 'UL');
            }
        },

        "validate facet reset after click event": function() {
            this.initValues();
            var content = Y.one(baseSelector + '_Content'),
                list = Y.all('li');
            Y.Assert.isNotNull(content);
            for (var i=0; i < list.size(); i++) {
                var content = list.item(i).get('children').item(0);
                if (content.get('nodeName') === 'A'){
                    Y.Assert.isTrue(content.hasClass('rn_FacetLink'));
                    var facetLink = Y.one('.rn_FacetLink');
                    facetLink.simulate('click');
                    //Checking if facet value is reset after click
                    Y.Assert.areSame('', this.instance._facet);
                }
            }
        },

        "No results in the response should be handled properly": function() {
            this.initValues();
            var responseData = '{"searchState":{"session":"Tenant2ayP8EmZl","transactionID":2,"priorTransactionID":2},"searchResults":{"page":1,"pageMore":0,"results":{"results":[],"facets":[]},"facet":null}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });
            this.instance.searchSource().fire("response", eo);
            var content = Y.one(baseSelector + '_Content');
            Y.Assert.isFalse(content.hasChildNodes());
        },

        "More option should be displayed if subfacet length is greater than maxSubFacet size": function() {
            this.initValues();
            var responseData = '{"searchResults":{"page":0,"pageMore":0,"results":{"results":[{"name":"ANSWER","pageNumber":0,"pageMore":0,"pageStart":0,"score":0.98737281560898,"pageSize":1,"unshownResults":0,"totalResults":1,"resultItems":[{"type":"unstructured","fileType":"CMS-XML","answerId":16777216,"globalAnswerId":"1000063","docId":12582963,"score":0.98737281560898,"title":"Help Windows Help You!","link":"IM:FAQ:0120941d4319fa0152eec65d91007f64:en_US:published:FAQ40:1000063:1.0","clickThroughLink":"?ui_mode=answer&prior_transaction_id=41616055&iq_action=4&answer_id=16777216&turl=IM%3AFAQ%3A0120941d4319fa0152eec65d91007f64%3Aen_US%3Apublished%3AFAQ40%3A1000063%3A1.0","similarResponseLink":"?ui_mode=answer&prior_transaction_id=41616055&iq_action=12&answer_id=16777216&related_ids=","highlightedLink":"null?ui_mode=answer&prior_transaction_id=41616055&iq_action=5&answer_id=16777216&highlight_info=12582963,5,12&turl=IM%3AFAQ%3A0120941d4319fa0152eec65d91007f64%3Aen_US%3Apublished%3AFAQ40%3A1000063%3A1.0#__highlight","textElements":[{"url":"IM:FAQ:0120941d4319fa0152eec65d91007f64:en_US:published:FAQ40:1000063:1.0","type":"STRING","snippets":[{"text":"Windows ","level":3},{"text":"can help you increase productivity and efficiency ","level":1}]}],"clickThroughUrl":"ZlV_ZGdoSTdwUnlVMXJOaVQzYXRsT0J0YkE4aWZvVVZYMms5WVkzcDVmWH5YR21BbkpkbWJiczVEMHdBcjV3TWFwREdMNnlhZXhDeGIzRkkwZmtpNFFPWDEyRFFkdTk0VHp1SFFEMTJicm11MnRIS1RwdzZPUTdXZkZYeHc0NX4yVmhoNXZtQUpCeW5qZHZ5eFZlSnhiNWFHdXlUWnVMb2k3bEFDNDJYVEpEb1FnTjJfMGh6YXpSVDZoYUdpMjZpYks0amgyY2owd2luel8wfjFrWHdSY3BHVENaMmJndmlERHlaN2M3RHFfcjhhRVN5bmFWfklKRn5pTkgxQXBHZFo0emZ5N2lrQmRoNmViNUhBb1dvS1V5TlQxVDk4dWRJX3AxYzVMYVN_fjV6T01YellqfndSM2R3ZGVNaHRXMXY1eFNEUTZhZm1OejlpM0w3X3VZdHltejZWMWo1Y2J0Rlpf","href":"\/a_id\/1000063\/loc\/en_US\/s\/16777216_5761312cfcd34-04c3-4a16-8db8-19b5e115b190_SEARCH\/prTxnId\/41616055#__highlight","dataHref":"\/a_id\/1000063\/loc\/en_US"}]}],"facets":[{"id":"DOC_TYPES","desc":"DocTypes","count":9,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"DOC_TYPES.CMS-XML","desc":"ARTICLES","count":9,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"COLLECTIONS","desc":"Collections","count":9,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"COLLECTIONS.OKKB-FAQ","desc":"FAQ","count":9,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"CMS-CATEGORY_REF","desc":"Category","count":5,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-CATEGORY_REF.RN_CATEGORY_11","desc":"Cat2","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-CATEGORY_REF.RN_CATEGORY_8","desc":"Cat1","count":5,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"CMS-PRODUCT","desc":"Product","count":9,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-PRODUCT.RN_PRODUCT_1","desc":"Product1","count":9,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2","desc":"Product1.1","count":9,"inEffect":true,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_20","desc":"Product1.1.2","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_21","desc":"Product1.1.3","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_22","desc":"Product1.1.4","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_23","desc":"Product1.1.5","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_3","desc":"Product1.1.1","count":5,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]}]}]}]},"facet":null},"searchState":{"session":"ZlVrSHBINVBhYVRVNm02blRFQlFVazNsYWRZd0FufmhYRDVlV0dlZE1DV2k2WjMwYlJyQThMblBwQ0VIZDRsbkVvN2pmZUF5NUlHQ3hMMjBvdmswVWtLcVZ0aX43RDV1eVNCN2FZcnRyTkZGS19hSmcxUjEwYnZpQjd2d3ZPNTZ0VGpEX3oxSHpTWGxCMThibF9QN3I1MHlHdEpLVW1yRmdN","transactionID":41616055,"priorTransactionID":41616055}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });
            this.instance.searchSource().fire("response", eo);
            var moreObject = Y.one('[id="F:CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2"]');
            Y.Assert.isNotNull(moreObject);
        },

        "Clicking on More option should display entire facet tree": function() {
            this.initValues();
            var responseData = '{"searchResults":{"page":0,"pageMore":0,"results":{"results":[{"name":"ANSWER","pageNumber":0,"pageMore":0,"pageStart":0,"score":0.98737281560898,"pageSize":1,"unshownResults":0,"totalResults":1,"resultItems":[{"type":"unstructured","fileType":"CMS-XML","answerId":16777216,"globalAnswerId":"1000063","docId":12582963,"score":0.98737281560898,"title":"Help Windows Help You!","link":"IM:FAQ:0120941d4319fa0152eec65d91007f64:en_US:published:FAQ40:1000063:1.0","clickThroughLink":"?ui_mode=answer&prior_transaction_id=41616055&iq_action=4&answer_id=16777216&turl=IM%3AFAQ%3A0120941d4319fa0152eec65d91007f64%3Aen_US%3Apublished%3AFAQ40%3A1000063%3A1.0","similarResponseLink":"?ui_mode=answer&prior_transaction_id=41616055&iq_action=12&answer_id=16777216&related_ids=","highlightedLink":"null?ui_mode=answer&prior_transaction_id=41616055&iq_action=5&answer_id=16777216&highlight_info=12582963,5,12&turl=IM%3AFAQ%3A0120941d4319fa0152eec65d91007f64%3Aen_US%3Apublished%3AFAQ40%3A1000063%3A1.0#__highlight","textElements":[{"url":"IM:FAQ:0120941d4319fa0152eec65d91007f64:en_US:published:FAQ40:1000063:1.0","type":"STRING","snippets":[{"text":"Windows ","level":3},{"text":"can help you increase productivity and efficiency ","level":1}]}],"clickThroughUrl":"ZlV_ZGdoSTdwUnlVMXJOaVQzYXRsT0J0YkE4aWZvVVZYMms5WVkzcDVmWH5YR21BbkpkbWJiczVEMHdBcjV3TWFwREdMNnlhZXhDeGIzRkkwZmtpNFFPWDEyRFFkdTk0VHp1SFFEMTJicm11MnRIS1RwdzZPUTdXZkZYeHc0NX4yVmhoNXZtQUpCeW5qZHZ5eFZlSnhiNWFHdXlUWnVMb2k3bEFDNDJYVEpEb1FnTjJfMGh6YXpSVDZoYUdpMjZpYks0amgyY2owd2luel8wfjFrWHdSY3BHVENaMmJndmlERHlaN2M3RHFfcjhhRVN5bmFWfklKRn5pTkgxQXBHZFo0emZ5N2lrQmRoNmViNUhBb1dvS1V5TlQxVDk4dWRJX3AxYzVMYVN_fjV6T01YellqfndSM2R3ZGVNaHRXMXY1eFNEUTZhZm1OejlpM0w3X3VZdHltejZWMWo1Y2J0Rlpf","href":"\/a_id\/1000063\/loc\/en_US\/s\/16777216_5761312cfcd34-04c3-4a16-8db8-19b5e115b190_SEARCH\/prTxnId\/41616055#__highlight","dataHref":"\/a_id\/1000063\/loc\/en_US"}]}],"facets":[{"id":"DOC_TYPES","desc":"DocTypes","count":9,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"DOC_TYPES.CMS-XML","desc":"ARTICLES","count":9,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"COLLECTIONS","desc":"Collections","count":9,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"COLLECTIONS.OKKB-FAQ","desc":"FAQ","count":9,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"CMS-CATEGORY_REF","desc":"Category","count":5,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-CATEGORY_REF.RN_CATEGORY_11","desc":"Cat2","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-CATEGORY_REF.RN_CATEGORY_8","desc":"Cat1","count":5,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"CMS-PRODUCT","desc":"Product","count":9,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-PRODUCT.RN_PRODUCT_1","desc":"Product1","count":9,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2","desc":"Product1.1","count":9,"inEffect":true,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_20","desc":"Product1.1.2","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_21","desc":"Product1.1.3","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_22","desc":"Product1.1.4","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_23","desc":"Product1.1.5","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_24","desc":"Product1.1.6","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_25","desc":"Product1.1.7","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_26","desc":"Product1.1.8","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_27","desc":"Product1.1.9","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_28","desc":"Product1.1.10","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_29","desc":"Product1.1.11","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2.RN_PRODUCT_3","desc":"Product1.1.1","count":5,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]}]}]}]},"facet":null},"searchState":{"session":"ZlVrSHBINVBhYVRVNm02blRFQlFVazNsYWRZd0FufmhYRDVlV0dlZE1DV2k2WjMwYlJyQThMblBwQ0VIZDRsbkVvN2pmZUF5NUlHQ3hMMjBvdmswVWtLcVZ0aX43RDV1eVNCN2FZcnRyTkZGS19hSmcxUjEwYnZpQjd2d3ZPNTZ0VGpEX3oxSHpTWGxCMThibF9QN3I1MHlHdEpLVW1yRmdN","transactionID":41616055,"priorTransactionID":41616055}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });
            this.instance.searchSource().fire("response", eo);
            var moreObject = Y.one('[id="F:CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2"]');
            moreObject.simulate('click');
            var moreFacets = Y.one('[data-id="CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2"]');
            Y.Assert.areSame(moreFacets.getElementsByTagName('li').size(), 9);
            var moreObject = Y.one('[id="F:CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2"]');
            moreObject.simulate('click');
            var moreFacets = Y.one('[data-id="CMS-PRODUCT.RN_PRODUCT_1.RN_PRODUCT_2"]');
            Y.Assert.areSame(moreFacets.getElementsByTagName('li').size(), 11);
            var topFacet = Y.one(".rn_ToggleExpandCollapse");
            topFacet.simulate('click');
            Y.Assert.isTrue(topFacet.hasClass('rn_FacetCollapsed'));
        },

        "Remove filter image should be displayed when the facet is selected": function() {
            this.initValues();
            var responseData = '{"searchState":{"session":"Tenant2ayP8EmZl","transactionID":2,"priorTransactionID":2},"searchResults":{"page":1,"pageMore":0,"results":{"results":[{"name":"ANSWER","pageNumber":0,"pageMore":0,"pageStart":0,"score":0.78057998418808,"pageSize":2,"unshownResults":1,"totalResults":3,"resultItems":[{"type":"unstructured","fileType":"HTML","answerId":16777216,"docId":16777226,"score":0.78057998418808,"relatedIds":[16777217],"title":{"url":"testhtmlcrwr.html","type":"STRING","snippets":[{"text":"Alert for webtesting","level":0}]},"link":"test.com","clickThroughLink":"test.com","similarResponseLink":"test.com","highlightedLink":"test.com","textElements":[{"url":"slc04oqn.us.oracle.com","type":"STRING","snippets":[{"text":"Find Answers","level":0}]}]}]}],"facets":[{"id":"DOC_TYPES","desc":"DocTypes","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"DOC_TYPES.HTML","desc":"HTML","count":3,"inEffect":true,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"COLLECTIONS","desc":"Collections","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"COLLECTIONS.Loan","desc":"Loan","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]}]},"facet":null}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });
            this.instance.searchSource().fire("response", eo);
            Y.Assert.isTrue(Y.one('[id="DOC_TYPES.HTML"]').hasClass("rn_ActiveFacet"));
        },

        "Clear link should remove all selected facet filters": function() {
            this.initValues();
            var responseData = '{"searchState":{"session":"Tenant2ayP8EmZl","transactionID":2,"priorTransactionID":2},"searchResults":{"page":1,"pageMore":0,"results":{"results":[{"name":"ANSWER","pageNumber":0,"pageMore":0,"pageStart":0,"score":0.78057998418808,"pageSize":2,"unshownResults":1,"totalResults":3,"resultItems":[{"type":"unstructured","fileType":"HTML","answerId":16777216,"docId":16777226,"score":0.78057998418808,"relatedIds":[16777217],"title":{"url":"testhtmlcrwr.html","type":"STRING","snippets":[{"text":"Alert for webtesting","level":0}]},"link":"test.com","clickThroughLink":"test.com","similarResponseLink":"test.com","highlightedLink":"test.com","textElements":[{"url":"slc04oqn.us.oracle.com","type":"STRING","snippets":[{"text":"Find Answers","level":0}]}]}]}],"facets":[{"id":"DOC_TYPES","desc":"DocTypes","count":3,"inEffect":true,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"DOC_TYPES.HTML","desc":"HTML","count":3,"inEffect":true,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"COLLECTIONS","desc":"Collections","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"COLLECTIONS.Loan","desc":"Loan","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]}]},"facet":null}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });
            
            this.instance.searchSource().fire("response", eo);
            Y.Assert.isTrue(Y.one('[id="DOC_TYPES.HTML"]').hasClass("rn_ActiveFacet"));
        },
        
        "Bad server responses should be handled properly": function() {
            this.initValues();
            var responseData = '{"searchResults":{"results":{"results":[],"facets":[]},"facet":null}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });
            
            this.instance.searchSource().fire("response", eo);
            
            Y.Assert.isNull(Y.one('.rn_FacetsTitle'));
            Y.Assert.isNull(Y.one('.rn_FacetsList'));
        },

        "Facet toggle should be working when the facet is selected for mobile view": function() {
            this.initValues();
            var responseData = '{"searchState":{"session":"Tenant2ayP8EmZl","transactionID":2,"priorTransactionID":2},"searchResults":{"page":1,"pageMore":0,"results":{"results":[{"name":"ANSWER","pageNumber":0,"pageMore":0,"pageStart":0,"score":0.78057998418808,"pageSize":2,"unshownResults":1,"totalResults":3,"resultItems":[{"type":"unstructured","fileType":"HTML","answerId":16777216,"docId":16777226,"score":0.78057998418808,"relatedIds":[16777217],"title":{"url":"testhtmlcrwr.html","type":"STRING","snippets":[{"text":"Alert for webtesting","level":0}]},"link":"test.com","clickThroughLink":"test.com","similarResponseLink":"test.com","highlightedLink":"test.com","textElements":[{"url":"slc04oqn.us.oracle.com","type":"STRING","snippets":[{"text":"Find Answers","level":0}]}]}]}],"facets":[{"id":"DOC_TYPES","desc":"DocTypes","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"DOC_TYPES.HTML","desc":"HTML","count":3,"inEffect":true,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"COLLECTIONS","desc":"Collections","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"COLLECTIONS.Loan","desc":"Loan","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]}]},"facet":null}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });
            this.instance.searchSource().fire("response", eo);
            if (widget.data.attrs.toggle_title) {
                var titleContainer = Y.one('.rn_FacetsTitle'),
                    facetContainer = Y.one('.rn_FacetsList');
                titleContainer.simulate('click');
                Y.Assert.areSame(facetContainer.getStyle('display'), 'block');
                titleContainer.simulate('click');
                Y.Assert.areSame(facetContainer.getStyle('display'), 'none');
            }
        },

        "Accessibility Tests": function() {
            this.initValues();
            var responseData = '{"searchState":{"session":"Tenant2ayP8EmZl","transactionID":2,"priorTransactionID":2},"searchResults":{"page":1,"pageMore":0,"results":{"results":[{"name":"ANSWER","pageNumber":0,"pageMore":0,"pageStart":0,"score":0.78057998418808,"pageSize":2,"unshownResults":1,"totalResults":3,"resultItems":[{"type":"unstructured","fileType":"HTML","answerId":16777216,"docId":16777226,"score":0.78057998418808,"relatedIds":[16777217],"title":{"url":"testhtmlcrwr.html","type":"STRING","snippets":[{"text":"Alert for webtesting","level":0}]},"link":"test.com","clickThroughLink":"test.com","similarResponseLink":"test.com","highlightedLink":"test.com","textElements":[{"url":"slc04oqn.us.oracle.com","type":"STRING","snippets":[{"text":"Find Answers","level":0}]}]}]}],"facets":[{"id":"DOC_TYPES","desc":"DocTypes","count":3,"inEffect":true,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"DOC_TYPES.HTML","desc":"HTML","count":3,"inEffect":true,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"COLLECTIONS","desc":"Collections","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"COLLECTIONS.Loan","desc":"Loan","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]}]},"facet":null}}',
                eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
                }),
                clearLink,
                activeFacet,
                spanForScreenReader,
                facetLink;
            
            this.instance.searchSource().fire("response", eo);
            clearLink = Y.one('.rn_ClearFacets');
            activeFacet = Y.one('.rn_ActiveFacet');
            facetLink = Y.one('.rn_FacetLink');
                
            // Check that the clear link has a descriptive text
            spanForScreenReader = clearLink.one('span');
            Y.Assert.isNotNull(spanForScreenReader);
            Y.Assert.isNotNull(spanForScreenReader.get('text'));
            Y.Assert.areSame(activeFacet.getAttribute('role'), 'button');
            Y.Assert.areSame(facetLink.getAttribute('role'), 'button');

            // Check that active facet link has a descriptive text
            spanForScreenReader = activeFacet.one('span');
            Y.Assert.isNotNull(spanForScreenReader);
            Y.Assert.isNotNull(spanForScreenReader.get('text'));
        },

        "Category tree view expand collapse for facets": function() {
            this.initValues();
            var responseData = '{"searchState":{"session":"Tenant2ayP8EmZl","transactionID":2,"priorTransactionID":2},"searchResults":{"page":1,"pageMore":0,"results":{"results":[{"name":"ANSWER","pageNumber":0,"pageMore":0,"pageStart":0,"score":0.78057998418808,"pageSize":2,"unshownResults":1,"totalResults":3,"resultItems":[{"type":"unstructured","fileType":"HTML","answerId":16777216,"docId":16777226,"score":0.78057998418808,"relatedIds":[16777217],"title":{"url":"testhtmlcrwr.html","type":"STRING","snippets":[{"text":"Alert for webtesting","level":0}]},"link":"test.com","clickThroughLink":"test.com","similarResponseLink":"test.com","highlightedLink":"test.com","textElements":[{"url":"slc04oqn.us.oracle.com","type":"STRING","snippets":[{"text":"Find Answers","level":0}]}]}]}],"facets":[{"id":"DOC_TYPES","desc":"DocTypes","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"DOC_TYPES.CMS-XML","desc":"ARTICLES","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"COLLECTIONS","desc":"Collections","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"COLLECTIONS.OKKB-FAQ","desc":"OKKB-FAQ","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"COLLECTIONS.OKKB-SOLUTIONS","desc":"OKKB-SOLUTIONS","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"CMS-CATEGORY_REF","desc":"IM Category","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-CATEGORY_REF.OPERATING_SYSTEMS","desc":"Operating Systems","count":2,"inEffect":true,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-CATEGORY_REF.OPERATING_SYSTEMS.WINDOWS","desc":"Windows","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]}]},{"id":"CMS-PRODUCT","desc":"Product","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-PRODUCT.COMPANIES","desc":"Companies","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]}]},"facet":null}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });

            this.instance.searchSource().fire("response", eo);
            var selectedFacetLi = Y.one('[id="CMS-CATEGORY_REF.OPERATING_SYSTEMS"]').get('parentNode');
            Y.Assert.isTrue(selectedFacetLi.one('span').hasClass("rn_FacetExpanded"));
        },

        "Product tree view expand collapse for facets": function() {
            this.initValues();
            var responseData = '{"searchState":{"session":"Tenant2ayP8EmZl","transactionID":2,"priorTransactionID":2},"searchResults":{"page":1,"pageMore":0,"results":{"results":[{"name":"ANSWER","pageNumber":0,"pageMore":0,"pageStart":0,"score":0.78057998418808,"pageSize":2,"unshownResults":1,"totalResults":3,"resultItems":[{"type":"unstructured","fileType":"HTML","answerId":16777216,"docId":16777226,"score":0.78057998418808,"relatedIds":[16777217],"title":{"url":"testhtmlcrwr.html","type":"STRING","snippets":[{"text":"Alert for webtesting","level":0}]},"link":"test.com","clickThroughLink":"test.com","similarResponseLink":"test.com","highlightedLink":"test.com","textElements":[{"url":"slc04oqn.us.oracle.com","type":"STRING","snippets":[{"text":"Find Answers","level":0}]}]}]}],"facets":[{"id":"DOC_TYPES","desc":"DocTypes","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"DOC_TYPES.CMS-XML","desc":"ARTICLES","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"COLLECTIONS","desc":"Collections","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"COLLECTIONS.OKKB-FAQ","desc":"OKKB-FAQ","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]},{"id":"COLLECTIONS.OKKB-SOLUTIONS","desc":"OKKB-SOLUTIONS","count":1,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"CMS-CATEGORY_REF","desc":"IM Category","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-CATEGORY_REF.OPERATING_SYSTEMS","desc":"Operating Systems","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"CMS-PRODUCT","desc":"Product","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-PRODUCT.COMPANIES","desc":"Companies","count":2,"inEffect":true,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"CMS-PRODUCT.COMPANIES.MICROSOFT","desc":"MicroSoft","count":2,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]}]}]},"facet":null}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });

            this.instance.searchSource().fire("response", eo);
            var selectedFacetLi = Y.one('[id="CMS-PRODUCT.COMPANIES"]').get('parentNode');
            Y.Assert.isTrue(selectedFacetLi.one('span').hasClass("rn_FacetExpanded"));
        },

        "Click event after empty search result": function() {
            this.initValues();
            var responseData = '{"searchState":{"session":"Tenant2ayP8EmZl","transactionID":2,"priorTransactionID":2},"searchResults":{"page":1,"pageMore":0,"results":{"results":[],"facets":[]},"facet":null}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });
            this.instance.searchSource().fire("response", eo);
            var content = Y.one(baseSelector + '_Content');
            Y.Assert.isFalse(content.hasChildNodes());

            this.initValues();
            var responseData = '{"searchState":{"session":"Tenant2ayP8EmZl","transactionID":2,"priorTransactionID":2},"searchResults":{"page":1,"pageMore":0,"results":{"results":[{"name":"ANSWER","pageNumber":0,"pageMore":0,"pageStart":0,"score":0.78057998418808,"pageSize":2,"unshownResults":1,"totalResults":3,"resultItems":[{"type":"unstructured","fileType":"HTML","answerId":16777216,"docId":16777226,"score":0.78057998418808,"relatedIds":[16777217],"title":{"url":"testhtmlcrwr.html","type":"STRING","snippets":[{"text":"Alert for webtesting","level":0}]},"link":"test.com","clickThroughLink":"test.com","similarResponseLink":"test.com","highlightedLink":"test.com","textElements":[{"url":"slc04oqn.us.oracle.com","type":"STRING","snippets":[{"text":"Find Answers","level":0}]}]}]}],"facets":[{"id":"DOC_TYPES","desc":"DocTypes","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"DOC_TYPES.HTML","desc":"HTML","count":3,"inEffect":true,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]},{"id":"COLLECTIONS","desc":"Collections","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[{"id":"COLLECTIONS.Loan","desc":"Loan","count":3,"inEffect":false,"incomplete":false,"showLink":true,"tempSelect":false,"children":[]}]}]},"facet":null}}';

            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(responseData)
            });

            this.instance.searchSource().fire("response", eo);
            Y.Assert.isTrue(Y.one('[id="DOC_TYPES.HTML"]').hasClass("rn_ActiveFacet"));
        },
    }));

    return suite;
});
UnitTest.run();
