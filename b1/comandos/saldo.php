<?php

$saldo = (string)number_format ($user ['saldo'], 2);

if ($tlg->Callback_ID () !== null){

	$tlg->answerCallbackQuery ([
		'callback_query_id' => $tlg->Callback_ID ()
	]);

}

$tlg->sendMessage ([
	'chat_id' => $tlg->ChatID (),
	'text' => "💲 Seu saldo disponível: <code>R\${$saldo}</code>",
	'parse_mode' => 'html',
	'reply_markup' => $tlg->buildInlineKeyboard ([[$tlg->buildInlineKeyBoardButton ('Recarregar Conta', null, '/recarregar')]])
]);