/**
 * NOTICE OF LICENSE
 *
 * Licensed under the MonsTec Prestashop Module License v.1.0
 *
 * With the purchase or the installation of the software in your application
 * you accept the license agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 * @author    Monstec UG (haftungsbeschränkt)
 * @copyright 2019 Monstec UG (haftungsbeschränkt)
 * @license   LICENSE.txt
 */

var gulp = require('gulp'),
    environments = require('gulp-environments'),
    noop = require('gulp-noop'),
    fs = require('fs'),
    preprocess = require("gulp-preprocess"),
    log = require('fancy-log'),
    gchanged = require('gulp-changed'),
    merge = require('merge-stream'),
    plumber = require('gulp-plumber'),
    livereload = require('gulp-livereload'),
    sass = require('gulp-sass'),
    autoprefixer = require('autoprefixer'),
    cssnano = require('cssnano'),
    postcss = require('gulp-postcss'),
    jshint = require('gulp-jshint'),
    stripDebug = require('gulp-strip-debug'),
    uglify = require('gulp-uglify'),
    rename = require('gulp-rename'),
    concat = require('gulp-concat'),
    sourcemaps = require('gulp-sourcemaps'),
    del = require('del'),
    chalk = require('chalk'),
    babel = require('gulp-babel'),
    gap = require('gulp-append-prepend'),
    lec = require('gulp-line-ending-corrector'),
    zip = require('gulp-zip');


// *************************** //
// *** Build configuration *** //
// *************************** //

var moduleName = 'produck';

var paths = {
    "source": {
        "mainfiles": "src/php",
        "php": "src/php/classes",
        "control": "src/php/controllers",
        "templ": "src/views/**/*",
        "css": ['src/css/**/*.scss', 'src/css/**/*.css'],
        "js": "src/js/**/*",
        "img": "resources/img/**/*",
        "static": {
            "base": "resources/static/base",
            "index": "resources/static/index"
        }
    },
    "build": {
        "root": "build",
        "php": "build/classes",
        "control": "build/controllers",
        "templ": "build/views",
        "css": "build/views/css",
        "js": "build/views/js",
        "img": "build/views/img",
        "maps": "/maps/" // this directory is meant for use with sourcemaps which uses a relative path
    },
    "dist": {
        "root": "dist",
        "base": "dist/" + moduleName,
        "php": "dist/" + moduleName + "/classes",
        "control": "dist/" + moduleName + "/controllers",
        "templ": "dist/" + moduleName + "/views",
        "js": "dist/" + moduleName + "/views/js",
        "css": "dist/" + moduleName + "/views/css",
        "img": "dist/" + moduleName + "/views/img"
    }
};

var licenseHeader = '/**\n'
                    + '  * NOTICE OF LICENSE\n'
                    + '  *\n'
                    + '  * Licensed under the MonsTec Prestashop Module License v.1.0\n'
                    + '  *\n'
                    + '  * With the purchase or the installation of the software in your application\n'
                    + '  * you accept the license agreement.\n'
                    + '  *\n'
                    + '  * You must not modify, adapt or create derivative works of this source code\n'
                    + '  *\n'
                    + '  * @author    Monstec UG (haftungsbeschränkt)\n'
                    + '  * @copyright 2019 Monstec UG (haftungsbeschränkt)\n'
                    + '  * @license   LICENSE.txt\n'
                    + '  */\n';

var formatError = chalk.redBright;
var formatWarning = chalk.keyword('orange');

var errorHandlerFunction = function (err) {
    log(formatError(err));
    this.emit('end');
};

// activate production build by passing '--env production' to gulp
var production = environments.production;
var development = environments.development;
var enableDebug = false;

var deploymentDir = process.env.PRESTASHOP_MODULES_DIR;
var doDeployment = checkDeploymentDir(deploymentDir);

// environment variable for preprocess context
var preprocessConfig = {};
if (production()) {
    preprocessConfig.context = { ENV: production.$name, DEBUG: enableDebug };
} else {
    preprocessConfig.context = { ENV: development.$name, DEBUG: enableDebug };
}


// ************************************ //
// *** Definition of task functions *** //
// ************************************ //

/*
 * Php sources are 'preprocess'ed and then placed into the build directory.
 */
