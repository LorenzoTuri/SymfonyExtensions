const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const { VueLoaderPlugin } = require('vue-loader')

module.exports = {
    entry: {
        index: require('path').resolve(__dirname, '../index.js'),
        login: require('path').resolve(__dirname, '../login.js')
    },

    output: {
        path: require('path').resolve(__dirname, '../../public/'),
        filename: '[name].bundle.js'
    },

    module: {
        rules: [{
            enforce: 'pre',
            test: /\.(js|vue)$/,
            loader: 'eslint-loader',
            exclude: /node_modules/,
            options: {
                fix: true,
                emitError: true,
                emitWarning: true,
            },
        }, {
            test: /\.vue$/,
            use: [
                'vue-loader',
                'ts-loader'
            ]
        }, {
            test: /\.[s]?css$/,
            use: [
                MiniCssExtractPlugin.loader,
                'css-loader',
                'sass-loader'
            ]
        }, {
            test: /\.ts$/,
            loader: 'ts-loader',
            exclude: /node_modules/,
            options: {
                appendTsSuffixTo: [/\.vue$/]
            }
        }]
    },
    resolve: {
        extensions: ['.ts', '.js', '.vue', '.scss', '.css'],
    },
    plugins: [
        new VueLoaderPlugin(),
        new MiniCssExtractPlugin()
    ],
};