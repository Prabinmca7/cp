UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ["node", "json", "io-base", "panel", "Helpers", "FormToken"],
    preloadFiles: [
        '/euf/core/admin/js/formToken.js',
        '/euf/core/admin/js/versions/components/helpers.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({
        name: "Admin form token tests" ,
    });
    window.formToken = "sampleToken";
    window.submitTokenExp = 30;
    window.labels = {parserError: "parserError", genericError: "genericError"};
    var url = '/sampleRequest';
    var ajaxConfig = {method: 'POST',
        data: {
        },
        on: {
            success: function() {
            },
            failure: function() {

            }
        }
    };

    suite.add(new Y.Test.Case({
        name: "Admin form token tests",
        "Form token exists in jsons post data test": function() {
            Y.FormToken.makeAjax = function(url, config) {
                if (url !== '/ci/admin/commonActions/getNewFormToken') {
                    if (typeof config.data === 'string') {
                        Y.Assert.isTrue(config.data.indexOf("formToken=" + formToken) !== -1);
                    }
                    else {
                        Y.Assert.areSame(config.data.formToken, formToken);
                    }
                }
            };
            Y.FormToken.makeAjaxWithToken(url, ajaxConfig);
        },
        
        "Admin form token test with parse error": function () {
            Y.FormToken.tokenExpiredDialog = function(text) {
                Y.Assert.areSame(text, labels.parserError);
            };
            Y.FormToken.makeAjax = function(url, config) {
                if (url !== '/ci/admin/commonActions/getNewFormToken') {
                    config.on.success(1, {responseText: "{invalid:}"});
                }
            };
            Y.FormToken.makeAjaxWithToken(url, ajaxConfig);
        },
        
        "Admin form token generic error test": function () {
            Y.FormToken.tokenExpiredDialog = function(text) {
                Y.Assert.areSame(text, labels.genericError);
            };
            Y.FormToken.makeAjax = function(url, config) {
                if (url !== '/ci/admin/commonActions/getNewFormToken') {
                    config.on.success(1, {responseText: "{}"});
                }
            };
            Y.FormToken.makeAjaxWithToken(url, ajaxConfig);
        },
        
        "Admin expired form token test": function () {
            Y.FormToken.tokenExpiredDialog = function(text) {
                Y.Assert.areSame(text, 'Token Error');
            };
            Y.FormToken.makeAjax = function(url, config) {
                if (url !== '/ci/admin/commonActions/getNewFormToken') {
                    config.on.success(1, {responseText: '{"errors":[{"externalMessage":"Token Error"}]}'});
                }
            };
            Y.FormToken.makeAjaxWithToken(url, ajaxConfig);
        },
        
        "Admin expired form token test while getting new token": function () {
            Y.FormToken.tokenExpiredDialog = function(text) {
                Y.Assert.areSame(text, 'Token Error');
            };
            Y.FormToken.makeAjax = function(url, config) {
                if (url === '/ci/admin/commonActions/getNewFormToken') {
                    config.on.success(1, {responseText: '{"errors":[{"externalMessage":"Token Error"}]}'});
                }
            };
            Y.FormToken.makeAjaxWithToken(url, ajaxConfig);
        },
        
        "Admin Form token exists in jsons post data for ajax from helper": function() {
            Y.FormToken.makeAjax = function(url, config) {
                if (url !== '/ci/admin/commonActions/getNewFormToken') {
                    if (typeof config.data === 'string') {
                        Y.Assert.isTrue(config.data.indexOf("formToken=" + formToken) !== -1);
                    }
                    else {
                        Y.Assert.areSame(config.data.formToken, formToken);
                    }
                }
            };
            Y.Helpers.ajax(url, ajaxConfig);
        }        
    }));
    return suite;
}).run();
