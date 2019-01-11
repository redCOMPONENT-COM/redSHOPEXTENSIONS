var gulp = require("gulp");

// Clean test site
gulp.task(
    "clean",
    [
        "clean:libraries",
        "clean:modules",
        "clean:packages",
        "clean:plugins"
    ], function () {
        return true;
    });