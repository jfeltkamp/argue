const Fontagon = require('fontagon')

Fontagon({
  files: [
    'iconfont/src/svg/**/*.svg'
  ],
  dist: 'iconfont/dist/',
  fontName: 'argue-icons',
  style: 'css',
  classOptions: {
    baseClass: 'argue-icon',
    classPrefix: 'arg'
  },
  styleTemplate: {
    css: 'iconfont/src/css.hbs',
    html: 'iconfont/src/html.hbs'
  },
  // html is not working
  html: true,
  htmlTemplate: 'iconfont/src/html.hbs',
  htmlDist: 'iconfont/dist/index.htm'
}).then((opts) => {
  console.log('done! ' ,opts)
}).catch((err) => {
  console.log('fail! ', err)
})
