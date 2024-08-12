UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: ['/euf/core/debug-js/RightNow.Text.js'],
    namespaces: ['RightNow.Text.Encoding']
}, function(Y){
    var rightnowTextEncodingTests = new Y.Test.Suite(
    {
        name:"RightNow.Text.Encoding",

        setUp : function() {
            var testExtender = {
                Encoding: RightNow.Text.Encoding
            };
            for(var item in this.items){
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    //base64Decode
    rightnowTextEncodingTests.add(new Y.Test.Case(
    {
        name : "base64DecodeTests",

        testValidEncodedStrings: function() {
            Y.Assert.areSame(this.Encoding.base64Decode("dGVzdCBzdHJpbmc."), "test string");
            Y.Assert.areSame(this.Encoding.base64Decode("MTIzNDU2Nzg5MA.."), "1234567890");
            Y.Assert.areSame(this.Encoding.base64Decode("eyJzZiI6eyIxNzYiOnsiZmlsdGVycyI6eyJub190cnVuY2F0ZSI6MCwicGFnZSI6MSwic2VhcmNoVHlwZSI6eyJmaWx0ZXJzIjp7ImRhdGEiOjUsInJuU2VhcmNoVHlwZSI6InNlYXJjaFR5cGUifX0sImtleXdvcmQiOnsiZmlsdGVycyI6eyJkYXRhIjoicm9hbWluZyIsInJuU2VhcmNoVHlwZSI6ImtleXdvcmQifX0sInAiOnsiZmlsdGVycyI6eyJkYXRhIjp7IjAiOltdfSwicm5TZWFyY2hUeXBlIjoibWVudWZpbHRlciJ9fSwiYyI6eyJmaWx0ZXJzIjp7ImRhdGEiOnsiMCI6W119LCJyblNlYXJjaFR5cGUiOiJtZW51ZmlsdGVyIn19LCJzb3J0X2FyZ3MiOnsiZmlsdGVycyI6eyJkYXRhIjp7ImNvbF9pZCI6LTEsInNvcnRfZGlyZWN0aW9uIjoxfX19fX19LCJjIjoyfQ.."), '{"sf":{"176":{"filters":{"no_truncate":0,"page":1,"searchType":{"filters":{"data":5,"rnSearchType":"searchType"}},"keyword":{"filters":{"data":"roaming","rnSearchType":"keyword"}},"p":{"filters":{"data":{"0":[]},"rnSearchType":"menufilter"}},"c":{"filters":{"data":{"0":[]},"rnSearchType":"menufilter"}},"sort_args":{"filters":{"data":{"col_id":-1,"sort_direction":1}}}}}},"c":2}');
        },
        
        testArray: function(){
            Y.Assert.areSame(this.Encoding.base64Decode([]), "");
        },
        
        testObject: function(){
            Y.Assert.areSame(this.Encoding.base64Decode({}), "");
        },

        testEmptyString: function(){
            Y.Assert.areSame(this.Encoding.base64Decode(""), "");
            Y.Assert.areSame(this.Encoding.base64Decode(null), "");
            Y.Assert.areSame(this.Encoding.base64Decode(0), "");
            Y.Assert.areSame(this.Encoding.base64Decode(false), "");
            Y.Assert.areSame(this.Encoding.base64Decode(undefined), "");
        }, 

        testSpecialCharacterString: function(){
            Y.Assert.areSame(this.Encoding.base64Decode("cV9pZD0xJg=="), "q_id=1&");
            Y.Assert.areSame(this.Encoding.base64Decode("PD4/"), "<>?");
            Y.Assert.areSame(this.Encoding.base64Decode("PD4+"), "<>>");
        }
    }));

    //base64Encode
    rightnowTextEncodingTests.add(new Y.Test.Case(
    {
        name : "base64EncodeTests",
        
        _should:{
            error:{
                testObject: true
            }
        },

        testValidDecodedStrings: function() {
            Y.Assert.areSame(this.Encoding.base64Encode("one two three"), "b25lIHR3byB0aHJlZQ..");
            Y.Assert.areSame(this.Encoding.base64Encode("{foo: {bar:baz}}"), "e2Zvbzoge2JhcjpiYXp9fQ..");
        },
        
        testArray: function(){
            Y.Assert.areSame(this.Encoding.base64Encode([]), "");
        },
        
        testObject: function(){
            this.Encoding.base64Encode({});
        }
    }));

    //utf8Length
    rightnowTextEncodingTests.add(new Y.Test.Case(
    {
        name : "utf8LengthTests",

        testUtf8Lengths: function() {
            Y.Assert.areSame(this.Encoding.utf8Length("one" + 0x0008), 4);
            Y.Assert.areSame(this.Encoding.utf8Length("one" + 0x0010), 5);
            Y.Assert.areSame(this.Encoding.utf8Length("one" + 0x0080), 6);
            Y.Assert.areSame(this.Encoding.utf8Length("one" + 0x0800), 7);
        },
        
        testArray: function(){
            Y.Assert.areSame(this.Encoding.utf8Length([]), 0);
        },
        
        testObject: function(){
            Y.Assert.areSame(this.Encoding.utf8Length({}), 0);
        }
    }));

    rightnowTextEncodingTests.add(new Y.Test.Case(
    {
        name : "privateMembersHiddenTest",

        testPrivateMembers: function()
        {
            UnitTest.recursiveMemberCheck(Y, this.Encoding);
        }
    }));
    
    return rightnowTextEncodingTests;
});
UnitTest.run();
