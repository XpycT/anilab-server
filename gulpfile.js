var gulp = require('gulp');
var rename = require('gulp-rename');
var elixir = require('laravel-elixir');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Less
 | file for our application, as well as publishing vendor resources.
 |
 */
/**
 * Copy any needed files.
 *
 * Do a 'gulp copyfiles' after bower updates
 */
gulp.task("copyfiles", function() {
    //jquery
    gulp.src("vendor/bower_dl/jquery/dist/jquery.js")
        .pipe(gulp.dest("resources/assets/js/"));
    //bootstrap
    gulp.src("vendor/bower_dl/bootstrap-sass/assets/stylesheets/**")
        .pipe(gulp.dest("resources/assets/sass/bootstrap"));

    gulp.src("vendor/bower_dl/bootstrap-sass/assets/javascripts/bootstrap.js")
        .pipe(gulp.dest("resources/assets/js/"));

    gulp.src("vendor/bower_dl/bootstrap-sass/assets/fonts/**")
        .pipe(gulp.dest("public/assets/fonts"));
    //font awesome
    gulp.src("vendor/bower_dl/fontawesome/scss/**")
        .pipe(gulp.dest("resources/assets/sass/fontawesome"));

    gulp.src("vendor/bower_dl/fontawesome/fonts/**")
        .pipe(gulp.dest("public/assets/fonts"));
    //datatables
    var dtDir = 'vendor/bower_dl/datatables-plugins/integration/';

    gulp.src("vendor/bower_dl/datatables/media/js/jquery.dataTables.js")
        .pipe(gulp.dest('resources/assets/js/'));

    gulp.src(dtDir + 'bootstrap/3/dataTables.bootstrap.css')
        .pipe(rename('dataTables.bootstrap.scss'))
        .pipe(gulp.dest('resources/assets/sass/others/'));

    gulp.src(dtDir + 'bootstrap/3/dataTables.bootstrap.js')
        .pipe(gulp.dest('resources/assets/js/'));

});

elixir(function(mix) {
    // Combine scripts
    mix.scripts([
            'js/jquery.js',
            'js/bootstrap.js',
            'js/jquery.dataTables.js',
            'js/dataTables.bootstrap.js'
        ],
        'public/assets/js/admin.js',
        'resources/assets'
    );
    mix.sass('app.scss', 'public/assets/css/admin.css');
});
