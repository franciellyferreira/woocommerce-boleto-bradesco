<?php
/*
* Plugin Name: WooCommerce Boleto Bradesco
* Plugin URI: www.linkedin.com/in/franciellyferreira
* Description: Faça pagamento através do boleto do Banco Bradesco.
* Author: Franciélly Ferreira e Silva
* Create: Julho/2018
* Version: 1.0
*/

/*
* This action hook registers our PHP class as a WooCommerce payment gateway
*/
add_filter( 'woocommerce_payment_gateways', 'boleto_add_gateway_class' );

function boleto_add_gateway_class( $gateways ) {
  $gateways[] = 'WC_Boleto_Gateway'; // your class name is here
  return $gateways;
}

/*
* The class itself, please note that it is inside plugins_loaded action hook
*/
add_action( 'plugins_loaded', 'boleto_init_gateway_class' );

function boleto_init_gateway_class() {

  class WC_Boleto_Gateway extends WC_Payment_Gateway {

    /**
    * Class constructor, more about it in Step 3
    */
    public function __construct() {

      $this->id = 'boleto'; // payment gateway plugin ID
      $this->icon = site_url() . '/wp-content/plugins/woocommerce-boleto-bradesco/assets/images/boleto.png'; // URL of the icon that will be displayed on checkout page near your gateway name
      $this->has_fields = true; // in case you need a custom credit card form
      $this->method_title = 'Boleto Bradesco';
      $this->method_description = 'Realizar pagamentos usando o boleto do Banco Bradesco.'; // will be displayed on the options page

      // gateways can support subscriptions, refunds, saved payment methods,
      // but in this tutorial we begin with simple payments
      $this->supports = array(
        'products'
      );

      // Method with all the options fields
      $this->init_form_fields();

      // Load the settings.
      $this->init_settings();
      $this->title = $this->get_option( 'title' );
      $this->description = $this->get_option( 'description' );
      $this->enabled = $this->get_option( 'enabled' );
      $this->testmode = 'yes' === $this->get_option( 'testmode' );
      $this->merchant_id = $this->get_option( 'merchant_id' );
      $this->link_bradesco = $this->testmode ? $this->get_option( 'link_homologacao' ) : $this->get_option( 'link_producao' );
      $this->chave_bradesco = $this->testmode ? $this->get_option( 'chave_homologacao' ) : $this->get_option( 'chave_producao' );
      $this->taxa_juros_parcela = $this->get_option( 'taxa_juros_parcela' );
      $this->email_usuario = $this->get_option( 'email_usuario' );
      $this->email_senha = $this->get_option( 'email_senha' );
      $this->email_nome = $this->get_option( 'email_nome' );
      $this->api_boleto = $this->get_option( 'url_api_boleto_bradesco' );

      // This action hook saves the settings
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

      // We need custom JavaScript to obtain a token
      add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

    }

    /**
    * Plugin options, we deal with it in Step 3 too
    */
    public function init_form_fields(){

      $this->form_fields = array(
        'enabled' => array(
          'title'       => 'Habilitar/Desabilitar',
          'label'       => 'Habilitar Boleto Bradesco',
          'type'        => 'checkbox',
          'description' => '',
          'default'     => 'no'
        ),
        'title' => array(
          'title'       => 'Título',
          'type'        => 'text',
          'description' => 'Este texto será exibido para o usuário na página de checkout.',
          'default'     => 'Boleto Bradesco',
          'desc_tip'    => true,
        ),
        'description' => array(
          'title'       => 'Descrição',
          'type'        => 'textarea',
          'description' => 'Essa descrição será exibida como informativo para o usuário na página de checkout.',
          'default'     => 'Pague no boleto à vista ou parcelado com juros.',
        ),
        'taxa_juros_parcela' => array(
          'title'       => '* Taxa de Juros na Parcela',
          'type'        => 'text',
          'description' => 'Informe a taxa de juros que deve ser aplicada na parcela.',
          'default'     => ''
        ),
        'merchant_id' => array(
          'title'       => '* Merchant ID',
          'type'        => 'text'
        ),
        'link_producao' => array(
          'title'       => '* Transação de Produção',
          'type'        => 'text'
        ),
        'chave_producao'=> array(
          'title'       => '* Chave de Produção',
          'type'        => 'password'
        ),
        'email_usuario' => array(
          'title'       => '* E-mail Remetente',
          'type'        => 'email',
        ),
        'email_senha'   => array(
          'title'       => '* Senha Remente',
          'type'        => 'password',
        ),
        'email_nome'    => array(
          'title'       => '* Nome Remetente',
          'type'        => 'text',
        ),
        'url_api_boleto_bradesco' => array(
          'title'       => '* URL da API do Boleto Bradesco',
          'type'        => 'text',
        ),
        'testmode' => array(
          'title'       => 'Modo de Teste',
          'label'       => 'Habilitar modo de teste',
          'type'        => 'checkbox',
          'description' => 'Deve ser habilitado caso você deseje realizar testes.',
          'default'     => 'yes',
          'desc_tip'    => true,
        ),
        'link_homologacao' => array(
          'title'       => 'Transação de Homologação',
          'type'        => 'text'
        ),
        'chave_homologacao' => array(
          'title'       => 'Chave de Homologação',
          'type'        => 'password',
        )
      );

    }

    /**
    * You will need it if you want your custom credit card form, Step 4 is about it
    */
    public function payment_fields() {

      // ok, let's display some description before the payment form
      if ( $this->description ) {
        // you can instructions for test mode, I mean test card numbers etc.
        if ( $this->testmode ) {
          $this->description .= ' MODO DE TESTE HABILITADO. No modo de teste, você pode gerar boletos de teste no ambiente de homologação do Banco Bradesco.';
          $this->description  = trim( $this->description );
        }

        // display the description with <p> tags etc.
        echo wpautop( wp_kses_post( $this->description ) );

      }

      // I will echo() the form, but you can close PHP tags and print it directly in HTML
      echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-form" class="wc-boleto-form wc-payment-form" style="background:transparent;">';

      // Add this action hook if you want your custom gateway to support it
      do_action( 'woocommerce_boleto_form_start', $this->id );

      // pega o valor total da compra
      $valor_compra = $this->valor_total_carrinho();

      // pega quantidade de parcelas possivel para a venda
      $quantidade_parcelas = $this->calcula_quantidade_parcelas();

      // calcula o valor de cada parcela e monta as options do select
      $opcoes = '';
      for($contador = 1; $quantidade_parcelas >= $contador ; $contador++) {
        $valor_parcela = $this->calcula_valor_parcela($valor_compra, $contador);
        if($contador == 1) {
          $opcoes .= '<option value="'.$contador.'">'.$contador.' x R$ '.number_format($valor_parcela, 2, ',', '.').' (À Vista)</option>';
        } else {
          $opcoes .= '<option value="'.$contador.'">'.$contador.' x R$ '.number_format($valor_parcela, 2, ',', '.').'</option>';
        }
      }

      // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
      echo '
      <div class="form-row form-row-wide selecionar-parcelas-boleto">
      <label><span class="required">*</span> Parcelar em:</label>
      <select id="parcelas" class="form-control" name="parcelas">
      '.$opcoes.'
      </select>
      </div>
      <div class="clear"></div>';

      do_action( 'woocommerce_boleto_form_end', $this->id );

      echo '</fieldset>';

    }

    /*
    * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
    */
    public function payment_scripts() {

      // we need JavaScript to process a token only on cart/checkout pages, right?
      if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
        return;
      }

      // if our payment gateway is disabled, we do not have to enqueue JS too
      if ( 'no' === $this->enabled ) {
        return;
      }

      // no reason to enqueue JavaScript if API keys are not set
      if ( empty( $this->link_bradesco ) || empty( $this->chave_bradesco ) ) {
        return;
      }

      // do not work with card detailes without SSL unless your website is in a test mode
      if ( ! $this->testmode && ! is_ssl() ) {
        return;
      }

    }

    /*
    * Fields validation, more in Step 5
    */
    public function validate_fields() {

      if( empty($_POST['billing_first_name'])) {
        wc_add_notice( 'É necessário informar o Primeiro Nome.', 'error' );
        return false;
      }
      return true;

    }

    /*
    * We're processing the payments here, everything about it is in Step 5
    */
    public function process_payment( $order_id ) {

      global $woocommerce;

      // we need it to get any order detailes
      $order = wc_get_order( $order_id );

      // grava dados de pagamento na tabela wp_postmeta
      $this->grava_pagamento_no_boleto($order_id);

      /*
      * Array with parameters for API interaction
      */
      $args = array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'body' => array(
          'bradesco' => [
            'merchant_id' => $this->criptografia_api_boleto($this->merchant_id),
            'chave' => $this->criptografia_api_boleto($this->chave_bradesco),
            'link' => $this->link_bradesco
          ],
          'email' => [
            'usuario' => $this->email_usuario,
            'senha' => $this->email_senha,
            'nome' => $this->email_nome
          ],
          'cod_venda' => $order_id
        ),
        'cookies' => array()
      );

      /*
      * Your API interaction could be built with wp_remote_post()
      */
      $response = wp_remote_post( $this->api_boleto, $args );

      if( !is_wp_error( $response ) ) {

        $body = json_decode( $response['body'], true );

        // it could be different depending on your payment processor
        if ( $body['response']['responseCode'] == 'APPROVED' ) {

          // grava url do boleto gerado no banco wp_postmeta
          $this->grava_link_boleto($order_id, $body['response']['url_boleto']);

          // we received the payment
          $order->payment_complete();
          $order->reduce_order_stock();

          // some notes to customer (replace true with false to make it private)
          $order->add_order_note( 'Olá, seu boleto foi gerado. Faça o pagamento! Obrigado!', true );

          // Empty cart
          $woocommerce->cart->empty_cart();

          // Redirect to the thank you page
          return array(
            'result' => 'success',
            'redirect' => $this->get_return_url( $order )
          );

        } else {
          wc_add_notice(  'Por favor tente novamente.', 'error' );
          return;
        }

      } else {
        wc_add_notice(  'Erro de conexão.', 'error' );
        return;
      }

    }

    /*
    * Calcula valor total dos produtos no carrinho.
    */
    private function valor_total_carrinho()
    {
      $currency = get_woocommerce_currency();
      $valor_compra = max( 0, apply_filters( 'woocommerce_calculated_total', round( WC()->cart->cart_contents_total + WC()->cart->fee_total + WC()->cart->tax_total, WC()->cart->dp ), WC()->cart ) );

      return $valor_compra;
    }

    /*
    * Verifica entre os itens do carrinho o item que tem a maior quantidade de parcelas.
    */
    private function calcula_quantidade_parcelas()
    {
      $quantidade_parcelas = 0;

      foreach ( WC()->cart->get_cart() as $cart_item ) {

        $produto_id = $cart_item['product_id'];
        $produto_parcelas = get_post_meta($produto_id, '_maximo_parcelas_boleto', true);

        if($quantidade_parcelas < $produto_parcelas) {
          $quantidade_parcelas = $produto_parcelas;
        }

      }

      return $quantidade_parcelas;
    }

    /*
    * Pega taxa de juros habilitada no painel do plugin.
    */
    private function taxa_juros()
    {
      $juros = $this->taxa_juros_parcela;
      $juros = str_replace('%','', $juros);
      $juros = str_replace(',','.', $juros);

      return $juros;
    }

    /*
    * Calcula o valor de cada parcela com juros.
    */
    private function calcula_valor_parcela($valor_compra, $quantidade_parcelas)
    {
      if($quantidade_parcelas > 1) {

        $juros = $this->taxa_juros();
        $valor_juros = (($valor_compra * $juros) / 100);
        $valor_parcela_com_juros = (($valor_compra / $quantidade_parcelas) + $valor_juros);

        return $valor_parcela_com_juros;

      } else {

        return $valor_compra;

      }
    }

    /*
    * Criptografa textos para usar para realizar a transacao com a API de Boleto do Bradesco.
    */
    private function criptografia_api_boleto($texto)
    {
      // Criar metodo de criptografia para transferir dados para a API
    }

    /*
    * Gravar dados do pagamento no boleto no banco de dados.
    */
    private function grava_pagamento_no_boleto($order_id)
    {
      // pega quantidade de parcelas selecionada para pagamento
      $quantidade_parcelas = $_POST['parcelas'];

      // pega valor total do carrinho
      $valor_compra = $this->valor_total_carrinho();

      // calcula valor da parcela
      $valor_parcela = $this->calcula_valor_parcela($valor_compra, $quantidade_parcelas);

      // pega porcentagem de juros aplicado por parcela
      $juros = $this->taxa_juros();

      if($quantidade_parcelas > 1) {
        $aplicou_juros = 'true';
      } else {
        $aplicou_juros = 'false';
      }

      // grava ou atualiza no banco na tabela wp_postmeta os dados escolhidos para pagamento no boleto
      if(get_post_meta($order_id, '_boleto_quantidade_parcelas', true)) {
        add_post_meta($order_id, '_boleto_quantidade_parcelas', $quantidade_parcelas, true);
        add_post_meta($order_id, '_boleto_valor_parcela', round($valor_parcela,2), true);
        add_post_meta($order_id, '_boleto_aplicou_juros', $aplicou_juros, true);
        add_post_meta($order_id, '_boleto_valor_juros', $juros, true);
      } else {
        update_post_meta($order_id, '_boleto_quantidade_parcelas', $quantidade_parcelas, '');
        update_post_meta($order_id, '_boleto_valor_parcela', round($valor_parcela,2), '');
        update_post_meta($order_id, '_boleto_aplicou_juros', $aplicou_juros, '');
        update_post_meta($order_id, '_boleto_valor_juros', $juros, '');
      }

    }

    /*
    * Grava link do boleto no banco de dados após a confirmação do pagamento.
    */
    private function grava_link_boleto($order_id, $url_boleto)
    {
      add_post_meta($order_id, '_boleto_url', $url_boleto, true);
    }
  }
}
