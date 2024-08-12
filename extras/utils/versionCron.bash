#!/bin/bash

###############################################################################
# GLOBALS
###############################################################################
CVS_BIN="/nfs/local/linux/bin/cvs"
MAKE_BIN="/nfs/local/linux/bin/make"

MAIL_RCPT_TO="luke.davison@oracle.com, james.v.watson@oracle.com"

export CVSROOT=/nfs/src/cvsroot

###############################################################################
# Functions
###############################################################################

#
# checkout_or_update - checkout or update a branch from CVS
#
checkout_or_update() {
    local path=$1
    local branch=$2
    local module=$3

    # TODO: remove this, just here for testing
    # if [ "${module}" == "cp-versions" ]; then
    #     echo "Exporting CVSROOT as /nfs/users/nondev/ldavison/.cvsroot/..."
    #     export CVSROOT=/nfs/users/nondev/ldavison/.cvsroot/
    # else
    #     export CVSROOT=/nfs/src/cvsroot
    # fi # END TODO

    if [ ! -d "${path}/${branch}" ]; then
        # make sure the base path exists first
        if [ ! -d $path ]; then
            mkdir -p $path
        fi

        echo "Checking out ${branch} from ${module} into ${path}..."
        if [ "${branch}" == "trunk" ]; then
            $CVS_BIN co -d $path -P $module 2>&1
        else
            $CVS_BIN co -d $path -r $branch -P $module 2>&1
        fi
    else
        echo "Updating ${branch} from ${module} in ${path}..."
        $CVS_BIN -q update $path 2>&1
    fi
}

#
# set_build_version_globals - parse defines used for make from rnw-X-X-fixes sandbox and set globals
#
set_build_version_globals() {
    local path=$1

    # matches: '    BUILD_VER  = 13.2.0.0', returns 13.2
    BUILD_VERSION=`awk '/\<BUILD_VER/ {print $0}' "${path}/common/include/make/defs" | perl -ne 'print $1 if /(\d+\.\d+)\.\d+\.\d+/' --`
    if [ -z "${BUILD_VERSION}" ]; then
        die "Unable to parse BUILD_VERSION"
    fi

    # matches: '    YAML_VERSION      = 1.1.0-1208', returns 1.1.0-1208
    YAML_VERSION=`awk '/YAML_VERSION/ {print \$3}' "${path}/common/include/make/defs"`
    if [ -z "${YAML_VERSION}" ]; then
        die "Unable to parse YAML_VERSION"
    fi

    # matches: 'PHP_VERSION=5.3.2-1211', returns 5.3.2-1211
    PHP_VERSION=`awk -F '=' '/PHP_VERSION=/ {print \$2}' "${path}/common/include/make/defs"`
    if [ -z "${PHP_VERSION}" ]; then
        die "Unable to parse PHP_VERSION"
    fi

    # matches: 'CP_FRAMEWORK_VERSION = 3.0', returns 3.0
    CP_FRAMEWORK_VERSION=`awk '/CP_FRAMEWORK_VERSION\ / {print \$3}' "${path}/rnw/install/dist/unix/dist.defs"`
    if [ -z "${CP_FRAMEWORK_VERSION}" ]; then
        die "Unable to parse CP_FRAMEWORK_VERSION"
    fi

    # matches: 'CP_FRAMEWORK_NANO_VERSION = 1', returns 1
    CP_FRAMEWORK_NANO_VERSION=`awk '/CP_FRAMEWORK_NANO_VERSION\ / {print \$3}' "${path}/rnw/install/dist/unix/dist.defs"`
    if [ -z "${CP_FRAMEWORK_NANO_VERSION}" ]; then
        die "Unable to parse CP_FRAMEWORK_NANO_VERSION"
    fi

    # assemble some paths based on the PHP and YAML extension version numbers
    PHP_PATH="/nfs/local/linux/php/php-${PHP_VERSION}"
    if [ ! -d "${PHP_PATH}" ]; then
        die "PHP_PATH (${PHP_PATH}) does not exist!"
    fi

    PHP_BIN="${PHP_PATH}/bin/php-${PHP_VERSION}"
    if [ ! -f "${PHP_BIN}" ]; then
        die "PHP_BIN (${PHP_BIN}) does not exist!"
    fi

    YAML_EXT="${PHP_PATH}/modules/yaml-${YAML_VERSION}.so"
    if [ ! -f "${YAML_EXT}" ]; then
        die "YAML_EXT (${YAML_EXT}) does not exist!"
    fi
}

