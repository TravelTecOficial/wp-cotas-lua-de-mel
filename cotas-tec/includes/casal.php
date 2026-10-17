<?php
/**
 * Casal: tipo de post, cotas e tela de cadastro.
 *
 * Cada casal é um post do tipo ctec_casal (página pública /lua-de-mel/nome-do-casal/).
 * Os presentes dos convidados ficam em ctec_presente (só visível no painel).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'ctec_registrar_tipos' );
add_action( 'save_post_ctec_casal', 'ctec_salvar_casal_gancho' );
add_action( 'add_meta_boxes', 'ctec_registrar_caixa' );
add_action( 'admin_enqueue_scripts', 'ctec_admin_assets' );
add_filter( 'wp_sitemaps_post_types', 'ctec_tirar_dos_sitemaps' );
add_filter( 'rank_math/sitemap/exclude_post_type', 'ctec_tirar_do_sitemap_rank_math', 10, 2 );

/** Casais nunca entram no sitemap do WordPress. */
function ctec_tirar_dos_sitemaps( $tipos ) {
	if ( is_array( $tipos ) ) {
		unset( $tipos['ctec_casal'] );
	}
	return $tipos;
}

/** Nem no sitemap do Rank Math. */
function ctec_tirar_do_sitemap_rank_math( $excluir, $tipo ) {
	return 'ctec_casal' === $tipo ? true : $excluir;
}

/** Registra os tipos de post do plugin. */
function ctec_registrar_tipos() {
	register_post_type(
		'ctec_casal',
		array(
			'labels'       => array(
				'name'          => 'Cotas de Lua de Mel',
				'singular_name' => 'Casal',
				'menu_name'     => 'Cotas de Lua de Mel',
				'all_items'     => 'Casais',
				'add_new'       => 'Adicionar casal',
				'add_new_item'  => 'Adicionar casal',
				'edit_item'     => 'Editar casal',
			),
			'public'              => true,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'rewrite'      => array( 'slug' => 'lua-de-mel', 'with_front' => false ),
			'supports'     => array( 'title', 'thumbnail' ),
			'has_archive'  => false,
			'show_in_rest' => false,
			'menu_icon'    => 'dashicons-heart',
		)
	);

	register_post_type(
		'ctec_presente',
		array(
			'labels'       => array(
				'name'          => 'Presentes',
				'singular_name' => 'Presente',
				'menu_name'     => 'Presentes',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'edit.php?post_type=ctec_casal',
			'supports'     => array( 'title' ),
			'capabilities' => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap' => true,
		)
	);
}

/** Cotas sugeridas para um casal novo. */
function ctec_cotas_padrao() {
	$cotas = array();
	foreach ( array( 'Bronze' => 50, 'Prata' => 100, 'Ouro' => 250, 'Diamante' => 500, 'Safira' => 1000 ) as $nome => $valor ) {
		$cotas[] = array( 'nome' => $nome, 'valor' => (float) $valor, 'foto' => 0 );
	}
	return $cotas;
}

/** Converte "1.500,00", "R$ 50", "50,5" ou "1500" em número. */
function ctec_valor( $texto ) {
	if ( is_int( $texto ) || is_float( $texto ) ) {
		return (float) $texto;
	}
	$t = preg_replace( '/[^0-9,.\-]/', '', (string) $texto );
	if ( false !== strpos( $t, ',' ) ) {
		$t = str_replace( ',', '.', str_replace( '.', '', $t ) );
	} elseif ( preg_match( '/^\d{1,3}(\.\d{3})+$/', $t ) ) {
		$t = str_replace( '.', '', $t ); // "1.500" é milhar, não decimal.
	}
	return is_numeric( $t ) ? (float) $t : 0.0;
}

/** Limpa as linhas de cotas vindas do formulário: sem nome ou sem valor, a linha some. */
function ctec_sanitizar_cotas( array $brutas ) {
	$cotas = array();
	foreach ( $brutas as $linha ) {
		if ( ! is_array( $linha ) ) {
			continue;
		}
		$nome  = sanitize_text_field( isset( $linha['nome'] ) ? $linha['nome'] : '' );
		$valor = ctec_valor( isset( $linha['valor'] ) ? $linha['valor'] : '' );
		if ( '' === $nome || $valor <= 0 ) {
			continue;
		}
		$cotas[] = array(
			'nome'  => $nome,
			'valor' => $valor,
			'foto'  => absint( isset( $linha['foto'] ) ? $linha['foto'] : 0 ),
		);
	}
	return $cotas;
}

/** Todos os dados de um casal, prontos para a página e para os e-mails. */
function ctec_dados_casal( $id ) {
	$post  = get_post( $id );
	$data  = (string) get_post_meta( $id, '_ctec_data', true );
	$cotas = get_post_meta( $id, '_ctec_cotas', true );
	$capa  = get_post_thumbnail_id( $id );
	$url   = $capa ? wp_get_attachment_image_url( $capa, 'large' ) : '';

	return array(
		'id'       => (int) $id,
		'nome'     => $post ? (string) $post->post_title : '',
		'slug'     => $post ? (string) $post->post_name : '',
		'link'     => get_permalink( $id ),
		'capa'     => $url ? $url : '',
		'cidade'   => (string) get_post_meta( $id, '_ctec_cidade', true ),
		'data'     => $data,
		'data_br'  => $data ? gmdate( 'd/m/Y', strtotime( $data ) ) : '',
		'mensagem' => (string) get_post_meta( $id, '_ctec_mensagem', true ),
		'email'    => (string) get_post_meta( $id, '_ctec_email', true ),
		'conta'    => (string) get_post_meta( $id, '_ctec_conta', true ),
		'cotas'    => is_array( $cotas ) ? $cotas : ctec_cotas_padrao(),
	);
}

/** Aceita só uma data real no formato Y-m-d. */
function ctec_data_valida( $texto ) {
	$texto = (string) $texto;
	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $texto, $m ) ) {
		return '';
	}
	return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $texto : '';
}

