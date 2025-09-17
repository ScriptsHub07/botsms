<?php


$pais = $user['pais'];
$servicos = json_decode ($api_sms->getPrices ($pais), true);
$lista_servicos = $servicos [$pais];
$dados_servico = json_decode (file_get_contents ('estaticos/servicos.json'), true); // info


// cria visualização para serviços personalizados dentro do serviço OUTROS
$lista_servicos = servicosPersonalizados ($lista_servicos, $dados_servico);

@natsort ($dados_servico);

if (empty ($lista_servicos)){

	$tlg->sendMessage ([
		'chat_id' => $tlg->ChatID (),
		'text' => "Não foi encontrado nenhum serviço para esse pais, use /paises para trocar",
		'parse_mode' => 'html'
	]);

}else {
	$langs = [];
	foreach($dados_servico as $s){
		$langs[strtolower($s['lang'])] = $s;
	}
	ksort($langs);

	foreach ($langs as $chave => $servico){

		$id_servico = $servico['serviceName'];
		if (!isset ($lista_servicos [$id_servico]['cost'])){
			continue;
		}

		// troca id dos serviços personalizados pelo id do serviço Outros
		if (strpos ($id_servico, 'pers_') !== false){
			$id_servico = 'ot';
		}

		$valor_real = $lista_servicos [$id_servico]['cost'];
		$quantidade = $lista_servicos [$id_servico]['count'] ?? 0;
		$valor_fixado = $servico['price'][$pais];

		if($valor_fixado != null && $valor_fixado != 0){
			$valor_sms = $valor_fixado;
		}else{
			$valor_sms = valorSMS ($valor_real, PORCENTAGEM_LUCRO);
		}

		$nome = "{$servico['lang']} - R$ {$valor_sms}";
		$comando = "/sms {$id_servico}";

		$botoes [$i][] = $tlg->buildInlineKeyBoardButton ($nome, null, $comando);

		if (count ($botoes [$i]) == 2){
			$i++;
		}

	}

	if ($tlg->Callback_ID () !== null){

		$tlg->answerCallbackQuery ([
			'callback_query_id' => $tlg->Callback_ID ()
		]);

	}

	$tlg->sendMessage ([
		'chat_id' => $tlg->ChatID (),
		'text' => "País atual <u><b>".PAISES [$user ['pais']]."</b></u> mude com /paises\n\n<b>Escolha para qual serviço você deseja receber SMS:</b>",
		'reply_markup' => $tlg->buildInlineKeyBoard ($botoes),
		'parse_mode' => 'html'
	]);

}
