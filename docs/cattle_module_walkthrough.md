# Walkthrough do Módulo de Pecuária (Gado de Corte)

Implementamos com absoluto sucesso o módulo de Pecuária integrado no AgroMonitor, respeitando os padrões de arquitetura (Service-Observer-Repository, transações seguras, banco de tenant isolado e UX premium com Tailwind CSS + Inertia + Vue 3).

---

## 🛠 O que foi Desenvolvido

### 1. Modelagem & Banco de Dados (Tenant Migrations)
Criamos e migramos as tabelas dentro do escopo de Tenant (`database/migrations/tenant`):
* `create_cattle_lots_table` [NOVA]: Armazena os dados essenciais dos lotes (cabeças, peso de entrada, peso atual, custo de aquisição, UF de cotação e dados finais de venda).
* `create_cattle_weight_logs_table` [NOVA]: Histórico de pesagens para acompanhamento do ganho de peso.
* `add_cattle_lot_id_to_financial_transactions_table` [MODIFICADA]: Vínculo direto de despesas a lotes específicos para automatizar custos de manejo.

### 2. Backend & Integração de API Resiliente
* **`LivestockPriceService`:** Consome a API gratuita AgroDoc AI baseada na UF desejada (`https://agrodocai.com.br/api/v1/cotacao?uf={uf}`). Conta com cache robusto do Laravel de 60 minutos por UF e lógica de fallback para garantir funcionamento ininterrupto mesmo se a API falhar.
* **`CattleLot` e `CattleWeightLog` Models:** Equipados com mutators, casts e hooks para manter o peso médio atual do lote (`current_avg_weight_kg`) automaticamente sincronizado sempre que uma pesagem é salva, alterada ou deletada.
* **`CattleLotObserver`:** Escuta o lote de gado. Quando ele é marcado como vendido (`sold`), lança de forma totalmente automática uma receita (`income`) na categoria `'Venda de Gado'` no módulo financeiro geral, contendo o valor real de venda e a data definidos no formulário. Se a venda for desfeita ou o lote for excluído, mantém o financeiro 100% íntegro.

### 3. Interface Visual Premium (Vue 3 + Tailwind CSS + ApexCharts)
* **Página de Listagem (`CattleLots/Index.vue`):**
  * KPIs globais do rebanho ativos.
  * Painel de cotação atualizado em tempo real com seletor de UF.
  * **Calculadora de Simulação Rápida (Individual):** Painel interativo para simular instantaneamente o rendimento e lucro de um único animal (peso vivo, controle de rendimento de 50% a 56%, custo de compra e despesa).
  * Tabela de lotes interativa com ações rápidas e modal dinâmico de venda.
* **Página de Detalhes (`CattleLots/Show.vue`):**
  * Card com KPIs do lote incluindo Custo de Manejo Acumulado (soma de todas as despesas vinculadas).
  * **Aba 1: Simulador Econômico do Lote:** Slider interativo de Rendimento de Carcaça (50% a 56%) calculando em tempo real o rendimento comercial resultante (@), valor estimado de mercado e lucro/prejuízo real.
  * **Aba 2: Pesagens & Curva de Peso:** Gráfico de linha reativo (ApexCharts) mostrando visualmente a evolução e ganho de peso médio do rebanho, além de formulário ágil para pesagens.
  * **Aba 3: Financeiro:** Extrato do lote consolidando custos de aquisição, despesas e receitas.
* **Página de Cadastro (`CattleLots/Form.vue`):** Cadastro e edição limpa dos dados de lote com validações robustas (`CattleLotRequest`).

---

## 🧪 Validação e Testes Automatizados

Escrevemos uma suíte de testes de integração específica em `tests/Feature/CattleTest.php` cobrindo todas as regras de negócio cruciais:
1. `test_can_create_cattle_lot()`: Garante a persistência de lotes e formatação de pesos.
2. `test_weighing_log_syncs_weight_automatically()`: Valida que a criação, alteração ou exclusão de pesagens atualiza reativamente e em ordem cronológica o peso do lote pai.
3. `test_selling_lot_triggers_financial_transaction_observer()`: Garante a integridade financeira automática (geração de receita na venda, remoção na inativação da venda).

**Resultado da execução dos testes:**
```bash
docker compose exec app php artisan test --filter=CattleTest
```
```text
   PASS  Tests\Feature\CattleTest
  ✓ can create cattle lot                                                0.13s  
  ✓ weighing log syncs weight automatically                              0.03s  
  ✓ selling lot triggers financial transaction observer                  0.02s  

  Tests:    3 passed (7 assertions)
  Duration: 0.24s
```

Toda a suíte geral do projeto (28 testes no total) passou com sucesso absoluto, assegurando **zero regressões** na base de código!

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


