// Polyfill for Function.prototype.bind
if (!Function.prototype.bind) {
    Function.prototype.bind = function(oThis) {
      if (typeof this !== 'function') {
        // closest thing possible to the ECMAScript 5
        // internal IsCallable function
        throw new TypeError('Function.prototype.bind - what is trying to be bound is not callable');
      }
      var aArgs   = Array.prototype.slice.call(arguments, 1),
          fToBind = this,
          fNOP    = function() {},
          fBound  = function() {
            return fToBind.apply(this instanceof fNOP ? this : oThis,
                   aArgs.concat(Array.prototype.slice.call(arguments)));
          };

      if (this.prototype) {
        // Function.prototype doesn't have a prototype property
        fNOP.prototype = this.prototype;
      }
      fBound.prototype = new fNOP();

      return fBound;
    };
}
UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'ChatTranscript_0'
}, function (Y, widget, baseSelector) {
    var chatTranscriptTests = new Y.Test.Suite({
        name: "standard/chat/ChatTranscript",
    });

    /* @@@ QA 130816-000036 */
    chatTranscriptTests.add(new Y.Test.Case({
        name: "Check various input values to ensure they output as expected",

        "Test ampersand in rich text agent post": function()
        {
            var serviceFinishTime = "1381018157000";
            var data = {
                messageBody: "&#169",
                richText: true,
                isEndUserPost: false,
                serviceFinishTime: serviceFinishTime,
                agent: {
                    name: "General Administrator"
                }
            };

            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));

            var transcript = Y.one(baseSelector);
            var post = transcript.getById(serviceFinishTime);

            // Test that the string "&#169" exists. We don't have just the post string wrapped in a tag, so we'll search the whole HTML element. This is less than ideal, but will work for now.
            Y.Assert.isTrue(post.get('textContent').indexOf('©') > 0);
        },

        "Test ampersand in plain text agent post": function()
        {
            var serviceFinishTime = "1381018158000";
            var data = {
                messageBody: "&#169",
                richText: false,
                isEndUserPost: false,
                serviceFinishTime: serviceFinishTime,
                agent: {
                    name: "General Administrator"
                }
            };

            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));

            var transcript = Y.one(baseSelector);
            var post = transcript.getById(serviceFinishTime);

            Y.Assert.isTrue(post.get('textContent').indexOf('&#169') > 0);
        },

        testCoBrowseStatusChange: function () {
            RightNow.Chat = {
                Model: {
                    ChatCoBrowseStatusCode: {
                        ACCEPTED: 0,
                        DECLINED: 1,
                        UNAVAILABLE: 2,
                        TIMEOUT: 3,
                        STARTED: 4,
                        STOPPED: 5,
                        ERROR: 6,
                    }
                }
            };

            RightNow.Event.fire('evt_chatCobrowseStatusResponse', new RightNow.Event.EventObject(widget,
                    { data: { coBrowseStatus: RightNow.Chat.Model.ChatCoBrowseStatusCode.STARTED } }));
            var transcript = Y.one(baseSelector);

            //test that screen sharing started
            Y.Assert.isTrue(transcript.getContent().indexOf(widget.data.attrs.label_screen_sharing_session_started) > 0);
        },
        testVideoChatStatusChange: function () {
            RightNow.Chat = {
                Model: {
                    ChatVideoChatStatusCode: {
                        ACCEPTED: 0,
                        DECLINED: 1,
                        UNAVAILABLE: 2,
                        TIMEOUT: 3,
                        STARTED: 4,
                        STOPPED: 5,
                        ERROR: 6,
                    }
                }
            };

            RightNow.Event.fire('evt_chatVideoChatStatusResponse', new RightNow.Event.EventObject(widget,
                    { data: { videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.STARTED } }));
            var transcript = Y.one(baseSelector);

            //test that video chat started
            Y.Assert.isTrue(transcript.getContent().indexOf( /*widget.data.attrs.label_screen_sharing_session_started*/RightNow.Interface.getMessage("THE_VIDEO_CHAT_SESSION_HAS_STARTED_LBL") ) > 0);
        },
        /* @@@ QA 170317-000115 */
        "Test whether video chat errors are properly handled": function () {
            RightNow.Chat = {
                Model: {
                    ChatVideoChatStatusCode: {
                        ACCEPTED: 0,
                        DECLINED: 1,
                        UNAVAILABLE: 2,
                        TIMEOUT: 3,
                        STARTED: 4,
                        STOPPED: 5,
                        ERROR: 6,
                    }
                }
            };

            RightNow.Event.fire('evt_chatVideoChatStatusResponse', new RightNow.Event.EventObject(widget,
                    { data: { videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.ERROR } }));
            var transcript = Y.one(baseSelector);

            Y.Assert.isTrue(transcript.getContent().indexOf( RightNow.Interface.getMessage("VIDEO_ENCOUNTERED_DUE_REMOTE_PROBLEM_LBL") ) > 0);
        }

    }));
    chatTranscriptTests.add(new Y.Test.Case({
   name: "Check various input values to ensure that richText have proper format substitution they output as expected",
    "Test rich text LINK  in rich text agent post": function()
        {
            var serviceFinishTime = "1381018158001";
            var data = {
                messageBody: "Hi visit www.google.com and </br> visit <a style='text-decoration: underline' href='mailto:user@oralce.com' target='_blank'>user@oralce.com</a> and </br> visit 'www.yahoo.com'",
                richText: true,
                isEndUserPost: false,
                serviceFinishTime: serviceFinishTime,
                agent: {
                    name: "General Administrator"
                }
            };

            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));

            var transcript = Y.one(baseSelector);
            var post = transcript.getById(serviceFinishTime);
            Y.Assert.isTrue(post.get('innerHTML').indexOf('<br>') > 0, 'Line break should be there...');
            Y.Assert.isTrue(post.get('innerHTML').indexOf('href="http://www.google.com"') > 0, 'Google should be a URL...');
            Y.Assert.isTrue(post.get('innerHTML').indexOf("'www.yahoo.com'") > 0, 'Yahoo should be there...');
            Y.Assert.isTrue(post.get('innerHTML').indexOf('href="mailto:user@oralce.com"') > 0, 'The e-mail address should be a link...');
            Y.Assert.isTrue(post.get('innerHTML').indexOf('style="text-decoration: underline') > 0, 'The link markup should remain intact...');
        }
 }));
    chatTranscriptTests.add(new Y.Test.Case({name: "Check whether HTML and HTML-like input get escaped properly for the end user",
    "Test HTML and HTML-like input": function ()
    {
            var serviceFinishTime = "1381018159000";
            var data = {
                messageBody: "If X<Y or X>Z then <b>this should render bold</b> and this <img src='scenery.jpg' /> should show. Even <h1>a title</h1> should remain. <!-- comment --> <\?xml version=\"1.0\" encoding=\"UTF-8\"\?> <!DOCTYPE html>",
                richText: true,
                isEndUserPost: true,
                serviceFinishTime: serviceFinishTime,
                messageId: serviceFinishTime,
                endUser: {
                    firstName: 'Annie',
                    lastName: 'Customer'
                }
            };

            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));

            var transcript = Y.one(baseSelector);
            var post = transcript.getById('eup_' + serviceFinishTime);

            Y.Assert.isTrue(post.get('innerHTML').indexOf("X&lt;Y") > 0, 'Standalone < should be encoded');
            Y.Assert.isTrue(post.get('innerHTML').indexOf("X&gt;Z") > 0, 'Standalone > should be encoded');
            Y.Assert.isTrue(post.get('innerHTML').indexOf("<b>this should render bold</b>") > 0, 'HTML should remain intact (opening/closing tag)');
            Y.Assert.isTrue(post.get('innerHTML').indexOf("<img src=\"scenery.jpg\">") > 0, 'HTML should remain intact (empty tag)');
            Y.Assert.isTrue(post.get('innerHTML').indexOf("<h1>a title</h1>") > 0, 'HTML should remain intact (tag names containing numbers)');
            Y.Assert.isTrue(post.get('innerHTML').indexOf("<!-- comment -->") > 0, 'HTML comments should remain intact');
            Y.Assert.isTrue(post.get('innerHTML').indexOf("<!DOCTYPE html>") == -1, 'HTML doctypes should be removed');
            Y.Assert.isTrue(post.get('innerHTML').indexOf("<!--\?xml version=\"1.0\" encoding=\"UTF-8\"\?-->") > 0, 'XML headers should remain intact');

    }
}));
chatTranscriptTests.add(new Y.Test.Case({name: "Check whether onXXX attributes get properly sanitized from input",
    "Test sanitization of onXXX attributes": function ()
    {
            var serviceFinishTime = "1381018159001";
            var data = {
                messageBody: "<img src='foo' onclick=alert('x') onmouseover='alert(\"x\")' onerror=\"alert('x')\" /><form action='/action.php'>First name:<input type='text' value='Annie'/><input type='text' value='A'/></form><dialog>Hey, I'm a dialog!</dialog><script>alert('foo');</script>",
                richText: true,
                isEndUserPost: true,
                serviceFinishTime: serviceFinishTime,
                messageId: serviceFinishTime,
                endUser: {
                    firstName: 'Annie',
                    lastName: 'Customer'
                }
            };

            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));

            var transcript = Y.one(baseSelector);
            var post = transcript.getById('eup_' + serviceFinishTime);

            Y.Assert.isTrue(post.get('innerHTML').indexOf('<img src="foo">') > -1, 'Image should be sanitized');
            Y.Assert.isTrue(post.get('innerHTML').indexOf('<form action=\'/action.php\'>') === -1, 'Form should be sanitized (removed)');
            Y.Assert.isTrue(post.get('innerHTML').indexOf('<input type=\'text\' value=\'Annie\'/>') === -1, 'Input should be sanitized (removed)');
            Y.Assert.isTrue(post.get('innerHTML').indexOf('<input type=\'text\' value=\'A\'/>') === -1, 'Same-level input field should be sanitized (removed)');
            Y.Assert.isTrue(post.get('innerHTML').indexOf('First name:') > -1, 'Text nodes within blacklisted tags should be retained');
            Y.Assert.isTrue(post.get('innerHTML').indexOf('Hey, I\'m a dialog!') > -1, 'Text nodes within some blacklisted tags should be retained');
            Y.Assert.isTrue(post.get('innerHTML').indexOf('alert(\'foo\');') === -1, 'Text nodes within blacklisted tags like style and script should be removed');
    }
}));
chatTranscriptTests.add(new Y.Test.Case({name: "Check whether HTML entities get properly rendered in plain text mode",
    "Test HTML entity translation in plain text mode": function ()
    {
            var serviceFinishTime = "1381018159003";
            var data = {
                messageBody: "&lt; &gt; &amp; &#230; < > &",
                richText: false,
                isEndUserPost: false,
                serviceFinishTime: serviceFinishTime,
                messageId: serviceFinishTime,
                agent: {
                    name: "General Administrator"
                }
            };

            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));

            var transcript = Y.one(baseSelector);
            var post = transcript.getById(serviceFinishTime);

            Y.Assert.isTrue(post.get('innerHTML').indexOf('&amp;lt; &amp;gt; &amp;amp; &amp;#230; &lt; &gt; &amp;') > -1, '& should be encoded in order to prevent the browser from interpreting the entitity (and to show the text as-is)');
    }
}));
chatTranscriptTests.add(new Y.Test.Case({name: "Check whether newlines get properly rendered",
    "Test newline conversion": function ()
    {
            var serviceFinishTime = "1381018159005";
            var data = {
                messageBody: "1\n2  spaces  between  each  word\n3   spaces   between   each   word\n<img src='homer.jpg' alt='Homer Simpson'/>\n<ul>\n  <li>Item</li>\n  <li>Another item</li>\n</ul>\n",
                richText: true,
                isEndUserPost: false,
                serviceFinishTime: serviceFinishTime,
                messageId: serviceFinishTime,
                agent: {
                    name: "General Administrator"
                }
            };

            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));

            var transcript = Y.one(baseSelector);
            var post = transcript.getById(serviceFinishTime);
            Y.Assert.isTrue(post.get('innerHTML').indexOf("1<br>2&nbsp; spaces&nbsp; between&nbsp; each&nbsp; word<br>3&nbsp;&nbsp; spaces&nbsp;&nbsp; between&nbsp;&nbsp; each&nbsp;&nbsp; word<br><img src=\"homer.jpg\" alt=\"Homer Simpson\"><ul><li>Item</li><li>Another item</li></ul>") > -1, 'Newlines should be converted to HTML br and excess spaces/newlines should be discarded between block level elements');
    }
}));
chatTranscriptTests.add(new Y.Test.Case({
   name: "Check whether position parameter gets removed from style attribute",
    "Test rich text LINK  in rich text agent post": function()
        {
            var serviceFinishTime = "1381018158006";
            var data = {
                messageBody: "<a href='http://hackercontrolledsite.com' style='z-index: 10000; position: fixed; top: 0px; left: 0; width: 1000000px; height: 100000px; background-color: grey ;'>Link to Click</a>",
                richText: true,
                isEndUserPost: false,
                serviceFinishTime: serviceFinishTime,
                agent: {
                    name: "General Administrator"
                }
            };

            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));

            var transcript = Y.one(baseSelector);
            var post = transcript.getById(serviceFinishTime);
            Y.Assert.isTrue(post.get('innerHTML').indexOf('style="z-index: 10000; top: 0px') > 0, 'Position should have been removed...');
            Y.Assert.isTrue(post.get('innerHTML').indexOf('position') === -1, 'Position should have been removed...');
        }
 }));

 // QA 210407-000148 - check for javascript in href
