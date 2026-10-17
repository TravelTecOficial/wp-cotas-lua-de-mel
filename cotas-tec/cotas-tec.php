<?php
/**
 * Plugin Name: Cotas Tec
 * Description: Cotas de lua de mel: página do casal, presentes dos convidados e dados para depósito.
 * Version:     0.1.0
 * Author:      TravelTec
 * Text Domain: cotas-tec
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CTEC_VERSION', '0.1.0' );
define( 'CTEC_DIR', plugin_dir_path( __FILE__ ) );
define( 'CTEC_URL', plugin_dir_url( __FILE__ ) );
define( 'CTEC_BASENAME', plugin_basename( __FILE__ ) );

foreach ( array( 'atualizador' ) as $ctec_arquivo ) {
	require_once CTEC_DIR . 'includes/' . $ctec_arquivo . '.php';
}
