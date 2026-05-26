# Documento de Regras de Negócio e Arquitetura — AgroMonitor

Este documento detalha as especificações técnicas, regras de negócio e a estrutura arquitetural da plataforma **AgroMonitor** — um sistema SaaS (Software as a Service) multi-tenancy para gestão agrícola de alta performance desenvolvido em Laravel, Inertia, Vue, Stancl Tenancy e Banco de Dados Isolado por Cliente.

---

## 1. Visão Geral da Arquitetura

O AgroMonitor adota uma arquitetura híbrida de banco de dados por inquilino (**Multi-DB Tenancy**) viabilizada pelo pacote `stancl/tenancy`.

```mermaid
graph TD
    subgraph Central (Landlord)
        Domain[Domínio Central: localhost] --> RouteC[routes/web.php]
        RouteC --> CentralDB[(Banco Landlord)]
        CentralDB --> Tenants[Model Tenant: free | pro | enterprise]
        CentralDB --> Users[Model User: Administradores e Contas]
    end

    subgraph Contexto de Tenant (Fazendas Isoladas)
        TDomain[Subdomínios: fazenda-demo.*] --> RouteT[routes/tenant.php]
        RouteT --> Middleware[InitializeTenancyByDomain]
        Middleware --> TenantDB[(Banco do Tenant Isolado)]
        
        TenantDB --> Plots[Plot: Talhões e Áreas]
        TenantDB --> Assets[Asset: Tratores e Equipamentos]
        TenantDB --> FieldLogs[FieldLog: Caderno de Campo]
        TenantDB --> FinTrans[FinancialTransaction: Fluxo de Caixa]
    end
```

### Domínio Central vs Tenant
1. **Banco Central (Landlord):** Gerencia os dados globais. Nele estão cadastrados os inquilinos (`tenants`), os domínios mapeados (`domains`) e os usuários da plataforma (`users`).
2. **Banco do Tenant (Isolado):** Cada fazenda possui seu próprio banco de dados MySQL físico, criado dinamicamente na contratação do plano. Os dados de talhões, ativos, caderno de campo e financeiro de uma fazenda são totalmente invisíveis e inacessíveis para as outras fazendas.

---

## 2. Modelos de Dados e Regras de Negócio Específicas

Abaixo estão detalhados os modelos do banco de dados do tenant, suas colunas fundamentais e a lógica de negócio associada a cada um deles.

### A. Ativo Agrícola (`Asset`)
Representa toda a frota de máquinas, tratores, colheitadeiras e implementos agrícolas de uma propriedade.

* **Tipos de Ativos:** `tractor` (Trator), `harvester` (Colheitadeira), `sprayer` (Pulverizador), `implement` (Implemento), `other` (Outro).
* **Status:** `active` (Ativo), `maintenance` (Em Manutenção), `inactive` (Inativo).
* **Valor Hora (`hourly_rate`):** Custo horário estimado do ativo para fins de cálculo de depreciação e consumo operacional (ex: valor do diesel + operador + desgaste por hora).

#### 🛠️ Regra de Negócio: Alerta de Manutenção Preventiva
O sistema calcula automaticamente se um equipamento precisa de manutenção preventiva com base nas horas trabalhadas acumuladas.
> **Fórmula do Alerta:**
> $$Horas\ sem\ Manutenção = total\_hours - hours\_at\_last\_maintenance$$
> Se o **Horas sem Manutenção** for maior ou igual ao limite configurado (`maintenance_alert_hours`), o atributo virtual `needs_maintenance` é retornado como `true`.
>
>Na interface Vue, os ativos em estado de alerta devem ser destacados visualmente utilizando a cor **Amber (amber-500)**.

#### 📱 Funcionalidade: QR Code Físico para Máquinas
Cada máquina possui uma URL única para geração de QR Code (`/assets/{asset}/qrcode`). O QR Code codifica o link direto para a página de edição do ativo, permitindo que operadores de campo façam a leitura do adesivo físico no trator com o celular para atualizar as horas de serviço no mesmo instante.

---

### B. Talhão (`Plot`)
O talhão representa a subdivisão de terra da propriedade destinada a uma plantação específica. É o centro agregador de custos e lucros da fazenda.

* **Atributos:** Área em hectares (`area_hectares`), cultura atual (`culture`, ex: soja, milho, algodão), safra (`season`, ex: 2025/2026) e tipo de solo (`soil_type`).
* **Coordenadas Geográficas (`location_coordinates`):** Campo em formato JSON que armazena polígonos ou coordenadas de localização GPS (GeoJSON-friendly) para mapeamento.
* **Status:** `active` (Em cultivo), `fallow` (Em pousio/descanso), `harvested` (Colhido).

