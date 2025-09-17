<?php

if ($tlg->Callback_ID () !== null){
	
	// encerra carregamento do botão de callback
	$tlg->answerCallbackQuery ([
		'callback_query_id' => $tlg->Callback_ID ()
	]);

}

$valor_pagamentos = RECARGA_MINIMA;

$bonus = (BONUS == 0) ? '' : '<em><u>+'.BONUS.'% bônus</u></em>';
$valor_pagamento = $valor_pagamentos; 

if (isset($complemento)){

	// pega valor e tipo de calculo
	@list ($valor, $calculo) = explode (' ', $complemento);

	switch ($calculo) {
		case "+1":

		$valor_pagamento = ++$valor;

			break;

		case "+5":

		$valor_pagamento = $valor+5;

			break;

		case "+10":

		$valor_pagamento = $valor+10;

			break;

		case "-10":

		$valor_pagamento = $valor-10;

			break;

		case "-5":

		$valor_pagamento = $valor-5;
		
			break;
		
		case "-1":

		$valor_pagamento = --$valor;

			break;
	}

	// checagem de valor abaixo do mínimo
	if ($valor_pagamento < $valor_pagamentos){
		$valor_pagamento = $valor_pagamentos;
	}

}

$dados_mensagem = [
	'chat_id' => $tlg->ChatID (),
	'text' => "🔹 <b>Escolha Um Valor Para Recarregar.</b>\n\n💰 | <b>Valor:</b> R$ ".number_format ($valor_pagamento, 2)." {$bonus}\n\n(💡) <em>Selecione os botões abaixo para <b>diminuir</b> ou <b>aumenter</b> o valor que deseja.</em>",
	'parse_mode' => 'html',
	'message_id' => $tlg->MessageID (),
	'disable_web_page_preview' => 'true',
	'reply_markup' => $tlg->buildInlineKeyboard ([
		[
			$tlg->buildInlineKeyBoardButton ('- R$1', null, "/recarregar {$valor_pagamento} -1"),
			$tlg->buildInlineKeyBoardButton ('+ R$1', null, "/recarregar {$valor_pagamento} +1")
		],
		[
			$tlg->buildInlineKeyBoardButton ('- R$5', null, "/recarregar {$valor_pagamento} -5"),
			$tlg->buildInlineKeyBoardButton ('+ R$5', null, "/recarregar {$valor_pagamento} +5")
		],
		[
			$tlg->buildInlineKeyBoardButton ('- R$10', null, "/recarregar {$valor_pagamento} -10"),
			$tlg->buildInlineKeyBoardButton ('+ R$10', null, "/recarregar {$valor_pagamento} +10")
		],
		[$tlg->buildInlineKeyBoardButton ('💠 Pagar Pix 💠', null, "/comprar {$valor_pagamento}")],
		[$tlg->buildInlineKeyBoardButton ('💰Pagar Criptomoeda💰', null, "/coinbase {$valor_pagamento}")],
		[$tlg->buildInlineKeyBoardButton (' ⬅️ Voltar ⬅️', null, "/start")]
	])
];

$r = $tlg->editMessageText ($dados_mensagem);

if($r['ok'] == false){
	$tlg->sendMessage($dados_mensagem);
}
