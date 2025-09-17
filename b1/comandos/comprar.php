<?php
date_default_timezone_set('America/Sao_Paulo');
$valor = number_format ($complemento, 2);
//$hash = $tlg->UserID ().mt_rand(111111, 999999);

$dados = dadosFulls();

$request = [
  'calendario' => [
    'expiracao' => 5400 // 1 Hora e 30 Minutos
  ],
  'devedor' => [
		'cpf' => $dados[0],
    'nome' => $dados[1]
  ],
  'valor' => [
    'original' => $valor,
  ],
  'chave' => CHAVE_PIX,
  'solicitacaoPagador' => 'SmszBot',
  'infoAdicionais' => [
    [
        "nome" => "Recarga",
        "valor" => "{$valor} R\$"
    ]
  ]
];

$txid = strtoupper(uniqid().str_shuffle(rand(10000, 99999).genString()));


$tlg->editMessageText ([
    'chat_id' => $tlg->ChatID (),
    'message_id' => $tlg->MessageID (),
    "parse_mode" => "Markdown",
    "text" => "*Gerando PIX...*",
	'reply_markup' => $tlg->buildInlineKeyboard ([
		[
			$tlg->buildInlineKeyBoardButton ('⏳ Gerando PIX... ⏳', null, '/esseCallbackNaoExiste')
		]
	])
]);


$Pix = $ApiPix->CriarCobranca($txid, $request);


if (!isset($Pix["loc"]["id"])) {

	$tlg->editMessageText ([
		'chat_id' => $tlg->ChatID (),
		'message_id' => $tlg->MessageID (),
		'text' => "<em>⚠️ Erro ao gerar o seu pix de pagamento, por favor tente novamente!</em>",
		'reply_markup' => $tlg->buildInlineKeyboard ([
			[
				$tlg->buildInlineKeyBoardButton ("Tentar Novamente", null, "/comprar {$valor}")
			]
		])
	]);

}else {

	$QrCode = $ApiPix->GerarQrCodePix($Pix["loc"]["id"]);

	$PixCopiaCola = $QrCode["qrcode"]; // gerar qr-code
	$PixValorOrigin = $Pix["valor"]["original"];
	$PixValor = number_format($PixValorOrigin, 2, ',', '.');
	$transaction_id = $Pix["txid"];
	$location_id = $Pix["loc"]["id"];
	$location = $Pix["loc"]["location"];
	$dateCreated = date('d-m-Y H:i:s');
	$dateCreatedFormatada = date('d/m/Y H:i:s', strtotime($dateCreated));

	// apagar gerando pix
	$tlg->deleteMessage([
		'chat_id' => $tlg->ChatID(),
		'message_id' => $tlg->MessageID ()
	]);

	// enviar QR Code
	$tlg->sendPhoto ([
		'chat_id' => $tlg->ChatID(),
		'photo' => 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($PixCopiaCola),
		"caption" => "*PIX Gerado Com Sucesso✅*\n\n💰*Valor da Recarga:* _{$PixValor} R$ _\n🆔*ID Da Transação:* _{$transaction_id}_\n\n💠*PIX Copia e Cola:* `{$PixCopiaCola}`\n\n💡*Observação:* _Após o Pagamento, Clique No Botão \n'✅ Confirmar Pagamento ✅'\nE Seu Saldo Será Creditado Automaticamente._",
		'parse_mode' => 'Markdown',
		//'message_id' => $tlg->MessageID (),
		'reply_markup' => $tlg->buildInlineKeyboard ([
			[
				$tlg->buildInlineKeyBoardButton ('⏳ Aguardando Pagamento... ⏳', null, '/esseCallbackNaoExiste')
			],
			[
				$tlg->buildInlineKeyBoardButton ('✅ Confirmar Pagamento ✅', null, "/confirmarpagamento {$transaction_id}")
			]
		])
	]);
	
	salvarRecarga([
        "message_id" => $tlg->MessageID (),
        "from_id" => $tlg->ChatID (),
        "transaction_id" => $transaction_id,
        "location_id" => $location_id,
        "location" => $location,
        "copiacola" => $PixCopiaCola,
        "status" => "Aguardando Pagamento",
        "valor" => $PixValorOrigin,
        "dataCriacao" => $dateCreated,
        "expiracao" => 5400
    ]);

}