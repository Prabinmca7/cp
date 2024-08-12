/*global messages*/
YUI.add('final-step', function(Y) {
    'use strict';

    var Widget = {};

    /**
     * Final step. Not really a step.
     * Sends Widget to the server and displays the response.
     */
    Y.FinalStep = Y.Step({
        name: 'last',
        /**
         * @constructor
         * @param {object} previousStep stepFour instance
         */
        init: function(widget) {
            Widget = widget;

            Y.FormToken.makeAjaxWithToken('/ci/admin/tools/widgetBuilder/buildwidget', {
                method: 'POST',
                data: 'widget=' + encodeURIComponent(Y.JSON.stringify(Widget)),
                on: {
                    success: this.done
                },
                context: this
            }, this);
        },
        /**
         * Ajax callback
         * @param {string} id IO transaction id
         * @param {object} resp Response object
         */
        done: function(id, resp) {
            resp = Y.JSON.parse(resp.responseText);
            var result;

            if (resp.error) {
                this.fire('step:five:reenable');
                result = (typeof resp.error === 'string')
                    ? resp.error
                    : resp.error.join('<br>');
            }
            else {
                this.hidePrevious();

                result = this.buildResponseMessage(resp.widget),

                result.append(this.buildFileList(resp.files)).append('<p>' + messages.activatedWidget + '</p>');
            }

            Y.Step.scrollToNode(this.content.setHTML(result).removeClass('bigwait'));
        },
        buildResponseMessage: function(widgetInfo) {
            return Y.one(document.createDocumentFragment())
                .append('<p>' + messages.subDone + '&nbsp;&nbsp;' +
                    '<a href="' + widgetInfo.davLink + '" target="_blank"><span class="widgetLink">' + widgetInfo.davLabel + '</span></a>&nbsp;|&nbsp;' +
                    '<a href="' + widgetInfo.docLink + '" target="_blank"><span class="widgetLink">' + widgetInfo.docLabel + '</span></a></p>');
        },

        buildFileList: function(files) {
            var list = Y.Node.create('<ul></ul>'),
                template = this.getTemplate('list-item');

            Y.Object.each(files, function(link, name, fileName) {
                fileName = link.substr(link.lastIndexOf('/') + 1);
                list.append(template.render({
                    link: link,
                    type: messages[name] || messages.jsViewSubstitution.replace('%s', name),
                    className: fileName.substr(fileName.indexOf('.') + 1),
                    label: fileName
                }));
            }, this);

            return list;
        },

        /**
         * Hides the previous steps when the valid widget response comes back.
         */
        hidePrevious: function() {
            var prev = this.container.previous('.step');
            while (prev) {
                prev.addClass('hide');
                prev = prev.previous('.step');
            }
        }
    });
}, null, {
    requires: ['step', 'io-base', 'json', 'node', 'FormToken']
});
