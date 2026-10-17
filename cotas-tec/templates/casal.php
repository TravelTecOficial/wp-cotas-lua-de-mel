<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$ctec_html = ctec_render_casal( ctec_dados_casal( get_queried_object_id() ) );
echo '<main id="content" class="ctec-main">' . $ctec_html . '</main>'; // phpcs:ignore -- HTML escapado em ctec_render_casal
get_footer();
