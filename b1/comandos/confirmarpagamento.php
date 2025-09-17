<?php
date_default_timezone_set('America/Sao_Paulo');
$txid = $complemento;

$jsonFile = file_get_contents(__DIR__."/../estaticos/Pagamentos.json");
$jsonPayments = json_decode($jsonFile, true);

if (isset($jsonPayments['Pagamentos'][$txid])) {

	if ($jsonPayments['Pagamentos'][$txid]['status'] == 'Aguardando Pagamento') {

		$external_reference = $txid;
		$id_telegram = $jsonPayments['Pagamentos'][$txid]['from_id'];
		$id_message_telegram = $jsonPayments['Pagamentos'][$txid]['message_id'];
		$cob = $ApiPix->ConsultarCobranca($txid);
		$valor_pagamento = $cob['valor']['original'];
		$valor_pago = $cob['pix'][0]['valor'];

		if ($cob["status"] == "CONCLUIDA" && $valor_pago == $valor_pagamento) {

			$valor = incrementoPorcento ($valor_pagamento, BONUS);

			$usuarioTlg = $tlg->getUsuarioTlg ($id_telegram); // usuario no telegram
			$usuarioBd = $bd_tlg->getUsuario ($id_telegram); // usuario no sistema/bd

			if (STATUS_AFILIADO && $bd_tlg->checkPrimeiroResgate($id_telegram) && $bd_tlg->checkReferencia ($id_telegram)){

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

			if ($bd_tlg->addResgate($id_telegram, $external_reference, $valor)){

				echo "Pagamento: {$usuarioTlg ['first_name']} ({$id_telegram}) - Valor: {$valor}\n";

				/*
				$tlg->editMessageText ([
					'chat_id' => $tlg->ChatID (),
					'message_id' => $tlg->MessageID (),
					'text' => "<b>Pronto, saldo de R\$ {$valor} adicionado na sua conta</b>",
					'parse_mode' => 'html'
				]);*/

				// apagar qrcode
				$tlg->deleteMessage([
					'chat_id' => $tlg->ChatID(),
					'message_id' => $tlg->MessageID ()
				]);

				$tlg->sendMessage ([
					'chat_id' => $tlg->ChatID (),
					'text' => "<b>Pronto, saldo de R\$ {$valor} adicionado na sua conta</b>",
					'parse_mode' => 'html'
				]);

				$tlg->sendMessage ([
					'chat_id' => CHAT_ID_NOTIFICACAO,
					'text' => "<b>✅RECARGA REALIZADA NO BOT VIA PIX</b>\n👨‍💻Usuário: {$usuarioTlg ['first_name']}!\n💰Valor: R\${$valor}",
					'parse_mode' => 'html'
				]);
				$bd_tlg->setSaldo ($id_telegram, $valor+$usuarioBd ['saldo']);
				pagarRecarga($txid, date('d-m-Y H:i:s'));
			}

		} else {
			$tlg->answerCallbackQuery ([
				'callback_query_id' => $tlg->Callback_ID (),
				'text' => 'Pagamento Ainda Nao Foi Recebido.',
				'show_alert' => '1'
			]);
		}
	} 
}

atualizarRecargaExpiradas();