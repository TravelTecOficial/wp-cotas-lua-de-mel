<?php
// Base dos testes: constantes, stubs mínimos do WordPress e verificadores. Não vai no zip.
define( 'ABSPATH', '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'CTEC_TESTE', true );

$GLOBALS['ct_falhas']     = 0;
$GLOBALS['ct_opcoes']     = array();
$GLOBALS['ct_transients'] = array();   // chave => [valor, ttl]
$GLOBALS['ct_ganchos']    = array();   // gancho => [callbacks]
$GLOBALS['ct_meta']       = array();   // id => [chave => valor]
$GLOBALS['ct_posts']      = array();   // id => objeto do post
$GLOBALS['ct_emails']     = array();   // cada wp_mail gravado
$GLOBALS['ct_mail_ok']    = true;
$GLOBALS['ct_tipos']      = array();   // register_post_type

function ok( $cond, $msg ) {
	echo ( $cond ? 'OK    ' : 'FALHA ' ) . $msg . "\n";
	if ( ! $cond ) { $GLOBALS['ct_falhas']++; }
}
function igual( $esperado, $atual, $msg ) {
	$bom = $esperado === $atual;
	echo ( $bom ? 'OK    ' : 'FALHA ' ) . $msg . "\n";
	if ( ! $bom ) {
		echo '      esperado: ' . var_export( $esperado, true ) . "\n      atual:    " . var_export( $atual, true ) . "\n";
		$GLOBALS['ct_falhas']++;
	}
}
function contem( $trecho, $texto, $msg ) { ok( false !== strpos( (string) $texto, $trecho ), $msg ); }
function nao_contem( $trecho, $texto, $msg ) { ok( false === strpos( (string) $texto, $trecho ), $msg ); }

class WP_Error {
	public $code; public $message; public $data;
	public function __construct( $code = '', $message = '', $data = '' ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $x ) { return $x instanceof WP_Error; }

function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url_raw( $s ) { return (string) $s; }
function esc_textarea( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_textarea_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_email( $s ) { return preg_replace( '/[^a-zA-Z0-9@._+\-]/', '', (string) $s ); }
function is_email( $s ) { return filter_var( $s, FILTER_VALIDATE_EMAIL ) ? $s : false; }
function wp_kses_post( $s ) { return $s; }
function remove_accents( $s ) {
	return strtr( $s, array( 'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c','Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ç'=>'C' ) );
}
function sanitize_title( $s ) {
	$s = strtolower( remove_accents( (string) $s ) );
	$s = preg_replace( '/[^a-z0-9]+/', '-', $s );
	return trim( $s, '-' );
}
function home_url( $p = '' ) { return 'https://exemplo.test' . $p; }
function admin_url( $p = '' ) { return 'https://exemplo.test/wp-admin/' . $p; }
function get_permalink( $id = 0 ) { return 'https://exemplo.test/?p=' . (int) $id; }
function wp_get_attachment_image_url( $id, $tam = 'thumbnail' ) { return $id ? 'https://exemplo.test/wp-content/uploads/' . (int) $id . '.jpg' : false; }

function get_option( $k, $padrao = false ) { return array_key_exists( $k, $GLOBALS['ct_opcoes'] ) ? $GLOBALS['ct_opcoes'][ $k ] : $padrao; }
function update_option( $k, $v, $autoload = null ) { $GLOBALS['ct_opcoes'][ $k ] = $v; return true; }
function get_transient( $k ) { return isset( $GLOBALS['ct_transients'][ $k ] ) ? $GLOBALS['ct_transients'][ $k ][0] : false; }
function set_transient( $k, $v, $ttl = 0 ) { $GLOBALS['ct_transients'][ $k ] = array( $v, $ttl ); return true; }
function delete_transient( $k ) { unset( $GLOBALS['ct_transients'][ $k ] ); return true; }

function get_post_meta( $id, $chave = '', $unico = false ) {
	if ( '' === $chave ) { return isset( $GLOBALS['ct_meta'][ $id ] ) ? $GLOBALS['ct_meta'][ $id ] : array(); }
	if ( isset( $GLOBALS['ct_meta'][ $id ][ $chave ] ) ) { return $GLOBALS['ct_meta'][ $id ][ $chave ]; }
	return $unico ? '' : array();
}
function update_post_meta( $id, $chave, $valor ) { $GLOBALS['ct_meta'][ $id ][ $chave ] = $valor; return true; }
function get_post( $id ) { return isset( $GLOBALS['ct_posts'][ $id ] ) ? $GLOBALS['ct_posts'][ $id ] : null; }
function wp_insert_post( $dados, $erro = false ) {
	$id = count( $GLOBALS['ct_posts'] ) + 1;
	$GLOBALS['ct_posts'][ $id ] = (object) array_merge( array( 'ID' => $id ), $dados );
	return $id;
}
function register_post_type( $tipo, $args = array() ) { $GLOBALS['ct_tipos'][ $tipo ] = $args; return true; }
function wp_mail( $para, $assunto, $corpo, $cabecalhos = '' ) {
	$GLOBALS['ct_emails'][] = array( 'para' => $para, 'assunto' => $assunto, 'corpo' => $corpo, 'cabecalhos' => $cabecalhos );
	return $GLOBALS['ct_mail_ok'];
}

function add_action( $g, $cb, $p = 10, $n = 1 ) { $GLOBALS['ct_ganchos'][ $g ][] = $cb; }
function add_filter( $g, $cb, $p = 10, $n = 1 ) { $GLOBALS['ct_ganchos'][ $g ][] = $cb; }
function register_activation_hook( $f, $cb ) {}
function plugin_dir_path( $f ) { return '/p/'; }
function plugin_dir_url( $f ) { return 'https://exemplo.test/wp-content/plugins/cotas-tec/'; }
function plugin_basename( $f ) { return 'cotas-tec/cotas-tec.php'; }
