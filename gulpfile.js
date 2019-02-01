var gulp = require('gulp');

// Plugins
var autoprefixer = require('gulp-autoprefixer');
var minifyCSS = require('gulp-minify-css');
var rename = require('gulp-rename');
var uglifyEs = require('gulp-uglify-es').default;
var watch = require('gulp-watch');

// Directories
var dir_assets = 'modules/*/assets/',
    dir_js = dir_assets + 'js/',
    dir_css = dir_assets + 'css/';

/**
 * TASK: styles
 */
var style_source_files = [
    dir_css + '*.css',
    '!' + dir_css + '*.min.css',
];

function styles() {
    return gulp.src(style_source_files)
        .pipe(rename(function(path) {
            path.extname = '.min.css';
        }))
        .pipe(autoprefixer({
            browsers: ['last 2 versions'],
            cascade: false
        }))
        .pipe(minifyCSS({compatibility: 'ie8'}))
        .pipe(gulp.dest('modules/'));
}

gulp.task('styles', styles);

/**
 * TASK: scripts
 */
var script_source_files = [
    dir_js + '*.js',
    '!' + dir_js + '*.min.js',
];

function scripts() {
    return gulp.src(script_source_files)
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
    gulp.watch(style_source_files, gulp.series('styles'));
    gulp.watch(script_source_files, gulp.series('scripts'));
}

gulp.task('watch', watch_changes);

/**
 * TASK: default
 */
gulp.task('default', gulp.parallel(styles, scripts));