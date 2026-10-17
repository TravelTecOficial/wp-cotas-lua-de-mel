<?php
// Página pública do casal: HTML, valores e privacidade dos dados bancários.
require '/t/bootstrap.php';
require '/p/includes/casal.php';
require '/p/includes/pagina.php';
define( 'CTEC_DIR', '/p/' );

igual( 'R$ 1.500,50', ctec_formatar_valor( 1500.5 ), 'formata 1500.5' );
igual( 'R$ 50,00', ctec_formatar_valor( 50 ), 'formata 50' );

$casal = array(
	'id' => 7, 'nome' => 'Rogério & Jéssica', 'slug' => 'rogerio-jessica', 'link' => 'https://exemplo.test/?p=7',
	'capa' => 'https://exemplo.test/capa.jpg', 'cidade' => 'Sorocaba', 'data' => '2026-10-31', 'data_br' => '31/10/2026',
	'mensagem' => 'Obrigado <script>alert(1)</script> por tudo', 'email' => 'noivos@segredo.test',
	'conta' => 'Banco SECRETO Ag 123 CC 456',
	'cotas' => array(
		array( 'nome' => 'Ouro', 'valor' => 1000.0, 'foto' => 5 ),
		array( 'nome' => 'Bronze', 'valor' => 50.0, 'foto' => 0 ),
	),
);
$h = ctec_render_casal( $casal );
contem( '<div class="ctec">', $h, 'raiz .ctec' );
contem( 'Rogério &amp; Jéssica', $h, 'nomes escapados' );
contem( 'Sorocaba · 31/10/2026', $h, 'cidade e data' );
contem( 'R$ 1.000,00', $h, 'valor da cota' );
contem( 'R$ 50,00', $h, 'valor Bronze' );
contem( 'data-cota="0"', $h, 'card índice 0' );
contem( 'data-cota="1"', $h, 'card índice 1' );
contem( 'data-cota="livre"', $h, 'card livre' );
contem( 'Monte seu presente', $h, 'texto livre' );
contem( 'ctec_site', $h, 'campo isca' );
contem( 'Escolha uma cota ou monte seu presente', $h, 'passo 1' );
contem( 'Deixe seu nome e uma mensagem para os noivos', $h, 'passo 2' );
contem( 'Faça o depósito na conta do casal', $h, 'passo 3' );
contem( 'Mensagem dos noivos', $h, 'mensagem dos noivos' );
contem( '&lt;script&gt;', $h, 'mensagem escapada' );
nao_contem( '<script>alert', $h, 'sem script cru' );
nao_contem( 'noivos@segredo.test', $h, 'sem e-mail do casal' );
nao_contem( 'SECRETO', $h, 'sem dados da conta' );
contem( '<dialog class="ctec-janela">', $h, 'janela' );
contem( 'window.print()', $h, 'imprimir' );
contem( '5.jpg', $h, 'foto da cota' );

$casal['mensagem'] = '';
$casal['capa']     = '';
$h2 = ctec_render_casal( $casal );
nao_contem( 'Mensagem dos noivos', $h2, 'sem mensagem não mostra seção' );
contem( 'ctec-capa', $h2, 'capa sem foto ainda renderiza' );

$casal['cidade'] = '';
contem( '31/10/2026', ctec_render_casal( $casal ), 'só data' );

// robots: noindex só na página do casal
$GLOBALS['ct_singular'] = 'ctec_casal';
$r = ctec_robots( array( 'max-image-preview' => 'large', 'index' => true ) );
ok( ! empty( $r['noindex'] ) && ! empty( $r['nofollow'] ), 'wp_robots dá noindex, nofollow no casal' );
ok( ! isset( $r['max-image-preview'] ), 'wp_robots sem max-image-preview no casal' );
igual( array( 'index' => 'noindex', 'follow' => 'nofollow' ), ctec_robots_rank_math( array( 'index' => 'index', 'follow' => 'follow' ) ), 'Rank Math: noindex, nofollow' );
$GLOBALS['ct_singular'] = 'post';
igual( array( 'index' => true ), ctec_robots( array( 'index' => true ) ), 'wp_robots não mexe em outras páginas' );
igual( array( 'index' => 'index' ), ctec_robots_rank_math( array( 'index' => 'index' ) ), 'Rank Math não mexe em outras páginas' );
ok( in_array( 'ctec_robots', $GLOBALS['ct_ganchos']['wp_robots'], true ), 'filtro wp_robots registrado' );
ok( in_array( 'ctec_robots_rank_math', $GLOBALS['ct_ganchos']['rank_math/frontend/robots'], true ), 'filtro do Rank Math registrado' );
ok( empty( $GLOBALS['ct_ganchos']['wp_head'] ) || ! in_array( 'ctec_noindex', $GLOBALS['ct_ganchos']['wp_head'], true ), 'sem meta manual no wp_head' );

// modelo do casal: prioridade 99 (acima do Theme Builder, 11)
igual( 99, $GLOBALS['ct_prio']['template_include']['ctec_template_casal'], 'template_include na prioridade 99' );
$GLOBALS['ct_singular'] = 'ctec_casal';
igual( '/p/templates/casal.php', ctec_template_casal( '/tema/single.php' ), 'casal usa o modelo do plugin' );
$GLOBALS['ct_singular'] = 'post';
igual( '/tema/single.php', ctec_template_casal( '/tema/single.php' ), 'outras páginas mantêm o modelo' );

// maxlength dos campos
contem( 'name="nome" maxlength="80"', $h, 'nome maxlength 80' );
contem( 'name="presente_nome" maxlength="80"', $h, 'presente_nome maxlength 80' );
contem( 'name="email" maxlength="100"', $h, 'email maxlength 100' );
contem( 'name="whatsapp" maxlength="30"', $h, 'whatsapp maxlength 30' );
contem( 'name="mensagem" rows="3" maxlength="500"', $h, 'mensagem maxlength 500' );

echo "\nFALHAS=" . $GLOBALS['ct_falhas'] . "\n";
