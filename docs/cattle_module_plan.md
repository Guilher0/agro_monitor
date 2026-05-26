# Plano de Implementação — Módulo de Pecuária (Cotação de Arroba & Lotes)

Este documento detalha o plano técnico de implementação para o novo módulo de Pecuária (Foco em Gado de Corte) integrado no AgroMonitor, aderindo estritamente aos padrões do projeto (Laravel 11, Inertia.js, Vue 3 + Tailwind CSS, Multi-tenancy com Stancl Tenancy e padrão Service-Observer-Repository).

---

## Decisões de Design Alinhadas

Após a rodada de alinhamento (`/grill-me`), definimos os seguintes diferenciais premium para o módulo:
1. **Valor de Venda Flexível:** Ao marcar um lote como "Vendido" (`sold`), um modal guiará o produtor para inserir o valor real da venda, sugerindo a cotação estimada do dia como padrão. Isso garante precisão contábil impecável no módulo Financeiro.
2. **Rendimento de Carcaça Dinâmico:** O simulador na tela do lote contará com um controle interativo (slider/campo de 50% a 56%) para que o produtor possa ajustar a eficiência de ganho e carcaça, estimando em tempo real diferentes cenários de retorno financeiro.
3. **Gráfico de Evolução de Peso:** A tela de detalhes do lote exibirá um lindo gráfico de linha reativo (ApexCharts) com a curva de evolução do peso médio do lote a partir das pesagens.
4. **Seletor Dinâmico de Região (UF):** O painel e o simulador permitirão selecionar o Estado (UF) dinamicamente para consultar a cotação regional da arroba via API AgroDoc AI, permitindo comparar mercados e salvar a UF preferida.
5. **Integração Financeira Completa (Custos de Manejo):** Adicionaremos um vínculo direto entre Transações Financeiras e Lotes de Gado (`cattle_lot_id`). Qualquer despesa lançada para o lote (vacinas, ração, sal mineral) será somada automaticamente como "Custo de Manejo" no cálculo de lucratividade real do lote!
6. **Calculadora Rápida Individual (Simulador de Bolso):** Um widget altamente interativo na listagem de lotes onde o produtor pode fazer simulações instantâneas para um único boi (sem precisar criar um lote), digitando o peso em kg, rendimento de carcaça e custos para ver o valor estimado de mercado e lucro líquido na hora.

---

## Estrutura do Banco de Dados (Migrations)

Criaremos e modificaremos 3 tabelas dentro do escopo do Tenant (`database/migrations/tenant`):

### 1. `create_cattle_lots_table` [NOVA]
Controla os lotes de animais de forma otimizada para performance.
* **Colunas:**
  * `id` (bigint primary key)
  * `tenant_id` (string indexado) — para portabilidade de dados.
  * `name` (string) — ex: "Lote 22 - Recria Pasto Alto".
  * `animal_count` (integer) — quantidade de cabeças.
  * `initial_avg_weight_kg` (decimal 8,2) — peso médio inicial de entrada.
  * `current_avg_weight_kg` (decimal 8,2) — peso médio atual (atualizado automaticamente na última pesagem).
  * `total_purchase_cost` (decimal 12,2) — custo total de aquisição do lote.
  * `status` (enum: `'active'`, `'sold'`) — padrão `'active'`.
  * `uf` (string 2, default `'TO'`) — Estado preferencial para cotação.
  * `sold_amount` (decimal 12,2, nullable) — valor real de venda final.
  * `sold_at` (date, nullable) — data em que o lote foi vendido.
  * `timestamps` e `softDeletes`.

### 2. `create_cattle_weight_logs_table` [NOVA]
Registra o histórico de pesagens periódicas para cálculo de ganho médio.
* **Colunas:**
  * `id` (bigint primary key)
  * `tenant_id` (string indexado)
  * `cattle_lot_id` (foreignId constrained to `cattle_lots` on delete cascade)
  * `weight_date` (date) — data da pesagem.
  * `avg_weight_kg` (decimal 8,2) — peso médio do lote no dia.
  * `notes` (text, nullable)
  * `timestamps`.

### 3. `add_cattle_lot_id_to_financial_transactions_table` [MODIFICAÇÃO]
Adiciona a chave estrangeira para permitir o cálculo integrado de Custos de Manejo.
* **Colunas adicionais:**
  * `cattle_lot_id` (foreignId nullable, constrained to `cattle_lots` on delete set null)

---

## Backend (PHP / Laravel)

