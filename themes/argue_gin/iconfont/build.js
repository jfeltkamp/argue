
const Fontagon = require('fontagon')

Fontagon({
  files: [
    'iconfont/src/svg/**/*.svg'
  ],
  dist: 'iconfont/dist/',
  fontName: 'argue-icons',
  style: 'css',
  html: true,
  classOptions: {
    baseClass: 'argue-icons',
    classPrefix: 'arg'
  },
  styleTemplate: {
    css: 'iconfont/src/css.hbs',
    html: 'iconfont/src/html.hbs'
  },
  htmlTemplate: 'iconfont/src/html.hbs',
  htmlDist: 'iconfont/dist/index.htm'
}).then((opts) => {
  console.log('done! ' ,opts)
}).catch((err) => {
  console.log('fail! ', err)
})
