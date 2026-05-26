Atue como um desenvolvedor Full-Stack especialista em Laravel 13, Inertia.js, Vue 3 (Composition API com TailwindCSS) e Stancl Tenancy. 

Preciso implementar um novo módulo de Pecuária (Foco em Gado de Corte e Cotação da Arroba) no AgroMonitor. Siga rigorosamente os padrões do projeto já existentes (Service-Observer-Repository, transações seguras e banco isolado).

Requisitos Técnicos a serem gerados:

1. MIGRATIONS (Executadas no contexto de tenant):
- `create_cattle_lots_table`: `id`, `tenant_id` (string indexado), `name` (string), `animal_count` (integer), `initial_avg_weight_kg` (decimal 8,2), `current_avg_weight_kg` (decimal 8,2), `total_purchase_cost` (decimal 12,2), `status` (enum: active, sold), timestamps e softDeletes.
- `create_cattle_weight_logs_table`: `id`, `tenant_id`, `cattle_lot_id` (foreignId), `weight_date` (date), `avg_weight_kg` (decimal 8,2), `notes` (text), timestamps.

2. SERVICE DE INTEGRAÇÃO COM API (`App\Services\LivestockPriceService`):
- Deve fazer uma requisição HTTP (via Http facade do Laravel) para a API gratuita: `https://agrodocai.com.br/api/v1/cotacao?uf=TO` (Deixe a UF dinâmica baseada nas configurações da fazenda, mas use 'TO' como padrão).
- Implemente um Cache do Laravel de 60 minutos para essa resposta, evitando estourar o limite diário de 100 requisições gratuitas da API.

3. CONTROLLER E INTEGRADOR FINANCEIRO (`CattleLotController`):
- Métodos CRUD básicos para os Lotes e as Pesagens.
- Vinculação opcional: Se um lote for marcado como vendido (`status = sold`), deve disparar via Observer um lançamento automático de receita (`income`) na tabela `financial_transactions`, usando o valor calculado na venda.

4. CALCULADORA DE GANHO/PERDA NO FRONTEND (Componente Vue 3 + Inertia):
- Crie a interface de exibição do Lote com uma aba/card de "Simulador de Rendimento e Viabilidade Econômica".
- A interface deve exibir em tempo real:
  - O preço atual da arroba recuperado do Service.
  - O cálculo do Total de Arrobas atuais do lote (Fórmula: (current_avg_weight_kg * animal_count) / 30).
  - O Valor de Mercado Estimado do Lote.
  - O Ganho/Perda Líquido comparando o Valor de Mercado com o `total_purchase_cost`.
- Use componentes visuais limpos do Tailwind CSS, aplicando badges de cor Green (green-500/600) para lucro e Red (red-500) para prejuízo estimado.

Me forneça os códigos das migrations, o Service em PHP com a lógica de Cache, o controller correspondente e a estrutura do componente Vue 3 estruturado com Tailwind.
