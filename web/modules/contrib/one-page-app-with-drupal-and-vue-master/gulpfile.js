var gulp = require('gulp');
var livereload = require('gulp-livereload')
var uglify = require('gulp-uglifyjs');
var sass = require('gulp-sass');
var autoprefixer = require('gulp-autoprefixer');
var sourcemaps = require('gulp-sourcemaps');
var imagemin = require('gulp-imagemin');
var pngquant = require('imagemin-pngquant');
sass.compiler = require('node-sass');

gulp.task('imagemin', function () {
    return gulp.src('./themes/custom/d6site/images/*') 
        .pipe(imagemin({
            progressive: true,
            svgoPlugins: [{removeViewBox: false}],
            use: [pngquant()]
        }))
        .pipe(gulp.dest('./themes/custom/d6site/images'));
});
gulp.task('sass', function () {
    return gulp.src('./sass/**/*.scss')
        .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(gulp.dest('./css'));
});
gulp.task('uglify', function() {
  gulp.src('./themes/custom/d6site/lib/*.js')
    .pipe(uglify('main.js'))
    .pipe(gulp.dest('./themes/custom/d6site/js'))
});
gulp.task('watch', function(){
    livereload.listen();
    gulp.watch('./sass/**/*.scss', gulp.series('sass'));
    gulp.watch('./lib/*.js', gulp.series('uglify'));
    gulp.watch(['/css/style.css', './**/*.twig', './js/*.js'], function (files){
        livereload.changed(files)
    });
});
