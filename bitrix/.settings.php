<?php

return array (
  'cache_flags' => 
  array (
    'value' => 
    array (
      'config_options' => 3600,
    ),
    'readonly' => false,
  ),
  'cookies' => 
  array (
    'value' => 
    array (
      'secure' => false,
      'http_only' => true,
    ),
    'readonly' => false,
  ),
  'exception_handling' => 
  array (
    'value' => 
    array (
      'debug' => false,
      'handled_errors_types' => 4437,
      'exception_errors_types' => 4437,
      'ignore_silence' => false,
      'assertion_throws_exception' => true,
      'assertion_error_type' => 256,
     'log' => array(
        'class_name' => 'Diag\\FileExceptionHandlerLogCustom',
        'required_file' => 'php_interface/src/Diag/FileExceptionHandlerLogCustom.php',
        'settings' => array(
          'file' => '/local/logs/exceptions.log',
          'log_size' => 1000000,
        ),
      ),
    ),
    'readonly' => false,
  ),
  'connections' => 
  array (
    'value' => 
    array (
      'default' => 
      array (
        'host' => 'localhost',
        'database' => 'cb523223_bitrix',
        'login' => 'cb523223_bitrix',
        'password' => 'JWnw1Vh7',
        'options' => 2,
        'className' => '\\Bitrix\\Main\\DB\\MysqliConnection',
      ),
    ),
    'readonly' => true,
  ),
  'crypto' => 
  array (
    'value' => 
    array (
      'crypto_key' => '0558542c3b34a3baeed206584f5f8900',
    ),
    'readonly' => true,
  ),
  'messenger' => 
  array (
    'value' => 
    array (
      'run_mode' => 'web',
      'brokers' => 
      array (
        'default' => 
        array (
          'type' => 'db',
          'params' => 
          array (
            'table' => 'Bitrix\\Main\\Messenger\\Internals\\Storage\\Db\\Model\\MessengerMessageTable',
          ),
        ),
      ),
      'queues' => 
      array (
      ),
    ),
    'readonly' => true,
  ),
);
