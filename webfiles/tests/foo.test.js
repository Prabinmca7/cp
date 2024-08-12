var customerJavaScriptTests = new YAHOO.tool.TestSuite({
    name: "RightNow.Text",
    setUp: function(){
        var testExtender = {
            Assert: YAHOO.util.Assert,
            Text: RightNow.Text
        };
        for(var item in this.items){
            YAHOO.lang.augmentObject(this.items[item], testExtender);
        }
    }
});

//getSubstringAfter
customerJavaScriptTests.add(new YAHOO.tool.TestCase(
{
    name: "getSubstringAfterTests",
    
    _should: {
        error: {
            testNumberHaystack: true,
            testObjectHaystack: true
        }
    },
       
    //Test Methods
    testFound: function() {
        this.Assert.areSame(this.Text.getSubstringAfter('test string', 'test'), ' string');
        this.Assert.areSame(this.Text.getSubstringAfter('a/b/c/d', 'b/c'), '/d');
        this.Assert.areSame(this.Text.getSubstringAfter('foo', 'f'), 'oo');
        this.Assert.areSame(this.Text.getSubstringAfter('a/b/c/d', 'd'), '');
    },
    
    testMissing: function() {
        this.Assert.areSame(this.Text.getSubstringAfter('foo', 'bar'), false);
        this.Assert.areSame(this.Text.getSubstringAfter('a/b/c/d', 'c/b'), false);
    },
    
    testNonStringNeedles: function() {
        this.Assert.areSame(this.Text.getSubstringAfter('foo', 1), false);
        this.Assert.areSame(this.Text.getSubstringAfter('a/b/c/d', {foo:'bar'}), false);
    },
    testNumberHaystack: function() {
        this.Text.getSubstringAfter(1, 1);
    },
    testObjectHaystack: function() {
        this.Text.getSubstringAfter({}, 1);
    }
}));

//getSubstringBetween
customerJavaScriptTests.add(new YAHOO.tool.TestCase(
{
    name: "getSubstringBetweenTests",
    
    _should: {
        error: {
            testNumberSubject: true,
            testObjectSubject: true,
            testNothing: true,
            testNoMarkers: true
        }
    },
       
    //Test Methods
    testFound: function() {
        this.Assert.areSame(this.Text.getSubstringBetween('banana assiduous rifle found', 'banana', 'found'), ' assiduous rifle ');
        this.Assert.areSame(this.Text.getSubstringBetween('banana assiduous rifle', 'banana', 'found'), ' assiduous rifle');
        this.Assert.areSame(this.Text.getSubstringBetween('http://mysite.custhelp.com/a_id/2345/banana/23', '/a_id/', '/'), '2345');
        this.Assert.areSame(this.Text.getSubstringBetween('http://mysite.custhelp.com/posts/a24R34T5/banana/23', '/posts/', '/'), 'a24R34T5');
        this.Assert.areSame(this.Text.getSubstringBetween('banana ', 'banana', 'found'), ' ');
        this.Assert.areSame(this.Text.getSubstringBetween('said banana ', ' ', ' '), 'banana');
        this.Assert.areSame(this.Text.getSubstringBetween('banana', '', 'banana'), 'banana');
        this.Assert.areSame(this.Text.getSubstringBetween('banana', '', ''), 'banana');
        this.Assert.areSame(this.Text.getSubstringBetween('banana first banana second', 'banana ', ' '), 'first');
    },
    
    testMissing: function() {
        this.Assert.areSame(this.Text.getSubstringBetween('banana', 'what', 'dinosaur'), false);
        this.Assert.areSame(this.Text.getSubstringBetween('help_me_find_my_name', ' ', 'help_me_find_my_name'), false);
    },
    
    testNonStringMarkers: function() {
        this.Assert.areSame(this.Text.getSubstringBetween('banana', 1), 'banana');
        this.Assert.areSame(this.Text.getSubstringBetween('banana', 1, -1), 'banana');
        this.Assert.areSame(this.Text.getSubstringBetween('banana', -1), 'banana');
        this.Assert.areSame(this.Text.getSubstringBetween('banana', {banana:'famous'}), 'banana');
    },
    testNumberSubject: function() {
        this.Text.getSubstringBetween(1, "banana", "win");
    },
    testObjectSubject: function() {
        this.Text.getSubstringBetween({}, "banana", "fail");
    },
    testNothing: function() {
        this.Text.getSubstringBetween();
    },
    testNoMarkers: function() {
        this.Text.getSubstringBetween("banana");
    }
}));

//sprintf
customerJavaScriptTests.add(new YAHOO.tool.TestCase(
{
    name: "sprintfTests",
    
    _should: {
        error: {
            testNotEnoughArguments: true
        }
    },

    //Test Methods
    testPropertyFormatted: function() {
        this.Assert.areSame(this.Text.sprintf('a %s string', 'simple'), 'a simple string');
        this.Assert.areSame(this.Text.sprintf('%d plus %d', 1, 2), '1 plus 2');
        this.Assert.areSame(this.Text.sprintf('%s and %d and a couple more %s %s %s', 'strings', 3, 'one', 'two', 'three'), 'strings and 3 and a couple more one two three');
    },
    
    testLiteralPercent: function() {
        this.Assert.areSame(this.Text.sprintf('a literal %% sign'), 'a literal % sign');
        this.Assert.areSame(this.Text.sprintf('%d %% %s', 4, 'string'), '4 % string');
    },
    
    testNotEnoughArguments: function() {
        this.Text.sprintf('no arguments but placeholder %s');
    },
    
    testNotEnoughPlaceholders: function() {
        this.Assert.areSame(this.Text.sprintf('no placeholders', 'string'), 'no placeholders');
        this.Assert.areSame(this.Text.sprintf('only 2 placeholders %s %s', 4, 'one', 'two'), 'only 2 placeholders 4 one');
    }
}));

