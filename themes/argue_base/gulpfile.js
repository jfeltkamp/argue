var gulp            = require('gulp'),
  concat          = require('gulp-concat'),
  sass            = require('gulp-sass')(require('sass')),
  cleanCSS        = require('gulp-clean-css'),
  sourcemaps      = require('gulp-sourcemaps'),
  prefixer        = require('gulp-autoprefixer'),
  browserSync     = require('browser-sync').create();



gulp.task('sass', function ()
{
  if(process.argv[3] === '--' + 'dev' || process.argv[2] === 'watch')
  {
    return gulp
      .src('./sass/**/*.scss')
      .pipe(sass({
        includePaths: ['node_modules']
      }))
      .pipe(prefixer({
        cascade: false
      }))
      .pipe(gulp.dest('./css'))
      .pipe(browserSync.stream());
  }
  else
  {
    return gulp
      .src('./sass/**/*.scss')
      .pipe(sass({
        includePaths: ['node_modules'],
        outputStyle: 'compressed'
      }))
      .pipe(prefixer({
        cascade: false
      }))
      .pipe(cleanCSS())
      .pipe(gulp.dest('./css'));
  }
});

gulp.task('watch', function ()
{
  browserSync.init({
    proxy: 'argue-test-inst.de.ddev.site'
  });

  gulp.watch('./sass/**/*', gulp.series('sass'));

  // basic shared tpls
  gulp.watch('./templates/**/*')
    .on('change', browserSync.reload);
});


gulp.task('default',  function (done) {
  gulp.series('sass');
  done();
});
