#!/bin/bash

###############################################################################
# MAIN
###############################################################################
{
    if [[ -z "$1" ]]; then
        echo "Usage: /bin/bash $0 <script/cp/ directory>"
        exit 1
    fi

    cd "$1"

    branches=(rnw-12-11-phpupgrade \
        rnw-13-2-phpupgrade rnw-13-5-phpupgrade rnw-13-8-phpupgrade rnw-13-11-phpupgrade \
        rnw-14-2-phpupgrade rnw-14-5-phpupgrade rnw-14-8-phpupgrade rnw-14-11-phpupgrade \
        rnw-15-2-phpupgrade rnw-15-5-phpupgrade rnw-15-8-phpupgrade rnw-15-11-phpupgrade \
        rnw-16-2-phpupgrade rnw-16-5-phpupgrade rnw-16-8-released rnw-16-11-released \
        rnw-17-2-released rnw-17-5-released rnw-17-8-released rnw-17-11-released \
        rnw-18-2-released rnw-18-5-released rnw-18-8-released rnw-18-11-released \
        rnw-19-2-fixes rnw-19-5-fixes rnw-19-8-fixes rnw-19-11-fixes \
        rnw-20-2-fixes rnw-20-5-fixes rnw-20-8-fixes rnw-20-11-fixes \
        rnw-21-2-fixes rnw-21-5-fixes rnw-21-8-fixes rnw-21-11-fixes \
        rnw-22-2-fixes rnw-22-5-fixes rnw-22-8-fixes rnw-22-11-fixes \
        rnw-23-2-fixes rnw-23-5-fixes rnw-23-8-fixes rnw-23-11-fixes \
        rnw-24-2-fixes rnw-24-5-fixes rnw-24-8-fixes)

    for branch in "${branches[@]}"
    do
        rm -rf "versions/$branch"
        git clone -b "$branch" git@orahub.oci.oraclecorp.com:appdev-cloud-rnpd/cp.git "versions/$branch"
    done
}
# END
