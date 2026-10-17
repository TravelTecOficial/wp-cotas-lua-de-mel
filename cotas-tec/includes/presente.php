<?php
/**
 * Presentear: endpoint público, validação, gravação e aviso por e-mail ao casal.
 *
 * Sem nonce (as páginas ficam em cache); a proteção é a isca (ctec_site) e o limite por IP.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'ctec_registrar_rota' );

function ctec_registrar_rota() {
	register_rest_route(
		'ctec/v1',
		'/presente',
		array(
			'methods'             => 'POST',
			'callback'            => 'ctec_rota_presente',
			'permission_callback' => '__return_true',
		)
	);
}

/** Callback da rota: lê o JSON enviado e entrega para ctec_processar_presente. */
function ctec_rota_presente( $request ) {
	$dados = $request->get_json_params();
	if ( ! is_array( $dados ) ) {
		$dados = $request->get_params();
	}
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$res = ctec_processar_presente( is_array( $dados ) ? $dados : array(), $ip );
	return is_wp_error( $res ) ? $res : rest_ensure_response( $res );
}

define( 'CTEC_LIMITE_HORA', 20 );

/** Janela do limite: array( envios, início ). Vazia ou vencida = zero envios. */
function ctec_janela_limite( $chave ) {
	$j = get_transient( $chave );
	if ( is_array( $j ) && isset( $j[0], $j[1] ) && $j[1] + HOUR_IN_SECONDS > time() ) {
		return array( (int) $j[0], (int) $j[1] );
	}
	return array( 0, time() );
}

/** Soma um envio na janela fixa; o transient expira junto com a janela original. */
function ctec_contar_limite( $chave, array $janela ) {
	$ttl = max( 1, $janela[1] + HOUR_IN_SECONDS - time() );
	set_transient( $chave, array( $janela[0] + 1, $janela[1] ), $ttl );
}

/** Corta em $max caracteres (multibyte). */
function ctec_cortar( $texto, $max ) {
	return function_exists( 'mb_substr' ) ? mb_substr( $texto, 0, $max, 'UTF-8' ) : substr( $texto, 0, $max );
}

/** Texto de uma linha só, sem tags (nada de quebra de linha em nomes e cabeçalhos). */
function ctec_linha( $texto ) {
	return trim( preg_replace( '/\s+/u', ' ', sanitize_text_field( (string) $texto ) ) );
}

/** Fluxo completo: isca, limite, casal, validação, gravação, e-mail. */
function ctec_processar_presente( array $dados, $ip ) {
	// 1. Isca preenchida: finge sucesso, sem gravar nem contar.
	if ( ! empty( $dados['ctec_site'] ) ) {
		return array( 'ok' => true, 'conta' => '' );
	}

	// Campos que não são texto/número (lista, objeto) viram vazio: a validação cuida do resto.
	foreach ( $dados as $k => $v ) {
		if ( ! is_scalar( $v ) ) {
			$dados[ $k ] = '';
		}
	}

	// 2. Limite por IP e casal: 20 envios válidos por hora (confere aqui, conta depois da validação).
	$id     = isset( $dados['casal'] ) ? absint( $dados['casal'] ) : 0;
	$chave  = 'ctec_ip_' . md5( $ip . '|' . $id );
	$janela = ctec_janela_limite( $chave );
	if ( $janela[0] >= CTEC_LIMITE_HORA ) {
		return new WP_Error( 'ctec_limite', 'Muitos envios. Tente de novo mais tarde.', array( 'status' => 429 ) );
	}

	// 3. Casal publicado.
	$post = $id ? get_post( $id ) : null;
	if ( ! $post || 'ctec_casal' !== $post->post_type || 'publish' !== $post->post_status ) {
		return new WP_Error( 'ctec_casal', 'Página não encontrada.', array( 'status' => 404 ) );
	}
	$casal = ctec_dados_casal( $id );

	// 4. Validação.
	$p = ctec_validar_presente( $dados, $casal );
	if ( is_wp_error( $p ) ) {
		return $p;
	}

	// Presente válido: agora conta no limite (janela fixa, mantém o fim original).
	ctec_contar_limite( $chave, $janela );

	// 5. Gravação.
	if ( ! ctec_gravar_presente( $p ) ) {
		return new WP_Error( 'ctec_erro', 'Não foi possível registrar o presente. Tente de novo.', array( 'status' => 500 ) );
	}

	// 6. Aviso ao casal (se falhar, o presente continua registrado).
	if ( '' !== $casal['email'] ) {
		$e = ctec_email_presente( $p, $casal );
		wp_mail( $e['para'], $e['assunto'], $e['corpo'], $e['cabecalhos'] );
	}

	return array( 'ok' => true, 'conta' => $casal['conta'] );
}

