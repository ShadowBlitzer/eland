var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enableSassLoader()
    .autoProvidejQuery()
    .addEntry('app', './assets/js/app.js')
    .createSharedEntry('vendor', [
        'jquery',
        'bootstrap',
        'font-awesome/scss/font-awesome.scss',
        'bootstrap/scss/bootstrap.scss',
        './assets/css/offcanvas.scss',
        './assets/js/base.js'
    ])
    .enablePostCssLoader()
;

module.exports = Encore.getWebpackConfig();
