const webpack = require('@cypress/webpack-preprocessor');
const Billmate = require('billmate');

const getPaymentInfo = (number) => {
    const bm = new Billmate('17882', '165964686216',{ test: true});
    return bm.getPaymentinfo(number);
   };

module.exports = on => {
    const options = {
        webpackOptions: require('../../webpack.config'),
        watchOptions: {}
    }

    on('file:preprocessor', webpack(options));
    on('task', {
        paymentInfo (number) {
            return getPaymentInfo(number);
        }
    });
}