#### 📈 Regra de Negócio: Viabilidade Econômica por Talhão
O painel de desempenho financeiro (gráfico ApexCharts no Dashboard) agrupa todas as movimentações financeiras vinculadas a este talhão para calcular sua rentabilidade:
> **Fórmulas de Performance:**
> * $Custo\ do\ Talhão = \sum (financial\_transactions.amount\ WHERE\ type = 'expense'\ AND\ plot\_id = id)$
> * $Receita\ do\ Talhão = \sum (financial\_transactions.amount\ WHERE\ type = 'income'\ AND\ plot\_id = id)$
> * $Lucro\ Líquido = Receita\ do\ Talhão - Custo\ do\ Talhão$

---

### C. Caderno de Campo Digital (`FieldLog`)
Representa o diário de bordo agrícola. Registra toda e qualquer atividade física, química ou mecânica realizada sobre a terra de um talhão.

* **Tipos de Atividade:** `planting` (Plantio), `spraying` (Pulverização), `harvesting` (Colheita), `fertilizing` (Adubação), `maintenance` (Manutenção local), `irrigation` (Irrigação), `other` (Outros).
* **Vínculos:** Obrigatório com um `Plot` (Talhão) e opcional com um `Asset` (Máquina/Equipamento, nulo se atividade manual).

#### 🧮 Regra de Negócio: Cálculo de Custo Operacional Automático
Toda vez que uma atividade de campo é registrada ou editada, o sistema calcula dinamicamente o custo real gerado com base no uso de maquinário e aplicação de insumos. Essa regra é centralizada em `App\Services\FieldLogService`.
> **Fórmula do Custo Operacional:**
> $$Custo\ Total = (machine\_hours \times asset.hourly\_rate) + (input\_quantity \times input\_unit\_price)$$
> 
> *Nota: Os custos de máquina e insumos são isoladamente opcionais (uma capina manual possui custo de máquina nulo; um preparo de solo mecanizado sem produto químico possui custo de insumo nulo).*

#### ⏱️ Regra de Negócio: Atualização do Horímetro de Ativos
Quando um `FieldLog` é inserido ou editado contendo horas de uso de uma máquina (`machine_hours`), a plataforma soma ou ajusta atomicamente a diferença de horas no horímetro total do equipamento (`total_hours` no model `Asset`). 
O `FieldLogService` calcula o delta exato entre as horas antigas e as novas para evitar duplicações no banco de dados em casos de edições ou trocas de tratores.

#### 💸 Regra de Negócio: Lançamento Financeiro Automático
Ao salvar um registro no Caderno de Campo com o parâmetro `generates_transaction = true`, o observador do Laravel (`FieldLogObserver`) cria automaticamente uma despesa financeira (`FinancialTransaction`) vinculada.
* **Tipo da transação:** `expense` (Saída).
* **Valor (`amount`):** Igual ao `total_cost` calculado do caderno.
* **Categoria:** Mapeada a partir do tipo de atividade (Ex: atividade `spraying` gera despesa na categoria `"Defensivos / Pulverização"`).
* **Sincronização:** Se o registro do caderno for alterado ou deletado, a despesa correspondente é atualizada ou sofre soft-delete simultaneamente no banco.

---

### D. Controle Financeiro (`FinancialTransaction`)
Controla o fluxo de caixa detalhado da fazenda, diferenciando o que são despesas e receitas ligadas à produção.

* **Tipos:** `income` (Entrada/Receitas, como a venda de grãos ou subsídios) e `expense` (Saída/Despesas, como insumos, mão-de-obra e diesel).
* **Flexibilidade de Vínculo:**
  * **Com Talhão e Sem Caderno de Campo:** Despesas ou receitas inseridas manualmente pelo produtor vinculadas àquela área (ex: venda de milho daquele talhão).
  * **Com Talhão e Com Caderno de Campo:** Despesa automática de atividade operacional integrada.
  * **Sem Talhão e Sem Caderno de Campo:** Despesas e receitas administrativas gerais da fazenda (ex: escritório, pró-labore, compra de sacarias).

---

## 3. Serviços e Padrões de Design Aplicados

A plataforma utiliza o padrão **Service-Observer-Repository** para manter os controllers limpos e garantir consistência transacional:

### `FieldLogService.php`
Encapsula a transação do banco de dados (`DB::transaction`). Garante que se a inserção da atividade falhar, o horímetro da máquina não seja incrementado e a despesa financeira não seja lançada, mantendo o banco 100% íntegro.

### `FieldLogObserver.php`
Escuta os gatilhos nativos do Eloquent (`created`, `updated`, `deleting`) para fazer a ponte de comunicação de custos entre a área operacional (`field_logs`) e a área financeira (`financial_transactions`) sem acoplamento direto nas classes de controle.

### Geração de PDF Relatório (`FieldLogPdfController.php`)
O caderno de campo é auditável e passível de fiscalização ambiental ou bancária (solicitação de crédito rural). O sistema dispõe de um gerador de PDF via `barryvdh/laravel-dompdf` em orientação paisagem (A4), completo com filtros por talhão, período e balanço contábil integrado.
