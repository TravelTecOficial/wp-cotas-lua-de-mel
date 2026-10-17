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

// limite: 20 por hora por IP + casal, só envios válidos contam
ct_reset();
$ch = 'ctec_ip_' . md5( '9.9.9.9|1' );
for ( $i = 1; $i <= 20; $i++ ) {
	$r = ctec_processar_presente( ct_dados(), '9.9.9.9' );
}
ok( ! is_wp_error( $r ), '20º envio passa' );
$r = ctec_processar_presente( ct_dados(), '9.9.9.9' );
ok( ct_erro( $r, 429 ), '21º envio -> 429' );
igual( 'Muitos envios. Tente de novo mais tarde.', $r->get_error_message(), 'mensagem do limite' );
igual( 20, $GLOBALS['ct_transients'][ $ch ][0][0], 'contador em 20' );
igual( 20, count( ct_presentes() ), 'o 21º não grava' );
$r = ctec_processar_presente( ct_dados(), '8.8.8.8' );
ok( ! is_wp_error( $r ), 'outro IP não é afetado' );
ct_casal( 3 );
$r = ctec_processar_presente( ct_dados( array( 'casal' => 3 ) ), '9.9.9.9' );
ok( ! is_wp_error( $r ), 'mesmo IP em outro casal não é afetado' );

// janela fixa: o fim original é mantido
ct_reset();
ctec_processar_presente( ct_dados(), '5.5.5.5' );
$ch = 'ctec_ip_' . md5( '5.5.5.5|1' );
$ini = $GLOBALS['ct_transients'][ $ch ][0][1];
$GLOBALS['ct_transients'][ $ch ][0][1] = $ini - 1800; // janela começou há 30 min
ctec_processar_presente( ct_dados(), '5.5.5.5' );
igual( 2, $GLOBALS['ct_transients'][ $ch ][0][0], 'segundo envio soma' );
igual( $ini - 1800, $GLOBALS['ct_transients'][ $ch ][0][1], 'início da janela não muda' );
ok( $GLOBALS['ct_transients'][ $ch ][1] <= 1800 && $GLOBALS['ct_transients'][ $ch ][1] >= 1790, 'TTL é o resto da janela' );
$GLOBALS['ct_transients'][ $ch ][0] = array( 20, time() - HOUR_IN_SECONDS - 5 ); // janela vencida
$r = ctec_processar_presente( ct_dados(), '5.5.5.5' );
ok( ! is_wp_error( $r ), 'janela vencida zera o limite' );
igual( 1, $GLOBALS['ct_transients'][ $ch ][0][0], 'nova janela começa em 1' );

// tentativas inválidas não contam
ct_reset();
for ( $i = 1; $i <= 30; $i++ ) {
	ctec_processar_presente( ct_dados( array( 'email' => 'x' ) ), '4.4.4.4' );
}
igual( array(), $GLOBALS['ct_transients'], 'inválidos não contam no limite' );
$r = ctec_processar_presente( ct_dados(), '4.4.4.4' );
ok( ! is_wp_error( $r ), 'depois de 30 inválidos ainda passa' );

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

// Reply-To sem , ; " < >
ct_reset();
ctec_processar_presente( ct_dados( array( 'nome' => 'Ana, "Bia"; Cia' ) ), '3.3.3.3' );
$cab = implode( "
", (array) $GLOBALS['ct_emails'][0]['cabecalhos'] );
igual( 'Reply-To: Ana Bia Cia <joao@exemplo.test>', $cab, 'Reply-To sem , ; "' );
$e = ctec_email_presente( array( 'cota' => 'X', 'valor' => 1.0, 'nome' => 'A<b>,C;"D', 'email' => 'a@b.co', 'whatsapp' => '1', 'mensagem' => '' ), array( 'email' => 'c@d.co' ) );
igual( 'Reply-To: AbCD <a@b.co>', $e['cabecalhos'][0], 'Reply-To sem < > (função direta)' );

// limites de tamanho
ct_reset();
ctec_processar_presente( ct_dados( array( 'nome' => str_repeat( 'á', 100 ), 'mensagem' => str_repeat( 'é', 600 ), 'whatsapp' => str_repeat( '1', 50 ), 'email' => str_repeat( 'a', 40 ) . '@' . str_repeat( 'b', 50 ) . '.' . str_repeat( 'c', 50 ) . '.com' ) ), '3.3.3.4' );
$p = ct_presentes();
$m = $GLOBALS['ct_meta'][ $p[0]->ID ];
igual( 80, mb_strlen( $m['_ctec_nome'], 'UTF-8' ), 'nome cortado em 80' );
igual( 500, mb_strlen( $m['_ctec_mensagem'], 'UTF-8' ), 'mensagem cortada em 500' );
igual( 30, strlen( $m['_ctec_whatsapp'] ), 'whatsapp cortado em 30' );
igual( 100, strlen( $m['_ctec_email'] ), 'e-mail cortado em 100' );
ct_reset();
ctec_processar_presente( ct_dados( array( 'cota' => 'livre', 'presente_nome' => str_repeat( 'ç', 120 ), 'presente_valor' => '10' ) ), '3.3.3.5' );
$p = ct_presentes();
igual( 80, mb_strlen( $GLOBALS['ct_meta'][ $p[0]->ID ]['_ctec_cota'], 'UTF-8' ), 'presente_nome cortado em 80' );

// campos que não são texto não derrubam
ct_reset();
$r = ctec_processar_presente( ct_dados( array( 'nome' => array( 'x' ), 'cota' => array( 1 ) ) ), '3.3.3.6' );
ok( ct_erro( $r, 400 ), 'nome em array -> 400, sem erro fatal' );
$r = ctec_processar_presente( ct_dados( array( 'mensagem' => array( 'a' => 'b' ), 'whatsapp' => array(), 'email' => new stdClass() ) ), '3.3.3.6' );
ok( ct_erro( $r, 400 ), 'objeto/lista nos campos -> 400' );
$r = ctec_processar_presente( ct_dados( array( 'cota' => array( 1 ) ) ), '3.3.3.6' );
ok( ct_erro( $r, 400 ), 'cota em array -> 400' );
$r = ctec_processar_presente( ct_dados( array( 'casal' => array( 1 ) ) ), '3.3.3.6' );
ok( ct_erro( $r, 404 ), 'casal em array -> 404' );

// falha ao gravar: 500 e sem e-mail
ct_reset();
$GLOBALS['ct_falha_insert'] = true;
$r = ctec_processar_presente( ct_dados(), '3.3.3.7' );
ok( ct_erro( $r, 500 ), 'gravação falha -> 500' );
igual( 'Não foi possível registrar o presente. Tente de novo.', $r->get_error_message(), 'mensagem do 500' );
igual( 0, count( $GLOBALS['ct_emails'] ), 'gravação falha não envia e-mail' );
$GLOBALS['ct_falha_insert'] = new WP_Error( 'x', 'y' );
$r = ctec_processar_presente( ct_dados(), '3.3.3.7' );
ok( ct_erro( $r, 500 ), 'WP_Error ao gravar -> 500' );
unset( $GLOBALS['ct_falha_insert'] );

// rota
ok( in_array( 'ctec_registrar_rota', $GLOBALS['ct_ganchos']['rest_api_init'], true ), 'rota registrada em rest_api_init' );
ctec_registrar_rota();
igual( 'POST', $GLOBALS['ct_rotas']['ctec/v1/presente']['methods'], 'rota POST ctec/v1/presente' );
igual( '__return_true', $GLOBALS['ct_rotas']['ctec/v1/presente']['permission_callback'], 'rota pública' );
