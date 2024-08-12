UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'OkcsSmartAssistant_0'
}, function(Y, widget, baseSelector){
    var smartAssistantTests = new Y.Test.Suite({
        name: "standard/okcs/OkcsSmartAssistant - inline answer functionality"
    });
    smartAssistantTests.add(new Y.Test.Case({
        name: 'Test inline answer content',

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
                        title: "Test PDF",
                        href : "/answer_data/ZlVzZzl5YVJlNGVrNUVpTmtRMjRUTFlxODR_emV4VF9wUUFBTTkya1lLc09mc0duVXZsR1J3S2N5bHNVYn51WWp_aXEwT0RlWjFaNVJ1cWlzY25BdzZyeGgxT0h5cjJGZ2FjRU1ncm1rbEp0cG02b1BOcTd2c2V6ZVFWek5GeUZEdGsxQlBjQUREZnZPMTMzaWlIMnFCfmZxam5KN1A3ck9JUk9ZUDJqajVOejJRRFVmTGEwblV1N1VrMVNoV1FVS1VuaWVKeFIwTmtkSTVnUGU1Q054TTJpQmNkUHlRTzRFT21RTWNyNENmbHRHTVNKMVY1ZEdfUUFzMXYyM01LODQxTTZZTFZKSjJVQX5xNFhtT2JPaEg5TDNxSDhCZk1rdV91bTB2RXdsZ3BIOFVLY2R4Y0dLcnBmOGN3dWdmWWRyZ2VwSn4yeUdnb2t_eFBScHI2QTk0NmoyamRoeEhyOTF2c3d3UmVqQWthbWl6OUNPZ1BmajNLQSEh",
                        url: "http:\/\/slc01fmq.us.oracle.com:8080\/content\/Dev\/WebCollection2\/Motorola_Milestone_Quick_Reference_Sheet.pdf#xml=foo\/srt\/pdf\/highlight.jsp?cid=15022Wci94l7m&ui_mode=answer&prior_transaction_id=1702296874&iq_action=6&answer_id=16777216&highlight_info=16777244,2,3&turl=http%3A%2F%2Fslc01fmq.us.oracle.com%3A8080%2Fcontent%2FDev%2FWebCollection2%2FMotorola_Milestone_Quick_Reference_Sheet.pdf",
                        docID : 16777244,
                        answerID : 16777216,
                        clickThrough : "ZlVFTXFIUFA2TFVnak40b09TeGt5NXVxQ1NoZXZLM0gwMmtWcUs1Mlo1b2dDQXRGYVlsMV9RbFRrQmlrd3ptZnRIQVlPaVUyVVpVUE5ZQ21XRFF6RGVVTEVyakE0STZvRmtHZ1N5OWRGbVdCTW5zV19sMkNvN21vWU8wRGFWNk1LflJXWl81S1dOZTFud1hybDZSb1U4dkJMcGtUT2tBRlNLVjRpR1pMRUNwM213SlZRfm1kVVg0RUE0WGJ5YXpQZHd6ZnlXSE1PV3E5WWtWbW1sckg5TkZnMUs0R3pVWFNtfjdSbVhrakFxQkVGXzV4TmtrZE5xRnlDfkRNZkd3S2dPaFk5NzRXQlppbkpRMnJHTk85aXdXZUFEMXdVVURqfnkydk5VNFB4SjNYb3RDc0lQbnpZeFVzQjBfZkRQVnN1VHphMjdpcGtXYWRCRV9WOHp6Y2dMcXcxNGprTU1vdzU3MG9ZRGgzNjVndll2MTkzM2VXVElaZEZKaHpoN05qOURCbnhITmV3T0VKc1ZzZW5zS29SRmVXUn5INmRuTlhlT3BfMzl2TTZ1bTQ4MDMyc3Z_VWxtekhhQTFNVkh2XzFiOUR2MVA0NzdXOHRTdHZiMUtaNDRfTVVUTjB2NU1xX0NUdGVVTVl_OFUyZTNHVElEbFJWY3B3ISE!",
                        iqAction : "4",
                        href : "\/answer_data\/ZlVIRkJEQUN1cUN0RkZSNmZzYzVtNmlFfnJFdkxYdV9Sb2xmU2QweGJtM2pzb3F4TjBoX2JpWXRERENQdEp4QURVNDJFY3Y2bG1aM19SM1VJU0QzZGI2MXNUQ3ZxQVRvVDRBN1FHWHo1UDFRUE1ZU0ZEdTVRTEJHR2VXcGs0YjFHR2JfQ28wb2JLaVg5WDc5OFVzWVp_blh2azkxaGk3VWFSUUJISUNQbH5LTkkzRU5MdDdoZnNtZTdKYVM1Y3Q0bmhyR1JJcXJjN0dkMFV5dEp5STlLQ09HOWh4OFQ3c2hlTERZdkE1SnB_RTdRZFhjdGJ1OGx_bW5VbktyZDQ2RUp2UFUzVzJDa3ZGMk5WejZBM3dMV2hZZTBDaE5WT1pjcFVSVEd0WkZXcm1hRkE4aDkybU9lQWF3ISE!",
                        type : "PDF"
                    },
                    {
                        title: "banana",
                        docID : 14680075,
                        answerID : 16777218,
                        clickThrough : "ZlVUOWhpVTZGVjQza1VtUGZ2Tkl4cHc0OE1LfnZ4NkxNaUVlSVc1U2ZKS0VjTnB1RFJ3dTFJWFVFTl9ZZVdkX3V3anJ0bVNWSTZZc0FJTmpIVWJoU2YxOFBYUnEzZXozUU82RXFGT3FSUmdPNTBfQlRScXlsV3I0YzJxY3cyUVpRd0FGc0xwN2NWNl9PWHI4ZDY1RktFRmxyYWsyQX5lQ2FRRnFyellDb01PczdhMFNjSEYxSkFMMUFjS1lOT1ByNjlCTlo2cH5CUEVJOWNJZ3hRZm1aVjc2OWw4VmJwS2N0Qk5qQTIxc05LQXZzWGhrbGdFMDZxb3RVSDlRRG5vV1loTThGMHZZemNfd0tUdXpQS2NIdVdtRE1VRHVLazllT25Hc09IUlNyb1hJS35UdmE4aklUTExnISE!",
                        iqAction : "4",
                        imDocID : 1000018,
                        highlightLink : "ZlUxUUNDdENzS2ZySjNBTE1Qd3pIYjh5VUxDMGl3cDNZTGc5cEV5OH5HcnFhdEQ2VUx_dzJXZmV1WEJkS1pkTWtLdlRYYkdmNldxfjJCclphbFFZX0xkZ343aHVSSVNXRm5xakVBSGpCdkNYR2JjUDczazJTdjYySEx0TWlVbWYxbEFnS1F1ZFZpNTd4R0phd0paRWNvcDBwMHdnfnBLMnViZWVEWUFQaGZTdlBWQ0VtUkRfbWQ0OFVDVElwRzZEcENHcU5sSEw0bGRUREp2T2lKaWZzblVxVVdSeWZaeUd1cFZKVkpFMmFMdWlZeVNJeU9TZm5iX2NGZUxNMkVKWHA5R3YybWlTeFdiZTFWbmJaRW9sVmNJdm9sd3d2VVZERnM1UEFUeURxbHREelBKVXpucmxFMDlmSDJER18zbjg5OENmams3bmkyOGhNemQ4Yk9fNFd4WlNtU1cyclNHc01LdFpicE9JUVg3VHpNdHVzMVBjOUpJUSEh",
                        type : "CMS_XML",
                        href : "\/a_id\/1000018\/loc\/en_US\/answer_data\/ZlU2MDBLbEhhU1NYTzVwMkt3c0xCQVJteFNVT3dOTmFTbVdkenltRnE2RzdVbUtJbnh5NFdiQ2VnVU9_eDMwRkFyNE1Kak00ZWo0RlpMZDdoTEZ6YlNST29rckJMTEFoSlVNeGJIR043dW4xNWM3UHFKTHJtYWZQVFBXNF9tZHhEc1FlczNBNlNYdVBZSGlaaU1Oc3N0aTh5anhGSnFiTDE3XzB3Y2g1OGgyQ1pKV3MwaWYyVFZCVmxpbzdUZ1NraTgyYU41eTVJMW1JcH5EWkxqRG9IdEE0NGdmc1A5QnNtcUhOTzV_dExZWk4xZVQ0Z243cml0d05_aFJfWVdzb1lyZVllaXQ5WmVMQ28yMWNBOEJtd0dFUVNDNktqNUgweWVFZ0xGdnZCakY5OElIaVU3N3lsbmRDdGdHTGFPSTV4RWJkd2oxOElQZEIzZjJlMmlCbn54bmVSaEVaM3ZvNEQ5dFdRV0dNaWZGbn4zcjR5Qlg1aDJKbUc0TkszdDcxRXNzRUg0NEFCelFIVSE!"
                    },
                    {
                        title: "apple",
                        docID : 1000013,
                        answerID : 1000013,
                        clickThrough : "ZlVTbUxIMlQ0UkN4bDZ0SFZtX3UwYWVXcmZjcExJbmZwZ3JfcHVxc2ZsbE9zQTMzR0ZXSml5VkZmTkJQOXV6SH56YnBEWWdST0tpSE92Y09sQ2JJd3N5U25GRXNoaTZmY2t_TlVveTh6OThLSmZkSFJCblpqRVNBSkJvfmJVSU1RUnZuWGQ0TW14cEp0V3dQUERSMFpCc3hNM3NIUzU4MFhVWXBDa3lleXRBNXZQNGl6cEFhbURDdjNvZWhwOHp6RHpTZjU3dGZQNzhvQllQS2JoV1hZfktOVkNNTDh4cnMxdEtIYk5MNU5XWkxya2pQektOUjhRU1RGVmlwM04yUH4ybU14b0c1WTdTTGNlbDIzUHZ0Y3F1MG5lNlc3eFJ3ZzVGbmtfMjJJbDk4WVlzNTRnZGhBdExnISE!",
                        iqAction : "4",
                        imDocID : 1000013,
                        highlightLink : "ZlU5MGRid2tRdFBoNWIwNFB3cG5ubTRCVDhZVE80VWdTc1FhdWdzbGdJQ0VPbm1LWmhtUUw0c29mS2tRNE9hanFTTWhaVVJ_S19VaVUzUDNoeTlmOUtUWk52ZEROOHRKTk8zYk5fan5Vc1hhZHNNaFBKV3R3UTllOUk5RUV3VUFNVlFYRkIzZGFrUktheGJBSXJUMVk0dnVnQlFXQ28wcVYxYWRXbXJXQldZYUJXMXpLNWw3T2NOOG50eHF_cjk0ZDYyMjVTdX5QWnZNWXlfM3I1ejUwMk93a09YaTVQeE1aV3I5UG80dl9iOH56dDJJN1ZRdWNOQ0ZjdFVrZWd5QWM1Z2ZpeTVRc05FdF9VRGhpUXhfVVMzbG5NcUptTnZSV0htUmwzR3NSWlhzUFgyZjdFWW91T3RlRjNKc3h3TzVhQl9xeUFIWDhzYVZSRHB1WlVPM1YxVmhMZzhfNn5Fb3d0TktDX2o4dE54bX5JcWhUejlRZGt1dyEh",
                        type : "CMS_XML",
                        href : "\/a_id\/1000013\/loc\/en_US\/answer_data\/ZlVwMXlEMlFQRFA5djJpSnhIekJQMmpUS2tNY05qU0pRY3pNSndic1NWNXRBcjJqd3ZyNmliOHEzYWVDZFBCMkhIbGVMeGZzc25vREE0Nl9GOHJBeldJTmRJRF9LbGljS1RqbFV5cVRJeFFaMFZsZFZ6aWczMnY5U3Y5MlpvbXYwUmtYfmNHOWl3M0xXMXNRTHk3QVhJY0VlRGpPV0tfT0NFQTVYbkxzNFU2cGVjTW9fc2VJMmZEdG5UM1Z4cGVqM35ZVnZSOU5_RXJGaVFZa1VETGlWYURXa0p4N2VxOUJNVlNfeXQ2dHIwdXVsS0ZuV1Qydmh2dkpVcjBNVDN3Rnlpamd2aVVhYjh3aURoNXBnSmVHdEFGZnRVc05iSXdGWXJaNlZFamZ0YzZ_bjZYbXVrQVJ3RnNzdnpXZUFXWm16MVdsSzNHZ050SX5SREcwUmF4T35QflpYSVJzcHpKWHcyeXdYQ1NPVFMzZ05FdkcySnpRfmJ2Q3lkbXdBamRIUEQ0VEd_aHJmWlhuQSE!"
                    }]
                }]
            }
        },

        okcsAnswerResponse: {"error":null,"id":"1000001","contents":{"title":"Proof Submission document in Windows","docID":"SO2","answerID":1000001,"version":"2.0","published":true,"publishedDate":"02\/04\/2015","content":{"SOLUTIONS":{"name":null,"type":"NODE","xPath":"SOLUTIONS","depth":0},"SOLUTIONS\/TITLE":{"name":"Title","type":"DISPLAY","value":"<a name=\"__highlight\"><\/a><span class=\"ok-highlight-sentence\"><a name=\"__highlight\"><\/a><span class=\"ok-highlight-sentence\">Proof Submission document in Windows<\/span><\/span>","xPath":"SOLUTIONS\/TITLE","depth":1},"SOLUTIONS\/DESCRIPTION":{"name":"Description","type":"DISPLAY","value":"<a name=\"__highlight\"><\/a><span class=\"ok-highlight-sentence\"><a name=\"__highlight\"><\/a><span class=\"ok-highlight-sentence\">Proof Submission document in Windows<\/span><\/span>","xPath":"SOLUTIONS\/DESCRIPTION","depth":1},"SOLUTIONS\/FILE_ATTACHMENT":{"name":"File Attachment","value":"Scan IPSF Submission Process Online FY 2014-15_1.pdf","type":"FILE","filePath":"http:\/\/slc01fjo.us.oracle.com:8228\/fas\/resources\/okcs__ok152b1__t2\/content\/draft\/08202020657ca941014b5436e418007fd2\/08202020657ca941014b5436e418007fca\/Scan%20IPSF%20Submission%20Process%20Online%20FY%202014-15_1.pdf","encryptedPath":"ZlVoOH5FV35XMkhDTXN5RFV1ZlFhUHNfNFNNdmN0bmZCZ2hxb0kyMGFqOXk2cW5DRHFIeUVNZFlpak91bFloaEh_MUpFQ2dyb1RWb3VmQ3FSfnRndXNoUmh6QzA1bGhCbElqen4wWHdlaThyWF8zbktTZnVzMmNjb2dGNWE5SEw3REJWTjR_bkxJQklrSVdHWk9hemxmcmgzRlpJOF9aUDhoflM2QmJCcFZJVHlIbGtRc3dRRHJQajlTYm1iX0kzdzA4T0czcmZnMnNGTldqOUk4QWlQWUdycG5XaGloclFac3JCTDh1QzE5flVYMXhQSDZidk5jSjJaRmxMWVpuM2c3cDNDSlRRU1FheWMzZGhfZnJFVklWb29pRFdUS1gxc2ttdX52WEVFRF83ZHU3bTljS1QwRWtfdUxiQk1XfllJWEVHTEExOHJXYk80UFFBbWJrYVF2THZGN1hPaU56VHdoUkRYMmlGd1hPZm54RkU1aWI5d0VGdyEh","xPath":"SOLUTIONS\/FILE_ATTACHMENT","depth":1},"xpathCount":{"SOLUTIONS\/TITLE":1,"SOLUTIONS\/DESCRIPTION":1,"SOLUTIONS\/FILE_ATTACHMENT":1,"SOLUTIONS":1}},"metaContent":null,"contentType":{"recordID":"08202020657ca941014b5436e418007ff5","referenceKey":"SOLUTIONS","name":"Solutions"},"resourcePath":"http:\/\/slc01fjo.us.oracle.com:8228\/fas\/resources\/okcs__ok152b1__t2\/content\/draft\/08202020657ca941014b5436e418007fd2\/08202020657ca941014b5436e418007fca\/","locale":"en_US","error":null}},

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
            });
        },

        verifyInjectedAnswerContents: function (container) {
            var children = container.get('children');

            Y.assert(container.hasClass('rn_ExpandedAnswerContent'));

            Y.Assert.areSame(2, children.size());

            var firstChild = children.item(0);
            Y.assert(firstChild.hasClass('rn_AnswerSummary'));
            Y.Assert.areSame(this.answerResponse.Question, firstChild.get('textContent'));

            var secondChild = children.item(1);
            Y.assert(secondChild.hasClass('rn_AnswerSolution'));
            Y.Assert.areSame(this.answerResponse.Solution, secondChild.get('textContent'));
        },
        verifyInjectedOkcsAnswerContents: function () {
            var answerDetail = Y.one('.rn_AnswerSolution');

            Y.Assert.isNotNull(answerDetail);

            var children = answerDetail.getDOMNode().children;

            Y.Assert.areSame(2, children.length);

            var firstChild = children.item(0);
            Y.Assert.areSame('rn_AnswerInfo rn_AnswerInfo_Top', firstChild.className);
            Y.Assert.areSame('Doc ID', firstChild.firstElementChild.textContent);
            Y.Assert.areSame('rn_InfoLabel', firstChild.firstElementChild.className);
            Y.Assert.areNotSame(0, firstChild.children.length);

            var secondChild = children.item(1);
            Y.Assert.areSame('rn_AnswerDetail', secondChild.className);
            Y.Assert.isNotNull(secondChild.firstElementChild.textContent);
            Y.Assert.areNotSame(0, secondChild.children.length);
            Y.Assert.areSame('rn_AnswerText', secondChild.firstElementChild.className);

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
            Y.Assert.areSame(widget.data.attrs.get_okcs_data_ajax, this.makeRequestCalledWith[0]);

            var openNewTabLinkText = Y.one(".rn_ExpandedAnswerContent").get("children");
            Y.Assert.areSame(openNewTabLinkText.item(0).get("text").trim(), widget.data.attrs.label_new_tab);
        },

        "Content from answer response is injected into the list after a global response event is fired": function() {
            var dialogContent = Y.one(baseSelector + "_DialogContent"),
                clickedAnswer = dialogContent.one('a:last-child'),
                hasFiredResponse = false;

            RightNow.Event.subscribe("evt_getAnswerResponse", function() {
                hasFiredResponse = true;
            }, this);

            if (Y.one('.rn_AnswerSolution') !== null ){
                widget._displayExternalResults(this.okcsAnswerResponse, {});
                this.verifyInjectedOkcsAnswerContents();
            }
        },

        "Hide button should not be available when display view type is 'inline'": function() {
            var toggle = Y.one('a.rn_ExpandedAnswer'),
                buttonContainer = Y.one('.rn_buttonContainer');

            if (toggle)
                Y.Assert.isNull(buttonContainer);
        },

        "Clicking an expanded answer toggle hides the answer content": function() {
            var toggle = Y.one('a.rn_ExpandedAnswer');
            if (toggle){
                toggle.simulate('click');
                Y.assert(!toggle.hasClass('rn_ExpandedAnswer'));
            }
        }
    }));
    return smartAssistantTests;
}).run();
