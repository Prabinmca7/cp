/**
 * NOTE: Since this tests a stepped process, the suites and test methods
 * within those suites are in a necessary order that build upon previous
 * tests' actions, so take care when adding or moving test methods.
 *
 * The final creation step is not tested, since creating a widget could potentially
 * screw up phpFunctional tests or cause other side-effecty problems on the test site.
 */
UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: ['tooltip', 'eventbus', 'step', 'sidebar', 'stepOne', 'stepTwo',
        'stepThree', 'stepFour', 'stepFive', 'finalStep', 'widgetBuilder'].map(function(i) {
            return '/euf/core/admin/js/tools/widgetBuilder/' + i + '.js';
        }).concat('/euf/core/admin/css/tools/widgetBuilder.css')
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "UI Behavior tests for the widget builder - going down the brand new, non-extension path" });

    suite.add(new Y.Test.Case({
        name: "Step one - new or extending",

        "Buttons work": function() {
            Y.Assert.areSame(2, Y.all('.one a').size());
            Y.assert(Y.one('.two').hasClass('hide'));
            Y.all('.one a').item(1).simulate('click');
            Y.assert(!Y.one('.two').hasClass('hide'));
            Y.one('.one a').simulate('click');
            Y.assert(!Y.one('.two').hasClass('hide'));
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Step two - names and folders",

        canContinue: function() {
            return !Y.one('.two button.continue').hasClass('hide');
        },

        nameField: Y.one('.two #name'),
        folderField: Y.one('.two #folder'),

        "Name and folder are required for new widget": function() {
            Y.one('.one a').simulate('click');

            Y.assert(!this.canContinue());

            Y.assert(!this.folderField.hasClass('hide'));
            Y.assert(!this.nameField.hasClass('hide'));

            this.nameField.focus().set('value', 'bar');
            this.nameField.simulate('keypress', {keyCode: 82});
            // Testing valueChanged is weird because YUI is essentially polling
            // for changes (with a default interval of 50 ms)
            this.wait(function() {
                Y.assert(!this.canContinue());
                this.wait(function() {
                    this.folderField.focus().set('value', 'foo');
                    this.folderField.simulate('keypress', {keyCode: 82});
                    this.wait(function() {
                        Y.assert(this.canContinue());
                    }, 50);
                }, 50);
            }, 50);
        },

        "A widget name cannot start with a digit": function() {
            this.nameField.focus().set('value', '123sdf_sdf_');
            this.nameField.simulate('keypress', {keyCode: 82});
            this.wait(function() {
                Y.assert(this.nameField.hasClass('highlight'));
                Y.assert(!this.canContinue());
            }, 50);
        },

        "Special chars not allowed for widget name": function() {
            this.nameField.focus().set('value', '@as2df$');
            this.nameField.simulate('keypress', {keyCode: 82});
            this.wait(function() {
                Y.assert(this.nameField.hasClass('highlight'));
                Y.assert(!this.canContinue());
            }, 50);
        },

        "Alphanumeric and underscores allowed for widget name": function() {
            this.nameField.focus().set('value', '_123sdf');
            this.nameField.simulate('keypress', {keyCode: 82});
            this.wait(function() {
                Y.assert(!this.nameField.hasClass('highlight'));
                Y.assert(this.canContinue());
            }, 50);
        },

        "Special chars not allowed for folder name": function() {
            this.folderField.focus().set('value', '@as2/df$');
            this.folderField.simulate('keypress', {keyCode: 82});
            this.wait(function() {
                Y.assert(this.folderField.hasClass('highlight'));
                Y.assert(!this.canContinue());
            }, 50);
        },

        "Folder names cannot start with a digit": function() {
            this.folderField.focus().set('value', '1/adsf23sd/f_sdf_');
            this.folderField.simulate('keypress', {keyCode: 82});
            this.wait(function() {
                Y.assert(this.folderField.hasClass('highlight'));
                Y.assert(!this.canContinue());
            }, 50);
        },

        "Alphanumeric, underscores, / allowed for folder": function() {
            this.folderField.focus().set('value', '_1/adsf23sd/f_sdf_');
            this.folderField.simulate('keypress', {keyCode: 82});
            this.wait(function() {
                Y.assert(!this.folderField.hasClass('highlight'));
                Y.assert(this.canContinue());
            }, 50);
        },

        "Spaces aren't allowed for name and are simply removed": function() {
            this.nameField.focus().set('value', 'sdf1    ');
            this.nameField.simulate('keypress', {keyCode: 82});
            this.wait(function() {
                Y.assert(!this.nameField.hasClass('highlight'));
                Y.Assert.areSame('sdf1', this.nameField.get('value'));
                Y.assert(this.canContinue());
            }, 50);
        },

        "Spaces aren't allowed for folder and are simply removed": function() {
            this.folderField.focus().set('value', 'sdf1    ');
            this.folderField.simulate('keypress', {keyCode: 82});
            this.wait(function() {
                Y.assert(!this.folderField.hasClass('highlight'));
                Y.Assert.areSame('sdf1', this.folderField.get('value'));
                Y.assert(this.canContinue());
            }, 50);
        },

        "Folder placeholder for folder input updates with entered name": function() {
            Y.Assert.areSame('/ ' + this.nameField.get('value'), Y.one('.two #widgetPlaceholder').getHTML());
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Step three - components",

        canContinue: function() {
            return !Y.one('.three button.continue').hasClass('hide');
        },

        turnOffAllComponents: function() {
            Y.one('.three').all('label').each(function(label) {
                if (Y.Lang.trim(label.get('text')).toLowerCase() === 'no') {
                    var input = Y.one('#' + label.get('for'));
                    if (!input.get('disabled')) {
                        input.simulate('click');
                    }
                }
            });
        },

        "Continue button is initially hidden": function() {
            Y.assert(!this.canContinue());
            Y.one('.two button.continue').simulate('click');
        },

        "AJAX is enabled when Controller is turned on": function() {
            var ajaxFields = Y.one('.three fieldset[data-for="ajax"]').all('input');
            Y.Assert.areSame('true,true', ajaxFields.get('disabled') + '');
            Y.one('.three fieldset[data-for="php"]').one('input').simulate('click');
            Y.Assert.areSame('false,false', ajaxFields.get('disabled') + '');

            Y.assert(this.canContinue());
        },

        "JS templates are enabled when JS is turned on": function() {
            var templateFields = Y.one('.three fieldset[data-for="jsView"]').all('input');
            Y.Assert.areSame('true,true', templateFields.get('disabled') + '');
            Y.one('.three fieldset[data-for="js"]').one('input').simulate('click');
            Y.Assert.areSame('false,false', templateFields.get('disabled') + '');

            Y.assert(this.canContinue());
        },

        "View options are not shown when view is turned on": function() {
            Y.assert(Y.one('.three [data-when="extending-view"]').hasClass('hide'));
            Y.one('.three #hasView').simulate('click');
            Y.assert(Y.one('.three [data-when="extending-view"]').hasClass('hide'));
        },

        "parentCSS section is not shown": function() {
            Y.assert(Y.one('.three [data-for="parentCss"]').ancestor('.hide'));
        },

        "YUI modules are enabled when JS is turned on": function() {
            var yuiModuleLink = Y.one('.three #addYUIModule');
            Y.assert(!yuiModuleLink.hasClass('disabled'));
            Y.one('.three fieldset[data-for="js"]').all('input').item(1).simulate('click');
            Y.assert(yuiModuleLink.hasClass('disabled'));
            Y.one('.three fieldset[data-for="js"]').one('input').simulate('click');

            Y.assert(this.canContinue());
        },

        "Can add YUI modules": function() {
            var yuiModuleLink = Y.one('.three #addYUIModule');
            yuiModuleLink.simulate('click');
            this.wait(function() {
                Y.Assert.areSame(1, Y.one('.three div[data-for="yui"]').all('input').size());
                yuiModuleLink.simulate('click');
                Y.Assert.areSame(2, Y.one('.three div[data-for="yui"]').all('input').size());
            }, 1000);
        },

        "Can remove YUI modules": function() {
            Y.one('.three div[data-for="yui"]').one('a.removeModule').simulate('click');
            Y.Assert.areSame(1, Y.one('.three div[data-for="yui"]').all('input').size());
        },

        "The next step button isn't hidden when a disabled element is clicked": function() {
            this.turnOffAllComponents();
            Y.assert(!this.canContinue());

            Y.one('.three input[disabled]').simulate('click');
            Y.assert(!this.canContinue());
            Y.one('.three a.disabled').simulate('click');
            Y.assert(!this.canContinue());

            Y.one('.three input').simulate('click');
            Y.assert(this.canContinue());
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Step four - attributes",

        "Inherited attributes appear": function() {
            Y.one('.three button.continue').simulate('click');
            this.wait(function() {
               Y.Assert.areNotSame(0, Y.all('.four .attribute')) ;
            }, 1000);
        },

        "Can add an attribute": function() {
            var origAttributes = Y.all('.four .attribute').size();
            Y.one('.four #addAttribute').simulate('click');
            this.wait(function(){
                Y.Assert.areSame(origAttributes + 1, Y.all('.four .attribute').size());
            }, 10);
        },

        "Default value field turns into a checkbox for boolean type": function() {
            this.newAttr = Y.all('.four .attribute').slice(-1).item(0);

            Y.Assert.areSame('text', this.newAttr.one('input[data-name="default"]').get('type'));
            this.newAttr.one('select[data-name="type"]').set('value', 'boolean').focus().simulate('change');
            Y.Assert.areSame('checkbox', this.newAttr.one('input[data-name="default"]').get('type'));
        },

        "Default value field is hidden for option or multioption types": function() {
            this.newAttr = Y.all('.four .attribute').slice(-1).item(0);

            this.newAttr.one('select[data-name="type"]').set('value', 'multioption').focus().simulate('change');
            Y.Assert.isTrue(this.newAttr.one('.row.default').hasClass('hide'));

            this.newAttr.one('select[data-name="type"]').set('value', 'option').focus().simulate('change');
            Y.Assert.isTrue(this.newAttr.one('.row.default').hasClass('hide'));

            this.newAttr.one('select[data-name="type"]').set('value', 'string').focus().simulate('change');
            Y.Assert.isFalse(this.newAttr.one('.row.default').hasClass('hide'));
        },

        "Option field appears for option type and options can be added and removed": function() {
            Y.assert(this.newAttr.one('.options').hasClass('hide'));
            this.newAttr.one('select[data-name="type"]').set('value', 'option').focus().simulate('change');
            Y.assert(!this.newAttr.one('.options').hasClass('hide'));

            this.newAttr.one('.addOption').simulate('click');
            Y.Assert.areSame(1, this.newAttr.one('.options').all('input[data-name="option"]').size());
            Y.Assert.areSame(1, this.newAttr.one('.options').all('input[type="radio"]').size());
            this.newAttr.one('a.removeOption').simulate('click');
            Y.Assert.areSame(0, this.newAttr.one('.options').all('input[data-name="option"]').size());
            Y.Assert.areSame(0, this.newAttr.one('.options').all('input[type="radio"]').size());

            this.newAttr.one('select[data-name="type"]').set('value', 'multioption').focus().simulate('change');
            Y.assert(!this.newAttr.one('.options').hasClass('hide'));

            this.newAttr.one('.addOption').simulate('click');
            Y.Assert.areSame(1, this.newAttr.one('.options').all('input[data-name="option"]').size());
            Y.Assert.areSame(1, this.newAttr.one('.options').all('input[type="checkbox"]').size());
            this.newAttr.one('a.removeOption').simulate('click');
            Y.Assert.areSame(0, this.newAttr.one('.options').all('input[data-name="option"]').size());
            Y.Assert.areSame(0, this.newAttr.one('.options').all('input[type="checkbox"]').size());
        },

        "An option is required when the attribute is an option type": function() {
            this.newAttr.one('select[data-name="type"]').set('value', 'option').focus().simulate('change');
            this.newAttr.all('input,textarea').set('value', 'train');
            Y.one('.four button.continue').simulate('click');
            Y.assert(this.newAttr.one('.options').one('.validation.error'));

            this.newAttr.one('select[data-name="type"]').set('value', 'multioption').focus().simulate('change');
            this.newAttr.all('input,textarea').set('value', 'train');
            Y.one('.four button.continue').simulate('click');
            Y.assert(this.newAttr.one('.options').one('.validation.error'));
        },

        "When switching from option to multioption radio buttons are converted to checkboxes": function() {
            this.newAttr.one('select[data-name="type"]').set('value', 'option').focus().simulate('change');

            this.newAttr.one('.addOption').simulate('click');
            Y.Assert.areSame(1, this.newAttr.one('.options').all('input[data-name="option"]').size());
            Y.Assert.areSame(1, this.newAttr.one('.options').all('input[type="radio"]').size());
            Y.Assert.areSame(0, this.newAttr.one('.options').all('input[type="checkbox"]').size());

            this.newAttr.one('select[data-name="type"]').set('value', 'multioption').focus().simulate('change');
            Y.Assert.areSame(1, this.newAttr.one('.options').all('input[data-name="option"]').size());
            Y.Assert.areSame(1, this.newAttr.one('.options').all('input[type="checkbox"]').size());
            Y.Assert.areSame(0, this.newAttr.one('.options').all('input[type="radio"]').size());

            this.newAttr.one('a.removeOption').simulate('click');
        },

        "Required fields are required before continuing": function() {
            var nameField = this.newAttr.one('input[data-name="name"]').set('value', ''),
                descField = this.newAttr.one('textarea[data-name="description"]').set('value', '');

            nameField.simulate('blur');
            descField.simulate('blur');

            Y.assert(nameField.hasClass('highlight'), 'nameField has highlight class');
            Y.assert(descField.hasClass('highlight'), 'descField has highlight class');
        },

        "Continue button is shown with correct data": function() {
            var nameField = this.newAttr.one('input[data-name="name"]').set('value', 'cucumber'),
                descField = this.newAttr.one('textarea[data-name="description"]').set('value', 'carrot');

            nameField.simulate('blur');
            descField.simulate('blur');

            Y.assert(!nameField.hasClass('highlight'), 'nameField does not have highlight class');
            Y.assert(!descField.hasClass('highlight'), 'descField does not have highlight class');
        },

        "Uppercase characters aren't allowed in attr names": function() {
            var nameField = this.newAttr.one('input[data-name="name"]').set('value', 'MOAR_Bananas');

            Y.one('.four button.continue').simulate('click');
            Y.assert(nameField.hasClass('highlight'));
            Y.assert(nameField.get('parentNode').one('.validation'));

            nameField.set('value', 'bananas');
        },

        "Spaces aren't allowed in attr names": function() {
            var nameField = this.newAttr.one('input[data-name="name"]').set('value', 'more bananas');

            Y.one('.four button.continue').simulate('click');
            Y.assert(nameField.hasClass('highlight'));
            Y.assert(nameField.get('parentNode').one('.validation'));

            nameField.set('value', 'bananas');
        },

        "Periods aren't allowed in attr names": function() {
            var nameField = this.newAttr.one('input[data-name="name"]').set('value', 'more.bananas');

            Y.one('.four button.continue').simulate('click');
            Y.assert(nameField.hasClass('highlight'));
            Y.assert(nameField.get('parentNode').one('.validation'));

            nameField.set('value', 'bananas');
        },

        "Can remove an attribute": function() {
            var origAttributes,
                nextButton = Y.one('.four button.continue');

            this.newAttr.one('.addOption').simulate('click');
            origAttributes = Y.all('.four .attribute').size();
            this.newAttr.one('a.remove').simulate('click');

            this.wait(function() {
                Y.Assert.areSame(origAttributes - 1, Y.all('.four .attribute').size());
                Y.Assert.isFalse(nextButton.hasClass("hide"), "continue button should still be visible");
            }, 1000);
        },

        "Uppercase characters are OK in option names": function() {
            this.newAttr.one('.addOption').simulate('click');
            var option = this.newAttr.one('.options').one('input[data-name="option"]')
                .set('value', 'MOARBananas');

            Y.one('.four button.continue').simulate('click');
            Y.assert(!option.hasClass('highlight'));
        },

        "Spaces are OK in option names": function() {
            this.newAttr.one('.addOption').simulate('click');
            var option = this.newAttr.one('.options').one('input[data-name="option"]')
                .set('value', 'more bananas');

            Y.one('.four button.continue').simulate('click');
            Y.assert(!option.hasClass('highlight'));
        },

        "Periods are OK in option names": function() {
            this.newAttr.one('.addOption').simulate('click');
            var option = this.newAttr.one('.options').one('input[data-name="option"]')
                .set('value', 'more.bananas');

            Y.one('.four button.continue').simulate('click');
            Y.assert(!option.hasClass('highlight'));
        },

        "Dashes are OK in option names": function() {
            this.newAttr.one('.addOption').simulate('click');
            var option = this.newAttr.one('.options').one('input[data-name="option"]')
                .set('value', 'more-bananas');

            Y.one('.four button.continue').simulate('click');
            Y.assert(!option.hasClass('highlight'));
        },

        "Underscores are OK in option names": function() {
            this.newAttr.one('.addOption').simulate('click');
            var option = this.newAttr.one('.options').one('input[data-name="option"]')
                .set('value', 'more_bananas');

            Y.one('.four button.continue').simulate('click');
            Y.assert(!option.hasClass('highlight'));
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Step five - optional docs",

        "Step is initially collapsed and continue button stays enabled": function() {
            Y.one('.four button.continue').simulate('click');
            Y.assert(!Y.all('.four .attribute .validation').size());
            Y.assert(Y.one('.five .content > div').hasClass('hide'));
            Y.assert(!Y.one('.final').hasClass('hide'));
            Y.assert(!Y.one('.final #finishIt').hasClass('hide'));

            Y.one('.five .content > h3 > a').simulate('click');
            Y.assert(!Y.one('.five .content > div').hasClass('hide'));
        },

        "Can add a param": function() {
            var origUrlParams = Y.all('.five .urlParams .urlParam').size();

            Y.one('.five #addUrlParam').simulate('click');
            this.wait(function() {
                Y.Assert.areSame(origUrlParams + 1, Y.all('.five .urlParams .urlParam').size());
            }, 20);
        },

        "All param fields are required": function() {
            Y.one('.final #finishIt').simulate('click');
            Y.Assert.areSame('true,true,true,true', Y.one('.five .urlParam').all('input,textarea').hasClass('highlight') + '');
        },

        "Spaces aren't allowed for param names": function() {
            var name = Y.one('.five .urlParam input[data-name="key"]').set('value', 'more bananas');

            Y.one('.final #finishIt').simulate('click');
            Y.assert(name.hasClass('highlight'));
            Y.assert(name.get('parentNode').one('.validation'));
        },

        "Can remove a param": function() {
            var origUrlParams = Y.all('.five .urlParams .urlParam').size();

            Y.one('.five .urlParam .remove').simulate('click');
            this.wait(function() {
                Y.Assert.areSame(origUrlParams - 1, Y.all('.five .urlParams .urlParam').size());
            }, 1000);
        },

        "Other JS modules are disabled and unchecked when JS module none is selected": function() {
            var standard = Y.one('.five .compatibility input[data-for="standard"]'),
                mobile = Y.one('.five .compatibility input[data-for="mobile"]'),
                none = Y.one('.five .compatibility input[data-for="none"]');

            Y.assert(standard.get('checked'));
            Y.assert(mobile.get('checked'));
            Y.assert(!none.get('checked'));

            none.simulate('click');

            Y.assert(!standard.get('checked'));
            Y.assert(standard.get('disabled'));
            Y.assert(!mobile.get('checked'));
            Y.assert(mobile.get('disabled'));
        }
    }));

    suite.add(new Y.Test.Case({
        name: "General",
        "Clicking something in a previous step hides the later ones": function() {
            Y.one('.one a').simulate('click');
            Y.all('.three,.four,.five,.last').each(function(node) {
                Y.assert(node.hasClass('hide'));
            });
        }
    }));

    return suite;
}).run();