### 1. Models & Relacionamentos
* **`App\Models\CattleLot`:**
  * Define relacionamentos `weightLogs()` (1:N) e `financialTransactions()` (1:N).
  * Mutator/Accessor para atualizar automaticamente o `current_avg_weight_kg` a partir do `weightLogs()->latest('weight_date')->first()`.
* **`App\Models\CattleWeightLog`:**
  * Pertence a um lote (`cattleLot`).
  * Observer/Event para disparar a atualização do `current_avg_weight_kg` no lote pai após criação, edição ou deleção de um log de pesagem.
* **`App\Models\FinancialTransaction`:**
  * Adiciona `cattle_lot_id` à propriedade `$fillable`.
  * Define relacionamento `cattleLot()`.

### 2. Integração de API (`App\Services\LivestockPriceService`)
* Consome `https://agrodocai.com.br/api/v1/cotacao?uf={uf}` usando a Facade `Http` do Laravel.
* Implementa o **Cache do Laravel** de 60 minutos para cada UF pesquisada (evitando estourar o limite diário de requisições).
* Tratamento de falhas resiliente: caso a API caia, retorna um valor mock padrão (ex: `R$ 345.50`) para não quebrar a aplicação, exibindo um aviso amigável.

### 3. Controllers
* **`App\Http\Controllers\Tenant\CattleLotController`:**
  * CRUD completo de Lotes de Gado.
  * Métodos dedicados para registrar pesagens de forma rápida.
  * Método para marcar lote como vendido (atualizando status, sold_amount e sold_at).

### 4. Observers (`App\Observers\CattleLotObserver`)
* Registra o evento `updated` do `CattleLot`.
* Se o status for alterado para `'sold'`, cria automaticamente uma transação do tipo `'income'`, na categoria `'Venda de Gado'`, usando o `sold_amount` e a data de venda definidos no formulário.
* Se a venda for desfeita (status volta para `'active'`), remove/inativa a transação financeira vinculada.
* Se o lote for excluído (soft-delete), faz soft-delete da transação financeira gerada de venda.

---

## Frontend (Vue 3 + Tailwind CSS)

### 1. Menu de Navegação (`AuthenticatedLayout.vue`)
* Adicionar o item **"Pecuária"** no menu principal de navegação.

### 2. Tela de Listagem (`CattleLots/Index.vue`)
* **KPI Cards Gerais:**
  * Total de Cabeças de Gado (Lotes ativos).
  * Peso Médio Global dos Lotes ativos.
  * Custo de Aquisição Total vs. Valor Estimado de Mercado Geral (ROI Estimado).
* **Painel de Cotação da Arroba:**
  * Exibe o preço da arroba para a UF activa com um seletor dinâmico de Estado (UF) do Brasil.
  * Botão de recarregar cotação (com indicador visual de carregamento).
* **Calculadora de Simulação Rápida (Individual):**
  * Um card interativo lateral ou aba expansível estilizada para simular um único animal.
  * Inputs reativos: Peso Vivo (kg), Rendimento de Carcaça (%) com slider (50% a 56%), Custo de Aquisição (R$, opcional), e Outros Custos (R$, opcional).
  * Outputs reativos em tempo real: Total de @, Valor de Mercado Estimado (multiplicado pelo valor da arroba da UF selecionada no painel) e Lucro/Prejuízo Líquido Estimado.
  * Visual limpo e badges dinâmicas de cores baseadas no lucro/prejuízo.
* **Tabela de Lotes:**
  * Nome do Lote, Quantidade de Cabeças, Peso Médio Atual (kg e @), Custo de Compra, Valor Estimado de Mercado, e Badge de Status (`Ativo` ou `Vendido`).
  * Ações rápidas: Editar, Nova Pesagem, Marcar Venda (abre o modal de confirmação do valor de venda), e Excluir.

### 3. Tela de Detalhes (`CattleLots/Show.vue`)
Dividida de forma premium em abas ou seções bem definidas:
* **Card Principal com KPIs do Lote:**
  * Contagem, peso médio e custo inicial.
  * Custo de Manejo Acumulado (soma de todas as despesas vinculadas na tabela `financial_transactions`).
* **Aba 1: Simulador de Rendimento e Viabilidade Econômica do Lote:**
  * Slider interativo para **Rendimento de Carcaça (%)** (de 50% a 56%, padrão 50%).
  * Cotação da Arroba em tempo real (baseada na UF salva do lote).
  * Exibição das fórmulas matemáticas de forma elegante e educativa.
  * Badges de cor reativos: Green (lucro) ou Red (prejuízo) com destaque visual premium e micro-animações.
