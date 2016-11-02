/**
 * Created by neil on 13/08/2016.
 */
module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        autoprefixer: {
            options: {
                browsers: ['> 5%', 'last 2 versions', 'ie 11']
            },
            dist: {
                files: {
                    'build/css/prefix-main.css': 'css/main.css',
                    'build/css/prefix-admin-theme.css': 'css/admin_theme.css'
                }
            }
        },
        watch: {
            styles: {
                files: ['css/main.css'],
                tasks: ['autoprefixer']
            }
        },
        browserSync: {
            dev: {
                bsFiles: {
                    src: ["wp-content/themes/islemag-child/style.css"]
                          
                },
                options: {
                    proxy: "http://127.0.0.1:8888"
                }
            }
        }
    });
    
    grunt.loadNpmTasks('grunt-autoprefixer');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-browser-sync');
    grunt.registerTask('default', ['browserSync']);
};
