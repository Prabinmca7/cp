UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'OkcsSmartAssistant_0'
}, function(Y, widget, baseSelector){
    var smartAssistantTests = new Y.Test.Suite({
        name: "standard/okcs/OkcsSmartAssistant - metadata position functionality"
    });
    smartAssistantTests.add(new Y.Test.Case({
        name: 'Test metadata position',

        setUp: function() {
            this._origMakeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = Y.bind(function() {
                this.makeRequestCalledWith = Array.prototype.slice.call(arguments);
            }, this);
        },

        tearDown: function() {
            RightNow.Ajax.makeRequest = this._origMakeRequest;
            this._origMakeRequest = null;
            this.makeRequestCalledWith = null;
        },

        suggestionResponse: {
            sessionParm: "",
            status: 1,
            sa: {
                canEscalate: true,
                token: '7',
                suggestions:[{
                    type: 'AnswerSummary',
                    list: [{
                        ID: 1,
                        title: "Test",
                        href: "http://test.com"
                    },
                    {
                        ID: 2,
                        title: "banana",
                        href: "http://banana.com"
                    },
                    {
                        ID: 3,
                        title: "apple",
                        href: "http://apple.com"
                    }]
                }]
            }
        },

        okcsAnswerResponse: {"error":null,"id":"1000001","contents":{"title":"Proof Submission document in Windows","docID":"SO2","answerID":1000001,"version":"2.0","published":"Published","publishedDate":"02/04/2015","data":[{"content":{"SOLUTIONS":{"name":null,"type":"NODE","xPath":"SOLUTIONS","depth":0},"SOLUTIONS/TITLE":{"name":"Title","type":"DISPLAY","value":"<a name=\"__highlight\"></a><span class=\"ok-highlight-sentence\"><a name=\"__highlight\"></a><span class=\"ok-highlight-sentence\">Proof Submission document in Windows</span></span>","xPath":"SOLUTIONS/TITLE","depth":1},"SOLUTIONS/DESCRIPTION":{"name":"Description","type":"DISPLAY","value":"<a name=\"__highlight\"></a><span class=\"ok-highlight-sentence\"><a name=\"__highlight\"></a><span class=\"ok-highlight-sentence\">Proof Submission document in Windows</span></span>","xPath":"SOLUTIONS/DESCRIPTION","depth":1},"SOLUTIONS/FILE_ATTACHMENT":{"name":"File Attachment","value":"Scan IPSF Submission Process Online FY 2014-15_1.pdf","type":"FILE","filePath":"http://slc01fjo.us.oracle.com:8228/fas/resources/okcs__ok152b1__t2/content/draft/08202020657ca941014b5436e418007fd2/08202020657ca941014b5436e418007fca/Scan%20IPSF%20Submission%20Process%20Online%20FY%202014-15_1.pdf","encryptedPath":"ZlVoOH5FV35XMkhDTXN5RFV1ZlFhUHNfNFNNdmN0bmZCZ2hxb0kyMGFqOXk2cW5DRHFIeUVNZFlpak91bFloaEh_MUpFQ2dyb1RWb3VmQ3FSfnRndXNoUmh6QzA1bGhCbElqen4wWHdlaThyWF8zbktTZnVzMmNjb2dGNWE5SEw3REJWTjR_bkxJQklrSVdHWk9hemxmcmgzRlpJOF9aUDhoflM2QmJCcFZJVHlIbGtRc3dRRHJQajlTYm1iX0kzdzA4T0czcmZnMnNGTldqOUk4QWlQWUdycG5XaGloclFac3JCTDh1QzE5flVYMXhQSDZidk5jSjJaRmxMWVpuM2c3cDNDSlRRU1FheWMzZGhfZnJFVklWb29pRFdUS1gxc2ttdX52WEVFRF83ZHU3bTljS1QwRWtfdUxiQk1XfllJWEVHTEExOHJXYk80UFFBbWJrYVF2THZGN1hPaU56VHdoUkRYMmlGd1hPZm54RkU1aWI5d0VGdyEh","xPath":"SOLUTIONS/FILE_ATTACHMENT","depth":1},"xpathCount":{"SOLUTIONS/TITLE":1,"SOLUTIONS/DESCRIPTION":1,"SOLUTIONS/FILE_ATTACHMENT":1,"SOLUTIONS":1}},"metaContent":null}],"contentType":{"recordID":"08202020657ca941014b5436e418007ff5","referenceKey":"SOLUTIONS","name":"Solutions"},"resourcePath":"http://slc01fjo.us.oracle.com:8228/fas/resources/okcs__ok152b1__t2/content/draft/08202020657ca941014b5436e418007fd2/08202020657ca941014b5436e418007fca/","locale":"en_US","error":null}},

        okcsHtmlResponse: {"error":null,"id":"16777216","contents":"<html><head><base href='http://slc04oqn.us.oracle.com/test/' /><title>slc04oqn.us.oracle.com - /test/</title></head><body><H1><a name='__highlight'></a><span class='ok-highlight-sentence'>slc04oqn.us.oracle.com - /test/</span></H1><hr>\r\n\r\n<pre><A HREF='/'>[To Parent Directory]</A><br><br> 9/20/2013 11:11 PM         9975 <A HREF='/test/!@%23$%25^&()%20Personal%20LOAN.docx'>!@#$%^&() Personal LOAN.docx</A><br> 9/20/2013 11:12 PM        13802 <A HREF='/test/!@%23$%25^&__%20Personal%20LOAN.pdf'>!@#$%^&__ Personal LOAN.pdf</A><br> 7/15/2013  3:31 AM           38 <A HREF='/test/Car%20Closing%20Loan.txt'>Car Closing Loan.txt</A><br> 8/28/2015  5:39 AM        16338 <A HREF='/test/Car%20Loan%20%25%20$%23@%20_25.docx'>Car Loan % $#@ _25.docx</A><br> 9/20/2013 11:08 PM        11514 <A HREF='/test/Car%20Loan%20%25%20$%23@%20_25.pdf'>Car Loan % $#@ _25.pdf</A><br> 9/20/2013 11:07 PM         9982 <A HREF='/test/Car%20Loan%20%25%2025.docx'>Car Loan % 25.docx</A><br> 9/20/2013 11:08 PM        10397 <A HREF='/test/Car%20Loan%20%25%2025.pdf'>Car Loan % 25.pdf</A><br> 7/22/2013  3:51 AM           25 <A HREF='/test/Car%20Loan%20Closure.txt'>Car Loan Closure.txt</A><br> 7/12/2013  2:17 AM        10029 <br> 8/24/2015  1:10 AM         2228 <A HREF='/test/IQXML.iqxml'>IQXML.iqxml</A><br>  4/3/2015  1:14 AM        &lt;dir&gt; <A HREF='/test/Language/'>Language</A><br>12/21/2013 11:29 AM         1184 <A HREF='/test/li.html'>li.html</A><br> 7/10/2014  7:41 AM         7420 <A HREF='/test/Loan%20Calculation%20sheet.ods'>Loan Calculation sheet.ods</A><br> 7/19/2013  6:08 AM         9973 <A HREF='/test/Loan%20EMI%20%25.pdf'>Loan EMI %.pdf</A><br> 8/23/2015 11:32 PM       121975 <A HREF='/test/Loan%20ODP.odp'>Loan ODP.odp</A><br> 7/11/2014  3:20 AM        31781 <A HREF='/test/Loan%20recovery%20amount%20for%20Car%20loan%20and%20clearing%20of%20car%20loan.rtf'>Loan recovery amount for Car loan and clearing of car loan.rtf</A><br> 7/11/2014  3:19 AM        26112 <A HREF='/test/Loan%20recovery%20amount%20for%20Home%20loan.doc'>Loan recovery amount for Home loan.doc</A><br> 7/11/2014  3:20 AM         9915 <A HREF='/test/Loan%20recovery%20for%20Personal%20loan.docx'>Loan recovery for Personal loan.docx</A><br>12/21/2013 11:29 AM         1184 <A HREF='/test/myfile.html'>myfile.html</A><br>12/20/2013 12:51 AM         4385 <A HREF='/test/new.html'>new.html</A><br>12/21/2013 11:29 AM         1184 <br></pre><hr></body></html>","ajaxTimings":[0,{"key":"https://okcsdv2225-qp.dv.lan/srt/api/v1/search/answer?priorTransactionId=361781437&answerId=16777216&highlightFlag=true&trackClickFlag=true | POST | Status code - 200","value":0.463939},{"key":"getIMContent | OkcsAjaxRequestController","value":0.48711109161377}]},

        verifyPromptContents: function(prompt) {
            Y.Assert.isTrue(prompt.hasClass('rn_Prompt'), "prompt is messed up!");
            Y.Assert.areSame('DIV', prompt.get('tagName'), "prompt is messed up!");
            Y.Assert.areSame(3, prompt.get('childNodes').size());
            Y.Assert.areSame(0, prompt.get('firstChild').get('textContent').indexOf(widget.data.attrs.label_prompt));
        },

        verifyListContents: function (list) {
            Y.Assert.isTrue(list.hasClass('rn_List'), "answer list is messed up!");
            Y.Assert.isTrue(list.hasClass('rn_InlineAnswers'), "answer list is messed up!");
            Y.Assert.areSame('UL', list.get('tagName'), "answer list is messed up!");

            var answerLinks = list.all('a');
            Y.Assert.areSame(this.suggestionResponse.sa.suggestions[0].list.length, answerLinks.size());

            answerLinks.each(function (item, index) {
                Y.Assert.areSame('rn_InlineAnswerLink rn_ExpandAnswer', item.get('className'));
                Y.Assert.areEqual(index + 1, item.getAttribute('accesskey'));
                Y.Assert.areSame('rn_' + widget.instanceID + '_Answer' + (index), item.get('id'));
            });
        },

        verifyInjectedOkcsAnswerContents: function () {
            var answerDetail = Y.one('.rn_AnswerSolution');

            Y.Assert.isNotNull(answerDetail);

            var children = answerDetail.getDOMNode().children;

            Y.Assert.areSame(2, children.length);

            var firstChild = children.item(0),
                secondChild = children.item(1);

            Y.Assert.areSame('rn_AnswerDetail', firstChild.className);
            Y.Assert.isNotNull(firstChild.firstElementChild.textContent);

            Y.Assert.areSame('rn_AnswerInfo', secondChild.className);
            Y.Assert.isNotNull(secondChild.firstElementChild.textContent);
        },

        "The dialog's inline answer content is properly constructed": function() {
            widget._displayResults("response", [ { data: { result: this.suggestionResponse } } ]);

            Y.Assert.areSame(this.suggestionResponse.sa.token, RightNow.UI.Form.smartAssistantToken);

            var dialogContent = Y.one(baseSelector + "_DialogContent"),
                contents = dialogContent.get("children");

            this.verifyPromptContents(contents.item(0));
            this.verifyListContents(contents.item(1));
        },

        "Clicking on an inline answer link fires a global request event before making an ajax request": function() {
            var dialogContent = Y.one(baseSelector + "_DialogContent"),
                clickedAnswer = dialogContent.one('a:first-child'),
                hasFiredRequest = false;

            RightNow.Event.subscribe("evt_getAnswerRequest", function(type, args) {
                hasFiredRequest = true;
                args = args[0];
                Y.assert(!this.makeRequestCalledWith);
                Y.Assert.areSame(args.w_id, widget.instanceID);
                Y.Assert.areSame(args.data.objectID, clickedAnswer.getAttribute('data-id'));
            }, this);
            clickedAnswer.simulate('click');
            Y.Assert.isTrue(hasFiredRequest, 'The request for inline content did not fire.');
        },

        "Content from answer response is injected into the list after a global response event is fired": function() {
            var dialogContent = Y.one(baseSelector + "_DialogContent"),
                clickedAnswer = dialogContent.one('a:first-child'),
                hasFiredResponse = false;

            RightNow.Event.subscribe("evt_getAnswerResponse", function() {
                hasFiredResponse = true;
            }, this);
            if (Y.one('.rn_AnswerSolution') !== null ){
                widget._onIMContentResponse(this.okcsAnswerResponse, {});
                this.verifyInjectedOkcsAnswerContents();
            }
        },

        "Verify external html content loading": function() {
            var dialogContent = Y.one(baseSelector + "_DialogContent"),
                clickedAnswer = dialogContent.one('a:first-child'),
                hasFiredResponse = false;

            RightNow.Event.subscribe("evt_getAnswerResponse", function() {
                hasFiredResponse = true;
            }, this);
            if (Y.one('.rn_AnswerSolution') !== null ){
                widget._onIMContentResponse(this.okcsHtmlResponse, {});
                this.verifyInjectedOkcsAnswerContents();
            }
        }
    }));
    return smartAssistantTests;
}).run();
