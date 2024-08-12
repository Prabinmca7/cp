UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'EmailCredentials_0'
}, function(Y, widget, baseSelector){
    var emailCredentialsTests = new Y.Test.Suite({
        name: 'standard/input/EmailCredentials',

        setUp: function(){
            var testExtender = {
                initValues: function() {
                    this.credentialType = widget.data.attrs.credential_type;
                    this.errorDivID = baseSelector  + '_' + this.credentialType + '_ErrorDiv';
                    this.inputField = Y.one(baseSelector + '_' + this.credentialType + '_Input');
                    this.inputValue = this.credentialType === 'password' ? 'someUsername' : 'some@email.address';
                    this.dialogMessage = 'We just sent you an email';

                    this.expectedValues = {
                        requestType: widget.data.js.request_type,
                        value: this.inputValue,
                        w_id: widget.data.info.w_id,
                        f_tok: UnitTest.NO_VALUE,
                    };
                },
                getErrorContents: function() {
                    return Y.one(this.errorDivID).one('a').get('innerHTML');
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    emailCredentialsTests.add(new Y.Test.Case({
        name: 'input validation',
        testInvalidEntries: function() {
            this.initValues();
            var usernameLabel = RightNow.Interface.getMessage('USERNAME_LBL'),
                lessGreaterThanMessage = RightNow.Text.sprintf(RightNow.Interface.getMessage('PCT_S_CNT_THAN_MSG'), usernameLabel).replace('>', '&gt;').replace('<', '&lt;'),
                invalidEmailMessage = RightNow.Interface.getMessage('EMAIL_IS_NOT_VALID_MSG'),
                inputs, expected;

            Y.Assert.isTrue(this.credentialType === 'username' || this.credentialType === 'password');
            if (this.credentialType === 'password') {
                inputs = {
                    '': widget.data.js.field_required,
                    'space s': RightNow.Text.sprintf(RightNow.Interface.getMessage('PCT_S_MUST_NOT_CONTAIN_SPACES_MSG'), usernameLabel),
                    'double"quotes': RightNow.Text.sprintf(RightNow.Interface.getMessage('PCT_S_CONTAIN_DOUBLE_QUOTES_MSG'), usernameLabel),
                    'less<than': lessGreaterThanMessage,
                    'greater>than': lessGreaterThanMessage
                };
            }
            else {
                inputs = {
                    '': widget.data.js.field_required,
                    'some@invalidemail': invalidEmailMessage
                };
            }

            for (var input in inputs) {
                expected = inputs[input];
                this.inputField.set('value', input);
                widget._onSubmit();
                Y.Assert.areSame("<h2>" + RightNow.Interface.getMessage("ERROR_LBL") + "</h2>" + expected, this.getErrorContents());
            }
        },

        testSubmission: function() {
            var responseInfo = {'message': this.dialogMessage},
                responseObject = new RightNow.Event.EventObject(widget, {data: this.expectedValues});

            // Invalid Entry, makeRequest should not be called.
            this.inputField.set('value', '');
            UnitTest.overrideMakeRequest(widget.data.attrs.email_credentials_ajax, {fail: 'Request should not have been made.'});
            widget._onSubmit();
            Y.Assert.isFalse(Y.one(this.errorDivID).hasClass('rn_Hidden'));

            // Valid Entry, makeRequest should be called
            this.inputField.set('value', this.inputValue);            
            UnitTest.overrideMakeRequest(widget.data.attrs.email_credentials_ajax, this.expectedValues, '_onResponseReceived', widget, responseInfo, responseObject);
            UnitTest.overrideMakeRequest('/ci/ajaxRequest/getNewFormToken');
            widget._onSubmit();
            Y.Assert.isTrue(Y.one(this.errorDivID).hasClass('rn_Hidden'));
            Y.Assert.isFalse(Y.one(baseSelector + '_' + this.credentialType + '_LoadingIcon').hasClass('rn_Loading'));

            var dialog = Y.one('#rnDialog1');
            Y.assert(!dialog.ancestor('.yui3-panel-hidden'));
            Y.Assert.areNotSame(-1, dialog.one('.yui3-widget-bd').get('innerHTML').indexOf(this.dialogMessage));
            Y.Assert.isNotNull(dialog.one('.yui3-widget-ft button'));
        }
    }));
    return emailCredentialsTests;
});
UnitTest.run();
