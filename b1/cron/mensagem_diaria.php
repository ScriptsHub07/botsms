<?php

include __DIR__.'/../includes/includes.php';

$tlg = new Telegram (TOKEN_BOT);

$tlg->sendMessage ([
'chat_id' => '@vkconsultas',
'text' => "💠 Como conseguir um número?
Use o comando /servicos e veja os serviços disponiveis.
Você irá receber um número temporário para receber sms, assim conseguirá criar contas, logins novos.

💠 Posso ter um número fixo?
No momento os números do bot são temporários, você consegue receber sms durante 18 minutos, após o uso eles são descartados do bot.

💠 Como colocar saldo na minha conta?
Use o comando /recarregar ou entre em contato com o suporte /info.

💠  Algumas coisas importantes:
Você só é cobrado após receber o sms, caso n chegar em até 2min é só cancelar e solicitar outro número. Em alguns apps como whatsapp e telegram ative a verificação de duas etapas para ter mais segurança. Evite cancelar muitos números para evitar falta de números.       ",
'parse_mode' => 'html'
]);