#!/nfs/local/linux/ruby/current/bin/ruby
#coding: utf-8

# # Workflow for consuming a new YUI release:
# ## Copy files into place on nfs.
#
# 1. Download the new release.
# 2. The build dir in the downloaded package is what we put up
#    as /nfs/local/generic/yui/major.minor/ so copy that over.
# 3. Run this script, pointing its yui-dir at the new dir from
#    step 2.
# 4. Copy the produced rollup files into the new dir from step 2.
# 5. There's a data.json file within the api dir in the downloaded
#    package. Copy that into the new dir from step 2.
# 6. Copy the gallery-treeview module from the previous YUI version
#    into the new dir from step 2.
#
# → To recap, the yui version dir should contain:
# * All the module dirs + gallery-treeview module dir
# * Our rollup combined-*.js files
# * data.json file
#
# ## Change and commit files pointing to the new version in the product.
#
# 1. Update the version and location in the cp/core/framework/manifest file.
# 2. Update the location in the ~/yoursource/bin/create_private_html_root file.
#    Note: you can also manually update your own site without having to re-run create_test_site:
#
#       cd /home/httpd/html/per_site_html_root/<yoursite>/rnt/rnw && ln -s /nfs/local/generic/yui/major.minor yui_major.minor
#
# 3. Update rnw/install/dist/unix/makefile, adding something like
#
#       cp -r $(YUI_ROOT)/major.minor $(DOCROOT)/rnt/rnw/yui_major.minor
#
#    Near the other YUI stuff in there.
#
# 4. You're done. Take the rest of the day off. (Don't take the rest of the day off. This all should've taken you like about 10 minutes.)

require "yaml"

class YUIBuild
  BUILD_INFO_FILE = 'yuiBuild.yml'
  COMMENT_PATTERN = /^\/\*([^*]|[\r\n]|(\*+([^*\/]|[\r\n])))*\*+\//

  @@verbose = false

  def self.init(path_to_yui, output_dir, build_file, script_type)
    path_to_yui.sub!(/\/$/, '')
    output_dir.sub!(/\/$/, '')
    log "YUI is at #{path_to_yui}"
    log "Outputting files to #{output_dir}"

    get_buildinfo(build_file).each do |key, value|
      code = combine(key, value, path_to_yui, script_type)
      write_file("#{output_dir}/#{key}.js", code) if !code.empty?
    end
  end

  def self.verbose=(verbose)
    @@verbose = verbose
  end

  private

  def self.combine(filename, modules, path_to_yui, script_type)
    allcode = ''
    file_count = 0

    if script_type.nil?
      script_type = '-min'
    elsif script_type == 'unmin'
      script_type = ''
    elsif script_type == 'debug'
      script_type = '-debug'
    else
      log("Unknown option #{script_type} supplied. Defaulting to min.", true)
      script_type = '-min'
    end

    modules.each do |mod|
      file = "#{path_to_yui}/#{mod}/#{mod}#{script_type}.js"
      log file

      begin
        code = File.read(file)
      rescue
        log("Couldn't read #{file}", true)
        next
      end

      file_count += 1

      # Rip out the standard YUI boilerplate comment block at
      # the top of every module, but leave it at the top
      # of the rollup file.
      code.gsub!(COMMENT_PATTERN, '') if file_count > 1

      allcode += code.lstrip
    end
    allcode
  end

  def self.write_file(filename, contents)
    puts ">> #{filename}"
    begin
      File.open(filename, 'w') { |f| f.write(contents) }
    rescue
      log("Couldn't write to #{filename}")
    end
  end

  def self.get_buildinfo(build_file = '')
      build_file = build_file || BUILD_INFO_FILE
      # Prepend the abs. path if the path specified is relative.
      build_file = "#{Dir.pwd}/#{build_file}" if build_file[0] != '/'

      log "Loading #{build_file}"

      begin
        contents = IO.read(build_file)
      rescue
        log("Couldn't read the #{build_file}", true)
        exit
      end
      begin
        YAML.load(contents)
      rescue
        log("There's a problem with the yaml in #{build_file}", true)
        exit
      end
  end

  def self.log(message, error = false)
    if error
      puts "Error: #{message}"
    elsif @@verbose
      puts message
    end
  end
end


desc = <<DESC

***************************
YUI rollup builder
***************************
Combines the YUI modules specified in the yuiBuild.yml file.
NOTE: This script does **not** do any module dependency resolution;
  it simply combines the files that are specified.
***************************
Usage: ./yuiBuild.rb
Command line options:

--help
    Print this message
--verbose, -v
    Talkative mode
--yui-dir, -y
    Directory that contains all of the YUI module dirs in it.
    On NFS, it'd typically be
        /nfs/local/generic/yui/3.x/
    For a downloaded YUI distribution it'd typically be
        theyuidownloaddir/build/
    -> If not specified, the current directory is used.
--build-file, -b
    The YAML file that specifies what is to be built.
    The keys are the names of the files (excluding the .js suffix) whose values are
    arrays listing YUI module names.
    -> If not specified, a build file named yuiBuild.yml in the current
    directory is assumed.
--output-dir, -o
    Where to write out the rollup files.
    -> If not specified, the files are output in the current directory.
--script-type, -s
    YUI includes three files for each module: normal, -min, and -debug.
    Specify "debug" for the -debug script (e.g. yui/yui-debug.js)
    Specify "unmin" for the vanilla, unminified script (e.g. yui/yui.js)
    -> If not specified, the -min file is used (e.g. yui/yui-min.js).

(o\\_!_/o)
***************************
DESC

if ARGV.include?('--help')
  puts desc
  exit
end
if ARGV.include?('--verbose') || ARGV.include?('-v')
  YUIBuild.verbose = true
end

supplied = {}
arguments = {
  'b' => {'alt' => 'build-file', 'default' => nil},
  'y' => {'alt' => 'yui-dir', 'default' => Dir.pwd},
  'o' => {'alt' => 'output-dir', 'default' => Dir.pwd},
  's' => {'alt' => 'script-type', 'default' => nil},
}

arguments.each do |key, val|
  index = ARGV.index("-#{key}") || ARGV.index("--#{val['alt']}")
  supplied[key] = index.nil? ? val['default'] : ARGV[index + 1].dup
end

YUIBuild.init(supplied['y'], supplied['o'], supplied['b'], supplied['s'])
