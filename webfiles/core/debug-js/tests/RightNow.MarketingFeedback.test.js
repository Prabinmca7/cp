UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: ['/euf/core/debug-js/RightNow.MarketingFeedback.js', '/euf/core/debug-js/RightNow.Text.js'],
    namespaces: ['RightNow.MarketingFeedback', 'RightNow.Text']
}, function(Y){
    var rightnowMarketingFeedbackTests = new Y.Test.Suite("RightNow.MarketingFeedback");

    //validateMarketingFields
    rightnowMarketingFeedbackTests.add(new Y.Test.Case(
    {
        name: "validateMarketingFields",

        //Test Methods
        testMenuField: function() {
            var fieldData = {reqd_msg: "reqd_msg"};

            var formElement = document.createElement("form");
            var menuElement = document.createElement("select");

            menuElement.id = "menuElement";
            menuElement.appendChild(document.createElement("option"));
            menuElement.appendChild(document.createElement("option"));
            menuElement.appendChild(document.createElement("option"));

            formElement.appendChild(menuElement);
            document.body.appendChild(formElement);

            var menuField = {name: 'wf_2_49',
                            label: 'menu1',
                            type: 1,
                            maxlen: 1,
                            flags: 1};

            var errorDisplay = document.createElement("div");
            errorDisplay.id = 'testMenuField';
            document.body.appendChild(errorDisplay);
            // Should have error
            RightNow.MarketingFeedback.validateMenuField(menuElement, fieldData, menuField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');

            // Should not have errror
            menuElement.selectedIndex = 2;
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateMenuField(menuElement, fieldData, menuField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_Hidden');
        },

        testBoolField: function() {
            var fieldData = {reqd_msg: "reqd_msg"};

            var formElement = document.createElement("form");
            var choiceA = document.createElement("input");
            var choiceB = document.createElement("input");

            choiceA.name = "testBool";
            choiceB.name = "testBool";

            formElement.appendChild(choiceA);
            formElement.appendChild(choiceB);

            var boolField = {name: 'wf_2_50',
                            label: 'optin',
                            type: 8,
                            maxlen: 0,
                            flags: 1};

            var errorDisplay = document.createElement("div");
            errorDisplay.id = 'testBoolField';
            document.body.appendChild(errorDisplay);

            // Should have error
            RightNow.MarketingFeedback.validateBoolField(formElement.testBool, fieldData, boolField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');


            // Should not have errror
            choiceA.checked = true;
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateBoolField(formElement.testBool, fieldData, boolField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_Hidden');
        },

        testIntField: function() {
            var fieldData = {reqd_msg: "reqd_msg"};

            var formElement = document.createElement("form");
            var intInputElement = document.createElement("input");
            intInputElement.id = 'intInputElement';

            var intField = {name: 'wf_2_48',
                            label: 'int1',
                            type: 3,
                            maxlen: 2,
                            flags: 1};


            formElement.appendChild(intInputElement);
            document.body.appendChild(formElement);
            var errorDisplay = document.createElement("div");
            errorDisplay.id = 'testIntField';
            document.body.appendChild(errorDisplay);

            // Should have error
            RightNow.MarketingFeedback.validateIntField(intInputElement, fieldData, intField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');

            // Should not have errror
            intInputElement.value = 100;
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateIntField(intInputElement, fieldData, intField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_Hidden');

            // Should have error
            intInputElement.value = "I can't take strings, silly goose";
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateIntField(intInputElement, fieldData, intField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');
        },

        testTextField: function() {
            var fieldData = {
            not_valid_phone_char_msg: "not_valid_phone_char_msg",
            oversz_msg: "oversz_msg",
            email_msg: "email_msg",
            reqd_msg: "reqd_msg",
            ascii_msg: "ascii_msg",
            email_expr: "^(([-!#$%&\'*+/=?^~`{|}\\w]+(\\.[-!#$%&\'*+/=?^~`{|}\\w]+)*)|(\"[^\"]+\"))@[0-9A-Za-z]+(-[0-9A-Za-z]+)*(\\.[0-9A-Za-z]+(-[0-9A-Za-z]+)*)+$"
            };

            var formElement = document.createElement("form");
            var textInputElement = document.createElement("input");
            textInputElement.id ='textInputElement';
            formElement.appendChild(textInputElement);
            document.body.appendChild(formElement);

            var textField = {name: 'wf_2_52',
                            label: 'int1',
                            type: 5,
                            maxlen: 80,
                            flags: 1};

            var errorDisplay = document.createElement("div");
            errorDisplay.id = 'testTextField';
            document.body.appendChild(errorDisplay);

            // Should have error - required
            RightNow.MarketingFeedback.validateTextField(textInputElement, fieldData, textField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');

            // Should not have errror
            textInputElement.value = "HELLO WORLD";
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateTextField(textInputElement, fieldData, textField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_Hidden');

            // Should have errror - maxlen
            textInputElement.value = "HELLO WORLD HELLO WORLD HELLO WORLD HELLO WORLD HELLO WORLD HELLO WORLD HELLO WORLD HELLO WORLD HELLO WORLD HELLO WORLD HELLO WORLD";
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateTextField(textInputElement, fieldData, textField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');

            // set up an email required field
            textField = {name: 'wf_2_52',
                            label: 'text1',
                            type: 5,
                            maxlen: 80,
                            flags: 5};

            // should have an error - expecting email
            textInputElement.value = "HELLO WORLD";

            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateTextField(textInputElement, fieldData, textField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');

            // valid email address - no error
            textInputElement.value = "test@test.com";
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateTextField(textInputElement, fieldData, textField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_Hidden');

        },

        testDateTimeField: function() {
            var fieldData = {reqd_msg: "reqd_msg",
                             dt_sfx: ['_mon', '_day', '_yr'],
                             dt_lbl: ['mon_lbl', 'day_lbl', 'yr_lbl', 'hr_lbl', 'min_lbl']};

            var formElement = document.createElement("form");

            var monthSelect = document.createElement("select");
            monthSelect.setAttribute('id', 'wf_2_46_mon');
            monthSelect.appendChild(document.createElement("option"));
            monthSelect.appendChild(document.createElement("option"));
            monthSelect.appendChild(document.createElement("option"));
            monthSelect.name = 'wf_2_46_mon';

            var daySelect = document.createElement("select");
            daySelect.appendChild(document.createElement("option"));
            daySelect.appendChild(document.createElement("option"));
            daySelect.appendChild(document.createElement("option"));
            daySelect.name = 'wf_2_46_day';

            var yearSelect = document.createElement("select");
            yearSelect.appendChild(document.createElement("option"));
            yearSelect.appendChild(document.createElement("option"));
            yearSelect.appendChild(document.createElement("option"));
            yearSelect.name = 'wf_2_46_yr';

            formElement.appendChild(monthSelect);
            formElement.appendChild(daySelect);
            formElement.appendChild(yearSelect);
            document.body.appendChild(formElement);

            var dateField = {name: 'wf_2_46',
                            label: 'date1',
                            type: 7,
                            maxlen: 0,
                            flags: 1};

            var errorDisplay = document.createElement("div");
            errorDisplay.id = 'testDateTimeField';
            document.body.appendChild(errorDisplay);


            // Should have error
            RightNow.MarketingFeedback.validateDateTimeField(formElement, fieldData, dateField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');

            monthSelect.selectedIndex = 2;
            daySelect.selectedIndex = 2;

            // Should still have error
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateDateTimeField(formElement, fieldData, dateField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');

            monthSelect.selectedIndex = 2;
            daySelect.selectedIndex = 2;
            yearSelect.selectedIndex = 2;

            // Should not have error
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateDateTimeField(formElement, fieldData, dateField, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_Hidden');
        }
    }));

    //validateSurveyFields
    rightnowMarketingFeedbackTests.add(new Y.Test.Case(
    {
        name: "validateSurveyFields",

        //Test Methods
        testSurveyValidation: function() {
            var fieldData = {reqd_msg: "reqd_msg",
                             fld_too_mny_chars_msg: "fld_too_mny_chars_msg",
                             too_few_options_msg: "too_few_options_msg",
                             too_many_options_msg: "too_many_options_msg"};

            var formElement = document.createElement("form");

            // question 1 - choice
            var choiceA = document.createElement("input");
            choiceA.name = 'q_1';
            var choiceB = document.createElement("input");
            choiceB.name = 'q_1';

            formElement.appendChild(choiceA);
            formElement.appendChild(choiceB);

            // question 2 - text
            var textElement = document.createElement("textarea");
            textElement.name = 'q_2';

            formElement.appendChild(textElement);


            // question 3 - matrix
            var choice1A = document.createElement("input");
            choice1A.name = 'q_3_r_1';
            var choice1B = document.createElement("input");
            choice1B.name = 'q_3_r_1';
            var choice1C = document.createElement("input");
            choice1C.name = 'q_3_r_1';

            var choice2A = document.createElement("input");
            choice2A.name = 'q_3_r_2';
            var choice2B = document.createElement("input");
            choice2B.name = 'q_3_r_2';
            var choice2C = document.createElement("input");
            choice2C.name = 'q_3_r_2';

            var choice3A = document.createElement("input");
            choice3A.name = 'q_3_r_3';
            var choice3B = document.createElement("input");
            choice3B.name = 'q_3_r_3';
            var choice3C = document.createElement("input");
            choice3C.name = 'q_3_r_3';

            formElement.appendChild(choice1A);
            formElement.appendChild(choice1B);
            formElement.appendChild(choice1C);

            formElement.appendChild(choice2A);
            formElement.appendChild(choice2B);
            formElement.appendChild(choice2C);

            formElement.appendChild(choice3A);
            formElement.appendChild(choice3B);
            formElement.appendChild(choice3C);

            // question 4 - choice
            var choice4A = document.createElement("input");
            choice4A.name = 'q_4';
            var choice4B = document.createElement("input");
            choice4B.name = 'q_4';
            var choice4C = document.createElement("input");
            choice4C.name = 'q_4';
            var choice4D = document.createElement("input");
            choice4D.name = 'q_4';

            formElement.appendChild(choice4A);
            formElement.appendChild(choice4B);
            formElement.appendChild(choice4C);
            formElement.appendChild(choice4D);


            var surveyFields = [
                               {id: '1',
                                min: 1,
                                max: 1,
                                type: 2,
                                elements: 'q_1_1:q_1_2',
                                question_text: 'question 1',
                                force_ranking: false},
                               {id: '2',
                                min: -1,
                                max: 10,
                                type: 1,
                                elements: '',
                                question_text: 'question 2',
                                force_ranking: false},
                               {id: '3_r_1',
                                min: 1,
                                max: 3,
                                type: 3,
                                elements: 'q_16_r_1_1:q_16_r_1_2:q_16_r_1_3',
                                question_text: 'question 3 - row 1',
                                force_ranking: true},
                               {id: '3_r_2',
                                min: 1,
                                max: 3,
                                type: 3,
                                elements: 'q_16_r_2_1:q_16_r_2_2:q_16_r_2_3',
                                question_text: 'question 3 - row 1',
                                force_ranking: true},
                               {id: '3_r_3',
                                min: 1,
                                max: 3,
                                type: 3,
                                elements: 'q_16_r_3_1:q_16_r_3_2:q_16_r_3_3',
                                question_text: 'question 3 - row 1',
                                force_ranking: true},
                               {id: '4',
                                min: 2,
                                max: 3,
                                type: 2,
                                elements: 'q_4_1:q_4_2:q_4_3:q_4_4',
                                question_text: 'question 4',
                                force_ranking: false}
                                ];

            var errorDisplay = document.createElement("div");
            errorDisplay.id = 'testSurveyValidation';
            document.body.appendChild(errorDisplay);


            // Should have error
            RightNow.MarketingFeedback.validateSurveyFields(formElement, fieldData, surveyFields, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');

            choiceA.checked = true;



            choice1A.checked = true;
            choice2B.checked = true;
            choice3C.checked = true;

            // too few options for question 4
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateSurveyFields(formElement, fieldData, surveyFields, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');

            choice4A.checked = true;

            // still too few options for question 4
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateSurveyFields(formElement, fieldData, surveyFields, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');


            choice4B.checked = true;
            choice4C.checked = true;
            choice4D.checked = true;

            // too many options for question 4
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateSurveyFields(formElement, fieldData, surveyFields, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');


            choice4D.checked = false;
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateSurveyFields(formElement, fieldData, surveyFields, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_Hidden');



            textElement.value = 'THIS IS MORE THAN 10 CHARACTERS';
            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateSurveyFields(formElement, fieldData, surveyFields, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_MessageBox rn_ErrorMessage');

            textElement.value = '< 10';

            RightNow.MarketingFeedback.removeErrorsFromForm(formElement, errorDisplay);
            RightNow.MarketingFeedback.validateSurveyFields(formElement, fieldData, surveyFields, errorDisplay);
            Y.Assert.areSame(errorDisplay.className, 'rn_Hidden');
        }
    }));
    return rightnowMarketingFeedbackTests;
});
UnitTest.run();
