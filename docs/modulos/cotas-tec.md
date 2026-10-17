# Módulo: Cotas Tec

## Objetivo
Agências de viagens oferecem lista de cotas de lua de mel. A agência cadastra vários casais; cada casal tem
página pública com fotos, mensagem e cotas (nome + valor). O convidado escolhe a cota, informa seus dados e vê
os dados da conta do casal para depósito. O casal recebe e-mail; a agência vê todos os presentes no painel.
Sem pagamento online. Desenho completo: `docs/superpowers/specs/2026-10-17-cotas-tec-design.md`.

## Etapas
- Pronta no DEV: 0.1.0 esqueleto, testes, empacotador e atualizador (17/10/2026), ainda não instalado em site.
- Em andamento: casal (tipo de post, cotas, rotas).
- Falta: página pública, presentear (formulário, e-mail, gravação), painel de presentes + CSV, release no GitHub.
- No PROD: nada.

## Regras e decisões do dono
- Plugin separado (não é aba do Voucher Tec). Uma agência, vários casais, uma conta por casal.
- Presentear: formulário com nome, e-mail, WhatsApp e mensagem; depois mostra os dados da conta (botão Copiar).
- Convidado pode criar o próprio presente (nome + valor livre, mínimo R$ 1) no card "Monte seu presente".
- Casal recebe e-mail (cota, valor, dados do convidado, Reply-To = convidado); convidado e agência não recebem.
- Cada presente fica gravado no painel (somente leitura, filtro por casal, CSV).
- Sem página de busca/lista de casais; acesso só pelo link do casal. Página do casal com `noindex`.
- Sem caixa de consentimento: contatos não são usados para marketing.
- Não mexer em cabeçalho/rodapé/Theme Builder do site.
- Endereço base `/lua-de-mel/`; cotas padrão Bronze 50, Prata 100, Ouro 250, Diamante 500, Safira 1.000.

## Onde está o código
Repo `C:/Users/User/Documents/Clientes/_wordpress-base/padrao/plugin-cotas`, pasta `cotas-tec/`
(`cotas-tec.php`, `includes/`). Testes em `tests/`. Pacote por `empacotar.py`.
Site de teste: site-modelo do Voucher Tec (yellowgreen-termite-955684), nunca localhost.

## Pendências de PROD
Repo `TravelTecOficial/cotas-tec` e release com anexo `cotas-tec.zip`; só com OK do dono.

## Próximo passo
Task 2: tipo de post do casal, cotas padrão e rotas (adicionar `register_activation_hook`).
