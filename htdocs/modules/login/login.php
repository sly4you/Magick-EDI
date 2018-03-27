<?php
// Start template
$mod_out = new Xtemplate(__DIR_TEMPLATES__ . '/login.html');
$mod_out->parse( 'login' );
ob_start();
$mod_out->out( 'login' );
$result = ob_get_contents();
ob_end_clean();
return $result;