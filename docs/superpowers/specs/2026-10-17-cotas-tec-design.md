# Cotas Tec — plugin de cotas de lua de mel (design)

Data: 17/10/2026 · Referência visual: https://escultur.com.br/lua-de-mel-cotas/cota/nome/rogerio-jessica

## Por quê
Agências de viagens vendem lua de mel. Com uma lista de "cotas", os convidados do casamento presenteiam
o casal com valores que vão direto para a conta do casal. A agência oferece isso como serviço e fica
com o casal como cliente da viagem. Sistema simples: **sem pagamento online**, só mostra onde depositar.

## Objetivos
- Agência cadastra vários casais no painel do WordPress.
- Cada casal tem uma página pública com fotos, mensagem e cotas (nome + valor).
- Convidado escolhe a cota, informa seus dados e vê os dados da conta do casal para depósito.
- Casal recebe e-mail com o presente; a agência vê todos os presentes gravados no painel.

## Fora do escopo (não fazer)
- Pagamento online, gateway, PIX dinâmico, confirmação de depósito.
- Página pública com lista/busca de casais (decisão do dono: acesso só pelo link que o casal divulga).
- Login para o casal.
- Integração com Voucher Tec, Cliente Ideal ou n8n (pode vir depois).

## Anti-objetivos
- Mexer no cabeçalho/rodapé/Theme Builder do site (lição do incidente AV Turismo, Voucher Tec 2.3.4).
- Expor os dados da conta para buscadores: página do casal com `noindex`.

## Decisões do dono
- Plugin **separado** (não é aba do Voucher Tec).
- Uma agência, vários casais; uma conta (banco/PIX) por casal.
- Ao clicar em "Presentear": formulário com **nome, e-mail, WhatsApp e mensagem** → mostra os dados da conta.
- Casal recebe e-mail com cota, valor e dados do convidado.
- Cada presente fica gravado no painel.
- Sem página de busca; cada casal é acessado pelo link que ele divulga.
- Convidado pode criar seu próprio presente (nome + valor livre), além das cotas do casal.
- Contatos dos convidados **não** são usados para marketing: sem caixa de consentimento.
- E-mail só para o casal (convidado e agência não recebem).

## Como funciona

### Cadastro do casal (painel › Cotas de Lua de Mel › Casais)
Tipo de post próprio. Campos:
- Nomes do casal (título), ex.: "Rogério & Jéssica" → endereço `/lua-de-mel/rogerio-jessica/`.
- Foto de capa; cidade; data do casamento; mensagem dos noivos.
- E-mail do casal (recebe os avisos).
- Dados da conta: texto livre (banco, agência, conta, titular, chave PIX) — exibido como está.
- Cotas: lista repetível com nome, valor e foto opcional. Casal novo já vem com a sugestão
  Bronze R$ 50, Prata R$ 100, Ouro R$ 250, Diamante R$ 500, Safira R$ 1.000 (editável).

### Página do casal
Mesmo desenho do exemplo da Escultur, no padrão visual de Roteiros/Experiências do Voucher Tec
(cabeçalho do próprio site, foto de capa, títulos no tamanho do site):
foto de capa, nomes, cidade e data, botão Imprimir, "Mensagem dos noivos", "Como funciona"
(3 passos fixos), "Escolha seu presente" (cards com foto, nome, valor e botão Presentear).

Depois das cotas do casal, um card extra **"Monte seu presente"**: o convidado escreve o nome do
presente (ex.: "Jantar romântico") e o valor que quer dar (decisão do dono, 17/10/2026).

### Presentear
1. Clique em "Presentear" abre uma janela com a cota e o valor e o formulário (nome, e-mail, WhatsApp,
   mensagem; todos obrigatórios exceto a mensagem). Nas cotas do casal o valor é fixo; no card
   "Monte seu presente" o convidado preenche também nome do presente e valor (mínimo R$ 1).
2. Ao enviar: grava o presente, manda o e-mail ao casal e troca o formulário pelos dados da conta,
   com botão "Copiar".
3. Proteção simples contra robô (campo isca + nonce). Sem CAPTCHA.

### Presentes (painel › Cotas de Lua de Mel › Presentes)
Lista com data, casal, cota, valor, convidado, e-mail, WhatsApp e mensagem; filtro por casal;
exportar CSV. Só leitura (pode excluir). Total por casal na tela do casal.

### E-mail ao casal
Pelo `wp_mail` do site. Assunto "Você recebeu um presente: <cota>". Corpo com cota, valor, nome,
e-mail, WhatsApp e mensagem do convidado, e o aviso de que o depósito deve ser conferido na conta.
Responder ao e-mail responde ao convidado (Reply-To). Se o site não tiver SMTP, o e-mail pode não chegar
— o presente fica gravado mesmo assim.

## Restrições
- WordPress + tema Hello/Elementor dos clientes; não depende do Elementor para funcionar.
- Distribuição igual ao Viator Tec: repositório no GitHub (TravelTecOficial) com atualização automática
  pelo painel; versão exibida e CHANGELOG em toda entrega.
- Testes no site-modelo do Voucher Tec (yellowgreen-termite-955684), nunca em localhost.
- Documento de módulo em `docs/modulos/cotas-tec.md`.

## Deixado para quem constrói (minhas escolhas, o dono pode mudar)
- Nome "Cotas Tec" e endereço base `/lua-de-mel/`.
- Cotas gravadas como metadado do casal; presentes como tipo de post privado.
- Sem limite de quantidade por cota (várias pessoas podem dar a mesma).
- Textos de "Como funciona" fixos.
- Visual de Roteiros/Experiências **copiado** para dentro do plugin (não depende do Voucher Tec instalado).
- Casal sai do ar passando para rascunho; sem data de expiração.
