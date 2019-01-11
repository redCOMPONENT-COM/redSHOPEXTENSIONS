var gulp       = require("gulp");

// Copy to test site
gulp.task(
    'copy',
    [
        'copy:libraries',
        'copy:modules',
        'copy:packages',
        'copy:plugins'
    ], function() {
        return true;
    });