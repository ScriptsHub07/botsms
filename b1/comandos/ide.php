<?php

$tlg->sendMessage ([
	'chat_id' => $tlg->ChatID (),
	'text' => "<b>Seu ID:</b> <code>{$tlg->UserID ()}</code>",
	'parse_mode' => 'html',
	'disable_web_page_preview' => true,
	'reply_markup' => $tlg->buildKeyBoard ([
		[$tlg->buildInlineKeyboardButton ('♻️Menu'),('🔥 Serviços')]
	], true, true)
]);

// afiliados
if (isset ($complemento) && is_numeric ($complemento) && STATUS_AFILIADO){

	$ref = $tlg->getUsuarioTlg ($complemento);

	// se usuario existir e não tiver entrado no bot por indicação de alguem e tambem não pode ser ele mesmo
	if (isset ($ref ['id']) && $bd_tlg->checkReferencia ($tlg->UserID ()) == false && $complemento != $tlg->UserID ()){

		// salva usuario atual como referencia do dono do link
		$bd_tlg->setReferencia ($complemento, $tlg->UserID ());

	}

}