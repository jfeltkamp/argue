var gulp            = require('gulp'),
  concat          = require('gulp-concat'),
  sass            = require('gulp-sass'),
  cleanCSS        = require('gulp-clean-css'),
  sourcemaps      = require('gulp-sourcemaps'),
  prefixer        = require('gulp-autoprefixer'),
  browserSync     = require('browser-sync').create();

sass.compiler   = require('sass');


gulp.task('sass', function ()
{
  if(process.argv[3] === '--' + 'dev' || process.argv[2] === 'watch')
  {
    return gulp
      .src('./sass/app.scss')
      .pipe(sass({
        includePaths: ['node_modules']
      }))
      .pipe(prefixer({
        cascade: false
      }))
      .pipe(concat('argue_gin.css'))
      .pipe(gulp.dest('./css'))
      .pipe(browserSync.stream());
  }
  else
  {
    return gulp
      .src('./sass/app.scss')
      .pipe(sass({
        includePaths: ['node_modules'],
        outputStyle: 'compressed'
      }))
      .pipe(prefixer({
        cascade: false
      }))
      .pipe(cleanCSS())
      .pipe(concat('argue_gin.css'))
      .pipe(gulp.dest('./css'));
  }
});

gulp.task('watch', function ()
{
  browserSync.init({
    proxy: 'demo.arguepro.de.ddev.site'
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