function processPhp(baseDestDir, classesDestDir, controllerdestDir, deploy, dist) {
    var classesStream  = gulp.src(paths.source.php + '/**/*')
        .pipe(preprocess(preprocessConfig))
        .pipe(dist ? lec({verbose:false, eolc: 'LF', encoding:'utf8'}) : noop())
        .pipe(gulp.dest(classesDestDir))
        .pipe((deploy ? gulp.dest(getCorrespondingDeploymentDir(classesDestDir)) : noop()));

    var controllerStream = gulp.src(paths.source.control + '/**/*')
        .pipe(preprocess(preprocessConfig))
        .pipe(dist ? lec({verbose:false, eolc: 'LF', encoding:'utf8'}) : noop())
        .pipe(gulp.dest(controllerdestDir))
        .pipe((deploy ? gulp.dest(getCorrespondingDeploymentDir(controllerdestDir)) : noop()));

    var mainStream = gulp.src(paths.source.mainfiles + '/*.*')
        .pipe(preprocess(preprocessConfig))
        .pipe(dist ? lec({verbose:false, eolc: 'LF', encoding:'utf8'}) : noop())
        .pipe(gulp.dest(baseDestDir))
        .pipe((deploy ? gulp.dest(getCorrespondingDeploymentDir(baseDestDir)) : noop()));

    return addReloadBehaviour(merge(classesStream, controllerStream, mainStream));
}

// for build task
function processPhpBuild() {
    return processPhp(paths.build.root, paths.build.php, paths.build.control, doDeployment, false);
}

// for distribution task
function processPhpDist() {
    return processPhp(paths.dist.base, paths.dist.php, paths.dist.control, false, true);
}

/*
 * Css ans Sass processing for all modules.
 */
function processStyles(destDir, deploy) {
    var postCssPlugins = [
        autoprefixer({ browsers: ['last 2 version'] }),
        cssnano()
    ];

    var stream = gulp.src(paths.source.css)
        .pipe(plumber(errorHandlerFunction))
        .pipe(!production() ? sourcemaps.init() : noop())
        .pipe(sass({ errLogToConsole: false }))
        // autoprefixer currently breaks sourcemaps according to the gulp-sourcemaps-wiki, unless
        // used in conjunction with postcss. The configuration of postcss resides in package.
        .pipe(postcss(postCssPlugins))
        .pipe(rename({ suffix: '.min' }))
        .pipe(!production() ? sourcemaps.write(paths.build.maps).on('error', log) : noop())
        .pipe(gap.prependText(licenseHeader))
        .pipe(gulp.dest(destDir))
        .pipe((deploy ? gulp.dest(getCorrespondingDeploymentDir(destDir)) : noop()))
        .pipe(plumber.stop());

    return addReloadBehaviour(stream);
}

// for build task
function processStylesBuild() {
    return processStyles(paths.build.css, doDeployment);
}

// for distribution task
function processStylesDist() {
    return processStyles(paths.dist.css, false);
}

/*
 * Javascript processing for all modules.
 */
function processScripts(destDir, deploy, distribute) {
    var jsFileName = moduleName + ".js";

    var stream = gulp.src(paths.source.js)
        .pipe(plumber(errorHandlerFunction))
        .pipe(preprocess(preprocessConfig))
        .pipe(!production() ? sourcemaps.init() : noop())
        .pipe(jshint('.jshintrc'))
        .pipe(jshint.reporter('default'))
        .pipe(concat(jsFileName))
        .pipe(babel({ presets: ['env'] }))
        // in production and in the distribution archive there is no need for the non-minified version
        .pipe((!production() && !distribute) ? gulp.dest(destDir) : noop())
        .pipe((!production() && deploy) ? gulp.dest(getCorrespondingDeploymentDir(destDir)) : noop())
        .pipe(rename({ suffix: '.min' }))
        .pipe(production() ? stripDebug() : noop())
        .pipe(uglify())
        .pipe(!production() ? sourcemaps.write(paths.build.maps).on('error', log) : noop())
        .pipe(gap.prependText(licenseHeader))
        .pipe(gulp.dest(destDir))
        .pipe(deploy ? gulp.dest(getCorrespondingDeploymentDir(destDir)) : noop())
        .pipe(plumber.stop());

    return addReloadBehaviour(stream);
}

// for build task
function processScriptsBuild() {
    return processScripts(paths.build.js, doDeployment, false);
}

