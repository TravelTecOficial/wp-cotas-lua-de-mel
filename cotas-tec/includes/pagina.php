<?php
/**
 * Página pública do casal (/lua-de-mel/nome-do-casal/).
 * Nunca imprime o e-mail nem os dados da conta: esses só voltam depois do presente registrado.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'template_include', 'ctec_template_casal' );
add_action( 'wp_head', 'ctec_noindex' );
add_action( 'wp_enqueue_scripts', 'ctec_assets_publicos' );

/** R$ 1.000,00 */
function ctec_formatar_valor( $v ) {
	return 'R$ ' . number_format( (float) $v, 2, ',', '.' );
}

/** Usa o modelo do plugin na página do casal (o cabeçalho e o rodapé continuam os do site). */
function ctec_template_casal( $modelo ) {
	if ( is_singular( 'ctec_casal' ) ) {
		return CTEC_DIR . 'templates/casal.php';
	}
	return $modelo;
}

function ctec_noindex() {
	if ( is_singular( 'ctec_casal' ) ) {
		echo '<meta name="robots" content="noindex, nofollow">' . "\n";
	}
}

function ctec_assets_publicos() {
	if ( ! is_singular( 'ctec_casal' ) ) {
		return;
	}
	wp_enqueue_style( 'ctec', CTEC_URL . 'assets/cotas-tec.css', array(), CTEC_VERSION );
	wp_enqueue_script( 'ctec', CTEC_URL . 'assets/cotas-tec.js', array(), CTEC_VERSION, true );
	wp_localize_script(
		'ctec',
		'ctecDados',
		array(
			'rest'  => rest_url( 'ctec/v1/presente' ),
			'casal' => (int) get_queried_object_id(),
		)
	);
}

/** Um card de cota: foto (ou bloco de cor), nome, valor e botão. */
function ctec_card_cota( $indice, $cota ) {
	$foto = ! empty( $cota['foto'] ) ? wp_get_attachment_image_url( (int) $cota['foto'], 'medium' ) : '';
	$img  = $foto
		? '<img class="ctec-foto" src="' . esc_url( $foto ) . '" alt="" loading="lazy">'
		: '<div class="ctec-foto ctec-foto-vazia" aria-hidden="true">&#9825;</div>';
	return '<div class="ctec-cota" data-cota="' . esc_attr( $indice ) . '">' . $img
		. '<div class="ctec-cota-corpo"><h3 class="ctec-cota-nome">' . esc_html( $cota['nome'] ) . '</h3>'
		. '<p class="ctec-cota-valor">' . esc_html( ctec_formatar_valor( $cota['valor'] ) ) . '</p>'
		. '<button type="button" class="ctec-btn ctec-presentear">Presentear</button></div></div>';
}

/** HTML da página do casal. */
function ctec_render_casal( array $casal ) {
	$local  = trim( $casal['cidade'] . ( $casal['cidade'] && $casal['data_br'] ? ' · ' : '' ) . $casal['data_br'] );
	$estilo = $casal['capa'] ? ' style="background-image:url(' . esc_url( $casal['capa'] ) . ')"' : '';

	$h  = '<div class="ctec">';
	$h .= '<header class="ctec-capa"' . $estilo . '><div class="ctec-capa-texto"><h1 class="ctec-nomes">' . esc_html( $casal['nome'] ) . '</h1>';
	if ( $local ) {
		$h .= '<p class="ctec-local">' . esc_html( $local ) . '</p>';
	}
	$h .= '</div></header>';
	$h .= '<div class="ctec-acoes"><button type="button" class="ctec-btn ctec-btn-claro" onclick="window.print()">Imprimir</button></div>';

	if ( '' !== trim( $casal['mensagem'] ) ) {
		$h .= '<section class="ctec-secao"><h2 class="ctec-titulo">Mensagem dos noivos</h2><div class="ctec-caixa ctec-mensagem">'
			. nl2br( esc_html( $casal['mensagem'] ) ) . '</div></section>';
	}

	$h .= '<section class="ctec-secao"><h2 class="ctec-titulo">Como funciona</h2><ol class="ctec-passos">'
		. '<li><span>1</span>Escolha uma cota ou monte seu presente</li>'
		. '<li><span>2</span>Deixe seu nome e uma mensagem para os noivos</li>'
		. '<li><span>3</span>Faça o depósito na conta do casal</li></ol></section>';

	$h .= '<section class="ctec-secao"><h2 class="ctec-titulo">Escolha seu presente</h2><div class="ctec-grade">';
	foreach ( $casal['cotas'] as $i => $cota ) {
		$h .= ctec_card_cota( $i, $cota );
	}
	$h .= '<div class="ctec-cota ctec-cota-livre" data-cota="livre"><div class="ctec-foto ctec-foto-vazia" aria-hidden="true">&#9733;</div>'
		. '<div class="ctec-cota-corpo"><h3 class="ctec-cota-nome">Monte seu presente</h3>'
		. '<p class="ctec-cota-valor">Você escolhe o valor</p>'
		. '<button type="button" class="ctec-btn ctec-presentear">Presentear</button></div></div>';
	$h .= '</div></section>';

	$h .= '<dialog class="ctec-janela"><form class="ctec-form" method="dialog" novalidate>'
		. '<button type="button" class="ctec-fechar" aria-label="Fechar">&times;</button>'
		. '<h2 class="ctec-janela-titulo">Presentear</h2><p class="ctec-escolha"></p>'
		. '<div class="ctec-livre"><label>Nome do presente *<input type="text" name="presente_nome" maxlength="80"></label>'
		. '<label>Valor (R$) *<input type="text" name="presente_valor" inputmode="decimal" placeholder="150,00"></label></div>'
		. '<label>Seu nome *<input type="text" name="nome" maxlength="80" required></label>'
		. '<label>E-mail *<input type="email" name="email" maxlength="120" required></label>'
		. '<label>WhatsApp *<input type="tel" name="whatsapp" maxlength="30" required></label>'
		. '<label>Mensagem para os noivos<textarea name="mensagem" rows="3" maxlength="500"></textarea></label>'
		. '<input type="text" name="ctec_site" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">'
		. '<p class="ctec-erro" role="alert" hidden></p>'
		. '<button type="submit" class="ctec-btn">Confirmar presente</button></form>'
		. '<div class="ctec-sucesso" hidden></div></dialog>';

	return $h . '</div>';
}
