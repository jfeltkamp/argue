var gulp            = require('gulp'),
  sequence        = require('gulp-sequence'),
  concat          = require('gulp-concat'),
  sass            = require('gulp-sass'),
  sourcemaps      = require('gulp-sourcemaps'),
  prefixer        = require('gulp-autoprefixer'),
  readlineSync    = require('readline-sync'),
  browserSync     = require('browser-sync').create();



gulp.task('sass', function ()
{
  if(process.argv[3] === '--' + 'dev' || process.argv[2] === 'watch')
  {
    return gulp
      .src('./sass/app.scss')
      .pipe(sourcemaps.init())
      .pipe(sass({
        includePaths: ['./node_modules/']
      }))
      .pipe(prefixer({
        browsers: ['last 2 versions', 'IE 11'],
        cascade: false
      }))
      .pipe(sourcemaps.write())
      .pipe(concat('argue_theme.css'))
      .pipe(gulp.dest('./assets'))
      .pipe(browserSync.stream());
  }
  else
  {
    return gulp
      .src('./sass/app.scss')
      .pipe(sass({
        includePaths: ['./node_modules/'],
        outputStyle: 'compressed'
      }))
      .pipe(prefixer({
        browsers: ['last 2 versions', 'IE 11'],
        cascade: false
      }))
      .pipe(concat('argue_theme.css'))
      .pipe(gulp.dest('./assets'));
  }
});

gulp.task('watch', function ()
{
  browserSync.init({
    proxy: 'argue.org.ddev.site'
  });

  gulp.watch('./sass/**/*', ['sass']);
  // .on('change', browserSync.reload);

  //gulp.watch('./js/**')
  //  .on('change', browserSync.reload);

  // basic shared tpls
  gulp.watch('./templates/**')
    .on('change', browserSync.reload);
});


gulp.task('default', function(callback) {
  sequence('sass')(callback);
});