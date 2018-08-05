const path = require('path');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');

module.exports = {
    entry: {
        main: '/app/assets/js/index.js'
    },
    output: {
        filename: './assets/js/[hash].js',
        path: path.resolve(__dirname, '../public')
    },
    plugins: [
        new CleanWebpackPlugin(
            [
                '/app/public/assets',
                '/app/src/View/common/webpackAssets.html'
            ],
            {
                root: path.resolve(__dirname, './..')
            }
        ),
        new HtmlWebpackPlugin({
            text: 'webpack generated',
            template: './assets/template/index.html',
            filename: '../src/View/common/webpackAssets.html'
        })
    ],
    module: {
        rules: [
            {
                test: /\.(png|svg|jpg|gif)$/,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: '/assets/images/[hash:7].[ext]'
                        }
                    }
                ]
            },
            {
                test: /\/app\/assets\/sass\/([a-z])+\.scss$/,
                use: [
                    'style-loader',
                    'css-loader',
                    'sass-loader'
                ]
            }
        ]
    }
};
