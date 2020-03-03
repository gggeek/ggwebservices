<?php
/**
 * @see also RegisterAllProtocolsFunctions in wsproviders.ini for an alternative
 *      way of declaring your webservices once and making them available via
 *      different protocols.
 *      In fact here we do it a different way for the single reason that we want
 *      the services defined in
 *      to be active for jsonrpc even when RegisterAllProtocolsFunctions is off
 */

include_once( 'extension/ggwebservices/xmlrpc/common.php' );
