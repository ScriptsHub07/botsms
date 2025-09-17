<?php

if ($tlg->Callback_ID () !== null){
	
	// encerra carregamento do botão de callback
	$tlg->answerCallbackQuery ([
		'callback_query_id' => $tlg->Callback_ID ()
	]);

}

if (STATUS_AFILIADO){

	$ultima = @$bd_tlg->getReferencias ($tlg->UserID (), 1)[0];

	if (isset ($ultima ['data'])){

		$ultima_indicacao = '<em>'.date ('d/m H:i', $ultima ['data']).'</em>';
		$usuario = "<a href=\"tg://user?id={$ultima ['id_indicado']}\">{$ultima ['id_indicado']}</a>";

	}else {

		$ultima_indicacao = '<em>Nenhuma ainda</em>';
		$usuario = '<em>n/a</em>';

	}

	$tlg->sendMessage ([
		'chat_id' => $tlg->ChatID (),
		'text' => "<b>💡 Ganhe bônus de ".BONUS_AFILIADO."% da recarga do seu indicado:</b>\n\n<b>🔸 Indicações:</b> {$bd_tlg->countReferencias ($tlg->UserID ())}\n<b>📍 Última:</b> {$ultima_indicacao}\n<b>🙇‍♂️ Indicado:</b> {$usuario}\n\n🔗 Link: <code>https://t.me/?start={$tlg->UserID ()}</code>",
		'parse_mode' => 'html',
		'reply_markup' => $tlg->buildInlineKeyboard (
			[
				[$tlg->buildInlineKeyBoardButton ('Compartilhar', "tg://share?text=Receba SMS diretamente pelo Telegram&url=https://t.me/?start={$tlg->UserID ()}")]
			]
		)
	]);

}else {

	$tlg->sendMessage ([
		'chat_id' => $tlg->ChatID (),
		'text' => "Nosso sistema de afiliados está desativado por enqunato :}"
	]);

}