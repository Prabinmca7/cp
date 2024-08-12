require 'rake'
require 'find'
require 'yaml'
require 'open3'

# task :default => [:build]

#
# GLOBALS
#
VERBOSE = true
CP_FRAMEWORK_VERSION = "3.11"
CP_FRAMEWORK_NANO_VERSION = "2"
CP_FRAMEWORK_FULL_VERSION = "#{CP_FRAMEWORK_VERSION}.#{CP_FRAMEWORK_NANO_VERSION}"
BUILD_NUM = ENV['BUILD_NUM'] || 10002
CX_BUILD_NUM = ENV['CX_BUILD_NUM'] || BUILD_NUM

# Artifactory variables
ARTIFACTORY_WEBSITE = ENV['ARTIFACTORY_WEBSITE'] || "https://artifacthub-iad.oci.oraclecorp.com"
ARTIFACTORY_REPO = ENV['ARTIFACTORY_REPO'] || "osvc-release-local"
ARTIFACTORY_TARBALLS_PATH = ENV['ARTIFACTORY_TARBALLS_PATH'] || "osvc-portal/compiled-tarballs"

BUILD_OFFSET_PATH = ENV['DEVELOPMENT'] || '../../'

# if trunk is 14.2 it will still be referred to as rnw-14-2-fixes
# even if that branch doesn't technically exist yet
OVERLAY_VERSION = "24-8"

NFSDIR = "/nfs/local"
ARCH = "linux"
REPO_PATH = File.dirname(__FILE__)

# grab build definitions from server
if ENV['DEVELOPMENT']
  eval(`make --no-print-directory -C #{File.expand_path('server/src/rnw php_vars_get', BUILD_OFFSET_PATH)}`)
else
  eval(`make --no-print-directory -C #{File.expand_path('../server/src/rnw php_vars_get', REPO_PATH)}`)
end
#CURL_VERSION initialized by eval call above
#YAML_VERSION = initialized by eval call above
#PHP_VERSION = initialized by eval call above
#PHP_DIR = initialized by eval call above
PHP = "#{PHP_DIR}/bin/php-#{PHP_VERSION}"
CURL_LIB = "#{PHP_DIR}/modules/curl-#{CURL_VERSION}.so"
YAML_LIB = "#{PHP_DIR}/modules/yaml-#{YAML_VERSION}.so"

TEMP_ARTIFACTORY_DIR = File.expand_path('artifactory', REPO_PATH)
BUILD_PATH = File.expand_path('build', REPO_PATH)

# an example from dist/unix = rnw-14.2.0.0_linux_mysql_build_36h
DIST_PATH = File.expand_path("cp-#{CP_FRAMEWORK_FULL_VERSION}_linux_mysql_build_#{BUILD_NUM}h", BUILD_PATH)
YUI_ROOT = "/nfs/local/generic/yui"

VERSIONS_PATH = File.expand_path("./versions", REPO_PATH)
VERSIONS_FILE = File.expand_path('.versions', VERSIONS_PATH)
VERSIONS = File.file?(VERSIONS_FILE) ? YAML.load_file(VERSIONS_FILE) : []
VERSIONS_TO_REBUILD = []
VERSIONS_TO_SKIP_COMPILE = []

#
# Global tasks and functions
#
desc "A rollup of all of the 'build' tasks"
task :build => ['build:printLibVersions', 'build:createTree', 'build:overlay', 'build:cp:generateModInfo', 'build:versions:deploy', 'build:versions:scriptCompile', 'build:cp:createThemesPackageSource', 'build:cp:packageThemeCSS']

desc "A rollup of all of the 'dist' tasks"
task :dist => ['dist:createTree', 'dist:install:scripts', 'dist:install:php', 'dist:install:yui', 'dist:install:cp', 'dist:deploy', 'dist:buildVersionTarballs', 'dist:cleanupInternalFiles']

desc "Run everything"
task :all => [:build, :dist]

#
# getVersionTarballArtifactName - Contructs the expected tarball name for a given CX version.
# This is done by combining the name of the version with the latest git hash for its branch
#
def getVersionTarballArtifactName(version)
  hash = `git rev-parse refs/remotes/origin/#{version}`.gsub("\n", "")
  artifactName = "#{version}.#{hash}.tar.gz"
end

#
# calculateTime - Calculates the time a task takes to execute in minutes
#
def calculateTime(task, startTime, endTime)
  duration = ((endTime - startTime) / 60).round(3)
  output = "Task #{task} took #{duration} minutes to execute"
end

