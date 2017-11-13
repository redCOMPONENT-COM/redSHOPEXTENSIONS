const gulp   = require("gulp");
const zip    = require("gulp-zip");
const path   = require("path");
const fs     = require("fs");
const xml2js = require("xml2js");
const merge  = require("merge-stream");

var parser = new xml2js.Parser();

/**
 * Execute gulp to release an extension
 *
 * @param arraySrc
 * @param fileName
 * @param dest
 * @returns {*}
 */
global.releaseExt = function releaseExt(arraySrc, fileName, dest) {
    return gulp.src(arraySrc).pipe(zip(fileName)).pipe(gulp.dest(dest));
};

// Overwrite "release" method
gulp.task("release",
    [
        "release:plugin",
        "release:module"
    ]
);

gulp.task("release:languages", function () {
    const langPath   = "./src/lang";
    const releaseDir = path.join(config.releaseDir, "language");

    const folders = fs.readdirSync(langPath).map(function (file) {
        return path.join(langPath, file);
    }).filter(function (file) {
        return fs.existsSync(path.join(file, "install.xml"));
    });

    // We need to combine streams so we can know when this task is actually done
    return merge(folders.map(function (directory) {
            const data = fs.readFileSync(path.join(directory, "install.xml"));

            // xml2js parseString is sync, but must be called using callbacks... hence this awkwards vars
            // see https://github.com/Leonidas-from-XIV/node-xml2js/issues/159
            var task;
            var error;

            parser.parseString(data, function (err, result) {
                if (err) {
                    error = err;
                    console.log(err);

                    return;
                }

                const lang     = path.basename(directory);
                const version  = result.extension.version[0];
                const fileName = config.skipVersion ? result.extension.name + ".zip" : result.extension.name + "-v" + version + ".zip";

                task = gulp.src([directory + "/**"]).pipe(zip(fileName)).pipe(gulp.dest(releaseDir));
            });

            if (error) {
                throw error;
            }

            if (!error && !task) {
                throw new Error("xml2js callback became suddenly async or something.");
            }

            return task;
        })
    );
});
