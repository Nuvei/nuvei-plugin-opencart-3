<style type="text/css">
    #nuvei_submit h3.required:before {
        content: '* ';
        color: #F00;
        font-weight: bold;
    }

    #nuvei_submit #sc_pm_error {
        color: red;
        font-size: 12px;
    }

	.sfcModal-dialog {
		width: 50%;
		margin: 0 auto;
		margin-top: 10%;
	}
    /* fixes for last field borders END */
    
/*    #nuvei_blocker {
        position: fixed;
        display: none;
        top: 0px;
        left: 0px;
        width: 100%;
        height: 100%;
        background-color: black;
        opacity: 0.5;
        z-index: 9999;
    }
    
    #nuvei_blocker img {
        position: absolute;
        z-index: 999999;
        left: 50%;
        position: relative;
        margin-left: -32px;
        top: 50%;
        margin-top: -32px;
        opacity: 1;
    }*/
</style>

<!--<div id="nuvei_blocker">
    <img src="catalog/view/theme/default/image/nuvei_loading.gif" />
</div>-->

<div class="modal" tabindex="-1" role="dialog" id="nuvei_modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modal title</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <p></p>
            </div>
            
            <div class="modal-footer">
                <!--<button type="button" class="btn btn-primary">Save changes</button>-->
                <button type="button" class="btn btn-secondary" data-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<form action="<?= $data['action']; ?>" method="POST" name="nuvei_submit" id="nuvei_submit">
    <div id="reload_apms_warning" class="alert alert-danger hide">
        <strong><?= $data['nuvei_attention']; ?></strong> <?= $data['nuvei_go_to_step_2_error']; ?>
        <a href="javascript:void(0);" class="close" onclick="$(this).parent('div').addClass('hide');" aria-label="close" title="close">&times;</a>
    </div>
        
    <div id="sc_pm_error" class="alert alert-danger hide">
        <span><?= @$data['choose_pm_error']; ?></span>
        <a href="javascript:void(0);" class="close" onclick="$(this).parent('div').addClass('hide');" aria-label="close" title="close">&times;</a>
    </div>
    
	<!--<input type="hidden" name="lst" id="sc_lst" value="<?= @$data['sessionToken']; ?>" />-->
	<input type="hidden" name="sc_transaction_id" id="sc_transaction_id" value="" />
    
    <div id="nuvei_checkout" class="container-fluid"></div>
    
<!--    <div class="buttons" style="display: none;">
        <div class="pull-right">
			<button type="button" class="btn btn-primary" onclick="nuveiUpdateOrder()"><?= $data['button_confirm']; ?></button>
        </div>
    </div>-->
</form>