#
# Tasks that apply to the "build" area
#
namespace :build do

  #
  # printLibVersions - Print the lib versions used in this build
  # these values should match server/src/common/include/make/defs
  #
  desc "Print the lib versions used in this build"
  task :printLibVersions do
    if VERBOSE
      puts "PHP and lib versions used."
      puts "CURL_VERSION = #{CURL_VERSION}"
      puts "YAML_VERSION = #{YAML_VERSION}"
      puts "PHP_VERSION = #{PHP_VERSION}"
      puts "PHP_DIR = #{PHP_DIR}"
      puts "PHP = #{PHP}"
      puts "CURL_LIB = #{CURL_LIB}"
      puts "YAML_LIB = #{YAML_LIB}"
    end
  end

  #
  # createTree - Create staging area to perform the build
  #
  desc "Create staging area to perform the build"
  task :createTree => ['versions:populate'] do
    startTime = Time.now
    rm_rf BUILD_PATH if File.directory?(BUILD_PATH)
    mkdir_p File.expand_path("rnw/scripts/cp", BUILD_PATH)

    # copy the files over
    cpPath = File.expand_path('rnw/scripts/cp', BUILD_PATH)
    excludedFiles = [BUILD_PATH, File.expand_path('Rakefile', REPO_PATH), TEMP_ARTIFACTORY_DIR]
    Dir["#{REPO_PATH}/*"].each do |item|
      cp_r item, cpPath unless excludedFiles.include?(item)
    end
    if VERBOSE
      endTime = Time.now
      puts calculateTime("build:createTree", startTime, endTime)
    end
  end

  #
  # overlay - Overlays the build files
  #
  desc "Overlays the build files"
  task :overlay => [:createTree] do
    startTime = Time.now
    cd BUILD_PATH do
      # get 'current' overlay files
      cp_r "#{BUILD_OFFSET_PATH}server/src/bin", "."
      cp_r "#{BUILD_OFFSET_PATH}server/src/common", "."
      cp_r "#{BUILD_OFFSET_PATH}server/src/rnw", "."
      cp_r "#{BUILD_OFFSET_PATH}common/server_generated", "."
    end
    if VERBOSE
      endTime = Time.now
      puts calculateTime("build:overlay", startTime, endTime)
    end
  end

  #
  # Build tasks that are CP specific
  #
  namespace :cp do

    #
    # generateModInfo - Generate scripts/cp/mod_info.phph
    #
    desc "Generate scripts/cp/mod_info.phph"
    task :generateModInfo => [:createTree] do
      startTime = Time.now
      `nice -20 make -j 8 -C #{File.expand_path('rnw/scripts/cp', BUILD_PATH)} BUILD_NUM=#{BUILD_NUM} CX_BUILD_NUM=#{CX_BUILD_NUM} RNT_BASE='../../..' mod_info.phph`
      if VERBOSE
        endTime = Time.now
        puts calculateTime("build:cp:generateModInfo", startTime, endTime)
      end
    end

    #
    # packageThemeCSS - Moves the SCSS into a subdir of the CSS theme.
    # Relies on the :createThemesPackageSource task that compiles the SCSS into CSS.
    #
    desc "Compile SCSS into CSS"
    task :packageThemeCSS => [:createThemesPackageSource] do
      startTime = Time.now
      themes = ['standard', 'mobile']

      themes.each do |relativeTheme|
        theme = File.expand_path("rnw/scripts/cp/webfiles/assets/themes/#{relativeTheme}", BUILD_PATH)
        scssTheme = copyTheme theme

        # Remove the SCSS source files and the SCSSLinter config file from the CSS theme.
        # Remove the SCSS license files
        # Remove the compiled CSS files from the SCSS theme.
        rm Dir["#{theme}/**/*.scss"] + Dir["#{theme}/\.*.yml"] + Dir["#{theme}/**/license.txt"] + Dir["#{scssTheme}/**/*.css"]

        removeEmptyDirs Dir["#{theme}/**/*"]
        removeEmptyDirs Dir["#{theme}/*"]

        mv scssTheme, "#{theme}/scss"
      end
      if VERBOSE
        endTime = Time.now
        puts calculateTime("build:cp:packageThemeCSS", startTime, endTime)
      end
    end

    def copyTheme(path)
      cp_r path, "#{path}-scss"
      "#{path}-scss"
    end

    def removeEmptyDirs(files)
      files.select { |file| File.directory?(file) && Dir["#{file}/*"].empty? }
           .each { |empty_dir| rm_rf empty_dir }
    end

    #
    # Creates a themesPackageSource dir for every widget with presentation css
    # inside a theme.
    #
    # Every widget that has a <WidgetName>.css file in any theme ends up with
    # a <themeName> sub-directory inside themesPackageSource with that
    # presentation css file and any relative assets linked to inside the css.
    #
    # Pre:
    #   WidgetName/
    #     view.php
    #   ...
    #   themes/
    #     standard/
    #       widgetCss/
    #         WidgetName.css (references ../images/image.png)
    #       images/
    #         image.png
    #
    # Post:
    #   WidgetName/
    #     view.php
    #     themesPackageSource/
    #       standard/
    #         widgetCss/
    #           WidgetName.css
    #         images/
    #           image.png
    #
    class WidgetThemePackager < Struct.new(:themePath, :themeName, :allWidgets)
      def createThemesPackageSource
        Dir["#{themePath}/widgetCss/*.css"].each do |cssFile|
          widgetPath = findWidgetForCssFile(cssFile)

          if widgetPath.nil?
            puts "Didn't find any widgets for #{cssFile}!"
            next
          end
          copyAssetsIntoWidget([cssFile] + extractLinkedAssets(cssFile), widgetPath)
        end
      end

      #
      # Update fontawesome path in font-awesome/_variables.scss from /euf/core/thirdParty to euf/core/X.Y/thirdParty
      #
      def updateFontAwesomePath(frameworkVersion)
        path = "#{themePath}/font-awesome/_variables.scss"
        `/bin/sed -i 's|/euf/core/thirdParty/fonts|/euf/core/#{frameworkVersion}/thirdParty/fonts|' #{path}` if File.exists?(path)
      end

      def compileScss(frameworkVersion)
        Dir["#{themePath}/**/[^_]*.scss"].each do |file|
          # do not compile dev-only all.scss file
          if File.basename(file) != "all.scss"
            `/nfs/project/cp/bin/sass #{file} #{file.sub(/\.scss/, '.css')}`
            abort "Failed to create css file for #{file}, probably because it contains an error. See the line above." unless $?.success? or (File.basename(file) == 'ProductCatalogSearchFilter.scss' and ['3.0', '3.1', '3.2', '3.3'].include?(frameworkVersion))
          end
        end
      end

      private

      def copyAssetsIntoWidget(assets, widgetPath)
        assets.each do |asset|
          dest = File.join(widgetPath, 'themesPackageSource', themeName, themeRelativeAssetPath(asset))

          puts "Copying #{asset} to #{dest}"
          FileUtils.mkdir_p File.dirname(dest)
          FileUtils.cp asset, dest
        end
      end

      def themeRelativeAssetPath(assetPath)
        assetPath.split("#{themeName}/")[1]
      end

      def findWidgetForCssFile(cssFile)
        widgetName = File.basename(cssFile, '.css')
        widgetIndex = allWidgets.index {|path| File.basename(path) == widgetName }

        allWidgets[widgetIndex] if !widgetIndex.nil?
      end

      def extractLinkedAssets(cssFile)
        themeAssetsToCopy = []
        assets = File.read(cssFile, :encoding=>"UTF-8").scan(/url\(([^\)]*)\)/)

        if !assets.empty?
          assets.each do |assetPath|
            asset = assetPath.first
            if asset !~ /^["']?data:/
              asset.gsub!(/("|')/, '')

              raise "#{cssFile} is attempting to relatively reference a resource outside of the theme!" if asset.start_with? '../../'

              # Leave absolute-path assets alone.
              themeAssetsToCopy << File.expand_path(asset, File.dirname(cssFile)) if !asset.start_with? '/'
            end
          end
        end

        themeAssetsToCopy.uniq
      end
    end

    desc "Create themesPackageSource structure for widgets"
    task :createThemesPackageSource => [:createTree] do
      startTime = Time.now
      allWidgets = Dir["#{BUILD_PATH}/rnw/scripts/cp/core/widgets/standard/*/*"]
      themes = Dir["#{BUILD_PATH}/rnw/scripts/cp/webfiles/assets/themes/*"]

      themes.each do |theme|
        packager = WidgetThemePackager.new(theme, File.basename(theme), allWidgets)
        packager.updateFontAwesomePath(CP_FRAMEWORK_VERSION)
        packager.compileScss(CP_FRAMEWORK_VERSION)
        packager.createThemesPackageSource
      end
      if VERBOSE
        endTime = Time.now
        puts calculateTime("build:cp:createThemesPackageSource", startTime, endTime)
      end
    end

  end

  #
  # Build tasks that are specific to CP versions
  #
  namespace :versions do

    #
    # deploy - Deploys all previous CP versions found in scripts/cp/versions
    #
    desc "Deploys all previous CP versions found in scripts/cp/versions"
    task :deploy => ['versions:populate', :overlay] do
      startTime = Time.now
      def getVersion(file)
        # read the first line of the file and parse the value inside double quotes
        return $1 if File.open(file, &:readline).match(/"(.+?)"/)
        ""
      end

      VERSIONS.each do |version, versionInfo|
        versionRoot = File.expand_path("rnw/scripts/cp/versions/#{version}", BUILD_PATH)
        # create mod_info.phph file
        puts "executing: make -C #{versionRoot} BUILD_NUM=#{BUILD_NUM} RNT_BASE='../../../../..' mod_info.phph"
        makeOutput = `make -C #{versionRoot} BUILD_NUM=#{BUILD_NUM} RNT_BASE='../../../../..' mod_info.phph`
        puts "output: #{makeOutput}"

        # if a current tarball already exists for the CX version then that version does not need to be re-deployed
        if VERSIONS_TO_REBUILD.include?(version)
          # create the generated directories
          mkdir_p File.expand_path("generated/production", versionRoot), {:mode => 0777}
          mkdir_p File.expand_path("generated/staging", versionRoot), {:mode => 0777}

          # symlink the rnt files
          ln_s File.expand_path("rnw/scripts/euf/webfiles/rightnow", BUILD_PATH),
            File.expand_path("webfiles/rightnow", versionRoot)

          # create doc_root and symlink everything under it
          docRoot = File.expand_path("doc_root", versionRoot)
          rm_rf docRoot if File.directory?(docRoot)
          mkdir_p File.expand_path("rnt/rnw", docRoot)
          ln_s File.expand_path("rnw/webfiles/javascript", BUILD_PATH), File.expand_path("rnt/rnw/javascript", docRoot)
          ln_s File.expand_path("webfiles", versionRoot), File.expand_path("euf", docRoot)

          # add in YUI
          [2.7, 3.7, 3.8, 3.13, 3.17, 3.18].each do |yuiVersion|
            ln_s "#{YUI_ROOT}/#{yuiVersion}/", File.expand_path("rnt/rnw/yui_#{yuiVersion}", docRoot)
          end

          # invoke PHP to deploy scripts and assets
          makeVersionScript = File.expand_path('core/util/versionsCron/makeVersion.php', versionRoot)
          cpVersionParts = versionInfo['cpVersion'].split('.')
          versionArgs = cpVersionParts[0] + '.' + cpVersionParts[1] + ' ' + cpVersionParts[2] # '{major}.{minor} {nano}'
          puts "executing: #{PHP} -d extension=#{YAML_LIB} -n -f #{makeVersionScript} #{version} #{versionArgs}"
          makeVersionOutput = `#{PHP} -d extension=#{YAML_LIB} -n -f #{makeVersionScript} #{version} #{versionArgs}`
          puts "output: #{makeVersionOutput}"

          if not ['3.0.1', '3.0.2', '3.1.1', '3.1.2', '3.1.3', '3.2.1', '3.2.2', '3.2.3', '3.2.4', '3.2.5', '3.2.6'].include?(versionInfo['cpVersion'])
            # runThemesPackageSource versionRoot
            allWidgets = Dir["#{versionRoot}/core/widgets/standard/*/*"]
            themes = Dir["#{versionRoot}/webfiles/assets/themes/*"]

            themes.each do |theme|
              packager = WidgetThemePackager.new(theme, File.basename(theme), allWidgets)
              packager.updateFontAwesomePath(cpVersionParts[0] + '.' + cpVersionParts[1])
              packager.compileScss(cpVersionParts[0] + '.' + cpVersionParts[1])
              packager.createThemesPackageSource
            end

            themes = ['standard', 'mobile']

            themes.each do |relativeTheme|
              theme = File.expand_path("webfiles/assets/themes/#{relativeTheme}", versionRoot)
              scssTheme = copyTheme theme

              # Remove the SCSS source files and the SCSSLinter config file from the CSS theme.
              # Remove the SCSS license files
              # Remove the compiled CSS files from the SCSS theme.
              rm Dir["#{theme}/**/*.scss"] + Dir["#{theme}/\.*.yml"] + Dir["#{theme}/**/license.txt"] + Dir["#{scssTheme}/**/*.css"]

              removeEmptyDirs Dir["#{theme}/**/*"]
              removeEmptyDirs Dir["#{theme}/*"]

              mv scssTheme, "#{theme}/scss"
            end
          end

          # add the default assets
          Dir[File.expand_path('euf/assets', docRoot)].each do |item|
            destPath = File.expand_path("../core/default/", item)
            rm_rf destPath if File.directory?(destPath)
            cp_r item, destPath
          end

          # make the versionMapping and widgetVersions file
          frameworkVersion = getVersion File.expand_path('core/framework/manifest', versionRoot)
          versionMapping = {'framework' => frameworkVersion, 'widgets' => {}}
          widgetVersions = {}
          standardWidgetPath = File.expand_path('core/widgets/standard/', versionRoot) + "/"
          Dir["#{standardWidgetPath}*/*"].each do |widget|
            widgetName = widget.partition(standardWidgetPath).last
            widgetVersion = getVersion File.expand_path('info.yml', widget)
            versionMapping['widgets']['standard/' + widgetName] = widgetVersion
            # remove the nano version for widgetVersions
            widgetVersions['standard/' + widgetName] = widgetVersion[0...widgetVersion.rindex('.')]
            # widgetVersions['standard/' + widgetName] = widgetVersion
          end

          File.open(File.expand_path('versionMapping', versionRoot), 'w') do |fh|
            fh.puts versionMapping.to_yaml
          end

          File.open(File.expand_path('widgetVersions', versionRoot), 'w') do |fh|
            fh.puts widgetVersions.to_yaml
          end

          # copy customer views into the framework so that we can pull it into src later
          coreViewsPath = File.expand_path('core/framework/views/', versionRoot)
          mkdir File.expand_path('core/framework/views', versionRoot)
          cp_r File.expand_path('customer/development/views/pages', versionRoot), coreViewsPath
          cp_r File.expand_path('customer/development/views/templates', versionRoot), coreViewsPath
          cp_r File.expand_path('customer/development/views/admin', versionRoot), coreViewsPath
          cp_r File.expand_path('core/framework/Views/Partials', versionRoot), coreViewsPath if File.exists?(File.expand_path('core/framework/Views/Partials', versionRoot))
        else
          puts "Current tarball for CX version #{version} already exists."
          # grab MOD_BUILD_SP value from mod_info.php file under BUILD_PATH
          spToken = File.open(File.expand_path('rnw/scripts/cp/mod_info.phph', BUILD_PATH)) {|f| f.grep(/MOD_BUILD_SP/).to_s.gsub(/\D/, '')}
          puts "Renaming JS path for #{version} to /webfiles/core/js/#{spToken}.#{BUILD_NUM}"
          `mv #{versionRoot}/webfiles/core/js/* #{versionRoot}/webfiles/core/js/#{spToken}.#{BUILD_NUM}`
        end # if VERSIONS_TO_REBUILD.include?(version)
      end # VERSIONS.each 'versions'
      if VERBOSE
        endTime = Time.now
        puts calculateTime("build:versions:deploy", startTime, endTime)
      end
    end # task

    #
    # scriptCompile - Script compile CP
    #
    desc "Script compile CP"
    task :scriptCompile => [:createTree] do
      startTime = Time.now
      # unzip compiled files for each CX version that does not need to be re-compiled
      if VERSIONS_TO_SKIP_COMPILE.length > 0
        mkdir_p File.expand_path("rnw/scripts/cp/compiled/versions/", BUILD_PATH)
        cd File.expand_path("currentCompiledVersions", TEMP_ARTIFACTORY_DIR)
        target = File.expand_path("rnw/scripts/cp/compiled/versions/", BUILD_PATH)
        VERSIONS_TO_SKIP_COMPILE.each do |version|
          puts "Unzipping compiled files for CX version #{version}"
          `unzip #{version}.compiled.zip -d #{target}`
        end
      end 

      `nice -20 make -j 8 -C #{File.expand_path('rnw/scripts/cp', BUILD_PATH)} HOSTED=y PRODUCT=y BUILD_NUM=#{BUILD_NUM} RNT_BASE='../../..'`

      # create zip of compiled files for each CX version that did need to be compiled
      if VERSIONS_TO_REBUILD.length > 0
        mkdir File.expand_path('newCompiledVersions', TEMP_ARTIFACTORY_DIR)
        newCompiledVersionsPath = File.expand_path("newCompiledVersions", TEMP_ARTIFACTORY_DIR)
        cd File.expand_path("rnw/scripts/cp/compiled/versions/", BUILD_PATH)
        VERSIONS_TO_REBUILD.each do |version|
          puts "Zipping compiled files for CX version #{version}"
          zipFileName = "#{version}.compiled.zip"
          `zip -rq #{zipFileName} #{version}`
          mv zipFileName, newCompiledVersionsPath
        end
      end
      if VERBOSE
        endTime = Time.now
        puts calculateTime("build:versions:scriptCompile", startTime, endTime)
      end
    end
  end # versions namespace
end # build namespace

#
# Tasks for distributing CP
#
namespace :dist do
  # define some common directories we'll be using
  docRoot = File.expand_path('doc_root', DIST_PATH)
  cfgPath = File.expand_path('cgi-bin/rightnow.cfg', DIST_PATH)
  scriptsPath = File.expand_path('scripts', cfgPath)
  tarballPath = File.expand_path('cp/core/util/tarball', scriptsPath)

  #
  # createTree - Create empty directory structure for a distribution
  #
  desc "Create empty directory structure for a distribution"
  task :createTree do
    startTime = Time.now
    # make the base output directories
    distdirs = [
      'curl', # for curl's .so
      'rnt_home/rnw/bin', # for upgrade_recovery.sh, not sure why this is needed?
      'rnt_home/rnw/lib', # for libhtdig
      'doc_root/rnt/rnw', # for yui_* dirs
      'cgi-bin/rightnow.cfg/dll', # for shared objects like curl, htdig and yaml
      'cgi-bin/rightnow.cfg/scripts/euf/config', # for splash.html
      'cgi-bin/rightnow.cfg/scripts/include/config',
      'cgi-bin/rightnow.cfg/scripts/include/msgbase',
      'cgi-bin/rightnow.cfg/scripts/include/tables',
      'cgi-bin/rightnow.cfg/scripts/include/views',
      'cgi-bin/rightnow.cfg/scripts/include/services',
      'cgi-bin/rightnow.cfg/scripts/include/src/config',
      'cgi-bin/rightnow.cfg/scripts/include/src/msgbase',
      'cgi-bin/rightnow.cfg/scripts/include/src/views',
      'cgi-bin/rightnow.cfg/scripts/include/src/headers/views',
      'cgi-bin/rightnow.cfg/scripts/include/saml2/common/classes',
      'cgi-bin/rightnow.cfg/scripts/include/saml2/common/exceptions',
      'cgi-bin/rightnow.cfg/scripts/include/saml2/common/view',
      'cgi-bin/rightnow.cfg/scripts/include/saml2/idp',
      'cgi-bin/rightnow.cfg/scripts/include/saml2/sp',
      'cgi-bin/rightnow.cfg/scripts/cp', # for CP, duh...
      'cgi-bin/rightnow.cfg/scripts/make', # for make.phpdefs and make.phprules (ie, makefile_php aka script compile)
      'doc_root/euf/assets/css',
    ]

    rm_rf DIST_PATH if File.directory?(DIST_PATH)
    distdirs.each do |dir|
      mkdir_p File.expand_path(dir, DIST_PATH)
    end
    if VERBOSE
      endTime = Time.now
      puts calculateTime("dist:createTree", startTime, endTime)
    end
  end

  #
  # Distribution dependency related tasks
  #
  namespace :install do

    #
    # php - Install PHP and extensions
    #
    desc "Install PHP and extensions"
    task :php => ['dist:createTree'] do
      startTime = Time.now
      # install curl
      install CURL_LIB, File.expand_path("curl/curl_php-#{PHP_VERSION}.so", DIST_PATH), :mode => 0755, :verbose => true

      # install PHP files
      install PHP, File.expand_path("php", cfgPath), :mode => 0755, :verbose => true
      install CURL_LIB, File.expand_path("dll/curl_nossl_php5.so", cfgPath), :mode => 0644, :verbose => true
      install CURL_LIB, File.expand_path("dll/curl_php5.so", cfgPath), :mode => 0644, :verbose => true
      install YAML_LIB, File.expand_path("dll/yaml.so", cfgPath), :mode => 0644, :verbose => true
      if VERBOSE
        endTime = Time.now
        puts calculateTime("dist:install:php", startTime, endTime)
      end
    end

    #
    # yui - Install all CP dependant versions of YUI
    #
    desc "Install all CP dependant versions of YUI"
    task :yui => ['dist:createTree'] do
      startTime = Time.now
      [2.4, 2.7, 3.7, 3.8, 3.13, 3.17, 3.18].each do |version|
        cp_r "#{YUI_ROOT}/#{version}/", File.expand_path("rnt/rnw/yui_#{version}", docRoot)
      end
      if VERBOSE
        endTime = Time.now
        puts calculateTime("dist:install:yui", startTime, endTime)
      end
    end

    #
    # scripts - Install any CP dependencies under the interface.cfg directory
    #
    desc "Install any CP dependencies under the interface.cfg directory"
    task :scripts => ['dist:createTree'] do
      startTime = Time.now
      includeScripts = ['init.phph', 'util_misc.phph', 'about.phph', 'rnt_include.phph']
      compiledIncludeScripts = includeScripts.map {|f| "/rnw/scripts/include/#{f}" }
      srcIncludeScripts = includeScripts.map {|f| "/rnw/scripts/include/compiled/#{f}" }

      # destination => ['sources']
      # rightnow.cfg/scripts/include/src/headers/views/view_defines.m4
      filesToInstall = {
        'php.ini' => [
          '/rnw/install/config_files/php.ini',
        ],
        'scripts/makefile_php' => [
          '/rnw/scripts/makefile_php',
        ],
        'scripts/include' => [
          '/rnw/scripts/include/compiled/*.php',
          '/rnw/scripts/include/compiled_headers/*.phph',
        ].concat(compiledIncludeScripts), # add in the srcIncludeScripts
        'scripts/include/src' => [
          '/rnw/scripts/include/*.php*',
        ].concat(srcIncludeScripts), # add in the compiledIncludeScripts
        'scripts/include/services' => [
          '/rnw/scripts/include/compiled/services/*.php',
        ],
        'scripts/include/msgbase' => [
          '/rnw/scripts/include/compiled_headers/msgbase/*.phph',
          '/common/scripts/include/compiled_headers/msgbase/*.phph',
        ],
        'scripts/include/src/msgbase' => [
          '/rnw/scripts/include/msgbase/*.phph',
          '/common/scripts/include/msgbase/*.phph',
        ],
        'scripts/include/config' => [
          '/rnw/scripts/include/compiled_headers/config/*.phph',
          '/common/scripts/include/compiled_headers/config/*.phph',
        ],
        'scripts/include/src/config' => [
          '/rnw/scripts/include/config/*.phph',
          '/common/scripts/include/config/*.phph',
        ],
        'scripts/include/src/headers/views' => [
          '/rnw/scripts/include/headers/views/view_defines.m4',
        ],
        'scripts/include/views' => [
          '/rnw/scripts/include/compiled_headers/views/views_defines.phph',
        ],
        'scripts/include/src/views' => [
          '/rnw/scripts/include/views/views_defines.phph',
        ],
        'scripts/make' => [
          '/common/scripts/make/make.phpdefs',
          '/common/scripts/make/make.phprules',
        ],
        'scripts/euf/config' => [
          '/rnw/scripts/euf/config/splash.html',
        ],
      }

      # intall files from build path into distribution tree
      filesToInstall.each do |destination, sourceList|
        sourceList.each do |source|
          Dir[BUILD_PATH + source].each {|file| install file, File.expand_path(destination, cfgPath), :mode => 0644, :verbose => true}
        end
      end

      # copy the euf assets into place
      cp_r File.expand_path('rnw/scripts/euf/webfiles/assets', BUILD_PATH), File.expand_path('euf', docRoot)
      rm_rf File.expand_path('euf/assets/themes', docRoot)
      if VERBOSE
        endTime = Time.now
        puts calculateTime("dist:install:scripts", startTime, endTime)
      end
    end

    #
    # cp - Install all CP files
    #
    desc "Install all CP files"
    task :cp => ['dist:createTree'] do
      startTime = Time.now
      # install CP
      cd File.expand_path('rnw/scripts/cp', BUILD_PATH) do
        Dir["./*.php"].each do |file|
          install file, File.expand_path('cp', scriptsPath), :mode => 0755, :verbose => true
        end
      end

      ['mod_info.phph', 'make.moddefs', 'core', 'versions', 'customer'].each do |dir|
        cp_r File.expand_path("rnw/scripts/cp/#{dir}", BUILD_PATH), File.expand_path('cp', scriptsPath)
      end
      cp_r File.expand_path('rnw/scripts/cp/webfiles/assets', BUILD_PATH), File.expand_path('euf', docRoot)
      cp_r File.expand_path('rnw/scripts/euf/webfiles/rightnow', BUILD_PATH), File.expand_path('euf', docRoot)
      cp_r File.expand_path('rnw/scripts/cp/webfiles/core', BUILD_PATH), File.expand_path('euf', docRoot)
      install File.expand_path('rnw/webfiles/css/live_tc.css', BUILD_PATH), File.expand_path('euf/assets/css/live_tc.css', docRoot), :mode => 0644, :verbose => true

      # adding / to the end of the src dir, will not create dest/src
      cp_r File.expand_path('rnw/scripts/cp/compiled', BUILD_PATH), File.expand_path('cp', scriptsPath)
      mkdir File.expand_path('cp/src', scriptsPath)

      # copy everything under scripts/cp -> scripts/cp/src
      puts "Copying everything under scripts/cp to scripts/cp/src"
      cd File.expand_path('cp', scriptsPath) do
        ['core', 'versions'].each do |dir|
          cp_r "./#{dir}", "./src"
        end
        install "./mod_info.phph", "./src/mod_info.phph", :mode => 0644, :verbose => true
      end

      # copy everything under scripts/cp/compiled -> scripts/cp
      puts "Copying everything under scripts/cp/compiled to scripts/cp/src"
      cd File.expand_path('cp/compiled', scriptsPath) do
        Dir["./**/*"].each do |item|
          mv item, "../#{item}" if File.file?(item) && File.file?("../#{item}")
        end
      end
      rm_rf File.expand_path('cp/compiled', scriptsPath)

      mkdir File.expand_path('euf/generated', docRoot)
      mkdir_p File.expand_path('cp/generated/production', scriptsPath)
      mkdir_p File.expand_path('cp/generated/staging', scriptsPath)

      # rm_rf File.expand_path('cp/core/framework/Views', scriptsPath)
      mkdir File.expand_path('cp/src/core/framework/views', scriptsPath)
      cp_r File.expand_path('rnw/scripts/cp/customer/development/views/pages', BUILD_PATH), File.expand_path('cp/src/core/framework/views/', scriptsPath)
      cp_r File.expand_path('rnw/scripts/cp/customer/development/views/templates', BUILD_PATH), File.expand_path('cp/src/core/framework/views/', scriptsPath)
      cp_r File.expand_path('rnw/scripts/cp/customer/development/views/admin', BUILD_PATH), File.expand_path('cp/src/core/framework/views/', scriptsPath)
      cp_r File.expand_path('rnw/scripts/cp/core/framework/Views/Partials', BUILD_PATH), File.expand_path('cp/src/core/framework/views/', scriptsPath)

      chmod_R 0755, File.expand_path('cp', scriptsPath)

      puts "Removing CP unit test files indiscriminately"
      ['cp/core', 
       'cp/src/core', 
       'cp/customer', 
       'cp/versions', 
       'cp/src/versions'].each do |path|
        Dir["#{File.expand_path(path, scriptsPath)}/**/"].each do |dir|
         rm_rf dir if File.basename(dir).match(/^test/) # matches 'test' and 'tests'
        end
      end

      # Remove these files from both main location and /src directory
      ['core/framework/Controllers/Admin/InternalTools.php',
       'core/framework/Internal/Utils/DevelopmentLogger.php',
       'core/framework/Internal/Libraries/Widget/DependencyInfo.php'].each do |file|
        rm_f File.expand_path("cp/#{file}", scriptsPath)
        rm_f File.expand_path("cp/src/#{file}", scriptsPath)
      end

      # Remove these directories from both main location and /src directory
      ['core/framework/Controllers/UnitTest',
       'core/framework/Views/Admin/internalTools'].each do |file|
        rm_rf File.expand_path("cp/#{file}", scriptsPath)
        rm_rf File.expand_path("cp/src/#{file}", scriptsPath)
      end

      # Remove development product images
      Dir["#{docRoot}/euf/assets/images/prodcat-images/*"].each do |file|
        unless ["default.png", "replacement-repair-coverage.png"].include?(File.basename(file))
          rm_rf file
        end
      end

      # Remove our extras directory and all custom widgets. They aren't under framework, so there isn't a copy under /src.
      rm_rf File.expand_path('cp/extras', scriptsPath)
      rm_rf Dir.glob(File.expand_path('cp/customer/development/widgets/custom/*', scriptsPath))
      Dir["#{docRoot}/euf/assets/themes/*/widgetCss/.placeholder"].each {|file| rm_f file}

      # remove admin views from src folder
      rm_rf File.expand_path('cp/src/core/framework/Views', scriptsPath)
      if VERBOSE
        endTime = Time.now
        puts calculateTime("dist:install:cp", startTime, endTime)
      end
    end
  end

  #
  # deploy - Deploy CP
  #
  desc "Deploy CP"
  task :deploy => ['install:scripts', 'install:php', 'install:yui', 'install:cp'] do
    startTime = Time.now
    cp_r File.expand_path('cp/customer/development/views/pages', scriptsPath), File.expand_path('cp/saveOkcsPages', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/okcs', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/basic', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/social', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/mobile/public_profile.php', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/public_profile.php', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/public_profile_update.php', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/account/profile_picture.php', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/mobile/account/profile_picture.php', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/mobile/utils/guided_assistant.php', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/answers/detail.php', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/answers/list.php', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/mobile/answers/detail.php', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/mobile/answers/list.php', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/products', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/mobile/products', scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages/mobile/social', scriptsPath)

    # replace standard template with okcs_standard for okcs
    cd File.expand_path('cp/saveOkcsPages', scriptsPath) do
        Dir["./**/*"].each do |f|
            if File.file?(f)
                fileData = File.open(f, :encoding => "UTF-8", &:read)
                currentMetaTag = fileData.match(/<rn\:meta[^\>]*\/>/).to_s
                newMetaTag = currentMetaTag.gsub('template="standard.php"','template="okcs_standard.php"')
                newMetaTag = newMetaTag.gsub('template="mobile.php"','template="okcs_mobile.php"')
                File.write(f, File.open(f, &:read).gsub(currentMetaTag, newMetaTag))
            end
        end
    end
    cp_r File.expand_path('cp/customer/development/views/pages/okcs', scriptsPath).to_s << '/.' , File.expand_path('cp/saveOkcsPages', scriptsPath)

    # remove the mobile, basic and okcs templates and pages from customer development area
    rm_rf File.expand_path('cp/customer/development/views/pages/mobile', scriptsPath)
    rm_f File.expand_path('cp/customer/development/views/templates/mobile.php', scriptsPath)
    rm_rf File.expand_path('cp/customer/development/views/pages/basic', scriptsPath)
    rm_f File.expand_path('cp/customer/development/views/templates/basic.php', scriptsPath)
    rm_rf File.expand_path('cp/customer/development/views/pages/okcs', scriptsPath)
    rm_rf File.expand_path('cp/customer/development/views/admin/okcs', scriptsPath)
    rm_f File.expand_path('cp/customer/development/views/templates/okcs_mobile.php', scriptsPath)
    rm_f File.expand_path('cp/customer/development/views/templates/okcs_standard.php', scriptsPath)

    puts "Running Deploy CP: #{PHP_DIR}/bin/php-#{PHP_VERSION} -d extension=#{cfgPath}/dll/yaml.so -n -f #{tarballPath}/deploy.php #{CP_FRAMEWORK_VERSION} #{CP_FRAMEWORK_NANO_VERSION}"
    deployOutput = `#{PHP_DIR}/bin/php-#{PHP_VERSION} -d extension=#{cfgPath}/dll/yaml.so -n -f #{tarballPath}/deploy.php #{CP_FRAMEWORK_VERSION} #{CP_FRAMEWORK_NANO_VERSION}`
    puts "output: #{deployOutput}"

    # now that staging and production files have been generated, copy back okcs pages to the 'src' folder
    frameworkVersionPath = "cp/src/core/framework/#{CP_FRAMEWORK_FULL_VERSION}"
    if !File.directory?(File.expand_path("#{frameworkVersionPath}/views/pages/okcs", scriptsPath))
      mkdir File.expand_path("#{frameworkVersionPath}/views/pages/okcs", scriptsPath)
    end
    cp_r File.expand_path('cp/saveOkcsPages', scriptsPath).to_s << '/.' , File.expand_path("#{frameworkVersionPath}/views/pages/okcs", scriptsPath)
    rm_rf File.expand_path('cp/saveOkcsPages', scriptsPath)
    mkdir File.expand_path("#{frameworkVersionPath}/views/templates/okcs", scriptsPath)
    cp_r File.expand_path("#{frameworkVersionPath}/views/templates/okcs_mobile.php", scriptsPath) , File.expand_path("#{frameworkVersionPath}/views/templates/okcs", scriptsPath)
    cp_r File.expand_path("#{frameworkVersionPath}/views/templates/okcs_standard.php", scriptsPath) , File.expand_path("#{frameworkVersionPath}/views/templates/okcs", scriptsPath)
    rm_rf File.expand_path("#{frameworkVersionPath}/views/templates/okcs_standard.php", scriptsPath)
    rm_rf File.expand_path("#{frameworkVersionPath}/views/templates/okcs_mobile.php", scriptsPath)

    # create okcs folder under templates directory for previous versions and copy okcs_standard and okcs_mobile templates
    VERSIONS.each do |version, versionInfo|
        cpVersion = versionInfo['cpVersion']
        prevFrameworkVersionPath = "cp/src/core/framework/#{cpVersion}"
        frameworkRoot = File.expand_path("#{prevFrameworkVersionPath}", scriptsPath)
        if File.directory?(frameworkRoot)
            okcsStdTemplate = File.expand_path("#{prevFrameworkVersionPath}/views/templates/okcs_standard.php", scriptsPath)
            okcsMobTemplate = File.expand_path("#{prevFrameworkVersionPath}/views/templates/okcs_mobile.php", scriptsPath)
            if File.file?(okcsStdTemplate) || File.file?(okcsMobTemplate)
                mkdir File.expand_path("#{prevFrameworkVersionPath}/views/templates/okcs", scriptsPath)
                if File.file?(okcsStdTemplate)
                    cp_r File.expand_path("#{prevFrameworkVersionPath}/views/templates/okcs_standard.php", scriptsPath) , File.expand_path("#{prevFrameworkVersionPath}/views/templates/okcs", scriptsPath)
                    rm_rf File.expand_path("#{prevFrameworkVersionPath}/views/templates/okcs_standard.php", scriptsPath)
                end
                if File.file?(okcsMobTemplate)
                    cp_r File.expand_path("#{prevFrameworkVersionPath}/views/templates/okcs_mobile.php", scriptsPath) , File.expand_path("#{prevFrameworkVersionPath}/views/templates/okcs", scriptsPath)
                    rm_rf File.expand_path("#{prevFrameworkVersionPath}/views/templates/okcs_mobile.php", scriptsPath)
                end
            end
        end
    end
    if VERBOSE
      endTime = Time.now
      puts calculateTime("dist:deploy", startTime, endTime)
    end
  end

  #
  # buildVersionTarballs - Build tarballs for previous CX release versions
  #
  desc "Build tarballs for previous CX release versions"
  task :buildVersionTarballs do
    startTime = Time.now
    if VERSIONS_TO_REBUILD.length > 0
      mkdir File.expand_path("tarballsToUpload", TEMP_ARTIFACTORY_DIR)
      tarballsToUploadPath = File.expand_path("tarballsToUpload", TEMP_ARTIFACTORY_DIR)
      cd File.expand_path("rnw/scripts/cp/versions/", BUILD_PATH)
      # if no tarball for the version exists, or the version was rebuilt because of a newer commit hash build a new tarball
      VERSIONS_TO_REBUILD.each do |version|
        puts "Creating tarball for CX version #{version}"
        tarballName = getVersionTarballArtifactName(version)
        #create tarball and store under TEMP_ARTIFACTORY_DIR so Jenkinsfile can upload to Artifactory
        #copy the zip of the versions compiled files into place so it will be included in the tarball
        cp File.expand_path("newCompiledVersions/#{version}.compiled.zip", TEMP_ARTIFACTORY_DIR), "./#{version}"
        #remove compiled zip after tarball creation so it is not included in the final build output
        `tar -czf #{tarballName} #{version} && rm -rf ./#{version}/#{version}.compiled.zip`
        mv tarballName, tarballsToUploadPath
      end
    end
    if VERBOSE
      endTime = Time.now
      puts calculateTime("dist:buildVersionTarballs", startTime, endTime)
    end
  end

  #
  # cleanupInternalFiles - Cleanup any files we don't want to ship
  #
  desc "Cleanup any files we don't want to ship"
  task :cleanupInternalFiles do
    startTime = Time.now
    rm_rf File.expand_path('cp/core/util/tarball', scriptsPath)
    rm_rf File.expand_path('cp/versions', scriptsPath)
    rm_rf File.expand_path('cp/src/versions', scriptsPath)

    Find.find(File.expand_path('cp/src', scriptsPath)) do |item|
      rm_rf item if item =~ /optimized/ && File.directory?(item)
      rm_f item if item =~ /optimized_includes.php/ && File.file?(item)
    end

    # remove themesPackageSource folder (located in widgets) from non-src and customer folders
    excludedFiles = ['frameworkVersion', 'widgetVersions', 'versionAuditLog', 'phpVersion']
    Find.find(File.expand_path('cp/core', scriptsPath), File.expand_path('cp/customer', scriptsPath), File.expand_path('cp/generated', scriptsPath)) do |item|
      next if excludedFiles.include?(item)
      rm_rf item if item =~ /themesPackageSource/ && File.directory?(item)
    end

    ['euf', 'include', 'make'].each {|path| rm_rf File.expand_path(path, scriptsPath)}
    rm_f File.expand_path("php", cfgPath)
    rm_f File.expand_path("php.ini", cfgPath)
    rm_rf File.expand_path("dll", cfgPath)
    rm_rf File.expand_path("curl", DIST_PATH)
    rm_rf File.expand_path("rnt_home", DIST_PATH)

    eufCorePath = "euf/core/#{CP_FRAMEWORK_VERSION}"
    mkdir_p File.expand_path("#{eufCorePath}/default", docRoot)
    cp_r File.expand_path("euf/assets/images", docRoot),
      File.expand_path("#{eufCorePath}/default", docRoot)
    cp_r File.expand_path("euf/assets/themes", docRoot),
      File.expand_path("#{eufCorePath}/default", docRoot)
    cp_r File.expand_path("euf/assets/feedback", docRoot),
      File.expand_path("#{eufCorePath}/default", docRoot)

    rm_f File.expand_path("#{eufCorePath}/default/PLACEHOLDER.README", docRoot)

    # Move the static directory out of the versioned sub directory
    mv File.expand_path("#{eufCorePath}/static", docRoot),
      File.expand_path("euf/core/", docRoot)
    if VERBOSE
      endTime = Time.now
      puts calculateTime("dist:cleanupInternalFiles", startTime, endTime)
    end  
  end
end

#
# CP Versions tasks
#
namespace :versions do
  #
  # populate - Populate previous CP versions
  #
  desc "Populate previous CP versions"
  task :populate => [:constructVersionsToRebuildList] do

    # version 3.0.1 doesn't have a versions directory since it was the first version
    # if we encounter this, bail
    startTime = Time.now
    return unless File.directory?(VERSIONS_PATH)
    mkdir File.expand_path('currentCompiledVersions', TEMP_ARTIFACTORY_DIR)
    currentCompiledVersionsPath = File.expand_path("currentCompiledVersions", TEMP_ARTIFACTORY_DIR)
    VERSIONS.each do |version, versionInfo|
      #if we have an up to date tarball for the CX version there is no need to rebuild that version
      if !VERSIONS_TO_REBUILD.include?(version)
        tarball = `find #{TEMP_ARTIFACTORY_DIR} -name #{version}.*.tar.gz -printf '%f\n'`
        puts "Extracting tarball for CX version #{version}. tarball: #{tarball}"
        #extract the tarball for the CX version into the /versions directory
        `tar -C #{VERSIONS_PATH} -xzf #{TEMP_ARTIFACTORY_DIR}/#{tarball}`
        #move compiled files for the CX version into the currentCompiledVersions dir
        mv File.expand_path("#{version}/#{version}.compiled.zip", VERSIONS_PATH), currentCompiledVersionsPath
      else
        puts "No current tarball for CX version #{version} exists, grabbing tree from orahub"
        versionPath = File.expand_path(version, VERSIONS_PATH)
        `/nfs/local/linux/git/current/contrib/workdir/git-new-workdir #{REPO_PATH} #{versionPath} #{version}` unless File.directory?(versionPath)
      end 
    end
    if VERBOSE
      endTime = Time.now
      puts calculateTime("versions:populate", startTime, endTime)
    end
  end

  #
  # constructVersionsToRebuildList - Constructs list of CX version tarballs that need to be rebuilt
  #
  desc "Constructs list of CX version tarballs that need to be rebuilt"
  task :constructVersionsToRebuildList do
    startTime = Time.now
    #grab list of tarballs currently stored in Artifactory
    `wget --recursive --level=1 --no-parent --no-verbose --no-directories -P#{TEMP_ARTIFACTORY_DIR} -A "*.tar.gz" #{ARTIFACTORY_WEBSITE}/#{ARTIFACTORY_REPO}/#{ARTIFACTORY_TARBALLS_PATH}`
    if !File.exists?(TEMP_ARTIFACTORY_DIR)
      puts "No current tarballs in Artifactory. All CX version tarballs will be created and uploaded"
      mkdir TEMP_ARTIFACTORY_DIR
    end
    
    currentTarballs = `ls #{TEMP_ARTIFACTORY_DIR}`.split("\n")
    puts "Current Tarballs"
    puts currentTarballs

    #construct list of expected tarball names for each CX version
    expectedTarballs = []
    VERSIONS.each do |version, versionInfo|
      expectedTarballs.push(getVersionTarballArtifactName(version))
    end
    puts "Expected Tarballs"
    puts expectedTarballs

    tarballsToRemoveFile = File.open(File.expand_path("tarballsToRemove.txt", TEMP_ARTIFACTORY_DIR), "w")
    expectedTarballs.each do |tarballArtifactName|
      version = tarballArtifactName.split('.')[0]
      #check if currentTarballs includes the expected tarball for each CX version
      if currentTarballs.include?(tarballArtifactName)
        VERSIONS_TO_SKIP_COMPILE.push(version)
      #if it does not we need to rebuild that version
      else
        VERSIONS_TO_REBUILD.push(version)
        #add tarball name to tarballsToRemove.txt file under TEMP_ARTIFACTORY_DIR so the Jenkinsfile can delete it from Artifactory
        tarballToRemove = `find #{TEMP_ARTIFACTORY_DIR} -name #{version}.*.tar.gz -printf '%f\n'`.to_s
        if tarballToRemove
          tarballsToRemoveFile.puts tarballToRemove
        end
      end
    end
    tarballsToRemoveFile.close
    puts "CX Versions to rebuild"
    puts VERSIONS_TO_REBUILD
    puts "CX Versions to skip compile"
    puts VERSIONS_TO_SKIP_COMPILE
    if VERBOSE
      endTime = Time.now
      puts calculateTime("versions:constructVersionsToRebuildList", startTime, endTime)
    end
  end 
end
