<?php
// Estrutura do plugin: versão coerente, CHANGELOG e guarda ABSPATH nos includes.
require __DIR__ . '/bootstrap.php';

$principal = file_get_contents( '/p/cotas-tec.php' );
ok( 1 === preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $principal, $m ), 'cabeçalho tem Version:' );
$cabecalho = isset( $m[1] ) ? $m[1] : '';
igual( '0.1.0', $cabecalho, 'Version do cabeçalho é 0.1.0' );
ok( 1 === preg_match( "/define\( 'CTEC_VERSION', '([^']+)' \)/", $principal, $c ), 'CTEC_VERSION definida' );
igual( $cabecalho, isset( $c[1] ) ? $c[1] : '', 'CTEC_VERSION == Version do cabeçalho' );

$changelog = is_file( '/c/CHANGELOG.md' ) ? file_get_contents( '/c/CHANGELOG.md' ) : '';
contem( '## 0.1.0', $changelog, 'CHANGELOG.md tem ## 0.1.0' );

$arquivos = glob( '/p/includes/*.php' );
ok( count( $arquivos ) > 0, 'includes/ tem arquivos' );
foreach ( $arquivos as $arq ) {
	$ini = substr( ltrim( file_get_contents( $arq ) ), 0, 1500 );
	contem( "if ( ! defined( 'ABSPATH' ) )", $ini, basename( $arq ) . ' começa com o guarda ABSPATH' );
}
echo "\nFALHAS=" . $GLOBALS['ct_falhas'] . "\n";
