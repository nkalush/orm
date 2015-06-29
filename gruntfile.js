module.exports = function(grunt) {

  grunt.initConfig({
    phpunit: {
        classes: {
            dir: 'tests'
        },
        options: {
            bin: 'vendor/bin/phpunit',
            bootstrap: './phpunit_bootstrap.php',
            colors: true
        }
    },
    watch: {
      phpunit: {
        files: [
          './src/*/*.php',
          './tests/*.php'
          ],
        tasks: ['phpunit:classes']
      },
    }
  });

  grunt.loadNpmTasks('grunt-phpunit');
  grunt.loadNpmTasks('grunt-contrib-watch');

  grunt.registerTask('default', ['phpunit']);
}