var gulp = require('gulp');

// Plugins
var rename = require('gulp-rename');
var uglifyEs = require('gulp-uglify-es').default;
var watch = require('gulp-watch');

// Directories
var dir_assets = 'modules/*/assets/',
    dir_js = dir_assets + 'js/';

/**
 * TASK: scripts
 */
function scripts() {
    return gulp.src(dir_js + 'scripts.js')
        .pipe(rename(function(path) {
            path.extname = '.min.js';
        }))
        .pipe(uglifyEs())
        .pipe(gulp.dest('modules/'));
}

gulp.task('scripts', scripts);

/**
 * TASK: watch
 *
 * Keep watching for changes in directories to automate tasks
 */

function watch_changes() {
    gulp.watch(dir_js + 'scripts.js', gulp.series('scripts'));
}

gulp.task('watch', watch_changes);

/**
 * TASK: default
 */
gulp.task('default', scripts);
