module.exports = function(grunt) {
	var target = grunt.option('target') || '8ch';
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		stylus: {
			compile: {
				options: {
					import: ['nib', '../lib/mixins'],
				},
				files: {
					'./wPaint.min.css': ['./lib/wColorPicker.min.css', './src/wPaint.css']
				}
			}
		},
		concat: {
			my_target: {
				files: {
					'./wPaint.min.js': ['./lib/rgbHex.js', './lib/keymaster.js', './lib/wColorPicker.js', './src/wPaint.utils.js', './src/wPaint.js'],
					'./plugins/main/main.js': ['./plugins/main/src/wPaint.menu.main.js', './plugins/main/src/fillArea.min.js'],
					'./plugins/text/text.js': ['./plugins/text/src/wPaint.menu.text.js'],
					'./plugins/shapes/shapes.js': ['./plugins/shapes/src/wPaint.menu.main.shapes.js', './plugins/shapes/src/shapes.min.js'],
					'./plugins/file/file.js': ['./plugins/file/src/wPaint.menu.main.file.js'],
					'./lib/plugins.js': ['./plugins/main/main.js', './plugins/text/text.js', './plugins/shapes/shapes.js', './plugins/file/file.js'],
					'./2ch.js': ['./locale/ja_JP.js', './lib/gettext.js', './lib/jquery.1.10.2.min.js', './lib/jquery-ui.custom.min.js', './wPaint.min.js', './lib/plugins.js', './src/oekaki8.js'],
					'./8ch.js': ['./wPaint.min.js', './lib/plugins.js'],
				}
			}
		},
		uglify: {
			my_target: {
				options: { /* Very important for Shift-JIS support! */
					ASCIIOnly: true
				},
				files: {
					'./oekaki.js': [target+'.js']
				}
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-stylus');
	grunt.loadNpmTasks('grunt-contrib-uglify');

	var tasks = ['stylus', 'concat', 'uglify'];
  
	grunt.registerTask('default', tasks);
};
