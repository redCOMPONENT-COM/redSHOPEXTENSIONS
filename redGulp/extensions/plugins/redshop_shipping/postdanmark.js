var gulp = require('gulp');
var del = require('del');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');

var group = 'redshop_shipping';
var name = 'postdanmark';

var baseTask = 'plugins.' + group + '.' + name;
var extPath = './plugins/' + group + '/' + name;

const helper = require('../../helpers/plugin');
releasePlugin(group, name);

gulp.task('compressjs:' + baseTask, function () {
    gulp.src(extPath + '/includes/js/functions-uncompressed.js')
        .pipe(uglify())
        .pipe(rename('functions.js'))
        .pipe(gulp.dest(extPath + '/includes/js/'));

    gulp.src(extPath + '/includes/js/map_functions-uncompressed.js')
        .pipe(uglify())
        .pipe(rename('map_functions.js'))
        .pipe(gulp.dest(extPath + '/includes/js/'));
});