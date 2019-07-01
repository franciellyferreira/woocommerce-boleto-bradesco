# Woocommerce Boleto Bradesco (Plugin)

O Plugin *Woocommerce Boleto Bradesco* foi desenvolvido para agregar ao Woocommerce a emissão de pagamento por Boleto do Banco Bradesco, portanto para que ele funcione corretamente é necessário que o Plugin do Woocommerce esteja ativo no Wordpress.

## 1- Instalar o Plugin

Copiar a pasta completa desse projeto para o diretório wp-content/plugins.

## 2- Configurar o painel de pagamento do Plugin

+ Taxa de Juros na Parcela (Inserir aqui a taxa de juros que será aplicado a cada parcela, por exemplo: 2.99)
+ Merchant ID (Disponibilizado pelo Shop Fácil/Banco Bradesco)
+ Transação de Produção (Disponibilizado pelo Shop Fácil/Banco Bradesco)
+ Chave de Produção (Disponibilizado pelo Shop Fácil/Banco Bradesco)
+ E-mail do Remetente (E-mail do remetente que enviará o boleto por e-mail)
+ Senha do Remetente (Senha do remetente que enviará o boleto por e-mail)
+ Nome Remetente (Nome do remetente que enviará o boleto por e-mail)
+ URL da API do Boleto Bradesco (Endereço da rota na API que gera o Boleto Bradesco)

## 3- Quantidade Máxima de Parcelas

Para que o processo funcione corretamente todos os produtos da loja que serão vendidos por boleto devem possuir o campo meta_key *_maximo_parcelas_boleto* na tabela _wp_postmeta_ com o meta_value informando a quantidade máxima de parcelas que pode ser vendido o produto no boleto.

## 4- Criptografia

Criar método para realizar a criptografia e comunicar com sua API que gera o boleto do Banco Bradesco.

## Resultado

Após gerar o boleto os seguintes dados serão armazenados no banco na tabela _wp_postmeta_, para que possam ser usados em consultas e relatórios de vendas:

- _boleto_quantidade_parcelas
- _boleto_valor_parcela
- _boleto_aplicou_juros (true ou false)
- _boleto_valor_juros
- _boleto_url

### Referências

Create a Payment Gateway Plugin for WooCommerce (https://rudrastyh.com/woocommerce/payment-gateway-plugin.html)

Payment Gateway API (https://docs.woocommerce.com/document/payment-gateway-api/)
