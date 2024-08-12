#!/nfs/local/linux/ruby/current/bin/ruby -W0
#coding: utf-8

require 'erb'
require 'yaml'
require 'ostruct'
require 'fileutils'
require '/nfs/project/cp/rb-readline/lib/readline'

class Color
    def self.colorize(input)
        # Apply white background so that blue / green don't blend into whatever
        # color scheme people have.
        # ** -> blue
        input.gsub!(/\*\*(.*)\*\*/) {|match| "\e[47m\e[34;1m#{$1}\e[0m" }
        # _ -> green
        input.gsub!(/\_(.*)\_/) {|match| "\e[47m\e[32m#{$1}\e[0m" }
        input
    end

    def self.error(input)
        "\e[31m#{input}\e[0m"
    end
end

class WidgetToExtendFrom < Struct.new(:subpath)
    def exists?
        File.directory?(path)
    end

    def path
        @path ||= Dir.pwd.split('cp/')[0] + "cp/core/widgets/standard/#{subpath}"
    end

    # Major.Minor version of the widget.
    def version
        version_line = ''
        File.open("#{path}/changelog.yml") {|f| version_line = f.readline }

        version_line.match(/\d\.\d/)[0]
    end
end

class Creator
    def initialize(name, folder, installer, options)
        @folders = %w[preview tests]
        @name = name
        @folder = folder
        @installer = installer
        @widget_folder = "#{folder}/#{name}"
        @templates = "#{Dir.pwd}/templates"
        @currentDate = Time.now.strftime("%d/%m/%Y")

        @meta = {
            # Conditionally generated based on user response
            :controller       => { :source => "controller.php" },
            :controller_test  => { :source => "controller.test.php", :dest => "tests/controller.test.js" },
            :view             => { :source => "view.php" },
            :js               => { :source => "logic.js" },
            :js_view          => { :source => nil, :dest => "view.ejs" },
            :test_js          => { :source => "base.test.js", :dest => "tests/base.test.js" },
            # Always generated
            :info             => { :source => "info.yml" },
            :changelog        => { :source => "changelog.yml" },
            :base_css         => { :source => "base.css" },
            :presentation_css => { :source => "base.css", :dest => File.expand_path(File.dirname(__FILE__)).split('/cp/').first + "/cp/webfiles/assets/themes/standard/widgetCss/#{name}.scss" },
            :test             => { :source => "base.test", :dest => "tests/base.test" },
        }

        options.merge!({ :test => true, :info => true, :changelog => true, :base_css => true, :presentation_css => true })
        options[:test_js] = true if options[:js]
        @options = options
    end

    def create_files
        File.umask(0)

        create_subfolders

        @meta.each do |key, value|
            if @options[key]
                result = render_file(value[:source], value[:dest] || value[:source])
            else
                result = "Didn't create a #{value[:source] || value[:dest]} file"
            end

            yield result if block_given?
        end
    end

    private

    def render_file(input_path, output_path)
        @widget_info ||= {
            :cxReleaseNumber  => @installer.cx_version,
            :currentDate      => @currentDate,
            :widgetName       => @name,
            :hasJS            => @options[:js],
            :extendsFrom      => @options[:extend].is_a?(WidgetToExtendFrom) ? {
                :widget  => @options[:extend].subpath,
                :version => @options[:extend].version,
            } : nil,
            :widgetPath       => @widget_folder.split('/').slice(-2, 2).join('/'),
            :framework        => @installer.framework_version,
        }

        output = !input_path.nil? ? ERB.new(File.read("#{@templates}/#{input_path}.erb")).result(OpenStruct.new(@widget_info).instance_eval { binding }) : ''
        path = (output_path.start_with?('/')) ? output_path : "#{@widget_folder}/#{output_path}"
        File.open(path, 'w') { |f| f.write(output) }

        output_path
    end

    def create_subfolders
        @folders.map {|dir| "#{@widget_folder}/#{dir}"}.each do |folder|
            FileUtils.mkdir_p folder
        end
    end
end

