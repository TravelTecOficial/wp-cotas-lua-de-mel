<?php
/**
 * Painel: lista de Presentes (colunas, filtro por casal, CSV) e totais na tela do casal.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'manage_ctec_presente_posts_columns', 'ctec_colunas_presente' );
add_action( 'manage_ctec_presente_posts_custom_column', 'ctec_coluna_presente', 10, 2 );
add_action( 'restrict_manage_posts', 'ctec_filtro_casal' );
add_action( 'pre_get_posts', 'ctec_filtrar_lista' );
add_action( 'add_meta_boxes', 'ctec_registrar_caixa_total' );
add_action( 'admin_post_ctec_csv', 'ctec_baixar_csv' );

// ---- Dados (testados em unidade) ----

/** Presentes gravados, mais recentes primeiro; $casal_id = 0 traz todos. */
function ctec_listar_presentes( $casal_id = 0 ) {
	$args = array(
		'post_type'   => 'ctec_presente',
		'post_status' => array( 'private', 'publish' ),
		'numberposts' => -1,
		'orderby'     => 'date',
		'order'       => 'DESC',
	);
	if ( $casal_id ) {
		$args['meta_query'] = array( array( 'key' => '_ctec_casal', 'value' => (int) $casal_id ) );
	}
	return get_posts( $args );
}

/** Quantidade e soma dos presentes de um casal. */
function ctec_total_casal( $id ) {
	$qtd   = 0;
	$total = 0.0;
	foreach ( ctec_listar_presentes( (int) $id ) as $p ) {
		$qtd++;
		$total += (float) get_post_meta( $p->ID, '_ctec_valor', true );
	}
	return array( 'qtd' => $qtd, 'total' => $total );
}

/** Link wa.me só com dígitos; o 55 entra quando o número tem 10 ou 11 dígitos. */
function ctec_whatsapp_link( $numero ) {
	$d = preg_replace( '/\D/', '', (string) $numero );
	if ( '' === $d ) {
		return '';
	}
	if ( 10 === strlen( $d ) || 11 === strlen( $d ) ) {
		$d = '55' . $d;
	}
	return 'https://wa.me/' . $d;
}

/** Corta a mensagem em $max palavras. */
function ctec_resumo_mensagem( $texto, $max = 20 ) {
	$palavras = preg_split( '/\s+/u', trim( (string) $texto ), -1, PREG_SPLIT_NO_EMPTY );
	if ( count( $palavras ) <= $max ) {
		return implode( ' ', $palavras );
	}
	return implode( ' ', array_slice( $palavras, 0, $max ) ) . '…';
}

/** Uma célula de CSV: protege contra fórmulas e põe aspas quando preciso. */
function ctec_csv_celula( $valor ) {
	$v = (string) $valor;
	if ( '' !== $v && false !== strpos( '=+-@', $v[0] ) ) {
		$v = "'" . $v;
	}
	if ( preg_match( '/[;"\r\n]/', $v ) ) {
		$v = '"' . str_replace( '"', '""', $v ) . '"';
	}
	return $v;
}

/** CSV dos presentes (BOM UTF-8, separador ;). */
function ctec_csv_presentes( $casal_id = 0 ) {
	$linhas   = array();
	$linhas[] = 'Data;Casal;Cota;Valor;Convidado;E-mail;WhatsApp;Mensagem';
	foreach ( ctec_listar_presentes( (int) $casal_id ) as $p ) {
		$casal = get_post( (int) get_post_meta( $p->ID, '_ctec_casal', true ) );
		$data  = ! empty( $p->post_date ) ? date( 'd/m/Y H:i', strtotime( $p->post_date ) ) : '';
		$cels  = array(
			$data,
			$casal ? $casal->post_title : '',
			get_post_meta( $p->ID, '_ctec_cota', true ),
			number_format( (float) get_post_meta( $p->ID, '_ctec_valor', true ), 2, ',', '' ),
			get_post_meta( $p->ID, '_ctec_nome', true ),
			get_post_meta( $p->ID, '_ctec_email', true ),
			get_post_meta( $p->ID, '_ctec_whatsapp', true ),
			get_post_meta( $p->ID, '_ctec_mensagem', true ),
		);
		$linhas[] = implode( ';', array_map( 'ctec_csv_celula', $cels ) );
	}
	return "\xEF\xBB\xBF" . implode( "\r\n", $linhas ) . "\r\n";
}

// ---- Telas (dependem do painel do WordPress) ----