//trimComma
customerJavaScriptTests.add(new YAHOO.tool.TestCase(
{
    name: "trimCommaTests",
    
    _should: {
        error: {
            testArray: true,
            testObject: true,
            testNumber: true
        }
    },

    //Test Methods
    testSingleComma: function() {
        this.Assert.areSame(this.Text.trimComma('trailing comma,'), 'trailing comma');
        this.Assert.areSame(this.Text.trimComma(','), '');
        this.Assert.areSame(this.Text.trimComma('one,two,'), 'one,two');
    },
    
    testMultipleComma: function() {
        this.Assert.areSame(this.Text.trimComma('multiple commas,,,,,'), 'multiple commas');
        this.Assert.areSame(this.Text.trimComma('multiple,,, commas,,,,,'), 'multiple,,, commas');
    },
    
    testNoComma: function() {
        this.Assert.areSame(this.Text.trimComma('no comma'), 'no comma');
        this.Assert.areSame(this.Text.trimComma('no trailing, comma'), 'no trailing, comma');
    },
    
    testArray: function() {
        this.Text.trimComma([]);
    },
    testObject: function() {
        this.Text.trimComma({});
    },
    testNumber: function() {
        this.Text.trimComma(1);
    }
}));

//isValidEmailAddress
customerJavaScriptTests.add(new YAHOO.tool.TestCase(
{
    name: "isValidEmailAddressTests",
    setUp: function () {
        RightNow.Interface.setConfigbase(function(){return {"DE_VALID_EMAIL_PATTERN":"^(([-!#$%&'*+\/=?^~`{|}\\w]+(\\.[-!#$%&'*+\/=?^~`{|}\\w]+)*)|(\"[^\"]+\"))@[0-9A-Za-z]+(-[0-9A-Za-z]+)*(\\.[0-9A-Za-z]+(-[0-9A-Za-z]+)*)+$"};});
    },

    //Test Methods
    testValidEmail: function() {
        this.Assert.areSame(this.Text.isValidEmailAddress('eturner@rightnow.com'), true);
        this.Assert.areSame(this.Text.isValidEmailAddress('a@b.c.d'), true);
    },
    
    testInvalidEmail: function() {
        this.Assert.areSame(this.Text.isValidEmailAddress('a@b'), false);
        this.Assert.areSame(this.Text.isValidEmailAddress('@c.com'), false);
        this.Assert.areSame(this.Text.isValidEmailAddress(''), false);
    },
    
    testInvalidLength: function() {
        var looong = '';
        while(looong.length < 100)
            looong += 'a';
        this.Assert.areSame(this.Text.isValidEmailAddress(looong), false);
        looong = '';
        while(looong.length < 72)
            looong += 'a';
        looong += '@foo.com';
        this.Assert.areSame(this.Text.isValidEmailAddress(looong), true);
        looong = 'a' + looong;
        this.Assert.areSame(this.Text.isValidEmailAddress(looong), false);
    }
}));

customerJavaScriptTests.add(new YAHOO.tool.TestCase(
{
    name : "isValidUrlTests",

    testValidUrls: function()
    {
        this.Assert.isTrue(this.Text.isValidUrl("google.com/"));
        this.Assert.isTrue(this.Text.isValidUrl("http://google.com/"));
        this.Assert.isTrue(this.Text.isValidUrl("http://google.com"));
        this.Assert.isTrue(this.Text.isValidUrl("http://www.google.com"));
        this.Assert.isTrue(this.Text.isValidUrl("ftp://google.com"));
        this.Assert.isTrue(this.Text.isValidUrl("FTP://google.com"));
        this.Assert.isTrue(this.Text.isValidUrl("a://127.0.0.0/main.html"));
        this.Assert.isTrue(this.Text.isValidUrl("a://1111.1111/1111"));
        this.Assert.isTrue(this.Text.isValidUrl("asdf://google.com"));
        this.Assert.isTrue(this.Text.isValidUrl("http://google.com/foo?bar=asdf"));
        this.Assert.isTrue(this.Text.isValidUrl("http://www.google.com/a/b/c/d?e=f&g=h"));
        this.Assert.isTrue(this.Text.isValidUrl("http://mail.google.com/"));
        this.Assert.isTrue(this.Text.isValidUrl("a://____.___"));
    },
    
    testInvalidUrls: function()
    {
        this.Assert.isFalse(this.Text.isValidUrl("asdf"));
        this.Assert.isFalse(this.Text.isValidUrl("123://google.com/"));
        this.Assert.isFalse(this.Text.isValidUrl("google.com/!@#$%^&"));
        this.Assert.isFalse(this.Text.isValidUrl("ftp:google.com/"));
        this.Assert.isFalse(this.Text.isValidUrl("asdf//google.com/"));
        this.Assert.isFalse(this.Text.isValidUrl("http/google.com/foo?bar=asdf"));
    }
}));

customerJavaScriptTests.add(new YAHOO.tool.TestCase(
{
    name : "privateMembersHiddenTest",

    testPrivateMembers: function()
    {
        UnitTest.recursiveMemberCheck(RightNow.Text);
    }
}));

UnitTest.runTestSuite(customerJavaScriptTests, ['/euf/core/debug-js/RightNow.Text.js']);
