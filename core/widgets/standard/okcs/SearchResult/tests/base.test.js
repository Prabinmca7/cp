UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SearchResult_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/SearchResult",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'SearchResult_0'
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.messageDOM = Y.one('.rn_NoSearchResultMsg');
                    this.title = Y.one('.rn_SearchResultTitle');
                    this.table = Y.one('table');
                    this.results = Y.all(".rn_SearchResultAnswerTitle");
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",
        
        "Accessibility Tests": function() {
            this.initValues();
            Y.Assert.isTrue(this.messageDOM.hasClass('rn_Hidden'));
            
            // Check that the class applied and and the value of the screen reader text corresponds to the type of file
            for (var i=0; i < this.results.size(); i++) {
                var spanScreenReader = this.results.item(i).previous(),
                    screenReaderFileType = this.results.item(i).getAttribute('data-type'),
                    className = 'rn_File_' + screenReaderFileType.toLowerCase().replace('-', '_');
                Y.Assert.areSame(spanScreenReader.get('text'), screenReaderFileType + ' file');
                Y.Assert.isTrue(spanScreenReader.hasClass(className));
                Y.Assert.isTrue(this.results.item(i).hasClass('rn_SearchResultAnswerTitle'));
                Y.Assert.isTrue(this.results.item(i).hasClass('rn_SearchResultContent'));
            }

            // Check valid links
            for (var i=0; i < this.results.size(); i++) {
                var linkHref = this.results.item(i).get('href'),
                    linkText = this.results.item(i).get('text');
                Y.Assert.isNotNull(linkHref);
                Y.Assert.isNotNull(linkText);
                Y.Assert.isFalse(linkText.length > widget.data.attrs.truncate_size + 3); // add 3 to truncate size for ellipsis '...'
            }
        },
        
        "Null response should be handled properly": function() {
            this.initValues();
            var data = '{"searchState":{"session":null,"transactionID":null,"priorTransactionID":null},"searchResults":{"page":1,"pageMore":0,"results":{"results":[]},"facets":[],"selectedLocale":null}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(data),
            });
            this.instance.searchSource().fire("response", eo);
            
            if (widget.data.attrs.hide_when_no_results)
                Y.Assert.isNull(Y.one('.rn_SearchResult'));
            else
                Y.Assert.isTrue(Y.one('.rn_SearchResult').hasClass('rn_NoSearchResult'));
        },
        
        "Internal document Url should have title, session and transactionId": function() {
            this.initValues();
            for (var i=0; i < this.results.size(); i++) {
                var documentUrl = this.results.item(i).one('.rn_SearchResultIcon.rn_File_cms_xml').getAttribute('href');
                if (documentUrl !== null) {
                    Y.Assert.isTrue(documentUrl.indexOf('/title/') > -1);
                    Y.Assert.isTrue(documentUrl.indexOf('/searchSession/') > -1);
                    Y.Assert.isTrue(documentUrl.indexOf('/txn/') > -1);
                    break;
                }
            }

        },
        
        "External document should not have search state informations": function() {
            this.initValues();
            for (var i=0; i < this.results.size(); i++) {
                var documentUrl = this.results.item(0).one('.rn_SearchResultIcon.rn_File_pdf').getAttribute('href');
                if (documentUrl !== null) {
                    Y.Assert.isFalse(documentUrl.indexOf('/title/') > -1);
                    Y.Assert.isFalse(documentUrl.indexOf('/searchSession/') > -1);
                    Y.Assert.isFalse(documentUrl.indexOf('/txn/') > -1);
                }
            }
        },
        
        "SearchResult should display excerpts": function() {
            this.initValues();
            for (var i=0; i < this.results.size(); i++) {
                Y.Assert.isNotNull(this.results.item(i).one('.rn_SearchResultExcerpt'));
            }
        },
        
        "External document should display file name as the answer title.": function() {
            this.initValues();
            for (var i=0; i < this.results.size(); i++) {
                var resultDom = this.results.item(i).one('.rn_SearchResultIcon.rn_File_pdf');
                var answerUrl = resultDom.getAttribute('href');
                var answerTitle = resultDom.getHTML();
                Y.Assert.isTrue(answerUrl.indexOf(answerTitle) > 0);
            }
        },
        
        "Display of icons based on fileType": function() {
            this.initValues();
            for (var i=0; i < this.results.size(); i++) {
                var spanScreenReader = this.results.item(i).previous(),
                    screenReaderFileType = this.results.item(i).getAttribute('data-type'),
                    className = 'rn_File_' + screenReaderFileType.toLowerCase().replace('-', '_');
                Y.Assert.isTrue(spanScreenReader.hasClass(className));
            }
        },
        
        "Navigation on result click": function() {
            this.initValues();
            this.instance._transactionID = 1;
            var data = '{"searchResults":{"page":1,"pageMore":4,"results":{"results":[{"name":"ANSWER","pageNumber":0,"pageMore":4,"pageStart":0,"score":0.7813606262207,"pageSize":10,"unshownResults":0,"totalResults":35,"resultItems":[{"type": "unstructured","fileType": "PDF","answerId": 16777221,"globalAnswerId": "1000009","docId": 29360140,"score": 0.75943398475647,"title": "Database Server Configuration","clickThroughLink": "?ui_mode=answer&prior_transaction_id=1547728825&iq_action=4&answer_id=16777221&turl=IM%3AFILE_ATTACHMENT%3A02014703cab4f06014c6f65dcf2007fbb%3Aen_US%3Apublished%3AFI1%3A1000009%3A1.0%3A%23Step%25203%2520%253D%253D%2520Siebel%25208.1%2520Database%2520Server%2520Configuration.pdf#Step%203%20%3D%3D%20Siebel%208.1%20Database%20Server%20Configuration.pdf","highlightedLink": "IM:FILE_ATTACHMENT:02014703cab4f06014c6f65dcf2007fbb:en_US:published:FI1:1000009:1.0:#Step%203%20%3D%3D%20Siebel%208.1%20Database%20Server%20Configuration.pdf#xml=foo/fas/pdf/highlight.jsp?cid=1502294986b4e-8a14-4650-8e1c-10a8241bf277&ui_mode=answer&prior_transaction_id=1547728825&iq_action=6&answer_id=16777221&highlight_info=29360140,287,294&turl=IM%3AFILE_ATTACHMENT%3A02014703cab4f06014c6f65dcf2007fbb%3Aen_US%3Apublished%3AFI1%3A1000009%3A1.0%3A%23Step%25203%2520%253D%253D%2520Siebel%25208.1%2520Database%2520Server%2520Configuration.pdf","textElements": [{"type": "STRING","snippets": [{"text": "4. Configure New Data Source.","level": 0}]},{"url": "IM:FILE_ATTACHMENT:02014703cab4f06014c6f65dcf2007fbb:en_US:published:FI1:1000009:1.0:#Step%203%20%3D%3D%20Siebel%208.1%20Database%20Server%20Configuration.pdf","type": "STRING","snippets": [{"text": "Test ","level": 3},{"text": "connection with SADMIN/SADMIN login.","level": 1}]}],"clickThroughUrl": "ZlVkeTZRTmZyNV95UzNnaVZkZVhtfn41U0ZxVWdtRnhyMjdLeVYzNXJRQnRiN3F3NzEwM2Z3a29uZkRzUVlla0h2dHJjOHZYUk5Sa0lLc3pleUFiaGNlSF9HUkNCYn45Q2VZbEhzRnlXa1JYMTNtcnY1ZjdLbUhicjhvZ1lGNm9uV0E4a0Q3UmJtVX5mY19vNUdVOFl_YXBOWTJyOXVXdklTTHNBODVSWnZHNmtvclNhblBIbm5DdGVpTkhJUG1VVzlXcldiWFpQQzZwdVFmVmF3cVoweTJNR3lzdU5pM21tVHlYMmJDQmkzaEZETG00SGJvfjg3UnRhflhvQml2T2dkcVYxX3ZINDU4eTRFN2ZOaXEwa2FuRlJOdURIU2d2MzZGSndyTGU1S2NDZnlxNVc4ekdMTER_SmRKcmFGM19sYVpFWVhjN35qMVdpTjZ4d3R_TWlZWm00TF92Y2Vtbmh_M01IalhZeHVXSXhCMm1GVjlaR3U2d2FyTHBwY3RyQzZLVE16Z35CYkR5OUtUbmQycWhXNUdldjloZ3BvWUduTU0xUEVEMEVweVlMWmg1c0k5cGVJWTNmZ2ZhRXl4cmdqZGx3MHlmNkx1YlEh","href": "/ci/okcsFattach/get/16777221/B3806A4B93A44DDE8BC16061734F4223"}]}]},"facet":null,"selectedLocale":null}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(data),
            });
            this.instance.searchSource().fire("response", eo);
            
            var urlToNavigate;

            RightNow.Url.navigate = function(url) {
                urlToNavigate = url;
            };
            Y.one('a').simulate('click');
            if (urlToNavigate !== undefined && urlToNavigate !== ''){
                Y.Assert.isTrue(decodeURIComponent(urlToNavigate).indexOf('/ci/okcsFattach/get/16777221/B3806A4B93A44DDE8BC16061734F4223')  >  -1);
            }
        },
        
        "Bad server response should be handled properly": function() {
            this.initValues();
            var data = '{"searchResults":{"page":1,"pageMore":0,"results":{"results":[]},"facet":null,"selectedLocale":null}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(data),
            });
            this.instance.searchSource().fire("response", eo);

            if (widget.data.attrs.hide_when_no_results)
                Y.Assert.isNull(Y.one('.rn_SearchResult'));
            else
                Y.Assert.isTrue(Y.one('.rn_SearchResult').hasClass('rn_NoSearchResult'));
        },
        
        "Verify that an updated response from the server updates the widget": function() {
            this.initValues();
            var data = '{"searchState":{"session":"Tenant21IP*s2Zl","transactionID":1,"priorTransactionID":1},"searchResults":{"page":1,"pageMore":4,"results":{"results":[{"name":"ANSWER","pageNumber":0,"pageMore":4,"pageStart":0,"score":0.7813606262207,"pageSize":10,"unshownResults":0,"totalResults":35,"resultItems":[{"type":"unstructured","fileType":"PDF","answerId":16777216,"href":"abcdef","docId":16777253,"score":0.7813606262207,"relatedIds":[16777217],"title":{"url":"www.pdf995.com/samples/pdf.pdf","type":"STRING","snippets":[{"text":"Enabling Alerts in Windows 7","level":0}]},"link":"http://slc04oqn.us.oracle.com/Test/Enabling%20Alerts%20in%20Windows%207.pdf","clickThroughLink":"?ui_mode=answer&prior_transaction_id=1&iq_action=4&answer_id=16777216&turl=http%3A%2F%2Fslc04oqn.us.oracle.com%2FTest%2FEnabling%2520Alerts%2520in%2520Windows%25207.pdf","similarResponseLink":"?ui_mode=answer&prior_transaction_id=1&iq_action=12&answer_id=16777216&related_ids=16777217","textElements":[{"url":"http://slc04oqn.us.oracle.com/Test/Enabling%20Alerts%20in%20Windows%207.pdf","type":"STRING","snippets":[{"text":"Enabling Alerts in ","level":1},{"text":"Windows ","level":3},{"text":"7","level":1}]}]}]}]},"facet":null,"selectedLocale":null}}';
            var eo = new RightNow.Event.EventObject(null, {
                data: RightNow.JSON.parse(data),
            });
            this.instance.searchSource().fire("response", eo);
            Y.Assert.isFalse(Y.one('.rn_SearchResult').hasClass('rn_NoSearchResult'));
            Y.Assert.isNotNull(Y.one('.rn_SearchResultExcerpt'));
            var excerptNodes = Y.one('.rn_SearchResultExcerpt').getDOMNode().children;
            Y.Assert.areSame(excerptNodes.length, 3);
            Y.Assert.areSame(excerptNodes[0].innerHTML, "Enabling Alerts in ");
            Y.Assert.areSame(excerptNodes[1].innerHTML, "Windows ");
            Y.Assert.areSame(excerptNodes[2].innerHTML, "7");
            Y.Assert.areSame(Y.one('.rn_SnippetLevel3').get('text'), 'Windows ');
        },
        
        "Checking fileType attribute is available": function() {
            this.initValues();
            for (var i=0; i < this.results.size(); i++) {
                var resultFileType = this.results.item(i).getAttribute('data-type');
                Y.Assert.isNotNull(resultFileType);
            }
        }
    }));

    return suite;
}).run();
