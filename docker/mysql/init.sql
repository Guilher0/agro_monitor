-- Script de inicialização do MySQL
-- Concede permissão ao usuário 'agro' para criar bancos de dados de tenant
-- Necessário para que o stancl/tenancy possa criar bancos isolados por produtor

GRANT ALL PRIVILEGES ON `tenant%`.* TO 'agro'@'%';
GRANT ALL PRIVILEGES ON `agro_tenant_%`.* TO 'agro'@'%';
FLUSH PRIVILEGES;
