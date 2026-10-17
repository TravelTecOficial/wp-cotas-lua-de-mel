<?php
// Página pública do casal: HTML, valores e privacidade dos dados bancários.
require '/t/bootstrap.php';
require '/p/includes/casal.php';
require '/p/includes/pagina.php';

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

echo "\nFALHAS=" . $GLOBALS['ct_falhas'] . "\n";