chatTranscriptTests.add(new Y.Test.Case({
   name: "Check whether javascript in href gets removed",
    "Test javscript embedded in single quotes": function()
        {
            var serviceFinishTime = "1381018158010";
            var data = {
                messageBody: "<a href='javascript:alert(document.domain)'>Click Here</a>",
                richText: true,
                isEndUserPost: false,
                serviceFinishTime: serviceFinishTime,
                agent: {
                    name: "General Administrator"
                }
            };
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));
            var transcript = Y.one(baseSelector);
            var post = transcript.getById(serviceFinishTime);
            Y.Assert.isTrue(post.get('innerHTML').indexOf('href') === -1, 'href is removed...');
        },
    "Test javscript embedded in double quotes": function()
        {
            var serviceFinishTime = "1381018158011";
            var data = {
                messageBody: "<a href=\"javascript:alert(document.domain)\">Click Here</a>",
                richText: true,
                isEndUserPost: false,
                serviceFinishTime: serviceFinishTime,
                agent: {
                    name: "General Administrator"
                },
                endUser: {
                    firstName: 'Foo',
                    lastName: 'Bar'
                }
            };
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));
            var transcript = Y.one(baseSelector);
            var post = transcript.getById(serviceFinishTime);
            Y.Assert.isTrue(post.get('innerHTML').indexOf('href') === -1, 'href is removed...');
        },
    "Test javscript without any quote": function()
        {
            var serviceFinishTime = "1381018158012";
            var data = {
                messageBody: "<a href=javascript:alert(document.domain)>Click Here</a>",
                richText: true,
                isEndUserPost: false,
                serviceFinishTime: serviceFinishTime,
                agent: {
                    name: "General Administrator"
                },
                endUser: {
                    firstName: 'Foo',
                    lastName: 'Bar'
                }
            };
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));
            var transcript = Y.one(baseSelector);
            var post = transcript.getById(serviceFinishTime);
            Y.Assert.isTrue(post.get('innerHTML').indexOf('href') === -1, 'href is removed...');
        },
    "Test javscript embedded with encoded quotes ": function()
        {
            var serviceFinishTime = "1381018158013";
            var data = {
                messageBody: "<a href=&somequo;javascript:alert(document.domain)>Click Here</a>",
                richText: true,
                isEndUserPost: false,
                serviceFinishTime: serviceFinishTime,
                agent: {
                    name: "General Administrator"
                },
                endUser: {
                    firstName: 'Foo',
                    lastName: 'Bar'
                }
            };
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));
            var transcript = Y.one(baseSelector);
            var post = transcript.getById(serviceFinishTime);
            Y.Assert.isTrue(post.get('innerHTML').indexOf('href') === -1, 'href is removed...');
        }
 }));

 // QA 210407-000148 - check for meta tag
