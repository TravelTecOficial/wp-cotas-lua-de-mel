<?php
/**
 * Plugin Name: Cotas Tec
 * Description: Cotas de lua de mel: página do casal, presentes dos convidados e dados para depósito.
 * Version:     0.1.1
 * Author:      TravelTec
 * Text Domain: cotas-tec
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CTEC_VERSION', '0.1.1' );
define( 'CTEC_DIR', plugin_dir_path( __FILE__ ) );
define( 'CTEC_URL', plugin_dir_url( __FILE__ ) );
define( 'CTEC_BASENAME', plugin_basename( __FILE__ ) );

foreach ( array( 'casal', 'pagina', 'presente', 'admin', 'atualizador' ) as $ctec_arquivo ) {
	require_once CTEC_DIR . 'includes/' . $ctec_arquivo . '.php';
}

add_action( 'init', 'ctec_conferir_versao', 20 );

/** Depois de atualizar o plugin, renova as regras de endereço uma vez (roda após ctec_registrar_tipos). */
function ctec_conferir_versao() {
	if ( get_option( 'ctec_versao' ) !== CTEC_VERSION ) {
		flush_rewrite_rules();
		update_option( 'ctec_versao', CTEC_VERSION );
	}
}

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

register_activation_hook(
	__FILE__,
	function () {
		ctec_registrar_tipos();
		flush_rewrite_rules();
	}
);
