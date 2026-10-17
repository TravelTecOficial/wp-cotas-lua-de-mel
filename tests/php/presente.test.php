<?php
// Presentear: validação, limite, gravação e e-mail.
require '/t/bootstrap.php';
require '/p/includes/casal.php';
require '/p/includes/pagina.php';
require '/p/includes/presente.php';

function ct_casal( $id, $status = 'publish', $email = 'casal@exemplo.test' ) {
	$GLOBALS['ct_posts'][ $id ] = (object) array( 'ID' => $id, 'post_type' => 'ctec_casal', 'post_status' => $status, 'post_title' => 'Ana e Bia', 'post_name' => 'ana-e-bia' );
	$GLOBALS['ct_meta'][ $id ]  = array(
		'_ctec_email' => $email,
		'_ctec_conta' => 'Banco X ag 1 cc 2',
		'_ctec_cotas' => ctec_cotas_padrao(),
	);
}
function ct_dados( $extra = array() ) {
	return array_merge( array( 'casal' => 1, 'cota' => '2', 'nome' => 'João', 'email' => 'joao@exemplo.test', 'whatsapp' => '(11) 91234-5678', 'mensagem' => 'Parabéns!' ), $extra );
}
function ct_reset() {
	$GLOBALS['ct_posts'] = array(); $GLOBALS['ct_meta'] = array(); $GLOBALS['ct_emails'] = array(); $GLOBALS['ct_transients'] = array(); $GLOBALS['ct_mail_ok'] = true;
	ct_casal( 1 );
	ct_casal( 2, 'draft' );
}
function ct_presentes() {
	return array_values( array_filter( $GLOBALS['ct_posts'], function ( $p ) { return 'ctec_presente' === $p->post_type; } ) );
}
function ct_erro( $r, $status ) {
	return is_wp_error( $r ) && $status === $r->data['status'];
}

// cota fixa: valor vem do cadastro
ct_reset();
$r = ctec_processar_presente( ct_dados( array( 'valor' => 1, 'cota_nome' => 'Hack' ) ), '1.2.3.4' );
igual( array( 'ok' => true, 'conta' => 'Banco X ag 1 cc 2' ), $r, 'sucesso devolve a conta' );
$p = ct_presentes();
igual( 1, count( $p ), 'grava um presente' );
igual( 'private', $p[0]->post_status, 'presente é private' );
igual( 'Ouro — João', $p[0]->post_title, 'título cota — nome' );
$m = $GLOBALS['ct_meta'][ $p[0]->ID ];
igual( 250.0, $m['_ctec_valor'], 'valor do cadastro, não do envio' );
igual( 'Ouro', $m['_ctec_cota'], 'nome da cota do cadastro' );
igual( 1, $m['_ctec_casal'], 'meta casal' );
igual( 'joao@exemplo.test', $m['_ctec_email'], 'meta e-mail' );
igual( '(11) 91234-5678', $m['_ctec_whatsapp'], 'meta whatsapp' );
igual( 'Parabéns!', $m['_ctec_mensagem'], 'meta mensagem' );

// e-mail
igual( 1, count( $GLOBALS['ct_emails'] ), 'um e-mail enviado' );
$e = $GLOBALS['ct_emails'][0];
igual( 'casal@exemplo.test', $e['para'], 'e-mail vai para o casal' );
igual( 'Você recebeu um presente: Ouro', $e['assunto'], 'assunto' );
contem( 'Reply-To: João <joao@exemplo.test>', implode( "\n", (array) $e['cabecalhos'] ), 'Reply-To do convidado' );
contem( 'R$ 250,00', $e['corpo'], 'corpo com valor formatado' );
contem( 'Parabéns!', $e['corpo'], 'corpo com mensagem' );
contem( 'Confira o depósito na sua conta antes de agradecer.', $e['corpo'], 'corpo com aviso' );

// livre
ct_reset();
$r = ctec_processar_presente( ct_dados( array( 'cota' => 'livre', 'presente_nome' => 'Jantar', 'presente_valor' => '1.500,00' ) ), '1.2.3.4' );
$p = ct_presentes();
igual( 1500.0, $GLOBALS['ct_meta'][ $p[0]->ID ]['_ctec_valor'], 'livre grava 1500' );
igual( 'Jantar', $GLOBALS['ct_meta'][ $p[0]->ID ]['_ctec_cota'], 'livre usa nome informado' );
foreach ( array( 'abc', '0,50' ) as $v ) {
	$r = ctec_processar_presente( ct_dados( array( 'cota' => 'livre', 'presente_nome' => 'X', 'presente_valor' => $v ) ), '1.2.3.5' );
	ok( ct_erro( $r, 400 ), 'livre valor ' . $v . ' -> 400' );
}
$r = ctec_processar_presente( ct_dados( array( 'cota' => 'livre', 'presente_nome' => '', 'presente_valor' => '10' ) ), '1.2.3.5' );
ok( ct_erro( $r, 400 ), 'livre sem nome -> 400' );

