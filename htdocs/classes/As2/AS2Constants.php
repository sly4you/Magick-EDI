<?php
// Magick Edi settings
require_once $_SERVER['DOCUMENT_ROOT'] . '/constants/constants_file.php';
require_once __DIR_CONFIG__ . 'configuration.php';

// AdoDb Framework
require_once __DIR_CLASSES__ . 'class.dboperation.php';

// files found in PEAR::Mail
require_once 'Mail/RFC822.php';
require_once 'Mail/mimeDecode.php';

// Horde framework common files
require_once __DIR_CLASSES__ .  'Horde/String.php';
require_once __DIR_CLASSES__ .  'Horde/Util.php';
require_once __DIR_CLASSES__ .  'Horde/MIME.php';
require_once __DIR_CLASSES__ .  'Horde/MIME/Part.php';
require_once __DIR_CLASSES__ .  'Horde/MIME/Message.php';
require_once __DIR_CLASSES__ .  'Horde/MIME/Structure.php';

// AS2Secure framework
require_once __DIR_CLASSES__ .  'As2/AS2Log.php';
require_once __DIR_CLASSES__ .  'As2/AS2Header.php';
require_once __DIR_CLASSES__ .  'As2/AS2Connector.php';
require_once __DIR_CLASSES__ .  'As2/AS2Partner.php';
require_once __DIR_CLASSES__ .  'As2/AS2Abstract.php';
require_once __DIR_CLASSES__ .  'As2/AS2Exception.php';
require_once __DIR_CLASSES__ .  'As2/AS2Adapter.php';
require_once __DIR_CLASSES__ .  'As2/AS2Client.php';
require_once __DIR_CLASSES__ .  'As2/AS2Message.php';
require_once __DIR_CLASSES__ .  'As2/AS2MDN.php';
require_once __DIR_CLASSES__ .  'As2/AS2Request.php';
require_once __DIR_CLASSES__ .  'As2/AS2Server.php';

define('AS2_AB_PATH', str_replace( 'htdocs', '/', $_SERVER['DOCUMENT_ROOT']));
define('AS2_DIR_CUSTOMERS', AS2_AB_PATH . 'customers/');
define('AS2_DIR_BIN', AS2_AB_PATH . 'bin/');
define('AS2_DIR_MESSAGES', AS2_AB_PATH . 'messages/');
define('AS2_DIR_LOGS',     AS2_AB_PATH . 'logs/');
define('AS2_DIR_PARTNERS', AS2_AB_PATH . 'partners/');
