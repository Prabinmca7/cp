(function() {
    // This was inspired by code at http://www.nczonline.net/blog/2009/06/23/loading-javascript-without-blocking/
    var _loadScript = function(url, context, callback) {
        var script = document.createElement("script");
        script.type = "text/javascript";

        // Callback function to be executed after the reCaptcha api is loaded completely
        onApiLoadCallback = function() {
            callback.call(context);
        };

        script.src = url;
        document.body.appendChild(script);
    },
    /**@inner*/
    _augmentObject = function(target, source) {
        for (var i in source) {
            if (source.hasOwnProperty(i)) {
                target[i] = source[i];
            }
        }
    },
    _captchaWidgetID;

    return {
        create: function(targetDivID, customOptions, callback, dialog) {
            _loadScript(defaultChallengeOptions.scriptUrl, this, function() {
                var customTranslations = false;
                if (defaultChallengeOptions && customOptions) {
                    customTranslations = customOptions.custom_translations;
                    if (defaultChallengeOptions.custom_translations && customTranslations) {
                        _augmentObject(defaultChallengeOptions.custom_translations, customTranslations);
                        customOptions.custom_translations = null;
                    }
                    _augmentObject(defaultChallengeOptions, customOptions);
                }
                defaultChallengeOptions.callback = function(){
                    //Add some offscreen text to improve the screen reader user experience
                    var instructionsSpan = document.getElementById("recaptcha_instructions_image");
                    if(instructionsSpan)
                        instructionsSpan.innerHTML += '<span style="position:absolute; height:1px; left:-10000px; overflow:hidden; top:auto; width:1px;">' + defaultChallengeOptions.cant_see_image + '</span>';
                    //add alt = "" to recaptcha image
                    var recaptchaImage = document.getElementById('recaptcha_image');
                    if(recaptchaImage && (recaptchaImage = recaptchaImage.getElementsByTagName("IMG"))[0]) {
                        recaptchaImage[0].setAttribute('alt', '');
                    }

                    if(callback)
                        callback.apply(this, arguments);
                };

                var captchaResponseCreated = function(response) {
                    // Enable and focus the dialog's OK button when a user returns a valid captcha check.
                    if(dialog) {
                        dialog.enableButtons();
                        Y.Lang.later(400, dialog.getButtons().item(0), 'focus');
                    }
                };

                var captchaResponseExpired = function() {
                    // Disable the dialog's OK button when a captcha response expires and the user needs to re-verify.
                    if(dialog) {
                        dialog.disableButtons();
                    }
                };

                _captchaWidgetID = grecaptcha.render(targetDivID, {
                                     'sitekey' :         defaultChallengeOptions.publicKey,
                                     'callback':         captchaResponseCreated,
                                     'expired-callback': captchaResponseExpired
                                    });

                // Run the callback function after the captcha is rendered. This is for reCaptcha v2.
                if(callback) {
                    callback.apply(this, arguments);
                }

                if (customOptions && customTranslations) {
                    customOptions.custom_translations = customTranslations;
                }
            });
        },

        getInputs: function(targetDivID) {
            var parentDiv = document.getElementById(targetDivID),
                inputs = {};
            if (parentDiv) {
                var response = grecaptcha.getResponse(_captchaWidgetID),
                    // opaque is no longer needed for reCaptcha2.0 but we are keeping it for backward compatibility with the gearman validation call
                    opaque = 'reCaptcha2';

                if (response) {
                    inputs.abuse_challenge_response = response.replace(/^\s+|\s+$/g, "");
                }
                if (opaque) {
                    inputs.abuse_challenge_opaque = opaque;
                }
            }
            return inputs;
        },

        focus: function(targetDivID) {
            try {
                document.getElementById(targetDivID).focus();
            }
            catch (ex) {
                // Focus isn't that important; suppress any errors that result from Recaptcha not being ready.
            }
        },

        destroy: function() {
            try {
                grecaptcha.reset(_captchaWidgetID);
            }
            catch (ex) {
                // Destroying isn't that important; suppress any errors that result from Recaptcha not being ready.
            }
        }
    };
})();
