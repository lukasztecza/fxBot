const path = require('path');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');

module.exports = {
    entry: {
        main: '/vagrant/src/Assets/js/index.js'
    },
    output: {
        filename: './assets/js/[hash].js',
        path: path.resolve(__dirname, 'public')
    },
    plugins: [
        new CleanWebpackPlugin([
            '/vagrant/public/assets',
            '/vagrant/src/View/common/webpackAssets.html'
        ]),
        new HtmlWebpackPlugin({
            text: 'webpack generated',
            template: './src/Assets/template/index.html',
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
                test: /\/vagrant\/src\/Assets\/sass\/([a-z])+\.scss$/,
                use: [
                    'style-loader',
                    'css-loader',
                    'sass-loader'
                ]
            }
        ]
    }
};
