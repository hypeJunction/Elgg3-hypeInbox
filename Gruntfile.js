module.exports = function (grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		// Watches files for changes and runs tasks based on the changed files
		watch: {
			bower: {
				files: ['bower.json']
			},
			composer: {
				files: ['composer.json'],
				tasks: ['composer:dev:update']
			},
			compass: {
				files: ['./sass/{,*/}*.{scss,sass}'],
				tasks: ['compass:dev']
			},
			gruntfile: {
				files: ['Gruntfile.js']
			}
		},
		compass: {
			release: {
				options: {
					sassDir: 'sass/',
					cssDir: 'views/default/css/framework/',
					imagesDir: 'graphics/',
					javascripts: 'views/default/js/framework/',
					fonts: 'fonts/',
					outputStyle: 'compact',
					environment: 'production',
					force: true
				}
			},
			dev: {
				options: {
					sassDir: 'sass/',
					cssDir: 'views/default/css/framework/',
					imagesDir: 'graphics/',
					javascripts: 'views/default/js/framework/',
					fonts: 'fonts/',
					outputStyle: 'expanded',
					environment: 'development',
					debugInfo: true,
					force: true
				}
			}
		},
		composer: {
			dev: {
				options: {}
			},
			release: {
				options: {
					flags: ['no-dev']
				}
			},
		},
		copy: {
			release: {
				src: ['**',
					'!node_modules/**',
					'!Gruntfile.js',
					'!package.json',
					'!composer.*',
					'!bower.*',
					'!.*',
					'!sass/**',
					'!tests/**',
					'!phpunit.xml',
					'!nbproject/**',
					'!config.rb',
					'!releases/**'
				],
				dest: 'release/',
			}
		},
		// Clean the release folder
		clean: {
			release: {
				src: ['release/']
			}
		},
		// Compress the release folder into an upload-ready zip file
		compress: {
			release: {
				options: {
					archive: 'releases/<%= pkg.version %>.zip'
				},
				cwd: 'release/',
				src: ['**/*'],
				//dest: '<%= pkg.name %>/'
			}
		},
		bower: {
			install: {
				dev: {
					options: {
						bowerOptions: {
							production: true
						}
					}
				},
				release: {
					options: {
						bowerOptions: {
							production: true
						}
					}
				}
			}
		},
		phpunit: {
			test: {
				color: true,
				verbose: true,
				logJson: true,
				coverageClover: true,
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-compass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-version');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-compress');
	grunt.loadNpmTasks('grunt-composer');
	grunt.loadNpmTasks('grunt-bower-task');
	grunt.loadNpmTasks('grunt-phpunit');

	grunt.registerTask('createPluginManifest', function () {
		var pkg = grunt.file.readJSON('package.json')
		var js2xmlparser = require('js2xmlparser');
		var manifest = {
			"@": {
				"xmlns": "http://www.elgg.org/plugin_manifest/1.8",
			},
			'id': pkg.name || null,
			'name': pkg.name || null,
			'author': pkg.author.name + '(' + pkg.author.email + ')' || null,
			'version': pkg.version || null,
			'description': pkg.description || null,
			'license': pkg.license || nul,
			'copyright': pkg.copyright || null,
		};

		var config = pkg.config.plugin_manifest;
		for (var index in config) {
			if (config.hasOwnProperty(index)) {
				var attr = config[index];
				manifest[index] = attr;
			}
		}

		var xml = js2xmlparser('plugin_manifest', manifest);
		return grunt.file.write('manifest.xml', xml, {'encoding': 'utf8'});
	});

	grunt.registerTask('init', ['createPluginManifest', 'bower:install:dev', 'composer:dev:update', 'compass:dev']);
	grunt.registerTask('default', ['watch']);
	grunt.registerTask('release', [
		'bower:install:release',
		'composer:release:update',
		'createPluginManifest',
		'clean:release',
		'copy:release',
		'compress:release',
		'clean:release'
	]);
	grunt.registerTask('test', ['phpunit']);

	/** @todo: add Test task **/
};