<script type="text/javascript">
    // update the order just before submit
    function nuveiUpdateOrder() {
        console.log('nuveiUpdateOrder()');
        
        // show Loading... button
		$('#nuvei_blocker').show();
        
        scOpenNewOrder(true);
        scValidateAPMFields();
    }

    function nuveiAfterSdkResponse(resp) {
        console.log('nuveiAfterSdkResponse', resp);

        if(resp.hasOwnProperty('result')) {
            if(resp.result == 'APPROVED' && resp.hasOwnProperty('transactionId')) {
                $('#sc_transaction_id').val(resp.transactionId);
                $('form#nuvei_submit').submit();

                return;
            }
            
            if(resp.result == 'DECLINED') {
                if (resp.hasOwnProperty('errorDescription') && 'Insufficient funds' == resp.errorDescription) {
                    scFormFalse("<?= $this->language->get('error_insuff_funds'); ?>");
                    return
                }
                
                scFormFalse("<?= $this->language->get('nuvei_order_declined'); ?>");
                return;
            }
            
//            scOpenNewOrder();
//
//            if(resp.hasOwnProperty('errorDescription') && resp.errorDescription !== '') {
//                scFormFalse(resp.errorDescription);
//                $('#nuvei_blocker').hide();
//                return;
//            }
//            
//            if(resp.hasOwnProperty('reason') && '' !== resp.reason) {
//                scFormFalse(resp.reason);
//                $('#nuvei_blocker').hide();
//                return;
//            }
//            
//            scFormFalse("<?= $data['nuvei_order_error']; ?>");
//            $('#nuvei_blocker').hide();
//            return;
        }
        
//        scOpenNewOrder();
//
//        alertDiv.find('span').html("<?= $data['nuvei_order_error']; ?>");
//        alertDiv.removeClass('hide');
//
        console.error('Error with SDK response: ' + resp);
        
        scFormFalse("<?= $data['nuvei_order_error']; ?>");
        
        $('html, body').animate({
            // show 100px above the error message
            scrollTop: $("#sc_pm_error").offset().top - 100
        }, 500);
//
//        $('#nuvei_blocker').hide();
    }
    
	function scOpenNewOrder(doNotCreateFields = false) {
        console.log('scOpenNewOrder(), doNotCreateFields:', doNotCreateFields);
    
		$.ajax({
			url: 'index.php?route=<?= NUVEI_CONTROLLER_PATH; ?>',
			type: 'post',
			dataType: 'json'
		})
		.done(function(resp) {
            if(doNotCreateFields) {
                return;
            }
            
			if('success' == resp.status) {
				// clean old data
				scData.sessionToken = resp.sessionToken;
				cardNumber = sfcFirstField = cardExpiry = cardCvc = null;
				
                $('#nuvei_blocker').hide();
			}
			else {
				window.location.reload();
			}
		});
	}

	function scFormFalse(_errorMsg) {
		$('#sc_pm_error').find('span').html(_errorMsg);
		$('#sc_pm_error').removeClass('hide');
        
        // scroll to element
        var elementt = document.getElementById('nuvei_submit');
        var headerOffset = 45;
        var elementPosition = elementt.getBoundingClientRect().top;
        var offsetPosition = elementPosition + window.pageYOffset - headerOffset;

        window.scrollTo({
            top: offsetPosition,
            behavior: "smooth"
        });

//		document.getElementById('sc_payment_method_' + selectedPM).scrollIntoView();
		
//        $('#nuvei_blocker').hide();
	}
    
    function showNuveiCheckout() {
        console.log('showNuveiCheckout()');

        nuveiCheckoutSdkParams              = <?= json_encode($data['nuvei_sdk_params']); ?>;
        nuveiCheckoutSdkParams.prePayment	= nuveiPrePayment;
        nuveiCheckoutSdkParams.onResult		= nuveiAfterSdkResponse;

        console.log(nuveiCheckoutSdkParams);

        checkout(nuveiCheckoutSdkParams);
    }
    
    function nuveiPrePayment(paymentDetails) {
        console.log('nuveiPrePayment');

        return new Promise((resolve, reject) => {
            $.ajax({
                url: 'index.php?route=<?= NUVEI_CONTROLLER_PATH; ?>',
                type: 'post',
                dataType: 'json'
            })
            .fail(function(){
                reject("<?= $this->language->get('nuvei_order_error'); ?>");
            })
            .done(function(resp) {
                if(resp.hasOwnProperty('sessionToken') && '' != resp.sessionToken) {
                    $('#lst').val(resp.sessionToken);
            
                    if(resp.sessionToken == nuveiCheckoutSdkParams.sessionToken) {
                        resolve();
                        return;
                    }
            
                    // reload the Checkout
                    nuveiCheckoutSdkParams.sessionToken	= resp.sessionToken;
                    nuveiCheckoutSdkParams.amount		= resp.amount;

                    showNuveiCheckout();
                    return;
                }
                
                reject("<?= $this->language->get('nuvei_order_error'); ?>");
            });
            
        });
    }

	$(function() {
        console.log('document loaded')
        
		// when change Payment Country and not click on Continue button show warning!
		$('#collapse-payment-address').on('change', '#input-payment-country', function(){
			$('#reload_apms_warning').removeClass('hide');
		});

		$('input[name="payment_method_sc"]').on('change', function() {
			$('.sc_apm_fields').addClass('collapse');
			$('input[name="payment_method_sc"]:checked').closest('.sc_apms_fieldset').find('.sc_apm_fields').removeClass('collapse');
		});
        
        showNuveiCheckout();
	});
	// document ready function END
</script>
