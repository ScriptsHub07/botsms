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
$valor_real = $dadosAPI[$codigo_servico]['cost'];

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
				'text' => "Pais: <b>".PAISES [$user ['pais']]."</b>\nServiço: <b>{$nome_real}</b>\nNúmero: <code>+{$numero}</code>\n\nEspera: 20 minutos\nSTATUS: <em>Aguardando sms...</em>",
				'parse_mode' => 'html',
				'message_id' => $tlg->MessageID (),
				'reply_markup' => $tlg->buildInlineKeyboard ([
					[$tlg->buildInlineKeyBoardButton ('❗', null, "/info")],
					[$tlg->buildInlineKeyBoardButton ('Cancelar SMS', null, "/cancelar {$id}")]
				])
			]);
			
			$tlg->sendMessage ([
					'chat_id' => CHAT_ID_NOTIFICACAO,
					'text' => "⚡️Número Gerado\n📱Serviço: {$nome_real}\n🚩Pais: ".PAISES [$user ['pais']]."\n🆔Id usuario: {$tlg->UserID ()}"
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