UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'OkcsSimpleSearch_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/OkcsSimpleSearch",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'OkcsSimpleSearch_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    
                    this.searchField = document.getElementById('rn_' + this.instanceID + '_SearchField');
                    this.submitButton = document.getElementById('rn_' + this.instanceID + '_Submit');

                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        /**
         * Tests the functionality of the widget in response to an onClick event
         */
        
        'Test PreFill attribute is rendered': function() {
            this.initValues();
            Y.Assert.isNotNull(this.widgetData.attrs.label_hint);
        },
        
        "Widget navigates to search page with empty keyword input": function() {
            Y.one(this.submitButton).simulate('click');
            Y.Assert.isNotNull(Y.one('.rn_BannerAlert'));
        },
        
        'Test Click redirects to Search Page': function() {
            this.initValues();
            this.searchField.value = 'Windows';
            var urlToNavigate;

            RightNow.Url.navigate = function(url) {
                urlToNavigate = url;
            };
            
            Y.one(this.submitButton).simulate('click');
            Y.Assert.areSame(this.widgetData.attrs.report_page_url + "/kw/Windows/loc/en-US", urlToNavigate);
        },
        
        'Test URL Encoding of HTML Special Characters': function() {
            this.initValues();
            this.searchField.value = '"" <> & ';
            var urlToNavigate;

            RightNow.Url.navigate = function(url) {
                urlToNavigate = url;
            };
            
            Y.one(this.submitButton).simulate('click');
            Y.Assert.areSame(this.widgetData.attrs.report_page_url+'/kw/%22%22%20%3C%3E%20%26/loc/en-US', urlToNavigate);
        },
        
        'Test Concatenation of Long Search Keyword': function() {
            this.initValues();
            this.searchField.value = 'To position windows server 2003 more competitively against other web servers, Microsoft has released a stripped-down-yet-impressive edition of windows server 2003 designed specially for web services. the feature set and licensing allows customers easy deployment of web pages, web sites, web applications and web services. Web Edition supports 2GB of RAM and a two-way symmetric'.slice(0,this.searchField.getAttribute("maxLength"));
            var urlToNavigate;

            RightNow.Url.navigate = function(url) {
                urlToNavigate = url;
            };
            
            Y.one(this.submitButton).simulate('click');
            Y.Assert.areSame(this.widgetData.attrs.report_page_url+'/kw/To%20position%20windows%20server%202003%20more%20competitively%20against%20other%20web%20servers%2C%20Microsoft%20has%20released%20a%20stripped-down-yet-impressive%20edition%20of%20windows%20server%202003%20designed%20specially%20for%20web%20services.%20the%20feature%20set%20and%20licensing%20allows%20customers%20easy%20dep/loc/en-US', urlToNavigate);
        }
    }));

    return suite;
}).run();