#
# prepare_fixes_sandbox - do the minimum that is required to perform a partial deploy via PHP
#
prepare_fixes_sandbox() {
    local base_path=$1
    local html_path="${base_path}/rnw/scripts/cp/doc_root"

    # create the mod_info.phph file - included in scripts/cp/core/util/tarball/VersionBootstrap.php
    cd ${base_path}/rnw/scripts/cp
    $MAKE_BIN mod_info.phph

    # generate server enums - provides rnwintf_generated.phph file
    if [ -d "${base_path}/tmp" ]; then
        rm -rf "${base_path}/tmp"
    fi
    mkdir "${base_path}/tmp" # re-create the directory
    ${base_path}/bin/create_server_enums ${base_path} ${base_path}/tmp
    cp ${base_path}/tmp/rnwintf_generated.phph ${base_path}/rnw/scripts/include/.

    # make sure generated/{production,staging} dirs exist and are world writable
    mkdir -p ${base_path}/rnw/scripts/cp/generated/{production,staging}
    chmod 777 ${base_path}/rnw/scripts/cp/generated/ -R

    # symlink the rnt files
    ln -s ${base_path}/rnw/scripts/euf/webfiles/rightnow ${base_path}/rnw/scripts/cp/webfiles/rightnow

    # create the doc_root and symlink everything under it
    if [ -d ${html_path} ]; then
        rm -rf ${html_path}
    fi
    mkdir -p "${html_path}/rnt/rnw"
    ln -s "${base_path}/rnw/scripts/cp/webfiles/" "${html_path}/euf"
    ln -s "${base_path}/rnw/webfiles/javascript/" "${html_path}/rnt/rnw/javascript"
    ln -s "/nfs/local/generic/yui/2.7/" "${html_path}/rnt/rnw/yui_2.7"
    ln -s "/nfs/local/generic/yui/3.7/" "${html_path}/rnt/rnw/yui_3.7"
    ln -s "/nfs/local/generic/yui/3.8/" "${html_path}/rnt/rnw/yui_3.8"
    ln -s "/nfs/local/generic/yui/3.13/" "${html_path}/rnt/rnw/yui_3.13"
    ln -s "/nfs/local/generic/yui/3.17/" "${html_path}/rnt/rnw/yui_3.17"
    ln -s "/nfs/local/generic/yui/3.18/" "${html_path}/rnt/rnw/yui_3.18"

    # add the default assets
    cd "${html_path}/euf/assets"
    for i in *; do
        # make sure what we have is current
        if [ -d "${html_path}/euf/core/default/${i}" ]; then
            rm -rf "${html_path}/euf/core/default/${i}"
        fi
        cp -r $i "${html_path}/euf/core/default/."
    done
}

