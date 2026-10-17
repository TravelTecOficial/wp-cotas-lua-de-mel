<?php
// Painel de presentes: CSV, totais, link de WhatsApp.
require '/t/bootstrap.php';
require '/p/includes/casal.php';
require '/p/includes/pagina.php';
require '/p/includes/presente.php';
require '/p/includes/admin.php';

function ct_casal2( $id, $nome, $slug ) {
	$GLOBALS['ct_posts'][ $id ] = (object) array( 'ID' => $id, 'post_type' => 'ctec_casal', 'post_status' => 'publish', 'post_title' => $nome, 'post_name' => $slug );
}
function ct_pres( $casal, $cota, $valor, $nome, $msg, $data, $wa = '(11) 91234-5678' ) {
	$id = ctec_gravar_presente( array( 'casal' => $casal, 'cota' => $cota, 'valor' => $valor, 'nome' => $nome, 'email' => 'x@exemplo.test', 'whatsapp' => $wa, 'mensagem' => $msg ) );
	$GLOBALS['ct_posts'][ $id ]->post_date = $data;
	return $id;
}
ct_casal2( 1, 'Ana e Bia', 'ana-e-bia' );
ct_casal2( 2, 'Caio e Dani', 'caio-e-dani' );
ct_pres( 1, 'Ouro', 1500.0, 'João', 'Parabéns; "felizes"!', '2026-10-17 09:05:00' );
ct_pres( 1, 'Bronze', 50.5, 'Maria', '=1+1', '2026-10-18 10:00:00', '+55 11 91234-5678' );
ct_pres( 2, 'Prata', 100.0, 'Zé', '', '2026-10-19 11:00:00' );

$csv = ctec_csv_presentes( 1 );
igual( "\xEF\xBB\xBF", substr( $csv, 0, 3 ), 'CSV começa com BOM' );
$linhas = explode( "\r\n", trim( substr( $csv, 3 ) ) );
igual( 'Data;Casal;Cota;Valor;Convidado;E-mail;WhatsApp;Mensagem', $linhas[0], 'cabeçalho exato' );
igual( 3, count( $linhas ), 'cabeçalho + 2 presentes do casal 1' );
contem( '1500,00', $csv, 'valor 1500,00' );
contem( '50,50', $csv, 'valor 50,50' );
contem( '"Parabéns; ""felizes""!"', $csv, 'mensagem com ; e aspas escapadas' );
contem( '18/10/2026 10:00;Ana e Bia;Bronze', $linhas[1], 'mais recente primeiro, data d/m/Y H:i' );
nao_contem( 'Zé', $csv, 'não inclui outro casal' );
igual( 4, count( explode( "\r\n", trim( ctec_csv_presentes() ) ) ), 'sem filtro traz todos' );

igual( "'=SOMA(1)", ctec_csv_celula( '=SOMA(1)' ), 'guarda contra fórmula =' );
igual( "'@x", ctec_csv_celula( '@x' ), 'guarda contra @' );
igual( "\"a\nb\"", ctec_csv_celula( "a\nb" ), 'quebra de linha entre aspas' );
igual( 'simples', ctec_csv_celula( 'simples' ), 'texto simples sem aspas' );

igual( array( 'qtd' => 2, 'total' => 1550.5 ), ctec_total_casal( 1 ), 'total do casal 1' );
igual( array( 'qtd' => 1, 'total' => 100.0 ), ctec_total_casal( 2 ), 'total do casal 2' );
igual( array( 'qtd' => 0, 'total' => 0.0 ), ctec_total_casal( 9 ), 'casal sem presentes' );

igual( 'https://wa.me/5511912345678', ctec_whatsapp_link( '(11) 91234-5678' ), 'wa: 11 dígitos recebe 55' );
igual( 'https://wa.me/551132345678', ctec_whatsapp_link( '11 3234-5678' ), 'wa: 10 dígitos recebe 55' );
igual( 'https://wa.me/5511912345678', ctec_whatsapp_link( '+55 11 91234-5678' ), 'wa: 13 dígitos fica' );
igual( '', ctec_whatsapp_link( 'abc' ), 'wa: sem número' );

igual( 'Um dois três', ctec_resumo_mensagem( 'Um dois três', 20 ), 'mensagem curta intacta' );
contem( '…', ctec_resumo_mensagem( implode( ' ', range( 1, 30 ) ), 20 ), 'mensagem longa cortada' );

contem( ';+55 11 91234-5678;', $csv, 'WhatsApp com + fica como digitado' );
contem( ";'=1+1", $csv, 'mensagem começando com = ganha apóstrofo' );
igual( "'\tx", ctec_csv_celula( "\tx" ), 'guarda contra tab inicial' );
igual( '+55', ctec_csv_celula( '+55', false ), 'sem guarda quando desligada' );
ok( isset( $GLOBALS['ct_ganchos']['admin_post_ctec_csv'] ), 'gancho do download' );
ok( isset( $GLOBALS['ct_ganchos']['manage_ctec_presente_posts_columns'] ), 'gancho das colunas' );
