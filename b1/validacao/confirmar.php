<?php

@list ($codigo_servico, $operadora) = @explode (' ', $complemento);

check_block ($codigo_servico);

function getInfos($codigo_servico){
	$servicos = json_decode(file_get_contents ('estaticos/servicos.json'), true);
	foreach($servicos as $json){
		if($json['serviceName'] == $codigo_servico){
			return $json;
		}
	}
}

// encerra carregamento do botão de callback
$tlg->answerCallbackQuery ([
	'callback_query_id' => $tlg->Callback_ID (),
	'text' => 'processando...'
]);

$pais = $user['pais'];
$servicos = json_decode (file_get_contents ('estaticos/servicos.json'), true);
$dadosAPI = json_decode ($api_sms->getPrices ($pais, $codigo_servico), true)[$pais];

// dados do serviço solicitado
$fileInfo = getInfos($codigo_servico);
$nome_real = ucfirst ($fileInfo['lang']);
$valor_fixado = $fileInfo['price'][$pais];
$valor_real = $dadosAPI[$codigo_servico]['cost'];

// valor fina após a conversão
if($valor_fixado !== null && $valor_fixado != 0){
	$valor_final = $valor_fixado;
}else{
	$valor_final = valorSMS ($valor_real, PORCENTAGEM_LUCRO);
}

if ($user ['saldo'] < $valor_final){
	$tlg->editMessageText ([
		'chat_id' => $tlg->ChatID (),
		'text' => "Você não tem saldo suficiente, recarregue sua conta ou ganhe saldo como /afiliado",
		'message_id' => $tlg->MessageID (),
		'parse_mode' => 'html',
		'reply_markup' => $tlg->buildInlineKeyboard ([
			[$tlg->buildInlineKeyBoardButton ('Recarregar Conta', null, '/recarregar')]
		])
	]);

}else {

	// pega um número e manda para o usuário
	$get_numero = $api_sms->getNumber ($codigo_servico, $user ['pais'], null, $operadora);

	if ($get_numero == 'NO_NUMBERS' || $get_numero == 'NO_BALANCE'){

		$tlg->editMessageText ([
			'chat_id' => $tlg->ChatID (),
			'text' => "<b>Nenhum número encontrado para esse serviço, tente novamente com o comando /servicos</b>",
			'parse_mode' => 'html',
			'message_id' => $tlg->MessageID (),
			'reply_markup' => $tlg->buildInlineKeyboard ([
				[$tlg->buildInlineKeyBoardButton ('Tentar Novamente', null, "/sms {$codigo_servico}")],
				[$tlg->buildInlineKeyBoardButton ('🔙', null, "/servicos")]
			])
		]);

		if ($get_numero == 'NO_BALANCE' || $api_sms->getBalance () < 200){

			// cache notificação
			if (!$redis->exists ('cache-saldo')){

				$tlg->sendMessage ([
					'chat_id' => ADMS [0],
					'text' => "Sua conta está com o saldo abaixo de 200₽, recarregue!!"
				]);

				// cria cache
				$redis->setEx ('cache-saldo', 600, 'true');

			}

		}

	}else {

		// ACCESS_NUMBER:376681682:5562993063325
		@list (, $id, $numero) = @explode (':', $get_numero);

		if (empty ($id) || empty ($numero)){

			$tlg->editMessageText ([
				'chat_id' => $tlg->ChatID (),
				'text' => "😢 <em>Não foi possivel gerar um número para você, tente novamente com /servicos</em>",
				'parse_mode' => 'html'
			]);

		}else {

			$tlg->editMessageText ([
				'chat_id' => $tlg->ChatID (),
				'text' => "<b>📨 Recebimento de SMS / Código</b>\n\n<b>📞 | Número temporário:</b>    <code>+{$numero}</code>\n\n<b>📲 | Serviço:</b> <code>{$nome_real}</code>\n\n<b>🏁 | País do Número:</b><code>".PAISES [$user ['pais']]."</code>\n<b>🕒 | Tempo:</b>  20 minutos\n\n<b>🔹 | Codigo:</b> <em>Aguardando sms...</em>\n\n<b>💡Observação:</b> Você pode receber quantos SMS's quiser do(a) {$nome_real} durante 20 minutos. Seu saldo é capturado enquanto esse pedido está ativo e caso nenhum SMS seja recebido durante esses 20 minutos o valor do serviço será devolvido.",
				'parse_mode' => 'html',
				'message_id' => $tlg->MessageID (),
				'reply_markup' => $tlg->buildInlineKeyboard ([
					[$tlg->buildInlineKeyBoardButton ('❗', null, "/info")],
					[$tlg->buildInlineKeyBoardButton ('Cancelar SMS', null, "/cancelar {$id}")]
				])
			]);
			
			$tlg->sendMessage ([
					'chat_id' => CHAT_ID_NOTIFICACAO,
					'text' => "Alguem acaba de pedir um numero!\n\nPais: ".PAISES [$user ['pais']]."\nServiço:   📲{$nome_real}"
			]);
			// adiciona sms na lista de processos para sere verificados
			$processos->setProcesso ($id, [
				'id_sms' => $id,
				'id_usuario' => $tlg->UserID (),
				'message_id' => $tlg->MessageID (),
				'time_criacao' => time (),
				'codigo_servico' => $codigo_servico,
				'nome_servico' => $nome_real,
				'valor' => $valor_final,
				'segundo_sms' => false,
				'numero' => $numero,
				"descontado" => false,
				"visualizado" => false
			]);

			$api_sms->setStatus (1, $id); // preparado para receber o sms

		}

	}

}