* **Aba 2: Histórico de Pesagens e Curva de Ganho de Peso:**
  * **Gráfico reativo (ApexCharts - Line):** eixo X = Data da Pesagem, eixo Y = Peso Médio (kg).
  * Formulário/Modal rápido para adicionar nova pesagem.
  * Tabela com as pesagens registradas e botão para excluir pesagens incorretas.
* **Aba 3: Financeiro do Lote (Despesas e Receitas Vinculadas):**
  * Tabela com todas as transações financeiras vinculadas a este lote (ração, vacinas, frete, comissão).
  * Botão para criar rapidamente um novo lançamento financeiro direto a partir do lote.

---

## Plano de Verificação e Testes

### Automatizado & Integração
1. **Migrations:** Executar `php artisan tenancy:migrate` e validar que as tabelas foram criadas com todos os índices e tipos corretos nos bancos de tenants.
2. **Caches e HTTP:** Testar o `LivestockPriceService` isoladamente garantindo que as chamadas à API são realizadas corretamente e cacheadas por 60 minutos. Validar fallback em caso de falha da API.
3. **Cálculo da Arroba e Regras:** Escrever testes de integração para garantir que:
   - Registrar pesagem atualiza `current_avg_weight_kg` no lote.
   - Venda de lote gera `FinancialTransaction` de entrada correta via Observer.
   - Deleção de lote limpa as transações geradas.

---

## 💡 Ajustes e Resolução de Erros (Troubleshooting)

### 1. Resolução do Erro de Tags de Cache (Stancl Tenancy)
* **Problema:** Durante o carregamento da listagem de lotes de gado, o Laravel disparava o erro `BadMethodCallException: This cache store does not support tagging` vindo do `CacheTenancyBootstrapper` do Stancl Tenancy.
* **Causa:** Por padrão, o Stancl Tenancy tenta aplicar tags de cache baseadas no tenant (`tenant_ID`) para isolar os caches. No entanto, no ambiente de desenvolvimento local (e dentro dos containers), a aplicação usa o driver `file` ou `database` configurado no `.env` (que não dão suporte nativo a tags).
* **Solução:** Como as cotações regionais do CEPEA/Scot são públicas e globais para todo o mercado de corte, desativamos o `CacheTenancyBootstrapper` comentando-o em [config/tenancy.php](file:///\\wsl.localhost\Ubuntu\home\guibs\agro_monitor\config\tenancy.php#L40). Isso permite que a aplicação utilize o cache padrão de forma rápida e segura sem crashar, aproveitando as cotações globalmente entre tenants de forma ultraeficiente.

### 2. Resolução do Erro de Route Model Binding (Wildcard Subdomains)
* **Problema:** Ao acessar endpoints que utilizam Route Model Binding (ex: `/assets/{asset}/edit`, `/assets/{asset}/qrcode`, `/assets/{asset}` com método `DELETE`), o Laravel quebrava a requisição inteira lançando um `TypeError: Argument #1 ($asset) must be of type App\Models\Asset, string given`.
* **Causa:** O grupo de rotas do Tenant usa o parâmetro `{tenant_domain}` no domínio da rota (`Route::domain('{tenant_domain}')`). Por padrão, o Laravel injeta qualquer variável de rota como o primeiro argumento dos métodos dos Controllers. Como a assinatura do método espera um objeto do model `Asset` e o Laravel injeta primeiro a string `"fazenda-demo"`, o erro de conflito de tipos ocorria em todos os controllers (`AssetController`, `PlotController`, `CattleLotController`, etc.).
* **Solução:** Criamos o middleware global [RemoveTenantDomainParameter.php](file:///\\wsl.localhost\Ubuntu\home\guibs\agro_monitor\app\Http\Middleware\RemoveTenantDomainParameter.php) que de forma limpa executa `$request->route()->forgetParameter('tenant_domain')` logo no início da requisição. Em seguida, registramos o middleware no pipeline do Tenant em [routes/tenant.php](file:///\\wsl.localhost\Ubuntu\home\guibs\agro_monitor\routes\tenant.php#L24). Isso remove o parâmetro do domínio de rota do pipeline antes que ele chegue nas assinaturas dos Controllers, garantindo que o Route Model Binding tradicional do Laravel funcione 100% de forma global e transparente, sem precisar modificar um único Controller!