function ctec_erro_dados( $mensagem ) {
	return new WP_Error( 'ctec_dados', $mensagem, array( 'status' => 400 ) );
}

/** Confere os dados do convidado; nome e valor das cotas fixas vêm sempre do cadastro. */
function ctec_validar_presente( array $dados, array $casal ) {
	$nome     = ctec_cortar( ctec_linha( isset( $dados['nome'] ) ? $dados['nome'] : '' ), 80 );
	$email    = ctec_cortar( sanitize_email( isset( $dados['email'] ) ? $dados['email'] : '' ), 100 );
	$whatsapp = ctec_cortar( ctec_linha( isset( $dados['whatsapp'] ) ? $dados['whatsapp'] : '' ), 30 );
	$msg      = ctec_cortar( sanitize_textarea_field( isset( $dados['mensagem'] ) ? (string) $dados['mensagem'] : '' ), 500 );

	if ( '' === $nome ) {
		return ctec_erro_dados( 'Informe o seu nome.' );
	}
	if ( ! is_email( $email ) ) {
		return ctec_erro_dados( 'Informe um e-mail válido.' );
	}
	if ( strlen( preg_replace( '/\D/', '', $whatsapp ) ) < 10 ) {
		return ctec_erro_dados( 'Informe um WhatsApp com DDD.' );
	}

	$cota = isset( $dados['cota'] ) ? $dados['cota'] : '';
	if ( 'livre' === $cota ) {
		$nome_cota = ctec_cortar( ctec_linha( isset( $dados['presente_nome'] ) ? $dados['presente_nome'] : '' ), 80 );
		$valor     = ctec_valor( isset( $dados['presente_valor'] ) ? $dados['presente_valor'] : '' );
		if ( '' === $nome_cota ) {
			return ctec_erro_dados( 'Diga qual presente você quer dar.' );
		}
		if ( $valor < 1 ) {
			return ctec_erro_dados( 'Informe um valor de pelo menos R$ 1,00.' );
		}
	} else {
		$cotas = array_values( $casal['cotas'] );
		$ind   = ( is_int( $cota ) || ( is_string( $cota ) && ctype_digit( $cota ) ) ) ? (int) $cota : -1;
		if ( $ind < 0 || ! isset( $cotas[ $ind ] ) ) {
			return ctec_erro_dados( 'Escolha uma cota válida.' );
		}
		$nome_cota = (string) $cotas[ $ind ]['nome'];
		$valor     = (float) $cotas[ $ind ]['valor'];
	}

	return array(
		'casal'    => (int) $casal['id'],
		'cota'     => $nome_cota,
		'valor'    => (float) $valor,
		'nome'     => $nome,
		'email'    => $email,
		'whatsapp' => $whatsapp,
		'mensagem' => $msg,
	);
}

/** Grava o presente (post privado, só visível no painel). */
function ctec_gravar_presente( array $p ) {
	$id = wp_insert_post(
		array(
			'post_type'   => 'ctec_presente',
			'post_status' => 'private',
			'post_title'  => $p['cota'] . ' — ' . $p['nome'],
		)
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	update_post_meta( $id, '_ctec_casal', $p['casal'] );
	update_post_meta( $id, '_ctec_cota', $p['cota'] );
	update_post_meta( $id, '_ctec_valor', $p['valor'] );
	update_post_meta( $id, '_ctec_nome', $p['nome'] );
	update_post_meta( $id, '_ctec_email', $p['email'] );
	update_post_meta( $id, '_ctec_whatsapp', $p['whatsapp'] );
	update_post_meta( $id, '_ctec_mensagem', $p['mensagem'] );
	return (int) $id;
}

/** E-mail em texto puro para o casal; o Reply-To é o convidado. */
function ctec_email_presente( array $p, array $casal ) {
	$linhas = array(
		'Você recebeu um presente de lua de mel.',
		'',
		'Cota: ' . $p['cota'],
		'Valor: ' . ctec_formatar_valor( $p['valor'] ),
		'Convidado: ' . $p['nome'],
		'E-mail: ' . $p['email'],
		'WhatsApp: ' . $p['whatsapp'],
		'Mensagem: ' . ( '' !== $p['mensagem'] ? $p['mensagem'] : '(sem mensagem)' ),
		'',
		'Confira o depósito na sua conta antes de agradecer.',
	);
	$nome_cab = trim( preg_replace( '/[,;"<>]/', '', $p['nome'] ) );
	return array(
		'para'       => $casal['email'],
		'assunto'    => 'Você recebeu um presente: ' . $p['cota'],
		'corpo'      => implode( "\n", $linhas ),
		'cabecalhos' => array( 'Reply-To: ' . $nome_cab . ' <' . $p['email'] . '>' ),
	);
}
