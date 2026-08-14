# Relatórios G4F

Plugin GLPI API-first para gerar rascunhos de relatórios com múltiplos contextos, revisão manual, análises editáveis e exportação DOCX em papel timbrado G4F.

## Principais pontos

- Extração de dados via API REST do GLPI.
- Sem tabelas próprias de negócio.
- Sem PHPWord, ZipArchive, bibliotecas JS externas ou CDN.
- Composer é opcional: o plugin inclui autoload interno em `inc/autoload.php`.
- `vendor/autoload.php` é intencionalmente vazio, seguindo o padrão usado no GLPIBot.
- O menu em Ferramentas abre somente o gerador de relatório.
- A configuração administrativa fica em Configurar > Geral > Relatórios G4F.
- Chamadas de teste e extração usam endpoints em `front/`, não `ajax/`.
- CSS/JS das páginas são inseridos pelo PHP da própria página, evitando 404 de assets estáticos em instalações com public-root/proxy.
- Exportação DOCX usa o papel timbrado G4F base e preserva cabeçalho/rodapé/grafismo.

## Instalação

```bash
cd /var/www/html/glpi/plugins
rm -rf g4freports
unzip g4freports_v1.4.0_canonical_ticket_pipeline.zip
chown -R www-data:www-data g4freports
```

Composer continua opcional:

```bash
cd /var/www/html/glpi/plugins/g4freports
composer install --no-dev --optimize-autoloader
chown -R www-data:www-data /var/www/html/glpi/plugins/g4freports
```

Depois:

1. Configurar > Plugins > Relatórios G4F > Instalar/Habilitar.
2. Configurar > Geral > Relatórios G4F.
3. Preencher o perfil padrão da API GLPI.
4. Usar o botão `Testar conexão padrão`.
5. Ferramentas > Relatórios G4F para gerar o relatório.

## Contextos do relatório

O usuário pode marcar múltiplos contextos ao mesmo tempo:

- Chamados atribuídos ao agente.
- Chamados solicitados pelo agente.
- Chamados atribuídos aos grupos selecionados explicitamente.
- Tarefas de chamado feitas pelo agente.
- Acompanhamentos feitos pelo agente.
- Soluções registradas pelo agente.
- Projetos gerenciados pelo agente.
- Projetos gerenciados pelos grupos selecionados explicitamente.
- Projetos em que o agente participa.
- Tarefas de projeto do agente.
- Tarefas de projeto dos grupos selecionados explicitamente.
- Entradas narrativas manuais.

## Análises selecionáveis

As análises são calculadas a partir do rascunho já extraído, sem criar uma segunda varredura independente na API:

- Resumo por dia.
- Distribuição por status.
- Origem das atividades.
- Agente x grupos selecionados.
- Top categorias.
- Localização dos chamados.
- Tempo médio de resposta.
- Tempo de primeira resposta.
- Eventos do solicitante sem resposta técnica.
- Tabela consolidada de indicadores.

As visualizações exportadas para DOCX são tabelas/gráficos editáveis em Word, sem imagens geradas dinamicamente.

## Alterações v0.9.0

- Corrigido ciclo de geração/exportação para evitar erro de autorização ao gerar um rascunho, exportar e depois alterar parâmetros sem recarregar a página.
- Exportação DOCX agora ocorre em iframe oculto, sem navegar a página principal.
- Sessões da API GLPI são finalizadas em `finally` nos endpoints de teste, busca e geração.
- Busca de grupos por nome usa descoberta de search options e tenta múltiplos campos seguros de nome/completename.
- ID e nome do agente são somente leitura e preenchidos pela busca.
- Adicionada área de análises selecionáveis junto dos contextos.
- Adicionadas análises de tempo de resposta com cálculo requester -> primeira ação técnica posterior.
- Adicionada análise de localização com fallback: localização do chamado, localização do solicitante e `Local não informado`.
- DOCX recebeu tabelas/gráficos editáveis para análises.
- Modelos operacional, gerencial, técnico/projeto e anexo ficaram mais diferenciados.

## Alterações v0.9.1

- Ajuste visual das análises exportadas em DOCX.
- Gráficos nativos do Word/LibreOffice para linha, pizza, rosca, barras horizontais e colunas.
- Mapa de calor editável em tabela Word para volumetria por dia da semana.
- Análises distribuídas ao longo do documento conforme o modelo selecionado, em vez de ficarem concentradas em um único bloco no início.
- Prévia web com visualizações variadas: linha em SVG, donut/pizza por CSS, heatmap em grade e barras apenas quando fizer sentido.
- Mapa de calor derivado da análise de volume diário.
- Mantida a lógica API-first, extração em lotes, sem /ajax, sem dependências externas e sem bibliotecas de gráficos.


## Alterações v1.0.1

- Adicionado Contexto de dados com filtro por múltiplos títulos de chamados.
- Busca de chamado por ID para usar o título como referência de filtro.
- Filtro por título aplica em conjunto com agente/grupos/contextos selecionados, sem substituir o escopo.
- Modo gerencial por grupos selecionados, omitindo detalhamento individual e consolidando indicadores por agente.
- Exportação DOCX preserva papel timbrado G4F e distribui análises/tabelas conforme o modelo selecionado.


