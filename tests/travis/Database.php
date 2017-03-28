<?php

$dbconfig = [

	'mysqli' => [
		'DSN'          => '',
		'hostname'     => 'localhost',
		'username'     => 'travis',
		'password'     => '',
		'database'     => 'satoripop',
		'DBDriver'     => 'MySQLi',
		'DBPrefix'     => '',
		'pConnect'     => false,
		'DBDebug'     => (ENVIRONMENT !== 'production'),
		'cacheOn'     => false,
		'cacheDir'     => '',
		'charset'      => 'utf8',
		'DBCollat'     => 'utf8_general_ci',
		'swapPre'      => '',
		'encrypt'      => false,
		'compress'     => false,
		'strictOn'     => false,
		'failover'     => [],
		'saveQueries' => true,
	],

    'postgres' => [
	    'DSN'          => '',
	    'hostname'     => 'localhost',
	    'username'     => 'postgres',
	    'password'     => '',
	    'database'     => 'satoripop',
	    'DBDriver'     => 'Postgre',
	    'DBPrefix'     => '',
	    'pConnect'     => false,
	    'DBDebug'     => (ENVIRONMENT !== 'production'),
	    'cacheOn'     => false,
	    'cacheDir'     => '',
	    'charset'      => 'utf8',
	    'DBCollat'     => 'utf8_general_ci',
	    'swapPre'      => '',
	    'encrypt'      => false,
	    'compress'     => false,
	    'strictOn'     => false,
	    'failover'     => [],
	    'saveQueries' => true,
    ]

];
