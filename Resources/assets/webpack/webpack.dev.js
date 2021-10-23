const { merge } = require('webpack-merge');
const common = require('./webpack.common.js');

module.exports = merge(common, {
    mode: 'development',
    devtool: "source-map",
    resolve: {
        alias: {
            'vue$': 'vue/dist/vue.esm.js'
        }
    }
});