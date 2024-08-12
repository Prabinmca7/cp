//= require admin/js/tools/widgetBuilder/tooltip.js
YUI().use('node', 'event-base', 'event-key', 'selector-css3', 'io-base', 'anim', 'json', 'template', 'querystring-stringify-simple', 'tip-it', 'collection', 'FormToken', function(Y) {
    /**
     * Useful helper functions.
     */
    var Cards = {},
        template = new Y.Template(),
        Helpers = {
            messageTemplate: template.compile(Y.one('#messageList').getHTML()),
            snippetTemplate: template.compile(Y.one('#snippetTemplate').getHTML()),
            instructionTemplate: template.compile('<li><i class="<%=data.icon%>"></i><div class="list-text"><%==data.message%></div></li>'),
            compareVersions: function(currentVersion, nextVersion) {
                var getValue = function(version) {
                    return ((version === messages.preVersionThree) ? '2.0' : version).split('.');
                };

                currentVersion = getValue(currentVersion);
                nextVersion = getValue(nextVersion);

                for(var i = 0; i < 2; i++) {
                    if(currentVersion[i] < nextVersion[i]) return -1;
                    if(currentVersion[i] > nextVersion[i]) return 1;
                }

                return 0;
            },
            ajax: function(url, data, loadingNode, success, scope) {
                Helpers.addLoading(loadingNode);

                Y.FormToken.makeAjaxWithToken(url, {
                    method: 'POST',
                    data: data,
                    on: {
                        success: function(id, response) {
                            response = Y.JSON.parse(response.responseText);
                            if(response.genericError) {
                                this.addError(response.genericError);
                                Helpers.removeLoading(loadingNode);
                                return;
                            }

                            var result = success.call(this, response);
                            Helpers.removeLoading(loadingNode);
                            return result;
                        },
                        failure: function() {
                            this.addError(messages.unexpectedError);
                            Helpers.removeLoading(loadingNode);
                        }
                    },
                    context: scope
                }, scope);
            },
            getUnitHtml: function(unitData, operationType, isConfirmation) {
                var result = Y.Node.create('<div>'),
                    instructionList, resultMessage;

                if(!unitData.errors || !unitData.errors.length) {
                    if(unitData.messages && unitData.messages.length) {
                        result.append(Helpers.messageTemplate({
                            header: messages.messageHeader,
                            iconType: 'fa fa-info-circle',
                            messages: unitData.messages
                        }));
                    }
                    if(unitData.instructions && unitData.instructions.length) {
                        instructionList = Y.Node.create('<ul class="message-list">');
                        Y.Array.each(unitData.instructions, function(instruction) {
                            var instructionHtml = Helpers.getInstructionHtml(instruction, operationType, isConfirmation);
                            if(instructionHtml) {
                                instructionList.append(instructionHtml);
                            }
                        });

                        resultMessage =
                            (operationType === 'suggestion' ? messages.suggestionInstructionHeader :
                            (isConfirmation ? messages.completionInstructionHeader : messages.conversionInstructionHeader));

                        result.appendChild('<div class="list-highlight">')
                              .append('<h4 class="title">' + resultMessage + '</h4>')
                              .append(instructionList);
                    }
                }
                else {
                    result.append(Helpers.messageTemplate({
                        header: messages.errorHeader,
                        iconType: 'fa fa-exclamation-circle',
                        messages: unitData.errors
                    }));
                }
                return result;
            },
            getInstructionHtml: function(instruction, operationType, isConfirmation) {
                var metadata = InstructionMetaData[instruction.type],
                    requiredParameters, parameters = [], handler;

                if(!metadata) return;
                if(metadata.type !== operationType) return;

                //Depending on their progress choose the appropriate parameters and handler
                instruction.message = InstructionMetaData[instruction.type][(isConfirmation) ? 'messageConfirmation' : 'message'];
                instruction.icon = InstructionMetaData[instruction.type].icon;
                requiredParameters = (isConfirmation && metadata.requiresBackup) ? metadata.parameters.concat(['backup']) : metadata.parameters;
                handler = (isConfirmation && metadata.handlerConfirmation) ? metadata.handlerConfirmation : metadata.handler;

                //Ensure that the required parameters exist on the instruction object
                var hasParameters = Y.Array.every(requiredParameters.concat(['icon']), function(parameter) {
                    if(!instruction[parameter]) return false;
                    parameters.push(instruction[parameter]);
                    return true;
                });

                if(!hasParameters) return;

                //Call the handler with the appropriate parameters and return the constructed HTML.
                return handler.apply(Helpers, parameters);
            },
            getSnippetHtml: function(message, source, snippets, icon) {
                var content = message.replace('%s', Helpers.getVisiblePath(source));

                //Add in all of the snippets
                Y.Array.each(snippets, function(snippet) {
                    content += this.snippetTemplate(snippet);
                }, this);

                return this.instructionTemplate({icon: icon, message: content});
            },
            getOnePathHtml: function(message, path, icon) {
                return this.instructionTemplate({icon: icon, message: message.replace('%s', Helpers.getVisiblePath(path))});
            },
            getTwoPathHtml: function(message, firstPath, secondPath, icon) {
                return this.instructionTemplate({icon: icon, message: message.replace('%s', Helpers.getVisiblePath(firstPath)).replace('%s', Helpers.getVisiblePath(secondPath))});
            },
            getThreePathHtml: function(message, firstPath, secondPath, thirdPath, icon) {
                return this.instructionTemplate({icon: icon, message: message.replace('%s', Helpers.getVisiblePath(firstPath)).replace('%s', Helpers.getVisiblePath(secondPath)).replace('%s', Helpers.getVisiblePath(thirdPath))});
            },
            getVisiblePath: function(pathObject) {
                if(pathObject.isDAV) {
                    return '<a target="_blank" href="/dav/' + pathObject.davPath + '">' + pathObject.visiblePath + '</a>';
                }
                return pathObject.visiblePath;
            },
            addLoading: function(node) {
                var loadingMessage = Y.Node.create('<div class="screenreader" role="alert" tabindex="0">' + messages.loading + '</div>');
                node.appendChild('<div class="bigwait loading"></div>').append(loadingMessage);
                loadingMessage.focus();
            },
            removeLoading: function(node) {
                node.one('.bigwait').remove();
                node.setAttribute('tabindex', 0).focus().removeAttribute('tabindex');

                //Now that we've lost focus, find and focus the next best thing
                var focusElement;
                if(focusElement = node.one('input, button')) {
                    focusElement.focus();
                }
                else if(focusElement = node.one('.validation')) {
                    focusElement.setAttribute('tabindex', 0).focus();
                }
            },
            extend: function(originalOptions, extendingOptions) {
                //Add originalOptions to extendingOptions for any property not already defined on extendingOptions
                return Y.mix(extendingOptions, originalOptions);
            }
        },
        InstructionMetaData = {
            'codeSnippets': {
                type: 'suggestion',
                parameters: ['message', 'source', 'snippets'],
                handler: Helpers.getSnippetHtml,
                message: messages.codeSnippets,
                icon: 'fa fa-info-circle'
            },
            'createDirectory': {
                type: 'conversion',
                parameters: ['message', 'source'],
                handler: Helpers.getOnePathHtml,
                message: messages.createDirectory,
                messageConfirmation: messages.createDirectoryConfirmation,
                icon: 'fa fa-plus-circle'
            },
            'createFile': {
                type: 'conversion',
                parameters: ['message', 'source'],
                handler: Helpers.getOnePathHtml,
                message: messages.createFile,
                messageConfirmation: messages.createFileConfirmation,
                icon: 'fa fa-plus-circle'
            },
            'deleteFile': {
                type: 'conversion',
                parameters: ['message', 'source'],
                requiresBackup: true,
                handler: Helpers.getOnePathHtml,
                handlerConfirmation: Helpers.getTwoPathHtml,
                message: messages.deleteFile,
                messageConfirmation: messages.deleteFileConfirmation,
                icon: 'fa fa-minus-circle'
            },
            'modifyFile': {
                type: 'conversion',
                parameters: ['message', 'source'],
                requiresBackup: true,
                handler: Helpers.getOnePathHtml,
                handlerConfirmation: Helpers.getTwoPathHtml,
                message: messages.modifyFile,
                messageConfirmation: messages.modifyFileConfirmation,
                icon: 'fa fa-info-circle'
            },
            'moveFile': {
                type: 'conversion',
                parameters: ['message', 'source', 'destination'],
                requiresBackup: true,
                handler: Helpers.getTwoPathHtml,
                handlerConfirmation: Helpers.getThreePathHtml,
                message: messages.moveFile,
                messageConfirmation: messages.moveFileConfirmation,
                icon: 'fa fa-info-circle'
            },
            'moveDirectory': {
                type: 'conversion',
                parameters: ['message', 'source', 'destination'],
                requiresBackup: true,
                handler: Helpers.getTwoPathHtml,
                handlerConfirmation: Helpers.getThreePathHtml,
                message: messages.moveDirectory,
                messageConfirmation: messages.moveDirectoryConfirmation,
                icon: 'fa fa-info-circle'
            }
        };

    /**
     * Define the `Card` constructor. Cards are used throughout the application and represent a single step in the
     * wizard process.
     */
    function Card(definition, args) {
        if(Y.Object.isEmpty(Card.prototype)) {
            Card.prototype.count = 0;
            Card.prototype.events = {};
        }
        Card.prototype.count++;

        var CardFactory = function() {
                //Generate the card and insert it into the DOM
                this.container = Y.one('#container');
                if(this.container.all('.card').size() < Card.prototype.count) {
                    this.container.append(this.defaultTemplate({number: Card.prototype.count, title: this.title || messages.noTitle}));
                }
                this.cardNumber = Card.prototype.count;
                this.card = Y.all('.card').item(this.cardNumber - 1);
                this.content = this.card.one('.content');
                this.detachHandles = [];

                //Subscribe to any events that the card needs
                if(this.events && Y.Lang.isArray(this.events) && !Card.prototype.events[Card.prototype.count]) {
                    Y.Array.each(this.events, function(item) {
                        if(!item.type || !item.selector || !item.handler || !this[item.handler]) return;

                        this.detachHandles.push(
                            this.card.delegate(item.type, cardKiller(this.cardNumber, this[item.handler], this), item.selector, this)
                        );
                    }, this);
                }

                //If the card defines a constructor, call it with the arguments
                this.initialize && this.initialize.apply(this, Y.Lang.isArray(args) ? args : [args]);
            },
            cardKiller = function(cardNumber, handler, scope) {
                return function(e) {
                    Y.all('.card').slice(cardNumber, Card.prototype.count).remove();
                    Card.prototype.count = cardNumber;

                    if(handler) {
                        return handler.call(scope || this, e);
                    }
                };
            };

        CardFactory.prototype = {
            defaultTemplate: template.compile('\
                <div class="card">\
                    <h3><span class="step"><%=data.number%>.</span><span class="title"><%=data.title%></span></h3>\
                    <div role="alert" class="content">\
                    </div>\
                </div>\
            '),
            addMessage: function(message, type) {
                var validationNode = this.content.one('.validation'), messageNode;

                if(!validationNode) {
                    validationNode = Y.Node.create('<div class="row validation"></div>');
                    this.content.prepend(validationNode);
                }
                messageNode = validationNode.get('firstChild') || validationNode.appendChild('<div class="' + type + '"></div>');

                if(messageNode.hasClass('error') && type === 'error' || messageNode.hasClass('note')) {
                    messageNode.set('text', message);
                }
            },
            addError: function(message) {
                this.addMessage(message, 'error');
            },
            addWarning: function(message) {
                this.addMessage(message, 'note');
            },
            clearError: function() {
                var validationNode = this.content.one('.validation');
                if(!validationNode) return;

                validationNode.all('.error').remove();
            },
            toggleExpanded: function(node, expand) {
                if(!node) return;

                var methods = ['addClass', 'removeClass'];
                if(expand) methods.reverse();

                node.one('.header')[methods[1]]('corner');
                node.one('.description').toggleClass('expanded');
                node.one('.iconplusminus')[methods[0]]('fa-minus')[methods[1]]('fa-plus');
            },
            resetAll: function() {
                Y.all('.card').slice(1, Card.prototype.count).remove();
                Card.prototype.count = 1;
            },
            detachEvents: function() {
                if(!Y.Lang.isArray(this.detachHandles) || !this.detachHandles.length) return;
                return Y.Array.every(this.detachHandles, function(handle) {
                    return handle.detach();
                });
            }
        };

        Y.mix(CardFactory.prototype, definition);
        return new CardFactory();
    }

    /**
     * Card Definitions. Define the implementation of each card including its user and server interactions.
     */
    Cards.Version = {
        template: template.compile(Y.one('#selectVersions').getHTML()),
        title: messages.selectVersions,
        events: [
            {type: 'click', selector: '.content button.continue', handler: 'nextCard'}
        ],
        initialize: function() {
            var dropdown = Y.one('#versionDropdown').getHTML(),
                developmentDropdown = template.render(dropdown, configuration.developmentVersions),
                productionDropdown = template.render(dropdown, configuration.productionVersions);

            this.content.append(this.template({
                instructions: messages.selectVersionMessage.replace('%s', productionDropdown).replace('%s', developmentDropdown)
            }));
        },
        nextCard: function(e) {
            var selects = this.content.all('select'),
                productionVersion = selects.item(0).get('value'),
                developmentVersion = selects.item(1).get('value');

            //If the production version is greater than or equal to the development version, display an error
            if(!developmentVersion || !productionVersion || Helpers.compareVersions(productionVersion, developmentVersion) !== -1) {
                this.addError(messages.versionError);
                return;
            }

            var hasChangedVersion =
                configuration.developmentVersions.selectedVersion !== developmentVersion ||
                configuration.productionVersions.selectedVersion !== productionVersion;

            new Card(Cards.Selection, [productionVersion, developmentVersion, hasChangedVersion]);
            this.clearError();
        }
    };

    Cards.Selection = {
        template: template.compile(Y.one('#selectionContent').getHTML()),
        title: messages.selectionTitle,
        events: [
            {type: 'click', selector: '.collapsible-list .header', handler: 'selectOption'},
            {type: 'click', selector: 'button.continue', handler: 'nextCard'}
        ],
        initialize: function(currentVersion, nextVersion, hasChangedVersion) {
            var successHandler = function(response) {
                if(hasChangedVersion) {
                    this.addWarning(messages.changedVersionWarning);
                }
                this.content.append(this.template(response));
            };

            Helpers.ajax('/ci/admin/assistant/retrieveOperations', {
                current: currentVersion,
                next: nextVersion
            }, this.content, successHandler, this);
        },
        selectOption: function(e) {
            var target = e.currentTarget.ancestor('.item'),
                list = target.ancestor('.collapsible-list');

            //Reset every item in the list
            list.all('.item input').set('checked', false);
            list.all('.description').removeClass('expanded');
            list.all('.header').addClass('corner');

            //Expand the selected item and scroll it into view
            new Y.Anim({
                node: list,
                duration: 0.4,
                to: {
                    scrollTop: list.get('scrollTop') + (target.getY() - list.getY()) - 20
                }
            }).run();

            target.one('.description').addClass('expanded');
            target.one('.header').removeClass('corner');
            target.one('input').set('checked', true);
        },
        nextCard: function() {
            var selected = this.content.one('input:checked'),
                item, type;

            if(!selected) {
                this.addError(messages.selectError);
                return;
            }

            //Select the appropriate next card and display it
            item = selected.ancestor('.item');
            type = item.getAttribute('data-type');
            type = type.charAt(0).toUpperCase() + type.slice(1);
            new Card(Cards[type], [item.getAttribute('data-id'), type]);
            this.clearError();
        }
    };

    Cards.Suggestion = {
        template: template.compile(Y.one('#suggestionContent').getHTML()),
        title: messages.suggestionTitle,
        events: [
            {type: 'click', selector: '.collapsible-list .header .title, .collapsible-list .header .expand', handler: 'expandUnit'},
            {type: 'click', selector: '.collapsible-list .header .rescan', handler: 'expandUnit'},
            {type: 'click', selector: '.content .reset', handler: 'resetAll'},
        ],
        initialize: function(id, type) {
            this.id = id;
            this.type = type.toLowerCase();

            var successHandler = function(response) {
                this.content.append(this.template(response));
            };

            Helpers.ajax('/ci/admin/assistant/retrieveUnits', {
                id: id
            }, this.content, successHandler, this);
        },
        expandUnit: function(e) {
            //Force a refresh if they clicked on the rescan button, otherwise just toggle expand/collapse
            var target = e.currentTarget.ancestor('.item'),
                forceRefresh = e.currentTarget.hasClass('rescan'),
                description = target.one('.description');

            if(target.one('.loading')) return;
            if(!forceRefresh || (forceRefresh && !description.hasClass('expanded'))) {
                this.toggleExpanded(target, description.hasClass('expanded'));
            }

            if(!target.getData('unitInformation') || forceRefresh) {
                description.get('childNodes').remove();
                this.executeUnit(target);
            }
        },
        executeUnit: function(target) {
            var successHandler = function(response) {
                var unitData = response[target.getData('unit')];

                target.setData('unitInformation', Y.JSON.stringify(unitData));

                target.one('.description').append(Helpers.getUnitHtml(unitData, this.type, false));

                //Add tooltips to suggestions
                if(this.type === 'suggestion') {
                    Y.TipIt('.pretty .line.marked a.tooltipLink');
                }

                //Prevent items with errors from being selected
                if(unitData.errors && unitData.errors.length) {
                    target.setData('error', true);
                    target.one('.header').removeClass('selected');
                    target.one('input').set('disabled', true).set('checked', false);
                    target.one('.title label').append('<i class="fa fa-exclamation-circle">');
                }
            };

            Helpers.ajax('/ci/admin/assistant/getInstructions', {
                unit: target.getData('unit'),
                id: this.id
            }, target.one('.description'), successHandler, this);
        }
    };

    Cards.Conversion = Helpers.extend(Cards.Suggestion, {
        template: template.compile(Y.one('#conversionContent').getHTML()),
        title: messages.conversionTitle,
        events: [
            {type: 'click', selector: '.collapsible-list .header .expand', handler: 'expandUnit'},
            {type: 'click', selector: '.content .all input', handler: 'selectAllUnits'},
            {type: 'click', selector: '.content .continue', handler: 'confirmChanges'},
            {type: 'click', selector: '.collapsible-list .header .title', handler: 'selectUnit'}
        ],
        selectUnit: function(e) {
            var target = e.currentTarget.one('input'),
                item = target.ancestor('.item'),
                list = this.content.one('.collapsible-list'),
                nodeName = e.target.get('nodeName').toLowerCase();

            // Stop if the user is clicking on the label since this handler will fire again for the checkbox or if the item is already loading or has an error
            if(nodeName === 'label' || item.one('.loading') || item.getData('error')){
                return;
            }

            if(nodeName !== 'input' && nodeName !== 'label') {
                target.set('checked', !target.get('checked'));
            }
            //Change highlighting
            target.ancestor('.header').toggleClass('selected');

            var activeNodes = 0;
            list.all('input').each(function(node) {
                if(!node.get('disabled')) activeNodes++;
            });

            this.content.one('.all input').set('checked', activeNodes === list.all('input:checked').size());
        },
        selectAllUnits: function(e) {
            var isAllChecked = e.currentTarget.get('checked');
            this.content.all('.collapsible-list input').each(function(node) {
                if(!node.get('disabled')) {
                    node.set('checked', isAllChecked);
                    node.ancestor('.header')[(isAllChecked) ? 'addClass' : 'removeClass']('selected');
                }
            });
        },
        confirmChanges: function(e) {
            var selectedItems = this.content.one('.collapsible-list').all('.item input:checked'),
                instructions = {}, units = [];

            if(!selectedItems.size()) {
                this.addError(messages.conversionError);
                return;
            }

            selectedItems.each(function(item) {
                item = item.ancestor('.item');
                if(item.getData('unitInformation')) {
                    instructions[item.getData('unit')] = Y.JSON.parse(item.getData('unitInformation'));
                }
                else {
                    units.push(item.getData('unit'));
                }
            });

            this.clearError();
            if(confirm(selectedItems.size() === 1 ? messages.confirmItem : messages.confirmItems)) {
                this.disableCard();
                new Card(Cards.Confirmation, [instructions, this.id, units]);
            }
        },
        disableCard: function() {
            this.detachEvents();
            this.content.one('.collapsible-list').removeClass('enabled');
            this.content.all('input, button').set('disabled', true);
            this.card.addClass('disabled');
        }
    });

    Cards.Confirmation = {
        template: template.compile(Y.one('#confirmation').getHTML()),
        title: messages.confirmationTitle,
        events: [
            {type: 'click', selector: '.collapsible-list .header', handler: 'expandUnit'},
            {type: 'click', selector: '.content .reset', handler: 'resetAll'},
        ],
        initialize: function(instructions, id, units) {
            Helpers.ajax('/ci/admin/assistant/commitInstructions', {
                id: id,
                instructions: Y.JSON.stringify(instructions),
                units: Y.JSON.stringify(units)
            }, this.content, this.commitInstructions, this);
        },
        commitInstructions: function(response) {
            this.content.append(this.template(response));

            //Insert the unit data into each item
            this.content.all('.collapsible-list .item').each(function(item) {
                var unitInformation =
                    response.successfulUnits[item.one('.header a').get('text')] ||
                    response.failedUnits[item.one('.header a').get('text')];

                item.one('.description').append(Helpers.getUnitHtml(unitInformation, 'conversion', true));
            });
        },
        expandUnit: function(e) {
            var target = e.currentTarget.ancestor('.item');
            this.toggleExpanded(target, target.one('.description').hasClass('expanded'));
        }
    };

    //Add in the first card. All subsequent cards are added dynamically based on user input.
    new Card(Cards.Version);
});
