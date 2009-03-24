<?php
/**
 * File containing the TestSuite class
 *
 * @author
 * @copyright
 * @license
 */

class ggwebservicesTestSuite extends ezpTestSuite
{
    public function __construct()
    {
        parent::__construct();
        $this->setName( "gg Webservices Extension Test Suite" );

        /// @todo...
    }

    public static function suite()
    {
        return new self();
    }
}

?>
