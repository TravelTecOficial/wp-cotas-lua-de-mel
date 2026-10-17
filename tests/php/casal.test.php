<?php
// Casal: tipos de post, valores, cotas e gravação do cadastro.
require '/t/bootstrap.php';
require '/p/includes/casal.php';

// ctec_valor
igual( 1500.0, ctec_valor( '1.500,00' ), 'valor 1.500,00' );
igual( 1500.0, ctec_valor( '1500' ), 'valor 1500' );
igual( 50.0, ctec_valor( 'R$ 50' ), 'valor R$ 50' );
igual( 50.5, ctec_valor( '50,5' ), 'valor 50,5' );
igual( 0.0, ctec_valor( 'abc' ), 'valor abc' );
igual( 1500.0, ctec_valor( '1.500' ), 'valor 1.500 (milhar)' );
igual( 50.5, ctec_valor( 50.5 ), 'valor float' );

// cotas padrão
$padrao = ctec_cotas_padrao();
igual( 5, count( $padrao ), '5 cotas padrão' );
igual( array( 'nome' => 'Bronze', 'valor' => 50.0, 'foto' => 0 ), $padrao[0], 'primeira é Bronze 50' );
igual( array( 50.0, 100.0, 250.0, 500.0, 1000.0 ), array_column( $padrao, 'valor' ), 'valores padrão' );
igual( array( 'Bronze', 'Prata', 'Ouro', 'Diamante', 'Safira' ), array_column( $padrao, 'nome' ), 'nomes padrão' );

// sanitizar
igual(
	array( array( 'nome' => 'Ouro', 'valor' => 250.0, 'foto' => 0 ) ),
	ctec_sanitizar_cotas( array(
		array( 'nome' => '<b>Ouro</b>', 'valor' => '250' ),
		array( 'nome' => '', 'valor' => '10' ),
		array( 'nome' => 'X', 'valor' => '0' ),
	) ),
	'sanitizar descarta inválidas e limpa nome'
);
$s = ctec_sanitizar_cotas( array( 5 => array( 'nome' => 'A', 'valor' => '1,5', 'foto' => '7' ), 9 => array( 'nome' => 'B', 'valor' => '2' ) ) );
igual( array( 0, 1 ), array_keys( $s ), 'sanitizar reindexa' );
igual( 7, $s[0]['foto'], 'foto absint' );

// dados do casal
$GLOBALS['ct_posts'][10] = (object) array( 'ID' => 10, 'post_title' => 'Ana e Bruno', 'post_name' => 'ana-e-bruno' );
$d = ctec_dados_casal( 10 );
igual( 5, count( $d['cotas'] ), 'sem meta de cotas devolve as 5 sugeridas' );
igual( 'Ana e Bruno', $d['nome'], 'nome' );
igual( 'ana-e-bruno', $d['slug'], 'slug' );
igual( 'https://exemplo.test/?p=10', $d['link'], 'link' );
igual( '', $d['capa'], 'sem capa' );
igual( '', $d['data'], 'sem data' );
igual( '', $d['data_br'], 'sem data_br' );
foreach ( array( 'id', 'cidade', 'mensagem', 'email', 'conta' ) as $k ) { ok( array_key_exists( $k, $d ), "chave $k" ); }

// salvar
$_pode = $GLOBALS['ct_pode'] = true;
ctec_salvar_casal( 10, array(
	'ctec_cidade'   => ' Lisboa <i>',
	'ctec_data'     => '2027-03-15',
	'ctec_mensagem' => "Olá\nobrigado",
	'ctec_email'    => 'email invalido',
	'ctec_conta'    => "Banco X\nAg 1",
	'ctec_cotas'    => array( array( 'nome' => 'Jantar', 'valor' => '120,00', 'foto' => '3' ) ),
) );
igual( '', get_post_meta( 10, '_ctec_email', true ), 'e-mail inválido grava vazio' );
igual( 'Lisboa', get_post_meta( 10, '_ctec_cidade', true ), 'cidade limpa' );
$GLOBALS['ct_meta'][10]['_thumbnail_id'] = 4;
$d = ctec_dados_casal( 10 );
igual( array( array( 'nome' => 'Jantar', 'valor' => 120.0, 'foto' => 3 ) ), $d['cotas'], 'cotas gravadas' );
igual( '2027-03-15', $d['data'], 'data' );
igual( '15/03/2027', $d['data_br'], 'data_br' );
contem( '/4.jpg', $d['capa'], 'capa' );
ctec_salvar_casal( 10, array( 'ctec_email' => 'a@b.com', 'ctec_data' => 'lixo' ) );
igual( 'a@b.com', get_post_meta( 10, '_ctec_email', true ), 'e-mail válido' );
igual( '', get_post_meta( 10, '_ctec_data', true ), 'data inválida vira vazio' );

// tipos
ctec_registrar_tipos();
$c = $GLOBALS['ct_tipos']['ctec_casal'];
igual( 'lua-de-mel', $c['rewrite']['slug'], 'slug lua-de-mel' );
igual( true, $c['public'], 'casal público' );
igual( false, $c['has_archive'], 'sem arquivo' );
$p = $GLOBALS['ct_tipos']['ctec_presente'];
igual( false, $p['public'], 'presente não público' );
igual( 'edit.php?post_type=ctec_casal', $p['show_in_menu'], 'presente no menu do casal' );
igual( 'do_not_allow', $p['capabilities']['create_posts'], 'presente sem criar' );
ok( in_array( 'ctec_registrar_tipos', $GLOBALS['ct_ganchos']['init'], true ), 'registrar_tipos em init' );
ok( isset( $GLOBALS['ct_ganchos']['save_post_ctec_casal'] ), 'gancho de salvar' );

// salvar pelo gancho: sem nonce não grava
update_post_meta( 10, '_ctec_cidade', 'Lisboa' );
$_POST = array( 'ctec_cidade' => 'Roma' );
ctec_salvar_casal_gancho( 10 );
igual( 'Lisboa', get_post_meta( 10, '_ctec_cidade', true ), 'sem nonce não grava' );
$_POST = array( 'ctec_casal_nonce' => 'nonce-ctec_casal', 'ctec_cidade' => 'Roma' );
ctec_salvar_casal_gancho( 10 );
igual( 'Roma', get_post_meta( 10, '_ctec_cidade', true ), 'com nonce grava' );

echo "\nFALHAS=" . $GLOBALS['ct_falhas'] . "\n";
