(function () {
  var raiz = document.querySelector('.ctec');
  if (!raiz || typeof ctecDados === 'undefined') { return; }
  var janela = raiz.querySelector('.ctec-janela');
  var form = janela.querySelector('.ctec-form');
  var sucesso = janela.querySelector('.ctec-sucesso');
  var corpo = janela.querySelector('.ctec-sucesso-corpo');
  var erro = janela.querySelector('.ctec-erro');
  var livre = janela.querySelector('.ctec-livre');
  var escolha = janela.querySelector('.ctec-escolha');
  var botao = form.querySelector('button[type=submit]');
  var cota = '';

  function abrir(card) {
    cota = card.getAttribute('data-cota');
    var ehLivre = cota === 'livre';
    livre.hidden = !ehLivre;
    escolha.textContent = ehLivre ? 'Monte seu presente'
      : card.querySelector('.ctec-cota-nome').textContent + ' · ' + card.querySelector('.ctec-cota-valor').textContent;
    form.reset();
    botao.disabled = false;
    corpo.textContent = '';
    erro.hidden = true;
    form.hidden = false;
    sucesso.hidden = true;
    if (janela.showModal) { janela.showModal(); } else { janela.setAttribute('open', ''); }
  }

  raiz.addEventListener('click', function (e) {
    var b = e.target.closest('.ctec-presentear');
    if (b) { abrir(b.closest('.ctec-cota')); }
  });
  Array.prototype.forEach.call(janela.querySelectorAll('.ctec-fechar'), function (b) {
    b.addEventListener('click', function () { janela.close(); });
  });
  janela.addEventListener('click', function (e) { if (e.target === janela) { janela.close(); } });

  function mostrarErro(msg) { erro.textContent = msg; erro.hidden = false; botao.disabled = false; }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var f = form.elements;
    var dados = {
      casal: ctecDados.casal, cota: cota,
      nome: f.nome.value, email: f.email.value, whatsapp: f.whatsapp.value, mensagem: f.mensagem.value,
      presente_nome: cota === 'livre' ? f.presente_nome.value : '',
      presente_valor: cota === 'livre' ? f.presente_valor.value : '',
      ctec_site: f.ctec_site.value
    };
    if (!dados.nome.trim() || !dados.email.trim() || !dados.whatsapp.trim()) { return mostrarErro('Preencha nome, e-mail e WhatsApp.'); }
    if (cota === 'livre' && (!dados.presente_nome.trim() || !dados.presente_valor.trim())) { return mostrarErro('Informe o nome e o valor do presente.'); }
    erro.hidden = true;
    botao.disabled = true;
    fetch(ctecDados.rest, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(dados) })
      .then(function (r) { return r.json().catch(function () { return {}; }); })
      .then(function (r) {
        if (!r || !r.ok) { return mostrarErro((r && r.message) || 'Não foi possível registrar. Tente de novo.'); }
        corpo.textContent = '';
        var t = document.createElement('h2'); t.textContent = 'Obrigado, ' + dados.nome + '!';
        var p = document.createElement('p'); p.textContent = 'Faça o depósito na conta do casal:';
        var pre = document.createElement('pre'); pre.className = 'ctec-conta'; pre.textContent = r.conta || '';
        var c = document.createElement('button'); c.type = 'button'; c.className = 'ctec-btn'; c.textContent = 'Copiar';
        c.addEventListener('click', function () {
          if (navigator.clipboard) { navigator.clipboard.writeText(pre.textContent).then(function () { c.textContent = 'Copiado!'; }); }
        });
        corpo.appendChild(t); corpo.appendChild(p); corpo.appendChild(pre); corpo.appendChild(c);
        form.hidden = true; sucesso.hidden = false; form.reset(); botao.disabled = false;
      })
      .catch(function () { mostrarErro('Sem conexão. Tente de novo.'); });
  });
})();
