<?php
#################################################################################
##                                                                             ##
##                                                                             ##
## --------------------------------------------------------------------------- ##
##                                                                             ##
##  Project:       TATAR WARS                                                  ##
##  Version:       2012.3.15                                                   ##
##  License:       Creative Commons BY-NC-SA 3.0                               ##
##  Copyright:     Bazaid (c) 2012 - All rights reserved                       ##
##  Source code:   https://github.com/Bazaid/tatar-wars                        ##
##                 http://sourceforge.net/projects/tatarwars/                  ##
#################################################################################


require( ".".DIRECTORY_SEPARATOR."app".DIRECTORY_SEPARATOR."boot.php" );
require_once( MODEL_PATH."payment.php" );
class GPage extends WebService
{

    public function load( )
    {
        $AppConfig = $GLOBALS['AppConfig'];
        if ( $this->isPost( ) )
        {
            $usedPackage = NULL;
            foreach ( $AppConfig['plus']['packages'] as $package )
            {
                if ( $package['cost'] == $_POST['amount'] )
                {
                    $usedPackage = $package;
                }
            }
            $merchant_id = $AppConfig['plus']['payments']['cashu']['merchant_id'];
            $usedPayment = NULL;
            foreach ( $AppConfig['plus']['payments'] as $payment )
            {
                if ( $payment['merchant_id'] == $merchant_id )
                {
                    $usedPayment = $payment;
                }
            }
            if ( !isset( $_GET[$usedPayment['returnKey']] ) )
            {
                return;
            }
            if ( $usedPackage != NULL && $usedPayment != NULL && $_POST['token'] == md5( sprintf( "%s:%s:%s:%s", $merchant_id, $_POST['amount'], strtolower( $_POST['currency'] ), $_POST['test_mode'] ? $usedPayment['testKey'] : $usedPayment['key'] ) ) )
            {
                $playerId = base64_decode( $_POST['session_id'] );
                $goldNumber = $usedPackage['gold'];
                $m = new PaymentModel( );
                $m->incrementPlayerGold( $playerId, $goldNumber );
                $m->dispose( );
                echo "<h2 style=\"color:#00ff00;\">success</h2>";
            }
            else
            {
                echo "<h2 style=\"color:#ff0000;\">failed</h2>";
            }
        }
    }

}

$p = new GPage( );
$p->run( );
?>
