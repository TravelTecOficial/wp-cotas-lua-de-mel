<?php
// Estrutura do plugin: versão coerente, CHANGELOG e guarda ABSPATH nos includes.
require __DIR__ . '/bootstrap.php';

$principal = file_get_contents( '/p/cotas-tec.php' );
ok( 1 === preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $principal, $m ), 'cabeçalho tem Version:' );
$cabecalho = isset( $m[1] ) ? $m[1] : '';
igual( '0.1.1', $cabecalho, 'Version do cabeçalho é 0.1.1' );
ok( 1 === preg_match( "/define\( 'CTEC_VERSION', '([^']+)' \)/", $principal, $c ), 'CTEC_VERSION definida' );
igual( $cabecalho, isset( $c[1] ) ? $c[1] : '', 'CTEC_VERSION == Version do cabeçalho' );

$changelog = is_file( '/c/CHANGELOG.md' ) ? file_get_contents( '/c/CHANGELOG.md' ) : '';
contem( '## 0.1.1', $changelog, 'CHANGELOG.md tem ## 0.1.1' );

$arquivos = glob( '/p/includes/*.php' );
ok( count( $arquivos ) > 0, 'includes/ tem arquivos' );
foreach ( $arquivos as $arq ) {
	$ini = substr( ltrim( file_get_contents( $arq ) ), 0, 1500 );
	contem( "if ( ! defined( 'ABSPATH' ) )", $ini, basename( $arq ) . ' começa com o guarda ABSPATH' );
}
// readme.txt e CHANGELOG descrevem o mesmo 0.1.0 final
$readme = file_get_contents( '/p/readme.txt' );
nao_contem( 'esqueleto', $readme, 'readme.txt sem "esqueleto"' );
contem( 'fora de buscas e sitemaps', $readme, 'readme.txt descreve o conjunto final' );
nao_contem( 'Esqueleto', $changelog, 'CHANGELOG sem "Esqueleto"' );

// CSV com dados pessoais: só quem edita posts de outros
$admin = file_get_contents( '/p/includes/admin.php' );
contem( "current_user_can( 'edit_others_posts' ) || ! wp_verify_nonce( \$nonce, 'ctec_csv' )", $admin, 'CSV exige edit_others_posts' );
nao_contem( "current_user_can( 'edit_posts' )", $admin, 'CSV não aceita edit_posts' );

// arquivo principal: versão gravada na init (depois dos tipos) e flush ao desativar
require '/p/cotas-tec.php';
igual( 'flush_rewrite_rules', $GLOBALS['ct_hooks']['desativar'], 'desativação limpa as regras de endereço' );
ok( $GLOBALS['ct_prio']['init']['ctec_conferir_versao'] > $GLOBALS['ct_prio']['init']['ctec_registrar_tipos'], 'versão conferida depois de registrar os tipos' );
$GLOBALS['ct_flush'] = 0;
ctec_conferir_versao();
igual( 1, $GLOBALS['ct_flush'], 'versão nova (sem opção) renova as regras' );
igual( '0.1.1', get_option( 'ctec_versao' ), 'opção ctec_versao gravada' );
ctec_conferir_versao();
igual( 1, $GLOBALS['ct_flush'], 'mesma versão não renova de novo' );
update_option( 'ctec_versao', '0.0.9' );
ctec_conferir_versao();
igual( 2, $GLOBALS['ct_flush'], 'versão diferente renova' );
igual( '0.1.1', get_option( 'ctec_versao' ), 'opção atualizada' );

echo "\nFALHAS=" . $GLOBALS['ct_falhas'] . "\n";
