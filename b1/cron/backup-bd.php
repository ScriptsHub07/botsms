<?php

include __DIR__.'/../includes/includes.php';

$tlg = new Telegram ('1883156590:AAELhFAy448FrXWI1owR1QQe1Jwb1OwVCGk');

print_r ($tlg->sendDocument ([
	'chat_id' => -1001459522082,
	'caption' => "Backup\n@vkmds\n".date ('d/m/Y H:i:s'),
	'document' => curl_file_create (__DIR__.'/../recebersmsbot.db')
]));