function ctec_colunas_presente( $colunas ) {
	return array(
		'cb'       => isset( $colunas['cb'] ) ? $colunas['cb'] : '',
		'data'     => 'Data',
		'casal'    => 'Casal',
		'cota'     => 'Cota',
		'valor'    => 'Valor',
		'nome'     => 'Convidado',
		'email'    => 'E-mail',
		'whatsapp' => 'WhatsApp',
		'mensagem' => 'Mensagem',
	);
}

function ctec_coluna_presente( $coluna, $post_id ) {
	$m = function ( $k ) use ( $post_id ) {
		return get_post_meta( $post_id, '_ctec_' . $k, true );
	};
	switch ( $coluna ) {
		case 'data':
			echo esc_html( get_the_date( 'd/m/Y H:i', $post_id ) );
			break;
		case 'casal':
			$c = get_post( (int) $m( 'casal' ) );
			if ( $c ) {
				echo '<a href="' . esc_url( get_edit_post_link( $c->ID ) ) . '">' . esc_html( $c->post_title ) . '</a>';
			}
			break;
		case 'valor':
			echo esc_html( ctec_formatar_valor( (float) $m( 'valor' ) ) );
			break;
		case 'whatsapp':
			$link = ctec_whatsapp_link( $m( 'whatsapp' ) );
			echo $link ? '<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener">' . esc_html( $m( 'whatsapp' ) ) . '</a>' : '';
			break;
		case 'mensagem':
			echo esc_html( ctec_resumo_mensagem( $m( 'mensagem' ), 20 ) );
			break;
		case 'cota':
		case 'nome':
		case 'email':
			echo esc_html( $m( $coluna ) );
			break;
	}
}

/** Select "Todos os casais" e botão "Exportar CSV" acima da lista. */
function ctec_filtro_casal( $tipo ) {
	if ( 'ctec_presente' !== $tipo ) {
		return;
	}
	$atual  = isset( $_GET['ctec_casal'] ) ? absint( $_GET['ctec_casal'] ) : 0;
	$casais = get_posts( array( 'post_type' => 'ctec_casal', 'post_status' => array( 'publish', 'draft', 'private' ), 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	echo '<select name="ctec_casal"><option value="0">Todos os casais</option>';
	foreach ( $casais as $c ) {
		echo '<option value="' . esc_attr( $c->ID ) . '"' . ( $atual === (int) $c->ID ? ' selected' : '' ) . '>' . esc_html( $c->post_title ) . '</option>';
	}
	echo '</select>';
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=ctec_csv&ctec_casal=' . $atual ), 'ctec_csv' );
	echo ' <a class="button" href="' . esc_url( $url ) . '">Exportar CSV</a>';
}

/** Aplica o filtro de casal só na lista principal de Presentes no painel. */
function ctec_filtrar_lista( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'ctec_presente' !== $query->get( 'post_type' ) ) {
		return;
	}
	$id = isset( $_GET['ctec_casal'] ) ? absint( $_GET['ctec_casal'] ) : 0;
	if ( $id ) {
		$query->set( 'meta_query', array( array( 'key' => '_ctec_casal', 'value' => $id ) ) );
	}
}

/** Download do CSV: precisa de permissão e nonce. */
function ctec_baixar_csv() {
	$nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
	if ( ! current_user_can( 'edit_posts' ) || ! wp_verify_nonce( $nonce, 'ctec_csv' ) ) {
		wp_die( 'Sem permissão.', '', array( 'response' => 403 ) );
	}
	$id    = isset( $_GET['ctec_casal'] ) ? absint( $_GET['ctec_casal'] ) : 0;
	$casal = $id ? get_post( $id ) : null;
	$nome  = 'presentes-' . ( $casal && $casal->post_name ? $casal->post_name : 'todos' ) . '-' . gmdate( 'Y-m-d' ) . '.csv';
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $nome . '"' );
	echo ctec_csv_presentes( $id ); // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}

function ctec_registrar_caixa_total() {
	add_meta_box( 'ctec_total', 'Presentes recebidos', 'ctec_desenhar_caixa_total', 'ctec_casal', 'side', 'default' );
}

function ctec_desenhar_caixa_total( $post ) {
	$t   = ctec_total_casal( $post->ID );
	$url = admin_url( 'edit.php?post_type=ctec_presente&ctec_casal=' . (int) $post->ID );
	echo '<p><strong>' . (int) $t['qtd'] . ( 1 === $t['qtd'] ? ' presente' : ' presentes' ) . ' · ' . esc_html( ctec_formatar_valor( $t['total'] ) ) . '</strong></p>';
	echo '<p><a href="' . esc_url( $url ) . '">Ver presentes</a></p>';
}
