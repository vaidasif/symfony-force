module.exports = function(grunt) {

  'use strict';

  require('time-grunt')(grunt);
  require('load-grunt-tasks')(grunt);
  grunt.option('force', true);

  grunt.registerTask('build', ['clean', 'concat', 'less', 'copy']);
  grunt.registerTask('dev', ['build', 'watch']);
  grunt.registerTask('release', ['build', 'removelogging', 'uglify', 'shell:release']);
  grunt.registerTask('default', ['build']);

  var isRelease = false;
  grunt.cli.tasks.forEach(function (ts) {
    if (ts === 'release') {
      isRelease = true;
    }
  });

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    version: '<%= pkg.name %>-<%= pkg.version %>',
    banner: '/**\n' +
      ' * <%= pkg.name %> - v<%= pkg.version %>\n' +
      ' *\n' +
      ' * <%= pkg.description %>\n' +
      ' */\n',

    src: {
      js: ['assets/js/**/*.js'],
      less: ['assets/less/app.less']
    },

    clean: ['web/js/*', 'web/css/*', 'web/fonts/*', 'web/img/*'],

    concat: {
      js: {
        options: {
          banner: "<%= banner %>"
        },
        src: ['<%= src.js %>'],
        dest: 'web/js/<%= version %>.js'
      },
      layout: {
        src: ['app/Resources/views/layout.dist.html.twig'],
        dest: 'app/Resources/views/layout.html.twig',
        options: {
          process: true
        }
      },
      vendor_js: {
        src: [
          'assets/vendor/jquery/dist/jquery.js',
          'assets/vendor/bootstrap/dist/js/bootstrap.js'
        ],
        dest: 'web/js/libraries.js'
      }
    },

    copy: {
      fonts: {
        files: [
          { dest: 'web/fonts/', cwd: 'assets/fonts/', src: '**', expand: true},
          { dest: 'web/fonts/', cwd: 'assets/vendor/bootstrap/dist/fonts/', src: '**', expand: true}
        ]
      },
      images: {
        files: [
          { dest: 'web/img/', cwd: 'assets/img/', src: '**', expand: true}
        ]
      },
      jq_map: {
        src: 'assets/vendor/jquery/dist/jquery.min.map',
        dest: 'web/js/jquery.min.map'
      }
    },

    less: {
      all: {
        options: {
          strictImports : true,
          compress: isRelease,
          sourceMap: !isRelease,
          outputSourceFiles: !isRelease,
          sourceMapURL: "<%= version %>.css.map"
        },
        files: {
          'web/css/<%= version %>.css': '<%= src.less %>'
        }
      }
    },

    uglify: {
      app: {
        options: {
          banner: "<%= banner %>"
        },
        src: ['<%= concat.js.dest %>'],
        dest: '<%= concat.js.dest %>'
      },
      vendor: {
        src: ['<%= concat.vendor_js.dest %>'],
        dest: '<%= concat.vendor_js.dest %>'
      }
    },

    removelogging: {
      app: {
        src: "<%= concat.js.dest %>",
        dest: "<%= concat.js.dest %>"
      }
    },

    shell: {
      release: {
        command: [
          'composer install --no-scripts --no-dev',
          'composer dump-autoload --optimize',
          'bin/archive ansible/frontend.tar.gz',
          'composer install'
        ].join('&&')
      }
    },

    watch: {
      js: {
        files: ['<%= src.js %>'],
        tasks: ['concat:js']
      },
      less: {
        files: ['assets/less/**/*.less'],
        tasks: ['less:dev'],
        options: { livereload: true }
      },
      images: {
        files: ['assets/img/**/*'],
        tasks: ['copy:images']
      },
      layout: {
        files: ['<%= concat.layout.src %>'],
        tasks: ['concat:layout']
      }
    }

  });
};