chatTranscriptTests.add(new Y.Test.Case({
   name: "Check whether meta tag gets removed",
    "Test meta tag sent in chat message": function()
        {
            var serviceFinishTime = "1381018158014";
            var data = {
                messageBody: "<meta http-equiv=\"refresh\" content=\"0;URL='http://google.com/'\"/> Works in Chrome!",
                richText: true,
                isEndUserPost: false,
                serviceFinishTime: serviceFinishTime,
                agent: {
                    name: "General Administrator"
                },
                endUser: {
                    firstName: 'Foo',
                    lastName: 'Bar'
                }
            };
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));
            var transcript = Y.one(baseSelector);
            var post = transcript.getById(serviceFinishTime);
            Y.Assert.isTrue(post.get('innerHTML').indexOf('meta') === -1, 'meta is removed...');
        }
 }));

 // QA 210407-000148 - check for svg xhref:link
chatTranscriptTests.add(new Y.Test.Case({
   name: "Check whether href:link gets removed",
    "Test svg with href:link sent in chat message": function()
        {
            var serviceFinishTime = "1381018158015";
            var data = {
                messageBody: "<svg><use xlink:href=\"data:image/svg+xml;base64,+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNTAiLz48L2E+Cjwvc3ZnPg==#circle\"/></svg>",
                richText: true,
                isEndUserPost: false,
                serviceFinishTime: serviceFinishTime,
                agent: {
                    name: "General Administrator"
                },
                endUser: {
                    firstName: 'Annie',
                    lastName: 'Customer'
                }
            };
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: data}));
            var transcript = Y.one(baseSelector);
            var post = transcript.getById(serviceFinishTime);
            Y.Assert.isTrue(post.get('innerHTML').indexOf('xhref:link') === -1, 'xhref:link is removed...');
        }
 }));