## Alterações v1.0.1

- Corrigida a lógica de filtro por título para que ela não use categoria/referência como substituto do título real do chamado.
- Entradas derivadas de chamados agora carregam `ticket_id` e `ticket_title` separadamente, evitando que o filtro pareça renomear itens encontrados.
- O filtro por título passa a ser aplicado após os contextos de agente/grupo/período, em composição com eles, e não como substituição do escopo.
- Em modo `somente detalhamento`, os indicadores e gráficos continuam usando o escopo completo, mas `detail_entries` retorna apenas os chamados cujo título real corresponde aos títulos selecionados.
- Em modo `global`, os fatos, entradas, indicadores e gráficos passam a considerar apenas os chamados cujo título real corresponde aos títulos selecionados.


## Alterações v1.1.1

- Resumo gerencial por agente agora prioriza nome e sobrenome do usuário GLPI em vez do login/ID.
- Login/ID foi mantido como referência secundária em coluna própria na prévia e no DOCX.
- Resolução de nomes usa cache e fallback seguro para não reintroduzir timeouts.

## Alterações v1.1.0

- Corrigida a paginação de contextos por grupo: cada grupo selecionado agora é processado como alvo independente, evitando `ERROR_RANGE_EXCEED_TOTAL` e evitando que um grupo grande bloqueie a leitura dos demais.
- Contextos por grupo passam a usar chaves internas por grupo, mantendo rótulo agregado na interface e permitindo totais por escopo mais confiáveis.
- Chamados de usuário/grupo agora são pesquisados por múltiplas bases de data em lotes separados: atualização, abertura, solução e fechamento, reduzindo casos de chamados presentes no GLPI mas ausentes no relatório.
- `ERROR_RANGE_EXCEED_TOTAL` é tratado como fim de paginação daquele alvo, não como erro de relatório.
- Modo gerencial passa a montar resumo por agente com fallback por técnico atribuído quando eventos de tarefa/acompanhamento/solução não estão disponíveis ou estão parciais.
- Eventos técnicos em modo gerencial consideram ações não solicitantes com autoria detectável, evitando resumo zerado quando a API não expõe vínculo de grupo no subitem.
- O preset Gerencial agora prioriza grupos selecionados e não contextos centrados no agente solicitante.
- O resumo executivo e as validações do modo gerencial usam chamados/fatos carregados, não a lista de entradas detalhadas que é omitida por design.


## Alterações v1.2.0

- Adicionado critério temporal explícito para chamados: criados no período com ação do agente, atualizados no período, solucionados/fechados no período e solucionados/fechados pelo agente.
- Removido o uso invisível de todos os campos de data ao mesmo tempo; os lotes agora usam apenas os critérios temporais selecionados na interface.
- Critérios “pelo agente” passam a exigir autoria detectável via tarefas, acompanhamentos ou soluções expostas pela API.
- Melhorada a cobertura da extração com painel de fontes, totais da API, linhas lidas, únicos adicionados, duplicados e limitações da API.
- Corrigida extração de IDs de pais em subitens quando a API retorna rótulos como “Chamado 123456” em vez de número puro.
- Restaurados métodos de última atividade de chamados/projetos que haviam sido removidos acidentalmente em um ajuste anterior.
- Leitura de subitens ganhou modo de verificação paginado e controlado por orçamento para evitar perdas silenciosas sem reintroduzir 504.
- A distribuição de origem em modo gerencial prioriza grupos selecionados sobre contexto de solicitante quando o chamado foi descoberto por múltiplas fontes.

## Alterações v1.4.0

- Corrige a chamada/runtime de `mergeTicketSearchRows` garantindo que o pacote contenha a implementação usada pelo fluxo temporal.
- Normaliza fontes de chamados com escopo temporal para as chaves-base (`tickets_assigned_user`, `tickets_requested_by_user`, `tickets_assigned_groups`) para evitar que chamados encontrados sejam tratados depois como fora do contexto.
- Mantém chaves detalhadas por grupo/data para cobertura, mas recalcula a fonte primária após mesclagem de lotes, priorizando grupos selecionados em relatórios gerenciais.
- Adiciona limitação compacta de cobertura quando buscas diretas de subitens falham, sem exportar erro técnico bruto no DOCX.
- Atualiza a versão em `setup.php` e `inc/common.php` para remover ambiguidade de pacotes v1.2.0.


### Correção v1.4.0

- O pipeline de tickets agora é canônico: linhas brutas da API e descobertas por múltiplos contextos são mescladas por ID de chamado antes de gerar o detalhamento editável.
- O relatório normal renderiza um único bloco por chamado, evitando duplicidade quando o mesmo ticket é encontrado por agente, grupo e critério temporal.
- A data exibida no detalhamento passa a seguir o critério temporal selecionado sempre que possível; em relatórios de criação no período, a data de abertura do chamado é priorizada em vez da última solução/comentário.
- Entradas cujo detalhamento cairia fora do período são omitidas do corpo normal e contabilizadas na cobertura como detalhes fora do período.
- Métricas e gráficos passam a usar os fatos finais/canônicos em vez de entradas parciais de lote.
