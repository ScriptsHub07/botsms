<?php

function getInfos($codigo_servico){
	$servicos = json_decode(file_get_contents ('estaticos/servicos.json'), true);
	foreach($servicos as $json){
		if(stristr($json['serviceName'], $codigo_servico)){
			return $json;
		}
	}
	return "erro";
}

// encerra carregamento do botão de callback
$tlg->answerCallbackQuery ([
	'callback_query_id' => $tlg->Callback_ID ()
]);

if (isset ($complemento)){
	$pais = $user['pais'];
	$codigo_servico = $complemento;
	$servico = getInfos($codigo_servico);
	$dados_servico = json_decode ($api_sms->getPrices($pais, $codigo_servico), true)[$pais];

	// dados do serviço solicitado
	$nome_real = @ucfirst ($servico['lang']);
	$valor_real = @$dados_servico [$codigo_servico]['cost'];
	$quantidade_real = @$dados_servico [$codigo_servico]['count'];
	$valor_fixado = $servico['price'][$pais];

	if($valor_fixado != null && $valor_fixado != 0){
		$valor_sms = $valor_fixado;
	}else{
		$valor_sms = valorSMS ($valor_real, PORCENTAGEM_LUCRO);
	}

	// formatar número
	$valor_sms = number_format($valor_sms, 2);

	if ($user ['saldo'] <= 0){
		$botoes [][] = $tlg->buildInlineKeyBoardButton ('Recarregar Conta', null, "/recarregar");
	}

	$botoes [][] = $tlg->buildInlineKeyBoardButton ('Receber SMS', null, "/confirmar {$codigo_servico}");
	$botoes [][] = $tlg->buildInlineKeyBoardButton ('Operadoras', null, "/operadora {$codigo_servico}");
	$botoes [][] = $tlg->buildInlineKeyBoardButton ('🔙', null, "/servicos");

	$notas = [
		"<em><b>Nota:</b> Novos números são adicionados durante o dia.</em>",
		"<em><b>Nota:</b> Aproveite, o reenvio de sms no mesmo número é grátis.</em>",
		"<em><b>Nota:</b> Gostou do bot? Indique aos seus amigos, agradecemos.</em>",
		"<em><b>Nota:</b> Somos inegavelmente o melhor bot de sms do Telegram!</em>",
		"<em><b>Nota:</b> Os valores variam de acordo com o país, use /paises</em>",
		"<em><b>Nota:</b> Evite abusos você pode ser penalizado com desconto no saldo e block :)</em>",
		"<em><b>Nota:</b> Quando não tiver números disponíveis use o comando /alertas</em>",
	];

	$tlg->editMessageText ([
		'chat_id' => $tlg->ChatID (),
		'text' => "Pais: <b>".PAISES [$user ['pais']]."</b>\nServiço: <b>$nome_real</b>\nValor: R$ {$valor_sms}\n\n🔹 <b>{$quantidade_real}</b> números disponíveis!\n\n{$notas [mt_rand (0, (count ($notas)-1))]}",
		'message_id' => $tlg->MessageID (),
		'parse_mode' => 'html',
		'disable_web_page_preview' => 'true',
		'reply_markup' => $tlg->buildInlineKeyboard ($botoes)
	]);

}