#
# stage_version - stage the files from rnw-X-X-fixes into the cp-versions sandbox
#
stage_version() {
    local fixes_path=$1
    local versions_path=$2
    local html_path="${fixes_path}/rnw/scripts/cp/doc_root"

    # if there is a version staging dir, remove it
    if [ -d "${versions_path}/staging" ]; then
        rm -rf "${versions_path}/staging"
    fi
    mkdir -p "${versions_path}/staging"

    # copy widgets and framework files, excluding unit tests and CVS files/dirs
    cd "${fixes_path}/rnw/scripts/cp/core" # to make sure the paths we copy are relative from here
    for i in widgets framework; do
        find $i -type d -name CVS -prune -o \
            -type d -name tests -prune -o \
            -type d -name UnitTest -prune -o \
            -type f -name ".*" -prune -o \
            -print | cpio -pdm "${versions_path}/staging"
    done

    # clean up individual files we don't want that are for internal use
    rm -f "${versions_path}/staging/framework/Controllers/Admin/InternalTools.php"
    rm -f "${versions_path}/staging/framework/Views/Admin/editConfig.php"
    rm -f "${versions_path}/staging/framework/Internal/Libraries/Widget/DependencyInfo.php"

    # copy some v2 assets into v3
    mkdir -p "${versions_path}/staging/assets/default"
    cd "${fixes_path}/rnw/scripts/euf/webfiles/assets"
    for i in {images,css}; do
        find $i -type d -name CVS -prune -o \
            -type d -name static -prune -o \
            -type d -name tests -prune -o \
            -type f -name PLACEHOLDER.README -prune -o \
            -type f -name ".*" -prune -o \
            -print | cpio -pdm "${versions_path}/staging/assets/default"
    done

    # replace the live_tc.css file with the correct one
    cp -f "${fixes_path}/rnw/webfiles/css/live_tc.css" "${versions_path}/staging/assets/default/css/."

    # copy the assets
    cd "${html_path}/euf/core"
    find . -type d -name CVS -prune -o \
        -type d -name static -prune -o \
        -type d -name tests -prune -o \
        -type f -name PLACEHOLDER.README -prune -o \
        -type f -name ".*" -prune -o \
        -print | cpio -pdm "${versions_path}/staging/assets"
    chmod 755 "${versions_path}/staging/assets"

    # add in the widget assets - overwrite any conflicts with v2 stuff added from above
    cd "${html_path}/euf/assets/themes"
    find . -type d -name CVS -prune -o \
        -type d -name static -prune -o \
        -type d -name tests -prune -o \
        -type f -name PLACEHOLDER.README -prune -o \
        -type f -name ".*" -prune -o \
        -print | cpio -pdm "${versions_path}/staging/assets/default/themes"

    # remove the x.x (sp.build) directory from assets/js/
    cd "${versions_path}/staging/assets/js"
    # there should be only one, but we don't know what the directory is called and we want to remove it
        # make sure it's a dir, there shouldn't be anything else in the assets/js dir, but you never know
    for i in *.*; do
        if [ -d "${i}/min" ]; then
            mv "${i}/min" . && rm -rf "${i}"
        fi
    done

    # add the customer/development/views into the framwork/views for Reference Implementation
    cd "${fixes_path}/rnw/scripts/cp/customer/development"
    find views -type d -name CVS -prune -o \
        -type f -name ".*" -prune -o \
        -print | cpio -pdm "${versions_path}/staging/framework"

    # Copy the `widgetVersions` file out to the cp_versions branch so we can use it in reference mode
    cp "${fixes_path}/rnw/scripts/cp/customer/development/widgetVersions" "${versions_path}/staging/framework"
}

#
# process_cvs_removals - determine if files need to be removed from cp-versions and make it so
#
process_cvs_removals() {
    local path=$1
    local build_version=$2

    if [ ! -d "${path}" ]; then
        die "Path (${path}) does not exist!"
    fi
    cd $path

    # diff between the staging and build version directory
    for i in `diff -qr $build_version staging | grep "^Only in ${build_version}" | grep -v CVS | perl -ne 'print "$1/$2\n" if /^Only in\ (.+?):\ (.+?)$/' --`; do
        if [ -d $i ]; then
            find $i -name CVS -type d -prune -o -print0 | xargs -0 $CVS_BIN remove -f
        else
            $CVS_BIN remove -f $i
        fi
    done
    $CVS_BIN commit -m "121030-000033 - Automated removal of files from CRON"
}

#
# move_build_version_to_staging - remove the existing cp-versions/X.X, while preserving CVS files in staging
#
move_build_version_to_staging() {
    local path=$1
    local build_version=$2

    cd "${path}/${build_version}"
    # keep the CVS info intact
    find . -name CVS -type d -print | xargs -I {} cp -r {} ../staging/{}
    cd $path
    rm -rf "${path}/${build_version}"
}

#
# commit_version_staging - promote the staging directory to X.X and commit to CVS
#
commit_version_staging() {
    local path=$1
    local build_version=$2

    # always move staging to the build version directory and schedule files for addition...
    mv "${path}/staging" "${path}/${build_version}"

    cd $path
    # if it's an update, this will report errors, but they are non-fatal
    find $build_version -name CVS -type d -prune -o -print0 | xargs -0 $CVS_BIN add
    $CVS_BIN commit -m "121030-000033 - Automated additions and updates from CRON"
}

#
# get_version - get the framework or a widget version
#
get_version() {
    local full_path="$2/$1"
    local version=`head -1 $full_path`
    local regex=".*\"(.*)\""

    if [[ $version =~ $regex ]]; then
        echo ${BASH_REMATCH[1]}
    fi
}