// validações
ct_reset();
foreach ( array( '9', '-1', 'abc', '', '1.5' ) as $c ) {
	$r = ctec_processar_presente( ct_dados( array( 'cota' => $c ) ), '1.2.3.6' );
	ok( ct_erro( $r, 400 ), 'cota "' . $c . '" -> 400' );
}
$r = ctec_processar_presente( ct_dados( array( 'nome' => ' ' ) ), '1.2.3.6' );
ok( ct_erro( $r, 400 ), 'sem nome -> 400' );
$r = ctec_processar_presente( ct_dados( array( 'email' => 'x' ) ), '1.2.3.6' );
ok( ct_erro( $r, 400 ), 'e-mail inválido -> 400' );
$r = ctec_processar_presente( ct_dados( array( 'whatsapp' => '123' ) ), '1.2.3.6' );
ok( ct_erro( $r, 400 ), 'whatsapp curto -> 400' );
igual( 0, count( ct_presentes() ), 'inválidos não gravam' );

// casal
$r = ctec_processar_presente( ct_dados( array( 'casal' => 2 ) ), '1.2.3.7' );
ok( ct_erro( $r, 404 ), 'casal rascunho -> 404' );
$r = ctec_processar_presente( ct_dados( array( 'casal' => 99 ) ), '1.2.3.7' );
ok( ct_erro( $r, 404 ), 'casal inexistente -> 404' );
$GLOBALS['ct_posts'][50] = (object) array( 'ID' => 50, 'post_type' => 'post', 'post_status' => 'publish' );
$r = ctec_processar_presente( ct_dados( array( 'casal' => 50 ) ), '1.2.3.7' );
ok( ct_erro( $r, 404 ), 'post de outro tipo -> 404' );

// isca
ct_reset();
$r = ctec_processar_presente( ct_dados( array( 'ctec_site' => 'http://spam' ) ), '1.2.3.8' );
igual( array( 'ok' => true, 'conta' => '' ), $r, 'isca devolve sucesso falso' );
igual( 0, count( ct_presentes() ), 'isca não grava' );
igual( 0, count( $GLOBALS['ct_emails'] ), 'isca não envia' );
igual( array(), $GLOBALS['ct_transients'], 'isca não conta no limite' );

// limite
ct_reset();
for ( $i = 1; $i <= 10; $i++ ) {
	$r = ctec_processar_presente( ct_dados(), '9.9.9.9' );
}
ok( ! is_wp_error( $r ), '10º envio passa' );
$r = ctec_processar_presente( ct_dados(), '9.9.9.9' );
ok( ct_erro( $r, 429 ), '11º envio -> 429' );
igual( 'Muitos envios. Tente de novo mais tarde.', $r->get_error_message(), 'mensagem do limite' );
igual( HOUR_IN_SECONDS, $GLOBALS['ct_transients'][ 'ctec_ip_' . md5( '9.9.9.9' ) ][1], 'TTL de 1 hora' );
$r = ctec_processar_presente( ct_dados(), '8.8.8.8' );
ok( ! is_wp_error( $r ), 'outro IP não é afetado' );

// falha de e-mail
ct_reset();
$GLOBALS['ct_mail_ok'] = false;
$r = ctec_processar_presente( ct_dados(), '7.7.7.7' );
igual( 'Banco X ag 1 cc 2', $r['conta'], 'e-mail falho ainda devolve a conta' );
igual( 1, count( ct_presentes() ), 'e-mail falho ainda grava' );

// casal sem e-mail
ct_reset();
ct_casal( 1, 'publish', '' );
$r = ctec_processar_presente( ct_dados(), '7.7.7.8' );
igual( 0, count( $GLOBALS['ct_emails'] ), 'sem e-mail do casal não envia' );
igual( 1, count( ct_presentes() ), 'sem e-mail do casal ainda grava' );

// tags e quebra de cabeçalho
ct_reset();
ctec_processar_presente( ct_dados( array( 'nome' => "Zé<script>x</script>\r\nBcc: a@b.c", 'mensagem' => '<b>Oi</b> <img src=x onerror=1>amigos' ) ), '7.7.7.9' );
$e = $GLOBALS['ct_emails'][0];
nao_contem( '<b>', $e['corpo'], 'corpo sem tags' );
nao_contem( '<img', $e['corpo'], 'corpo sem img' );
contem( 'Oi', $e['corpo'], 'mensagem preservada sem tags' );
nao_contem( "\n", implode( '', (array) $e['cabecalhos'] ), 'cabeçalho sem quebra de linha' );

// rota
ok( in_array( 'ctec_registrar_rota', $GLOBALS['ct_ganchos']['rest_api_init'], true ), 'rota registrada em rest_api_init' );
ctec_registrar_rota();
igual( 'POST', $GLOBALS['ct_rotas']['ctec/v1/presente']['methods'], 'rota POST ctec/v1/presente' );
igual( '__return_true', $GLOBALS['ct_rotas']['ctec/v1/presente']['permission_callback'], 'rota pública' );
