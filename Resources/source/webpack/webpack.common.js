const MiniCssExtractPlugin = require("mini-css-extract-plugin");
module.exports = {
    entry: require('path').resolve(__dirname, '../index.js'),

    output: {
        path: require('path').resolve(__dirname, '../../public/'),
        filename: 'base.js'
    },

    module: {
        rules: [{
            test: /\.html$/,
            use: [{
                loader: 'html-loader',
                options: {
                    minimize: true
                }
            }],
        }, {
            test: /\.scss$/,
            use: [
                MiniCssExtractPlugin.loader,
                'css-loader',
                'sass-loader'
            ]
        }]
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: "base.css"
        })
    ],
};