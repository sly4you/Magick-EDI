<?php
/*#########################################################################
## Author: Enrico Valsecchi <admin@hostyle.it>
## Copyright 2002 by Mizar Solutions s.r.l.
## Info: info@mizasol.com
## Visit url: http://www.mizasol.com
## File Description: file di definizione delle directory
## Creation Date: 25/10/2002
## Last CVS Date: $Revision$ $Date$
#########################################################################*/
// Definizione del percorso assoluto
define ('__AB_PATH__', $_SERVER['DOCUMENT_ROOT'] . '/' );

define ('__DIR_CLASSES__', __AB_PATH__ . 'classes/');
define ('__DIR_CONFIG__', __AB_PATH__ . 'configuration/');
define ('__DIR_CONSTANTS__', __AB_PATH__ . 'constants/');
define ('__DIR_FUNCTIONS__', __AB_PATH__ . 'functions/');

define ('__DIR_IMAGES__', __AB_PATH__ . 'images/');
define ('__DIR_MODULES__', __AB_PATH__ . 'modules/');
define ('__DIR_TEMPLATES__', __AB_PATH__ . 'templates/');

define ('__DIR_CUSTOMER__', __AB_PATH__ . '../customers/');

define ('__EDIMAGICK_AB_PATH__', str_replace( 'htdocs', '', $_SERVER['DOCUMENT_ROOT']));
define ('__DIR_CUSTOMERS__', __EDIMAGICK_AB_PATH__ . '/customers/');

define ('__DIR_EDI__', __DIR_CLASSES__ . 'EdiTranslate/');
define ('__DIR_EDI_STANDARD_DATA__', __DIR_EDI__ . 'data/' );

define( '__DIR_MESSAGES__', 'where.you.want.have.customer.message.dir/customers/1/certs/' );
define( '__DIR_MESSAGES_INBOX__', __DIR_MESSAGES__ . 'Inbox/' );
define( '__DIR_MESSAGES_OUTBOX__', __DIR_MESSAGES__ . 'Outbox/' );
define( '__DIR_MESSAGES_UNSENT__', __DIR_MESSAGES__ . 'Unsend/');
?>