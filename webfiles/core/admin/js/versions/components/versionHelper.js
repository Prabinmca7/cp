/**
 * Provides common utils and helpers needed in
 * a number of components.
 */
YUI.add('VersionHelper', function(Y) {
    'use strict';

    Y.VersionHelper = {};

    Y.VersionHelper.compareVersionNumbers = function(versionA, versionB) {
        versionA = (versionA.split('.') + new Array(3)).split(',', 3);
        versionB = (versionB.split('.') + new Array(3)).split(',', 3);

        for (var i = 0, v1, v2; i < 3; i++) {
            v1 = parseInt(versionA[i], 10);
            v2 = parseInt(versionB[i], 10);
            if (v1 > v2) {
                return 1;
            }
            if (v1 < v2) {
                return -1;
            }
        }
        return 0;
    };

    /**
     * Given a list of frameworkVersions check if the desiredVersion exists in the list. If it does, return true
     * otherwise return false. If the list does not exist then the widget has no dependencies, so it is always compatible.
     * @param {String|Array|null} frameworkVersions
     * @param {String} desiredVersion
     * @return {bool} Whether or not the desiredVersion is compatible with the frameworkVersions
     */
    Y.VersionHelper.hasCompatibleFramework = function (frameworkVersions, desiredVersion) {
        if(frameworkVersions) {
            frameworkVersions = (typeof frameworkVersions === 'string') ? frameworkVersions : frameworkVersions.join(',');
            return (Y.Array.indexOf(frameworkVersions.replace(/\s+/g, '').split(','), Y.Lang.trim(desiredVersion)) !== -1);
        }
        return true;
    };

}, null, {
    requires: []
});
