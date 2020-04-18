'use strict';

const autoprefixer = require('autoprefixer');

module.exports = [{
  context: __dirname,
  entry: ['./sass/app.scss', './js/app.js'],
  mode: 'production',
  devServer: {
    port: 8080,
    publicPath: 'https://[::1]:8080/profiles/contrib/argue/themes/argue_base/dist',
    proxy: {
      '/': {
        target: 'https://demo.arguepro.de.ddev.site',
        "*": "http://[::1]:8080",
        changeOrigin: true,
        secure: false
      }
    },
    allowedHosts: [
      'demo.arguepro.de.ddev.site'
    ]
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
              // Prefer Dart Sass
              implementation: require('sass'),
              sassOptions: {
                includePaths: ['./node_modules'],
              },
            },
          }
        ]
      },
      {
        test: /\.js$/,
        loader: 'babel-loader',
        query: {
          presets: ['@babel/preset-env']
        }
      }
    ]
  }
}];
