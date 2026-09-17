// The port must match `port` in .wp-env.json; the factory derives use.baseURL and webServer.port from it.
module.exports = require( '@a8csp/configs/node/playwright.config.base.js' )( {
	port: 8893,
} );
