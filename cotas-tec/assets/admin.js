// Tela do casal: linhas de cota, foto pelo seletor de mídia e botão de copiar o link.
( function () {
	var tabela = document.querySelector( '.ctec-cotas tbody' );
	var molde = document.getElementById( 'ctec-molde' );
	if ( ! tabela || ! molde ) { return; }

	// Mantém os índices ctec_cotas[i] sem buraco depois de remover/adicionar.
	function renumerar() {
		tabela.querySelectorAll( '.ctec-linha' ).forEach( function ( linha, i ) {
			linha.querySelectorAll( '[name^="ctec_cotas["]' ).forEach( function ( campo ) {
				campo.name = campo.name.replace( /^ctec_cotas\[[^\]]*\]/, 'ctec_cotas[' + i + ']' );
			} );
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		var alvo = e.target;
		if ( alvo.classList.contains( 'ctec-adicionar' ) ) {
			tabela.insertAdjacentHTML( 'beforeend', molde.innerHTML );
			renumerar();
		} else if ( alvo.classList.contains( 'ctec-remover' ) ) {
			alvo.closest( '.ctec-linha' ).remove();
			renumerar();
		} else if ( alvo.classList.contains( 'ctec-escolher' ) && window.wp && wp.media ) {
			var celula = alvo.closest( '.ctec-foto' );
			var quadro = wp.media( { title: 'Foto da cota', multiple: false, library: { type: 'image' } } );
			quadro.on( 'select', function () {
				var anexo = quadro.state().get( 'selection' ).first().toJSON();
				var url = ( anexo.sizes && anexo.sizes.thumbnail ) ? anexo.sizes.thumbnail.url : anexo.url;
				celula.querySelector( 'input[type="hidden"]' ).value = anexo.id;
				var img = celula.querySelector( 'img' );
				img.src = url;
				img.hidden = false;
			} );
			quadro.open();
		} else if ( alvo.classList.contains( 'ctec-copiar' ) ) {
			var campo = document.querySelector( '.ctec-link-campo' );
			campo.select();
			if ( navigator.clipboard ) { navigator.clipboard.writeText( campo.value ); } else { document.execCommand( 'copy' ); }
			alvo.textContent = 'Copiado!';
		}
	} );
} )();