// 200929-000066 widget needs to respect the agent_id attribute when the agent disconnects the chat
chatTranscriptTests.add(new Y.Test.Case({
   name: "Widget needs to respect the agent_id attribute when the agent disconnects the chat",
   // when the agent terminates a chat, it's actually handled in _onChatEngagementConcludedResponse
    "Test agent concludes engagement with agent_id set": function()
        {
            var agentName = "shibby123";
            var badAgentName = "does not match agent_id";
            var data = {
                messageBody: "shibby",
                isEndUserPost: false,
                agent: {
                    name: badAgentName
                }
            };

            var originalAgentId = widget.data.attrs.agent_id;
            widget.data.attrs.agent_id = agentName;
            try {
                RightNow.Event.fire('evt_chatEngagementConcludedResponse', new RightNow.Event.EventObject(widget, {data: data}));
            } finally {
                widget.data.attrs.agent_id = originalAgentId;
            }

            var transcript = Y.one(baseSelector);
            Y.Assert.isTrue(transcript.get('innerHTML').indexOf(badAgentName) == -1, 'Transcript used agent.name instead of agent_id in _onChatEngagementConcludedResponse');
            Y.Assert.isTrue(transcript.get('innerHTML').indexOf(agentName) > -1, 'Transcript should use agent_id in _onChatEngagementConcludedResponse');
        },
    "Test agent concludes engagement without agent_id set": function()
        {
            var agentName = "name which does not match agent_id";
            var data = {
                messageBody: "shibby",
                isEndUserPost: false,
                agent: {
                    name: agentName
                }
            };

            RightNow.Event.fire('evt_chatEngagementConcludedResponse', new RightNow.Event.EventObject(widget, {data: data}));

            var transcript = Y.one(baseSelector);
            Y.Assert.isTrue(transcript.get('innerHTML').indexOf(agentName) > -1, 'Transcript should use agent_name in the absence of agent_id in _onChatEngagementConcludedResponse');
        }
 }));

   return chatTranscriptTests;
}).run();
