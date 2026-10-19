(function ($) {
  // Wait until the DOM is fully loaded
  $(document).ready(function () {
    const $providerSelector = $('#provider');
    const $coinsnapWrapper = $('#coinsnap-settings-wrapper');
    const $btcpayWrapper = $('#btcpay-settings-wrapper');
    const $checkConnectionCoisnanpButton = $('#check_connection_coinsnap_button');
    const $checkConnectionBtcPayButton = $('#check_connection_btcpay_button');
    const coinsnapStoreIdField = document.getElementById('coinsnap_store_id');
    const coinsnapApiKeyField = document.getElementById('coinsnap_api_key');
    const btcpayStoreIdField = document.getElementById('btcpay_store_id');
    const btcpayApiKeyField = document.getElementById('btcpay_api_key');
    const btcpayUrlField = document.getElementById('btcpay_url');

    if ($providerSelector.val() === 'coinsnap' && coinsnapStoreIdField && coinsnapApiKeyField) {
      $checkConnectionCoisnanpButton.prop("disabled", !(coinsnapApiKeyField.value.length > 12 && coinsnapStoreIdField.value.length > 12));
      coinsnapApiKeyField.addEventListener('input', function () {
        $checkConnectionCoisnanpButton.prop("disabled", !(coinsnapApiKeyField.value.length > 12 && coinsnapStoreIdField.value.length > 12));
      });
      coinsnapStoreIdField.addEventListener('input', function () {
        $checkConnectionCoisnanpButton.prop("disabled", !(coinsnapApiKeyField.value.length > 12 && coinsnapStoreIdField.value.length > 12));
      });
    } else if ($providerSelector.val() === 'btcpay' && btcpayStoreIdField && btcpayApiKeyField && btcpayUrlField) {
      $checkConnectionBtcPayButton.prop("disabled", !(btcpayApiKeyField.value.length > 4 && btcpayStoreIdField.value.length > 12 && btcpayUrlField.value.length > 12));
      btcpayApiKeyField.addEventListener('input', function () {
        $checkConnectionBtcPayButton.prop("disabled", !(btcpayApiKeyField.value.length > 4 && btcpayStoreIdField.value.length > 12 && btcpayUrlField.value.length > 12));
      });
      btcpayStoreIdField.addEventListener('input', function () {
        $checkConnectionBtcPayButton.prop("disabled", !(btcpayApiKeyField.value.length > 4 && btcpayStoreIdField.value.length > 12 && btcpayUrlField.value.length > 12));
      });
      btcpayUrlField.addEventListener('input', function () {
        $checkConnectionBtcPayButton.prop("disabled", !(btcpayApiKeyField.value.length > 4 && btcpayStoreIdField.value.length > 12 && btcpayUrlField.value.length > 12));
      });
    }

    function checkConnection(storeId, apiKey, btcpayUrl) {
      const headers = btcpayUrl ? { 'Authorization': `token ${apiKey}` } : { 'x-api-key': apiKey, };
      const url = btcpayUrl
        ? `${btcpayUrl}/api/v1/stores/${storeId}/invoices`
        : `https://app.coinsnap.io/api/v1/stores/${storeId}`

      return $.ajax({
        url: url,
        method: 'GET',
        contentType: 'application/json',
        headers: headers
      })
        .then(() => true)
        .catch(() => false);

    }

    function registerProviderWebhook(storeId, apiKey, webhookUrl, btcpayUrl) {
      return $.ajax({
        url: coinsnap_bitcoin_crowdfunding_ajax.ajax_url,
        method: 'POST',
        dataType: 'json',
        data: {
          action: 'coinsnap_bitcoin_crowdfunding_register_webhook',
          apiNonce: coinsnap_bitcoin_crowdfunding_ajax.nonce,
          storeId: storeId,
          apiKey: apiKey,
          webhookUrl: webhookUrl,
          btcpayUrl: btcpayUrl || ''
        }
      });
    }

    function toggleProviderSettings() {
      if (!$providerSelector || !$providerSelector.length) {
        return;
      }
      const selectedProvider = $providerSelector.val();
      $coinsnapWrapper.toggle(selectedProvider === 'coinsnap');
      $btcpayWrapper.toggle(selectedProvider === 'btcpay');
    }

    toggleProviderSettings();

    $providerSelector.on('change', toggleProviderSettings);

    function getCookie(name) {
      const value = `; ${document.cookie}`;
      const parts = value.split(`; ${name}=`);
      if (parts.length === 2) return parts.pop().split(';').shift();
    }

    function setCookie(name, value, seconds) {
      const d = new Date();
      d.setTime(d.getTime() + (seconds * 1000));
      const expires = "expires=" + d.toUTCString();
      document.cookie = name + "=" + value + ";" + expires + ";path=/";
    }

    async function crowdfundingConnectionCheckHandler(isSubmit = false) {
      var connection = false;
      const ngrokLiveUrl = document.getElementById('ngrok_url')?.value;
      const origin = ngrokLiveUrl ? ngrokLiveUrl : new URL(window.location.href).origin;
      const webhookUrl = `${origin}/wp-json/coinsnap-bitcoin-crowdfunding/v1/webhook`;
      if ($providerSelector?.val() === 'coinsnap') {
        const coinsnapStoreId = $('#coinsnap_store_id').val();
        const coinsnapApiKey = $('#coinsnap_api_key').val();
        connection = await checkConnection(coinsnapStoreId, coinsnapApiKey);
        if (connection) {
          try {
            await registerProviderWebhook(coinsnapStoreId, coinsnapApiKey, webhookUrl);
          } catch (error) {
            console.error('Webhook registration failed');
          }
        }
      } else {
        const btcpayStoreId = $('#btcpay_store_id').val();
        const btcpayApiKey = $('#btcpay_api_key').val();
        const btcpayUrl = $('#btcpay_url').val();
        connection = await checkConnection(btcpayStoreId, btcpayApiKey, btcpayUrl);
        if (connection) {
          try {
            await registerProviderWebhook(btcpayStoreId, btcpayApiKey, webhookUrl, btcpayUrl);
          } catch (error) {
            console.error('Webhook registration failed');
          }
        }
      }
      setCookie('coinsnap_bitcoin_crowdfunding_connection', JSON.stringify({ 'connection': connection }), 20);
      if (!isSubmit) {
        $('#submit').click();

      }
    }

    $('#submit').click(async function (event) {
      await crowdfundingConnectionCheckHandler();
      $('#submit').click();
    });


    $checkConnectionCoisnanpButton.on('click', async (event) => { await crowdfundingConnectionCheckHandler(); })
    $checkConnectionBtcPayButton.on('click', async (event) => { await crowdfundingConnectionCheckHandler(); });

    const connectionCookie = getCookie('coinsnap_bitcoin_crowdfunding_connection');
    if (connectionCookie) {
      const connectionState = JSON.parse(connectionCookie)?.connection;
      const checkConnection = $(`#check_connection_${$providerSelector?.val()}`);
      connectionState
        ? checkConnection.css({ color: 'green' }).text('Connection successful')
        : checkConnection.css({ color: 'red' }).text('Connection failed');
    }
    
    //  Crownfunding list
    function toggleDonorFields() {
                    if ($('input[name="coinsnap_bitcoin_crowdfunding_collect_donor_info"]').is(':checked')) {
                        $('#donor-info-fields').show();
                    } else {
                        $('#donor-info-fields').hide();
                    }
                }

    function toggleShoutoutShortcode() {
                    if ($('input[name="coinsnap_bitcoin_crowdfunding_shoutout"]').is(':checked')) {
                        $('#shoutout-shortcode-row').show();
                    } else {
                        $('#shoutout-shortcode-row').hide();
                    }
                }

    $('input[name="coinsnap_bitcoin_crowdfunding_collect_donor_info"]').change(toggleDonorFields);
    $('input[name="coinsnap_bitcoin_crowdfunding_shoutout"]').change(toggleShoutoutShortcode);

    toggleDonorFields();
    toggleShoutoutShortcode();
    
    if($('#coinsnap_bitcoin_crowdfunding_default_currency').length){
        
        setStep();
        $('#coinsnap_bitcoin_crowdfunding_default_currency').change(
            function(){
                setStep();
            }    
        );
        
    }
    
    $('#coinsnap_bitcoin_crowdfunding_btcpay_wizard_button').click(function(e) {
        e.preventDefault();
        const host = $('#btcpay_url').val();
	if (isCrowdfundingValidUrl(host)) {
            let data = {
                'action': 'coinsnap_bitcoin_crowdfunding_btcpay_apiurl_handler',
                'host': host,
                'apiNonce': coinsnap_bitcoin_crowdfunding_ajax.nonce
            };
            
            $.post(coinsnap_bitcoin_crowdfunding_ajax.ajax_url, data, function(response) {
                if (response.data.url) {
                    window.location = response.data.url;
		}
            }).fail( function() {
		alert('Error processing your request. Please make sure to enter a valid BTCPay Server instance URL.')
            });
	}
        else {
            alert('Please enter a valid url including https:// in the BTCPay Server URL input field.')
        }
    });
    
    if($('.coinsnapConnectionStatus').length){
        
        console.log('Connection check is activated');
        
        let ajaxurl = coinsnap_bitcoin_crowdfunding_ajax.ajax_url;
        let data = {
            action: 'coinsnap_bitcoin_crowdfunding_connection_handler',
            apiNonce: coinsnap_bitcoin_crowdfunding_ajax.nonce,
            apiPost: coinsnap_bitcoin_crowdfunding_ajax.post
        };

        jQuery.post( ajaxurl, data, function( response ){

            connectionCheckResponse = $.parseJSON(response);
            let resultClass = (connectionCheckResponse.result === true)? 'success' : 'error';
            $('.coinsnapConnectionStatus').html('<span class="'+resultClass+'">'+ connectionCheckResponse.message +'</span>');
            
        });
    }
    
  });
  
  function setStep(){
        let step = 0.01;
        let currency = $('#coinsnap_bitcoin_crowdfunding_default_currency').val();
        if(currency === 'RUB' || currency === 'JPY' || currency === 'SATS'){
            step = 1;
        }
        $('.coinsnap_bitcoin_crowdfunding_amount').attr('step', step);
    }
  
  function isCrowdfundingValidUrl(serverUrl) {
        if(serverUrl.indexOf('http') > -1){
            try {
                const url = new URL(serverUrl);
                if (url.protocol !== 'https:' && url.protocol !== 'http:') {
                    return false;
                }
            }
            catch (e) {
                console.error(e);
                return false;
            }
            return true;
        }
        else {
            return false;
        }
    }

})(jQuery);
