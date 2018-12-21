'use strict';

const autoprefixer = require('autoprefixer');

module.exports = [{
  context: __dirname,
  entry: ['./sass/app.scss', './js/app.js'],
  devServer: {
    port: 9100,
    publicPath: '/profiles/contrib/argue/themes/argue_base',
    proxy: {
      '/': {
        target: 'http://arg_inst.docker.amazee.io/',
        changeOrigin: true
      }
    }
  },
  output: {
    filename: './assets/argue_theme.js'
  },
  module: {
    rules: [
      {
        test: /\.scss$/,
        use: [
          {
            loader: 'file-loader',
            options: {
              name: './assets/argue_theme.css'
            }
          },
          { loader: 'extract-loader' },
          { loader: 'css-loader' },
          {
            loader: 'postcss-loader',
            options: {
              plugins: () => [autoprefixer()]
            }
          },
          {
            loader: 'sass-loader',
            options: {
              includePaths: ['./node_modules']
            }
          }
        ]
      },
      {
        test: /\.js$/,
        loader: 'babel-loader',
        query: {
          presets: ['es2015'],
          plugins: ['transform-object-assign']
        }
      }
    ]
  }
}];