#
# create_version_mapping - create the versionMapping file
#
create_version_mapping() {
    local version_mapping_file="${1}/staging/versionMapping"
    local path="${2}/rnw/scripts/cp/core/widgets"
    local framework_version=$(get_version manifest "${2}/rnw/scripts/cp/core/framework")

    if [ -z "${framework_version}" ]; then
        die "Unable to parse framework version!"
    fi

    cat > $version_mapping_file <<EOF
framework: $framework_version
widgets:
EOF

    widget_paths=$(cd $path && find standard -name info.yml | sort)
    for widget_path in $widget_paths; do
        version=$(get_version $widget_path $path)
        widget_regex="(.*)/info\.yml"
        if [[ $widget_path =~ $widget_regex ]]; then
            widget_path=${BASH_REMATCH[1]}
        fi

        if [ -z "${version}" ]; then
            die "Unable to parse widget version (${widget_path})!"
        fi

        echo "  ${widget_path}: ${version}" >> $version_mapping_file
    done
}

#
# reset_sandbox - restore certain files from CVS, to remove version stamp comments and minified files
#
reset_sandbox() {
    local core_path="${1}/rnw/scripts/cp/core/"
    local webfiles_path="${1}/rnw/scripts/cp/webfiles"

    echo -n "Resetting sandbox..."
    # remove any modified or new files in the following places
    $CVS_BIN -qn update $core_path/{framework/Models,widgets/standard} 2>&1 | awk '{print $2}' | xargs rm -f
    $CVS_BIN -qn update $webfiles_path/{assets,core} 2>&1 | awk '{print $2}' | xargs rm -f
    rm -f "{$1}/rnw/scripts/cp/customer/development/widgetVersions"
    echo "done."
}

#
# die - should be called when an error is encountered
#
die() {
    echo $1 | mail -s "Make Version CRON Error" $MAIL_RCPT_TO
    exit
}

###############################################################################
# MAIN
###############################################################################
{
    if [[ -z "$1" || -z "$2" || -z "$3" ]]; then
        echo "Usage: /bin/bash $0 <sandbox directory> <rnw branch tag> <cp-version branch tag>"
        echo
        echo " - <sandox directory>      - The path to where the files should be checked out (eg, ~/tmp/code)"
        echo " - <rnw branch tag>        - The version you want to have cp-versions files generated for (eg, rnw-13-2-fixes)"
        echo " - <cp-version branch tag> - The version you want to check the generated cp-verson files into (eg, trunk or rnw-13-5-fixes)"
        echo
    fi

    # setup everything using the CLI arguments
    BASE_SANDBOX=$1
    FIXES_BRANCH=$2
    FIXES_SANDBOX="${BASE_SANDBOX}/fixes-${FIXES_BRANCH}"

    VERSIONS_BRANCH=$3
    VERSIONS_SANDBOX="${BASE_SANDBOX}/cp-versions-${VERSIONS_BRANCH}"

    if [ -d "${FIXES_SANDBOX}" ]; then
        # part of the make_version.php process adds version info as a comment in models and widgets
        # reset that, in case this sandbox will be re-used
        reset_sandbox $FIXES_SANDBOX
    fi

    # prepare the sandbox for PHP scrript execution
    checkout_or_update $VERSIONS_SANDBOX $VERSIONS_BRANCH "cp-versions"
    checkout_or_update $FIXES_SANDBOX $FIXES_BRANCH "papiservices"

    set_build_version_globals $FIXES_SANDBOX
    echo "Generating 'cp-versions' for CP Framework ${CP_FRAMEWORK_VERSION}.${CP_FRAMEWORK_NANO_VERSION} from ${BUILD_VERSION}..."
    echo

    # perform the minimum tasks needed to invoke the make_version.php script without running make or creating a site/db
    prepare_fixes_sandbox $FIXES_SANDBOX

    # run PHP script to generate optimized_*.{js,php} files
    ${PHP_BIN} -d extension=${YAML_EXT} -n -f \
        "${FIXES_SANDBOX}/rnw/scripts/cp/core/util/versionsCron/makeVersion.php" \
        ${CP_FRAMEWORK_VERSION} ${CP_FRAMEWORK_NANO_VERSION}

    # populate the trunk staging area of cp-versions with current build's generated CP files
    stage_version $FIXES_SANDBOX $VERSIONS_SANDBOX

    # create the versionMapping file
    create_version_mapping $VERSIONS_SANDBOX $FIXES_SANDBOX

    # have we generated cp-versions for this version before?
    if [ -d "${VERSIONS_SANDBOX}/${BUILD_VERSION}" ]; then
        process_cvs_removals $VERSIONS_SANDBOX $BUILD_VERSION
        move_build_version_to_staging $VERSIONS_SANDBOX $BUILD_VERSION
    fi
    commit_version_staging $VERSIONS_SANDBOX $BUILD_VERSION
}
# END
