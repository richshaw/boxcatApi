<?php
date_default_timezone_set('America/New_York');

return array(
  'Slim' => array(
        'debug' => false,
        'mode' => 'dev',
        'cookies.encrypt' => true,
        'cookies.secret_key' => 'CHANGE_ME',
        'cookies.cipher' => MCRYPT_RIJNDAEL_256,
        'cookies.cipher_mode' => MCRYPT_MODE_CBC,
    ),
  'Mongo' => array(
        'uri' => 'mongodb://boxcat:boxcat@kahana.mongohq.com:10075/boxcat_alpha',
    ),
);