/** Grava as metas do casal a partir de dados no formato de $_POST (já sem barras). */
function ctec_salvar_casal( $post_id, array $post ) {
	$pega = function ( $chave ) use ( $post ) {
		return isset( $post[ $chave ] ) ? $post[ $chave ] : '';
	};
	$email = sanitize_email( $pega( 'ctec_email' ) );

	update_post_meta( $post_id, '_ctec_cidade', sanitize_text_field( $pega( 'ctec_cidade' ) ) );
	update_post_meta( $post_id, '_ctec_data', ctec_data_valida( $pega( 'ctec_data' ) ) );
	update_post_meta( $post_id, '_ctec_mensagem', sanitize_textarea_field( $pega( 'ctec_mensagem' ) ) );
	update_post_meta( $post_id, '_ctec_email', is_email( $email ) ? $email : '' );
	update_post_meta( $post_id, '_ctec_conta', sanitize_textarea_field( $pega( 'ctec_conta' ) ) );
	$cotas = $pega( 'ctec_cotas' );
	update_post_meta( $post_id, '_ctec_cotas', ctec_sanitizar_cotas( is_array( $cotas ) ? $cotas : array() ) );
}

/** Gancho de save_post: confere nonce, autosave e permissão antes de gravar. */
function ctec_salvar_casal_gancho( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	$nonce = isset( $_POST['ctec_casal_nonce'] ) ? $_POST['ctec_casal_nonce'] : '';
	if ( ! wp_verify_nonce( $nonce, 'ctec_casal' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	ctec_salvar_casal( $post_id, wp_unslash( $_POST ) );
}

// ---- Tela de edição (não testada em unidade: depende do painel do WordPress) ----

function ctec_registrar_caixa() {
	add_meta_box( 'ctec_dados', 'Dados do casal', 'ctec_desenhar_caixa', 'ctec_casal', 'normal', 'high' );
}

/** Carrega o seletor de mídia e os arquivos da tela do casal. */
function ctec_admin_assets( $sufixo ) {
	$tela = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $tela || 'ctec_casal' !== $tela->post_type || ! in_array( $sufixo, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'ctec-admin', CTEC_URL . 'assets/admin.css', array(), CTEC_VERSION );
	wp_enqueue_script( 'ctec-admin', CTEC_URL . 'assets/admin.js', array(), CTEC_VERSION, true );
}

/** Uma linha da tabela de cotas (também usada como molde para "Adicionar cota"). */
function ctec_linha_cota( $i, $cota ) {
	$foto = (int) $cota['foto'];
	$url  = $foto ? wp_get_attachment_image_url( $foto, 'thumbnail' ) : '';
	$n    = 'ctec_cotas[' . $i . ']';
	?>
	<tr class="ctec-linha">
		<td><input type="text" name="<?php echo esc_attr( $n ); ?>[nome]" value="<?php echo esc_attr( $cota['nome'] ); ?>" placeholder="Ex.: Jantar romântico"></td>
		<td><input type="text" name="<?php echo esc_attr( $n ); ?>[valor]" value="<?php echo esc_attr( number_format( (float) $cota['valor'], 2, ',', '.' ) ); ?>" class="ctec-valor"></td>
		<td class="ctec-foto">
			<input type="hidden" name="<?php echo esc_attr( $n ); ?>[foto]" value="<?php echo esc_attr( $foto ); ?>">
			<img src="<?php echo esc_url( $url ); ?>" alt=""<?php echo $url ? '' : ' hidden'; ?>>
			<button type="button" class="button ctec-escolher">Foto</button>
		</td>
		<td><button type="button" class="button-link-delete ctec-remover">Remover</button></td>
	</tr>
	<?php
}

function ctec_desenhar_caixa( $post ) {
	$d = ctec_dados_casal( $post->ID );
	wp_nonce_field( 'ctec_casal', 'ctec_casal_nonce' );
	?>
	<div class="ctec-caixa">
		<?php if ( 'publish' === $post->post_status ) : ?>
			<p class="ctec-link">
				Link da página:
				<input type="text" readonly value="<?php echo esc_attr( $d['link'] ); ?>" class="ctec-link-campo">
				<button type="button" class="button ctec-copiar">Copiar</button>
			</p>
		<?php endif; ?>
		<p><label>Cidade ou destino<br><input type="text" name="ctec_cidade" value="<?php echo esc_attr( $d['cidade'] ); ?>" class="widefat"></label></p>
		<p><label>Data do casamento<br><input type="date" name="ctec_data" value="<?php echo esc_attr( $d['data'] ); ?>"></label></p>
		<p><label>Mensagem do casal<br><textarea name="ctec_mensagem" rows="4" class="widefat"><?php echo esc_textarea( $d['mensagem'] ); ?></textarea></label></p>
		<p><label>E-mail do casal (recebe o aviso de cada presente)<br><input type="email" name="ctec_email" value="<?php echo esc_attr( $d['email'] ); ?>" class="widefat"></label></p>
		<p><label>Dados para depósito (banco, agência, conta, Pix)<br><textarea name="ctec_conta" rows="4" class="widefat"><?php echo esc_textarea( $d['conta'] ); ?></textarea></label></p>

		<h4>Cotas</h4>
		<table class="widefat ctec-cotas">
			<thead><tr><th>Nome</th><th>Valor (R$)</th><th>Foto</th><th></th></tr></thead>
			<tbody>
				<?php foreach ( array_values( $d['cotas'] ) as $i => $cota ) { ctec_linha_cota( $i, $cota ); } ?>
			</tbody>
		</table>
		<p><button type="button" class="button ctec-adicionar">Adicionar cota</button></p>
		<template id="ctec-molde"><?php ctec_linha_cota( '__i__', array( 'nome' => '', 'valor' => 0, 'foto' => 0 ) ); ?></template>
	</div>
	<?php
}
