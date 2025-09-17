<?php

if ($tlg->Callback_ID () !== null){
	
	// encerra carregamento do botão de callback
	$tlg->answerCallbackQuery ([
		'callback_query_id' => $tlg->Callback_ID ()
	]);

}

$servicos = (string)number_format ($user ['servicos'], 2);

$saldo = (string)number_format ($user ['saldo'], 2);


$tlg->sendPhoto ([
	'chat_id' => $tlg->ChatID(),
	'photo' => 'https://www.linkpicture.com/q/WhatsApp-Image-2023-02-27-at-17.19.55.jpeg',
	'caption' => "😀 <b>Olá ".htmlentities ($tlg->FirstName ())." Seja bem-vindo (a)!</b>, \n<b>👨‍💻Eu Sou Um Bot Feito Para Receber SMS's De Varios Serviços.</b>\n\n<b>🚩Pais Selecionado:</b> <b>".PAISES [$user ['pais']]."</b>\n💰Seu Saldo:<code>R\${$saldo}</code>\n\n<b>Aqui Você Poderá Gerar o Seu Número Temporario Para Receber SMS, Use os Comandos Abaixo:</b>",
	'parse_mode' => 'html',
	'reply_markup' => $tlg->buildInlineKeyboard ([[$tlg->buildInlineKeyBoardButton ('⚡️Mostrar Serviços⚡️', null, '/servicos')], [$tlg->buildInlineKeyBoardButton ('🚩Escolher País🚩', null, '/paises')], [$tlg->buildInlineKeyBoardButton ('💹Saldo💹', null, '/saldo'), $tlg->buildInlineKeyBoardButton ('💸 Recarregar💸', null, '/recarregar')], [$tlg->buildInlineKeyBoardButton ('🎁Afiliados🎁', null, '/afiliados')], [$tlg->buildInlineKeyBoardButton ('⚡Sobre⚡', null, '/sobre'), $tlg->buildInlineKeyBoardButton ('🆘Ajuda🆘', null, '/info')]])
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