// for distribution task
function processScriptsDist() {
    return processScripts(paths.dist.js, false, true);
}

/*
 * View templates are just copied into the build directory.
 */
function copyTemplates(destDir, deploy) {
    var stream = gulp.src(paths.source.templ)
        .pipe(gulp.dest(destDir))
        .pipe((deploy ? gulp.dest(getCorrespondingDeploymentDir(destDir)) : noop()));

    return addReloadBehaviour(stream);
}

// for build task
function copyTemplatesBuild() {
    return copyTemplates(paths.build.templ, doDeployment);
}

// for distribution task
function copyTemplatesDist() {
    return copyTemplates(paths.dist.templ, false);
}

/*
 * Images are just copied into the build directory.
 */
function copyChangedImages(destDir, deploy) {
    var stream = gulp.src(paths.source.img)
        // only process changed images; this should save time for consecutive runs of this task or 'default'
        .pipe(gchanged(destDir))
        .pipe(gulp.dest(destDir))
        .pipe((deploy ? gulp.dest(getCorrespondingDeploymentDir(paths.build.img)) : noop()));

    return addReloadBehaviour(stream);
}

// for build task
function copyChangedImagesBuild() {
    return copyChangedImages(paths.build.img, doDeployment);
}

// for distribution task
function copyChangedImagesDist() {
    return copyChangedImages(paths.dist.img, false);
}

/*
 * Files in the base directory (especially main-php-file and logo-image) of a module are just copied
 * into the build directory.
 */
function copyStaticBaseDirFiles(destDir, deploy) {
    var stream = gulp.src(paths.source.static.base + '/*.*')
        .pipe(gulp.dest(destDir))
        .pipe((deploy ? gulp.dest(getCorrespondingDeploymentDir(destDir)) : noop()));

    return addReloadBehaviour(stream);
}

// for build task
function copyStaticBaseDirFilesBuild() {
    return copyStaticBaseDirFiles(paths.build.root, doDeployment);
}

// for distribution task
function copyStaticBaseDirFilesDist() {
    return copyStaticBaseDirFiles(paths.dist.base, false);
}

/*
 * Creates a zip archive for each module that can be installed via Prestashop's back office.
 */
function createDistributionArchive() {
    // The base folder of the src-directive must be changed so that gulp-zip includes the base directory of the
    // particular module. This is necessary because Prestashop requires the module-zip to contain this base directory
    // and not only the files in it. Otherwise it is not even possible to install the module.
    var stream = gulp.src(paths.dist.base + '/**/*', {base:paths.dist.base + '/..'})
        .pipe(zip(moduleName + '.zip'))
        .pipe(gulp.dest(paths.dist.root));

    return merge(stream);
}

/*
 * Prestashop requires a file called 'index.php' (with a particular content) in EVERY folder.
 */
function createIndexFiles(startDir) {
    var content = '<?php\n'
                  + licenseHeader;
                  + '\n'
                  + 'header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");\n'
                  + 'header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");\n'
                  + 'header("Cache-Control: no-store, no-cache, must-revalidate");\n'
                  + 'header("Cache-Control: post-check=0, pre-check=0", false);\n'
                  + 'header("Pragma: no-cache");\n'
                  + 'header("Location: [loc]");\n'
                  + 'exit;';

    function createIndexFile(currentDir, loc) {
        // write the index file (replacing any existing one)
        fs.writeFileSync(currentDir + '/index.php', content.replace('[loc]', loc));
        fs.chmodSync(currentDir + '/index.php', 0o755);

        fs.readdirSync(currentDir).forEach(function(file) {
            var currentPath = currentDir + '/' + file;
            var stat = fs.statSync(currentPath);

            if (stat.isDirectory()) {
                // recursively create the index.php-file in subdirectories
                createIndexFile(currentPath, loc + '../');
            }
        });
    }

    createIndexFile(startDir, '../');
}

// for build task
function createIndexFilesBuild(done) {
    createIndexFiles(paths.build.root, '../');
    if (doDeployment) createIndexFiles(getCorrespondingDeploymentDir(paths.build.root), '../');
    done();
}

// for distribution task
function createIndexFilesDist(done) {
    createIndexFiles(paths.dist.base, '../');
    done();
}

/*
 * Removes all files built during a previous run of any build task defined in this file.
 */
