# Travel Tec - Cotas de Lua-de-Mel

Plugin WordPress de cotas de lua de mel: a agência cadastra casais, cada casal tem uma página pública com cotas, e os convidados escolhem o presente e veem os dados da conta para depósito. Sem pagamento online.

- Código: `cotas-tec/`
- Testes: `cd tests && npm install && node run.mjs` (PHP 7.4 e 8.2 via php-wasm)
- Pacote: `python empacotar.py` gera `dist/cotas-tec.zip` (anexo da release no GitHub `TravelTecOficial/wp-cotas-lua-de-mel`)
- Desenho: `docs/superpowers/specs/2026-10-17-cotas-tec-design.md`
- Contexto do módulo: `docs/modulos/cotas-tec.md`
- Nova versão: suba `Version:` e `CTEC_VERSION` em `cotas-tec/cotas-tec.php` e acrescente `## X.Y.Z (data)` no `CHANGELOG.md`. Ao enviar para o `main`, o GitHub Actions (`.github/workflows/release.yml`) roda os testes, gera o zip e publica a release `vX.Y.Z` sozinho.
