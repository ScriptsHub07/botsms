<?php
date_default_timezone_set('America/Sao_Paulo');


$ApiPix = new GerenciaNet([
	'client_id' => CLIENT_ID,
	'client_secret' => CLIENT_SECRET,
	'sandbox'=> false, // true para testes // false para transacoes reais
	'certificado' => CERTIFICADO_PRODUCAO_GN
]);

$tlg = new TelegramTools (TOKEN_BOT);
$bd_tlg = new bdTelegram ();
$redis = rDis::con ();

$ultimos_pagamento = $ApiPix->ConsultarListaCobranca ([
    'inicio' => date('Y-m-d\TH:m:i\Z', strtotime('-3 hours')),
    'fim' => date('Y-m-d\TH:m:i\Z', strtotime('+5 hours')),
    'paginacao.itensPorPagina' => 1000
]);

if (empty ($ultimos_pagamento ['cobs'])) exit;

foreach ($ultimos_pagamento ['cobs'] as $pagamento){

	$jsonFile = file_get_contents(__DIR__."/../estaticos/Pagamentos.json");
	$jsonPayments = json_decode($jsonFile, true);

	if (!isset($jsonPayments['Pagamentos'][$pagamento['txid']])) continue;

	if ($pagamento['status'] != 'CONCLUIDA') continue;

	$id_pagamento = $pagamento ['id'];
	$external_reference = $pagamento ['external_reference'];
	$id_telegram = substr ($external_reference, 0, -6);

	if (!empty ($bd_tlg->getResgate ($external_reference))) continue;

	$usuarioTlg = $tlg->getUsuarioTlg ($id_telegram); // usuario no telegram
	$usuarioBd = $bd_tlg->getUsuario ($id_telegram); // usuario no sistema/bd

	if (empty ($usuarioBd) || empty ($usuarioTlg)) continue;

	$valor_pagamento = $pagamento['valor']['original'];
	$valor_pago = $pagamento['pix'][0]['valor'];
	if ($valor_pago != $valor_pagamento) continue;
	$valor = incrementoPorcento ($valor_pagamento, BONUS);

	### SISTEMA AFILIADO ###

	// afiliados ativo e verifica se é o primeiro resgate desse usuario e se ele já foi indicado de alguem
	if (STATUS_AFILIADO && $bd_tlg->checkPrimeiroResgate ($id_telegram) && $bd_tlg->checkReferencia ($id_telegram)){

		// pega quem fez a indicação desse usuario
		$afiliado = $bd_tlg->getReferenciaIndicado ($id_telegram);
		$saldo_afiliado = $bd_tlg->getSaldo ($afiliado ['id_telegram']); // pega o saldo atual do afiliado

		if (isset ($afiliado)){

			// o afiliado ganha % do valor recarregado
			$novo_saldo_afiliado = getPorcento ($valor_pagamento, BONUS_AFILIADO);
			$bd_tlg->setSaldo ($afiliado ['id_telegram'], $novo_saldo_afiliado+$saldo_afiliado);

			$tlg->sendMessage ([
				'chat_id' => $afiliado ['id_telegram'],
				'text' => "👏 Parabéns, um dos seus indicados acaba de fazer uma recarga.\n<b>Por indicação você ganhou R\$".number_format ($novo_saldo_afiliado, 2)." (".BONUS_AFILIADO."%) da recarga dele, use /saldo</b>",
				'parse_mode' => 'html'
			]);

		}

	}

	if ($bd_tlg->addResgate ($id_telegram, $external_reference, $valor)){

		echo "Pagamento: {$usuarioTlg ['first_name']} ({$id_telegram}) - Valor: {$valor}\n";

		$tlg->editMessageText ([
			'chat_id' => $jsonPayments['Pagamentos'][$pagamento['txid']]['from_id'],
			"message_id" => $jsonPayments['Pagamentos'][$pagamento['txid']]['message_id'],
			'text' => "<b>Pronto, saldo de R\$ {$valor} adicionado na sua conta</b>",
			'parse_mode' => 'html'
		]);

		$tlg->sendMessage ([
			'chat_id' => $id_telegram,
			'text' => "<b>Pronto, saldo de R\${$valor} adicionado na sua conta</b>",
			'parse_mode' => 'html'
		]);

		 $tlg->sendMessage ([
			'chat_id' => CHAT_ID_NOTIFICACAO,
			'text' => "<b>Saldo Resgatado por {$usuarioTlg ['first_name']}!</b>\nValor: R\${$valor}",
			'parse_mode' => 'html'
		]);

		$bd_tlg->setSaldo ($id_telegram, $valor+$usuarioBd ['saldo']);

	}

}
atualizarRecargaExpiradas();
