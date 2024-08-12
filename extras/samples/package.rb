#!/nfs/local/linux/ruby/current/bin/ruby
#coding: utf-8

require 'yaml'
require 'fileutils'

class PackageIt
  EXCLUDE = ["CVS", "output"]
  FILE_TYPES = "php,js,yml,css,ejs,md,png,gif,svg,html,xml,json,zip"
  OUTPUT_DIR = "output"
  STRUCTURE = {
    :files           => "customer/development/widgets/custom",
    :assets          => "customer/assets/themes/standard/images",
    :widget_css      => "customer/assets/themes/standard/widgetCss",
    :views           => "customer/development/views/pages",
    :models          => "customer/development/models/custom",
    :controllers     => "customer/development/controllers",
    :exports         => "exports"
  }
  OPTIONAL_FILES = [:views, :controllers, :models, :images, :exports]
  COPYRIGHT = <<DISCLAIM
Copyright © #{Time.now.year}, Oracle Corporation and/or its affiliates. All rights reserved.

The sample code in this document or accessed through this document is not certified or
supported by Oracle. It is intended for educational or testing purposes only. Use of this
sample code implies acceptance of the License Agreement
(http://www.oracle.com/technetwork/licenses/standard-license-152015.html).
DISCLAIM

  @@verbose = false

  def self.init
    current_dir = Dir.pwd

    samples = process_files(current_dir)

    if !samples.empty?
      write_samples(samples)
      zip_samples
      return puts "Violà. Check output/"
    end

    log("No widgets found :(", true)
  end

  def self.verbose=(verbose)
    @@verbose = verbose
  end

  def self.clean
    log "Removing #{OUTPUT_DIR} dir"
    FileUtils.rm_rf(OUTPUT_DIR)
  end

  private

  def self.zip_samples
    Dir.chdir(OUTPUT_DIR)
    Dir.glob("*/").each do |dir|
      puts "#{OUTPUT_DIR}/#{dir}"
      dir = dir.gsub(" ", "\\ ") || dir
      dir.chop!
      log "Zipping #{dir}"
      log `zip -r #{dir} #{dir}`
    end
  end

  def self.write_samples(samples)
    samples.each do |sample_name, widgets|
      next if sample_name.nil?
      widgets.each do |widget_info|
        next if widget_info[:folder].nil?

        # Saves the readme directly inside the project dir. This is sorta dubious because the readme gets overwritten when...
        # * There's multiple widgets in the same sample project and they each have a readme (only one should).
        # * There's multiple versions of the widget. The latest version's readme will always win. :|
        copy_files(widget_info[:readme], widget_info[:name], "#{OUTPUT_DIR}/#{sample_name}") {|file| File.basename(file)}

        copy_files(widget_info[:files], widget_info[:name], "#{OUTPUT_DIR}/#{sample_name}/#{STRUCTURE[:files]}/#{widget_info[:folder]}/#{widget_info[:name]}")
        copy_files(widget_info[:assets], widget_info[:name], "#{OUTPUT_DIR}/#{sample_name}/#{STRUCTURE[:assets]}") {|file| File.basename(file)}

        OPTIONAL_FILES.each do |type|
          if widget_info.include?(type)
            path = STRUCTURE[type] || ''
            copy_files(widget_info[type], widget_info[:name], "#{OUTPUT_DIR}/#{sample_name}/#{path}") {|file| File.basename(file)}
          end
        end

        if !widget_info[:widget_css].empty?
          # Omit the version directory in the presentation css's path
          # when writing out to widgetCss. This means that if there's
          # multiple versions of a widget, the latest always wins. :|
          copy_files(widget_info[:widget_css], widget_info[:name], "#{OUTPUT_DIR}/#{sample_name}/#{STRUCTURE[:widget_css]}") {|file| File.basename(file)}
        end
      end
    end
  end

  def self.process_files(current_dir)
    samples = {}

    Dir.glob("*/").each do |widget_name|
      widget_name.chop!
      next if EXCLUDE.include?(widget_name)

      log "Processing #{widget_name}"

      Dir.chdir("#{current_dir}/#{widget_name}")

      widget_info = process_widget_files(Dir.glob("**/*.{#{FILE_TYPES}}"), widget_name)

      if !widget_info[:project].nil?
        project_name = widget_info[:project]
        if !samples.has_key?(project_name)
          samples[project_name] = []
        end
        samples[project_name] << widget_info
      else
        log("Couldn't find a package.yml file for #{widget_name}", true)
      end
    end

    Dir.chdir(current_dir)

    samples
  end

  # Optional block may be passed to help resolve destination file
  def self.copy_files(files, src_prefix, dest_prefix)
    files.each do |file|
      src = "#{src_prefix}/#{file}"
      file = yield(file) if block_given?
      dest = "#{dest_prefix}/#{file}"

      log "Copying #{src} to #{dest}"

      begin
        FileUtils::mkdir_p(File.dirname(dest))
        FileUtils::copy(src, dest)
      rescue
        log("Couldn't copy #{src} to #{dest}. Does #{src} exist?", true)
        next
      end

      apply_copyright(dest)
    end
  end

  def self.process_widget_files(files, widget_name)
    widget_info = {
      :name         => widget_name,
      :files        => [],
      :assets       => [],
      :widget_css   => [],
      :readme       => [],
      :project      => nil,
      :folder       => nil,
    }
    to_remove = []

    files.each do |file|
      widget_info[:files] << file
      file_name = File.basename(file)

      case File.extname(file)[1..-1]
      when 'css'
        # Pull out widget presentation css
        if file_name.include?(widget_name)
          widget_info[:widget_css] << file
          widget_info[:files].delete(file)
        end
      when 'png', 'gif', 'svg'
        # Pull out all assets (that aren't preview images)
        if !File.fnmatch('*/preview/*.png', file)
          widget_info[:assets] << file
          widget_info[:files].delete(file)
        end
      when 'yml'
        # Pull out package.yml
        if file_name == 'package.yml'
          settings = YAML.load(IO.read(file))
          # project and folder are required
          widget_info[:project] = settings['package']
          widget_info[:folder] = settings['folder']
          # additionalFiles is optional. May contain
          # models, controllers keys
          if settings.include?('additionalFiles')
            settings['additionalFiles'].each do |key, val|
              val = [val] if val.is_a?(String)
              val.map! {|name| "#{File.dirname(file)}/#{name}"}
              to_remove = to_remove | val
              widget_info[key.to_sym] = val
            end
          end
          widget_info[:files].delete(file)
        end
      when 'md'
        # Pull out README
        if file_name.downcase == 'readme.md'
          widget_info[:readme] << file
          widget_info[:files].delete(file)
        end
      end
    end

    if !to_remove.empty?
      # Stop the additional files (models, controllers, images)
      # from being written to the dest. widget dir.
      widget_info[:files].keep_if {|file| !to_remove.include?(file)}
      widget_info[:assets].keep_if {|file| !to_remove.include?(file)}
    end

    widget_info
  end

  # Adds the standard copyright notice
  # into the header comment section at the
  # top of all CSS, JS, EJS, PHP, and YML files
  def self.apply_copyright(file_path)
    file_type = File.extname(file_path)[1..-1]

    case file_type
    when 'css', 'js', 'php', 'ejs'
      line_prefix = ' * '
      lead = "/**\n"
      trail = " */\n"

      if file_type == 'php'
        opener = "<?php\n"
        opener_with_header = /^<\?(php)?\n*\s*\/\*(\*)?/
        opener_without_header = /^<\?(php)?\n/
      elsif file_type == 'ejs'
        opener = "<%\n"
        opener_with_header = /^<%\n*\s*\/\*(\*)?/
        opener_without_header = /^<%?\n/
      end
    when 'yml'
      line_prefix = '# '
      lead = ''
      trail = "#\n"
    end

    return if line_prefix.nil?

    file = IO.read(file_path)
    comment = COPYRIGHT.gsub(/^/, line_prefix)
    if !opener.nil?
      # php / ejs files
      if file.index(opener_with_header) == 0
        # file already containing header comment ->
        # Tack the copyright into the header comment.
        file.sub!(opener_with_header, "#{opener}#{lead}#{comment}#{line_prefix}")
      else
        # file without a header comment ->
        # Add the copyright comment block but report the omission.
        file.sub!(opener_without_header, "#{opener}#{lead}#{comment}#{trail}")
        log("#{file_path} doesn't contain a header comment", true)
      end
    elsif file.index(/^\s*\/\*(\*)?/) == 0
      # js, css files that contain a header comment
      file.sub!(/^\s*\/\*(\*)?/, "#{lead}#{comment}#{line_prefix}")
    else
      # yaml comment or other type of file
      # that didn't already have a header
      file = "#{lead}#{comment}#{trail}#{file}"
      log("#{file_path} doesn't contain a header comment", true) if file_type != 'yml'
    end

    File.open(file_path, 'w') { |f| f.write(file) }
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
Packager for code samples.
***************************
Produces zip files for each sample code 'project' and places them in an "output/" directory.

NOTES:

This script is intended to be run from a dir where all of the widget dirs live.

  e.g.

  package.rb
  WidgetA/1.0/package.yml
  WidgetA/1.0/controller.php
    ...
  WidgetB/1.0/package.yml
  WidgetC/1.0/
  ...

The only requirement is that each widget has a package.yml file consisting of two fields.

    package: Name of the sample project zip file. If several widgets are part of a sample project
             then they'd both have the same package.
    folder: Name of the subfolder under widgets/custom to place the widget dir into.

  Additional files that aren't part of the widget code but should be included in the sample project may be listed.

    additionalFiles:
      controllers: String or Array of filenames of controllers
      models:      String or Array of filenames of models
      images:      String or Array of filenames of instructive / setup images that should be placed at the
                   top level of the project along with the Readme

  e.g.

    package: Extend AnswerFeedback
    folder: feedback
    additionalFiles:
      models: [myModel.php, otherModel.php]
      controllers: myAjax.php
      images: [pic1.png, "setup step one.jpg", "preview/banana.png"]

If there's multiple widgets as part of a sample code project then only one of them should house the Readme.md file for the project.

***************************
Usage: ./package.rb
Command line options:

--help          -> Print this message
--verbose, -v   -> Talkative mode
--clean, -c     -> Remove the output dir so that files aren't overlayed on top of anything already existing in it

d-_-b
***************************
DESC


if ARGV[0] == '--help'
  puts desc
  exit
end
if ARGV.include?('--verbose') || ARGV.include?('-v')
  PackageIt.verbose = true
end
if ARGV.include?('--clean') || ARGV.include?('-c')
  PackageIt.clean
end

PackageIt.init