function clean() {
    // Note for this task: It does not work to chain del operations like
    //   var delResult = del(['build/**/*', 'dist/**/*']);
    //   return delResult.then(del(['build', 'dist']));
    // see https://stackoverflow.com/questions/51404088/how-does-gulp-treat-the-promise-of-a-then
    //
    // If later more del-calls have to be used then using del.sync() might be a solution. Using that
    // and the done-callback of gulp a task without a stream could be written using synchronous deletion
    // and controlled flow.
    return del(['build/**', 'dist/**']);
}

/*
 * Start watching source files for automatic build initiation.
 */
function watch() {
    // Start the live reload server that will trigger a reload in the browser when the watch task detects any
    // changes. For this to work a corresponding plugin has to be installed in the browser.
    livereload.listen();

    // Watch css and sass files
    gulp.watch(paths.source.css, processStylesBuild);

    // Watch javascript code
    gulp.watch(paths.source.js, processScriptsBuild);

    // Watch php files
    gulp.watch([paths.source.mainfiles + '/**/*'], processPhpBuild);

    // Watch templates
    gulp.watch(paths.source.templ, copyTemplatesBuild);

    // Watch images
    gulp.watch(paths.source.img, copyChangedImagesBuild);

    //Watch module's base directory
    gulp.watch(paths.source.static.base, copyStaticBaseDirFilesBuild);
}


// *************************************** //
// *** Definition of actual gulp tasks *** //
// *************************************** //

gulp.task('styles', processStylesBuild);
gulp.task('scripts', processScriptsBuild);
gulp.task('php', processPhpBuild);
gulp.task('templates', copyTemplatesBuild);
gulp.task('images', copyChangedImagesBuild);
gulp.task('static', copyStaticBaseDirFilesBuild);

gulp.task('clean', clean);
gulp.task('watch', watch);

gulp.task('default', gulp.series('clean', logDeploymentStatus,
                                 gulp.parallel('php', 'scripts', 'styles', 'templates', 'images', 'static'),
                                 createIndexFilesBuild));

gulp.task('dist', gulp.series('clean', logDeploymentStatus,
                              gulp.parallel(processPhpDist, processScriptsDist, processStylesDist, copyTemplatesDist,
                                            copyChangedImagesDist, copyStaticBaseDirFilesDist),
                                            createIndexFilesDist, createDistributionArchive));


// ************************************** //
// *** Definition of Helper functions *** //
// ************************************** //

function logDeploymentStatus(done) {
    log("Deployment will " + ((!doDeployment) ? "not " : "") + "be conducted.");
    done();
}

// This function will check if a deployment directory is defined (i.e. the environment variable
// PRESTASHOP_MODULES_DIR is set and has a valid value). If the check is successful it will return true or false
// otherwise.
function checkDeploymentDir(targetDir) {
    // check if the environment variable exists and does have a value
    if (targetDir === undefined || targetDir === '') {
        log(formatWarning('The environment variable PRESTASHOP_MODULES_DIR is not set. Deployment to Prestashop will not take place.'));
        return false;
    }

    // check if the directory defined in PRESTASHOP_MODULES_DIR exists and is writeable for the current process
    try {
        fs.accessSync(targetDir, fs.constants.W_OK);
    } catch (err) {
        log(formatWarning(err.message));
        log(formatWarning('Deployment to webserver will not take place.'));
        return false;
    }

    return true;
}

// Determines the exact file system position in the deployment directory that corresponds to a given
// build directory. This function is used to "convert" a build directory into a deployment directory by
// replacing the "build"-part in the value of the argument.
function getCorrespondingDeploymentDir(buildDir) {
    // For the sake of uniformity replace all backslashes in the defined deployment directory with slashes
    var prefix = deploymentDir.replace(/\\/g, '/');

    // If there is no trailing slash add one so that result can be concatenated with the module name
    prefix = prefix + (!prefix.endsWith('/') ? '/' : '');

    // all files have to be deployed in a subdirectory so change the deployment directory accordingly
    prefix += moduleName;

    // Remove the build-directory part from the given buildDir by replacing it with the deployment directory.
    var result = buildDir.replace(paths.build.root, prefix);
    return result;
}

// give your gulp stream to this method if you want to use the livereload plugin in your task
function addReloadBehaviour(stream) {
    stream.on('end', function () {
        livereload.reload();
    });

    return stream;
}