class Installer
    def initialize(name, path_to_widget)
        @name = name
        @folder = path_to_widget.split('/')[-2..-1].join('/')
        @cp_root = path_to_widget[0..path_to_widget.index('cp/') + 2]
    end

    def modify_widget_versions
        write_yaml("#{@cp_root}customer/development/widgetVersions") do |widget_versions|
            widget_versions["#{@folder}/#{@name}"] = "current"
            widget_versions
        end
    end

    def modify_cp_history
        write_yaml("#{@cp_root}core/cpHistory") do |history|
            history['widgetVersions']["#{@folder}/#{@name}"] = {
                '1.0.1' => {
                    'requires' => {
                        'framework' => [framework_version]
                    }
                }
            }
            history
        end
    end

    def framework_version
        @framework_version ||= begin
            full_framework_version.split('.')[0..1].join('.')
        end
    end

    def full_framework_version
        @full_framework_version ||= begin
            YAML.load(IO.read("#{@cp_root}core/framework/manifest"))['version']
        end
    end

    def cx_version
        File.read("#{@cp_root}core/cpHistory").match(/"(.*)": #{Regexp.escape(full_framework_version)}/)[1] rescue "Fill in CX version"
    end

    private
    def write_yaml(file)
        yaml = YAML.load_file(file)
        raise "Couldn't load the YAML in #{file}" if !yaml

        yaml = yield(yaml)

        File.open(file, 'w') { |out| out.write(conform_yaml(yaml)) }
    end

    def conform_yaml(yaml)
        yaml = YAML.dump(yaml)

        yaml.gsub!("'", '"')
        yaml.gsub!(/\s\n/, "\n")
        yaml += "...\n"

        yaml
    end
end

class Prompter
    def ask(question, binary_question = false)
        output(question, binary_question)
        response = get_response

        begin
            response = yield(response) if block_given?
        rescue Exception => e
            puts Color.error(e.message)
            exit
        end

        if binary_question
            response = response[0].downcase == 'y'
        end

        response
    end

    private

    def output(question, binary_question)
        question = Color.colorize(question)
        question += " [y/n]" if binary_question
        puts question
    end

    def get_response
        begin
            response = Readline.readline("> ", true)
        rescue Exception
            # ctrl-C
            puts Color.error("\nWell goodbye then")
            exit
        end

        response = '' if response.nil? # ctrl-D
        response.strip!

        if response == ''
            puts Color.error("You didn't answer the question")
            exit
        end

        response
    end
end

class Interviewer
    def initialize
        @prompt = Prompter.new
        @options = {}
        @name, @folder = ''
    end

    def get_location
        @folder = @prompt.ask("What **folder** (e.g. input)?") do |folder|
            # verify category folder exists
            parent_dir = Dir.pwd.split('cp/')[0] + "cp/core/widgets/standard/#{folder}"
            raise "#{parent_dir} isn't a directory" if !File.directory?(parent_dir)

            parent_dir
        end

        @name = @prompt.ask("Widget **name**?") do |name|
            # verify pascal case
            raise "#{name} must be PascalCased" if name.match(/^[A-Z][a-z]+(?:[A-Z][a-z]+)*$/).nil?

            name
        end
    end

    def get_details
        @options[:extend] = @prompt.ask("Does this widget **extend** from another widget?", true)

        if @options[:extend] == true
            @options[:extend] = @prompt.ask("Folder and name of widget being extended from (e.g. input/TextInput):") do |extend_from|
                # verify extends from
                widget = WidgetToExtendFrom.new(extend_from)

                if !widget.exists?
                    raise "#{extend_from} isn't valid"
                end

                widget
            end
        end

        @options[:controller_test] = @options[:controller] = @prompt.ask("Have a **controller**?", true)
        @options[:view] = @prompt.ask("Have a **view**?", true)
        @options[:js] = @prompt.ask("Have **JavaScript**?", true)
        if @options[:js]
            @options[:js_view] = @prompt.ask("Have a **JS view**?", true)
        end
    end

    def tell_creator
        installer = Installer.new(@name, @folder)
        creator = Creator.new(@name, @folder, installer, @options)
        creator.create_files do |file|
            puts Color.colorize("_#{file}_")
        end

        begin
            installer.modify_widget_versions
            installer.modify_cp_history
            puts "#{@name} has been created and installed. Happy coding!"
            puts "＼(＾O＾)／＼(＾O＾)／＼(＾O＾)／"
        rescue => er
            puts Color.error("#{@name} has been created, but couldn't be installed.")
            puts Color.error(er.message)
            server = `uname -n`
            puts "Change the permissions and re-run or hit <http://yoursite.#{server.strip}.rightnowtech.com/ci/admin/internalTools/addStandardWidget/#{File.basename(@folder)}/#{@name}>"
        end
    end
end

interviewer = Interviewer.new
interviewer.get_location
interviewer.get_details
interviewer.tell_creator
