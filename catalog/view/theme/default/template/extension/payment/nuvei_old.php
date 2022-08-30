<style type="text/css">
    #safechargesubmit h3.required:before {
        content: '* ';
        color: #F00;
        font-weight: bold;
    }

    #safechargesubmit #sc_pm_error {
        color: red;
        font-size: 12px;
    }

    #safechargesubmit .apm_container {
        width: 100%;
        height: 100%;
        cursor: pointer;
        padding: 1rem 0 0 0;
    }

    #safechargesubmit .apm_title  {
        cursor: pointer;
        border-bottom: .1rem solid #939393;
        padding-bottom: 0.5em;
        position: relative;
    }
    
    #safechargesubmit .sc_apm_fields { margin-left: 21px; }
    
    #safechargesubmit #nuvei_save_upo_cont { display: none; }
	
	#safechargesubmit .alert-dismissable .close, .alert-dismissible .close { right: 0; }
	
	.sc_apms_fieldset .radio label input { margin-top: 12px; }
	#sc_card_expiry, #sc_card_cvc, #sc_card_number, .nuvei_upo_cvv_field { padding-top: 10px; }
    .SfcField iframe { min-height: 20px !important; }
	
	.sfcModal-dialog {
		width: 50%;
		margin: 0 auto;
		margin-top: 10%;
	}
    /* fixes for last field borders END */
    
    #nuvei_blocker {
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
    }
</style>

<div id="nuvei_blocker">
    <img src="catalog/view/theme/default/image/nuvei_loading.gif" />
</div>

<form action="<?= $data['action']; ?>" method="POST" name="safechargesubmit" id="safechargesubmit">
    <div id="reload_apms_warning" class="alert alert-danger hide">
        <strong><?= $data['nuvei_attention']; ?></strong> <?= $data['nuvei_go_to_step_2_error']; ?>
        <a href="javascript:void(0);" class="close" onclick="$(this).parent('div').addClass('hide');" aria-label="close" title="close">&times;</a>
    </div>
        
    <div id="sc_pm_error" class="alert alert-danger hide">
        <span><?= $data['choose_pm_error']; ?></span>
        <a href="javascript:void(0);" class="close" onclick="$(this).parent('div').addClass('hide');" aria-label="close" title="close">&times;</a>
    </div>
    
    <?php if(!empty($data['nuvei_upos'])): ?>
        <h3 class="required"><?= $data['nuvei_upos_title']; ?></h3>
		<hr/>
        
        <?php foreach($data['nuvei_upos'] as $payment_method): ?>
			<div class="sc_apms_fieldset">
				<div class="radio">
					<label>
						<input type="radio" id="sc_payment_method_<?= $payment_method['userPaymentOptionId'] ?>" name="payment_method_sc" value="<?= $payment_method['userPaymentOptionId'] ?>" data-upo-name="<?= $payment_method['paymentMethodName']; ?>" />
						
                        <img src="<?= $payment_method['logoURL']; ?>" alt="<?= $payment_method['name']; ?>" style="height: 36px;" />
                        &nbsp;<?= 'cc_card' == $payment_method['paymentMethodName'] ? $payment_method['upoData']['ccCardNumber'] : $payment_method['upoName']; ?>
					</label>&nbsp;&nbsp;&nbsp;
                    
                    <button type="button" data-toggle="tooltip" title="" class="btn btn-danger" onclick="nuveiRemoveUpo(<?= $payment_method['userPaymentOptionId'] ?>);" data-original-title="Remove"><i class="fa fa-trash"></i></button>
				</div>

				<?php if('cc_card' == $payment_method['paymentMethodName']): ?>
                    <div class="collapse sc_apm_fields">
						<div class="alert alert-danger hide">
							<span>error msg</span>
							<a href="javascript:void(0);" class="close" onclick="$(this).parent('div').addClass('hide');" aria-label="close" title="close">&times;</a>
						</div>
						
                        <div class="row">
							<div class="col-xs-3 form-group">
								<div id="sc_card_cvc_<?= $payment_method['userPaymentOptionId'] ?>" class="form-control nuvei_upo_cvv_field"></div>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
        <br/>
    <?php endif; ?>
    
    <?php if(!empty($data['payment_methods'])): ?>
        <h3 class="required"><?= $data['nuvei_pms_title']; ?></h3>
		<hr/>
		
		<?php foreach($data['payment_methods'] as $payment_method): ?>
			<div class="sc_apms_fieldset">
				<div class="radio">
					<label>
						<input type="radio" 
                               id="sc_payment_method_<?= $payment_method['paymentMethod'] ?>" 
                               name="payment_method_sc" 
                               value="<?= $payment_method['paymentMethod'] ?>" 
                               data-nuvei-is-direct="<?= $payment_method['isDirect'] ?>" />
						
						<?php if('cc_card' == $payment_method['paymentMethod']): ?>
							<img src="catalog/view/theme/default/image/visa_mc_maestro.svg" alt="<?= @$payment_method['paymentMethodDisplayName'][0]['message'] ?>" style="height: 36px;" />
						<?php else: ?>
							<img src="<?= str_replace('/svg/', '/svg/solid-white/', @$payment_method['logoURL']); ?>" alt="<?= @$payment_method['paymentMethodDisplayName'][0]['message'] ?>" />
						<?php endif; ?>
							
						&nbsp;<?= @$payment_method['paymentMethodDisplayName'][0]['message'] ?>
					</label>
				</div>

				<?php if(in_array($payment_method['paymentMethod'], array('cc_card'))): ?>
					<div class="collapse sc_apm_fields">
						<div class="alert alert-danger hide">
							<span>error msg</span>
							<a href="javascript:void(0);" class="close" onclick="$(this).parent('div').addClass('hide');" aria-label="close" title="close">&times;</a>
						</div>
						
						<div class="form-group">
							<input type="text" 
								   id="sc_card_holder_name" 
								   name="<?= $payment_method['paymentMethod']; ?>[cardHolderName]" 
								   placeholder="Card holder name"
								   class="form-control" />
						</div>

						<div class="row">
							<div class="col-xs-12 col-sm-6 form-group">
								<div id="sc_card_number" class="form-control"></div>
							</div>

							<div class="col-xs-12 col-sm-6 form-group">
								<div class="row">
									<div class="col-xs-6">
										<div id="sc_card_expiry" class="form-control"></div>
									</div>

									<div class="col-xs-6">
										<div id="sc_card_cvc" class="form-control"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php elseif(count($payment_method['fields']) > 0): ?>
					<div class="collapse sc_apm_fields">
						<div class="alert alert-danger hide">
							<span></span>
							<a href="javascript:void(0);" class="close" onclick="$(this).parent('div').addClass('hide');" aria-label="close" title="close">&times;</a>
						</div>
						
						<?php foreach($payment_method['fields'] as $p_field): ?>
							<div class="form-group">
								<input id="<?= $payment_method['paymentMethod']; ?>_<?= $p_field['name']; ?>" 
									   class="form-control"
									   name="<?= $payment_method['paymentMethod']; ?>[<?= $p_field['name']; ?>]" 
									   type="<?= ('nettelerAccount' == $p_field['name']) ? 'email' : $p_field['type']; ?>" 
									   <?php if(!empty($p_field['regex'])): ?>pattern="<?= $p_field['regex'] ?>"<?php endif; ?> 
									   <?php if(!empty($p_field['caption'][0]['message'])): ?>placeholder="<?= @$p_field['caption'][0]['message']; ?>"<?php else: ?>placeholder="<?= @$p_field['name']; ?>"<?php endif; ?> />
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-danger hide"><?= $data['rest_no_apms_error']; ?></div>
    <?php endif;?> 
        
	<input type="hidden" name="lst" id="sc_lst" value="<?= @$data['sessionToken']; ?>" />
	<input type="hidden" name="sc_transaction_id" id="sc_transaction_id" value="" />
    
    <?php if(1 == $data['isUserLogged'] && 'yes' == $data['useUpos']): ?>
        <div class="form-group" id="nuvei_save_upo_cont">
            <label>
                <input type="checkbox" name="nuvei_save_upo" id="nuvei_save_upo" value="0" />
                &nbsp;Save selected payment method as Preferred?
            </label>
        </div>
    <?php endif; ?>
        
    <div class="buttons">
        <div class="pull-right">
			<button type="button" class="btn btn-primary" onclick="nuveiUpdateOrder()"><?= $data['button_confirm']; ?></button>
        </div>
    </div>
</form>

<script type="text/javascript">
    var scData = {
        merchantSiteId		: "<?= $data['merchantSiteId']; ?>",
        merchantId			: "<?= $data['merchantId']; ?>",
        sessionToken		: "<?= $data['sessionToken']; ?>",
        sourceApplication	: "<?= @$data['sourceApplication']; ?>",
        env                 : "<?= 'yes' == $data['sc_test_env'] ? 'int' : 'prod'; ?>"
    };
    
	var scCard			= null;
	var sfc				= null;
	var scFields		= null;
	var selectedPM		= '';
	var sfcFirstField	= null;
	var cardNumber		= null;
	var cardExpiry		= null;
	var cardCvc			= null;
	var alertDiv		= null;
	
    function nuveiRemoveUpo(upoId) {
        if(!confirm("<?= $data['nuvei_ask_del_upo']; ?>")) {
            return;
        }
        
        $('#nuvei_blocker').show();
        
        $.ajax({
			url: 'index.php?route=<?= $data['ctr_path']; ?>',
			type: 'post',
			dataType: 'json',
            data: {
                action: 'remove_upo',
                upoId: upoId
            }
		})
        .done(function(resp){
            if(resp.hasOwnProperty('status') && 1 == resp.status) {
                $('#sc_payment_method_' + upoId).closest('.sc_apms_fieldset').remove();
                $('#nuvei_blocker').hide();
                return;
            }
            
            if(resp.hasOwnProperty('msg') && '' != resp.msg) {
                scFormFalse(resp.msg);
                return;
            }
            
            scFormFalse('<?= $data['nuvei_error_default']; ?>');
            
        });
    }
    
	/**
	 * Function createSCFields
	 * Call Nuvei method and pass the parameters
	 */
	function createSCFields() {
		sfc = SafeCharge(scData);

		// set some classes
		var elementClasses = {
			focus: 'focus'
			,empty: 'empty'
			,invalid: 'invalid'
		};
		
		var scFieldsStyle = {
			base: {
				iconColor: "#c4f0ff",
				color: "#000",
				fontWeight: 500,
				fontFamily: "arial",
				fontSize: '12px',
				fontSmoothing: "antialiased",
				":-webkit-autofill": {
					color: "#fce883"
				},
				"::placeholder": {
					color: "black" 
				}
			},
			invalid: {
				iconColor: "#FFC7EE",
				color: "red"
			}
		};
		
		scFields = sfc.fields({
			locale: "<?= $data['scLocale'] ?>"
		});
        
        // reset
        cardNumber = sfcFirstField = cardExpiry = cardCvc = null;
        $('#sc_card_number, #sc_card_expiry, #sc_card_cvc, .nuvei_upo_cvv_field').html('');
		
        if('cc_card' == selectedPM) {
            cardNumber = sfcFirstField = scFields.create('ccNumber', {
                classes: elementClasses
                ,style: scFieldsStyle
            });
            cardNumber.attach('#sc_card_number');

            cardExpiry = scFields.create('ccExpiration', {
                classes: elementClasses
                ,style: scFieldsStyle
            });
            cardExpiry.attach('#sc_card_expiry');

            cardCvc = scFields.create('ccCvc', {
                classes: elementClasses
                ,style: scFieldsStyle
            });
            cardCvc.attach('#sc_card_cvc');
        }
        else if(
            !isNaN(selectedPM) 
            && 'cc_card' == $('#sc_payment_method_' + selectedPM).attr('data-upo-name')
        ) {
            cardCvc = scFields.create('ccCvc', {
                classes: elementClasses
                ,style: scFieldsStyle
            });
            cardCvc.attach('#sc_card_cvc_' + selectedPM);
        }
	}

    // update the order just before submit
    function nuveiUpdateOrder() {
        console.log('nuveiUpdateOrder()');
        
        // show Loading... button
		$('#nuvei_blocker').show();
        
        scOpenNewOrder(true);
        scValidateAPMFields();
    }

	/**
	  * Function validateScAPMsModal
	  * When click save on modal, check for mandatory fields and validate them.
	  */
	function scValidateAPMFields() {
        console.log('scValidateAPMFields');
        
        if(typeof selectedPM == 'undefined' || selectedPM == '') {
			$('#sc_pm_error').removeClass('hide');
			
            window.location.hash = 'sc_pm_error';
			window.location.hash;
            
            $('#nuvei_blocker').hide();
			return;
		}
        
		var formValid   = true;
        alertDiv        = $('#sc_payment_method_' + selectedPM)
            .closest('.sc_apms_fieldset').find('.alert-danger');

        var nuveiPaymentParams = {
            sessionToken    : scData.sessionToken,
            merchantId      : "<?= @$data['merchantId'] ?>",
            merchantSiteId  : "<?= @$data['merchantSiteId'] ?>",
            webMasterId		: "<?= @$data['webMasterId'] ?>"
        }

        if (
            $('body').find('#nuvei_save_upo').is(':checked')
            || typeof $('input[name="payment_method_sc"]:checked').attr('data-upo-name') != 'undefined'
        ) {
            nuveiPaymentParams.userTokenId = "<?= $data['nuveiUserTokenId']; ?>";
        }

        // CC with WebSDK
        if(selectedPM == 'cc_card') {
            console.log('payment cc_card');
            
            if($('#sc_card_holder_name').val() == '') {
                scFormFalse("<?= $data['nuvei_cc_name_error']; ?>");
                return;
            }

            if(
                $('#sc_card_number.empty').length > 0
                || $('#sc_card_number.sfc-complete').length == 0
            ) {
                scFormFalse("<?= $data['nuvei_cc_num_error']; ?>");
                return;
            }

            if(
                $('#sc_card_expiry.empty').length > 0
                || $('#sc_card_expiry.sfc-complete').length == 0
            ) {
                scFormFalse("<?= $data['nuvei_cc_expiry_error']; ?>");
                return;
            }

            if(
                $('#sc_card_cvc.empty').length > 0
                || $('#sc_card_cvc.sfc-complete').length == 0
            ) {
                scFormFalse("<?= $data['nuvei_cc_cvc_error']; ?>");
                return;
            }

            nuveiPaymentParams.cardHolderName   = document.getElementById('sc_card_holder_name').value;
            nuveiPaymentParams.paymentOption    = sfcFirstField;

            sfc.createPayment(nuveiPaymentParams, function(resp){
                nuveiAfterSdkResponse(resp);
            });
        }
        // UPO CC with WebSDK
        else if(
            !isNaN(selectedPM) 
            && 'cc_card' == $('#sc_payment_method_' + selectedPM).attr('data-upo-name')
        ) {
            console.log('payment sdk upo');
    
            if(
                $('#sc_card_cvc_'+ selectedPM +'.empty').length > 0
                || $('#sc_card_cvc_'+ selectedPM +'.sfc-complete').length == 0
            ) {
                scFormFalse("<?= $data['nuvei_cc_cvc_error']; ?>");
                return;
            }

            nuveiPaymentParams.paymentOption = {
                userPaymentOptionId: selectedPM,
                card: {
                    CVV: cardCvc
                }
            };

            console.log(nuveiPaymentParams);

            // create payment with WebSDK
            sfc.createPayment(nuveiPaymentParams, function(resp){
                nuveiAfterSdkResponse(resp);
            });
        }
        // use APM data
//        else if(isNaN(parseInt(selectedPM))) {
        else {
            console.log('payment apm');
        
            nuveiPaymentParams.paymentOption = {
                alternativePaymentMethod: {
                    paymentMethod: selectedPM
                }
            };

            var checkId = 'sc_payment_method_' + selectedPM;

            // iterate over payment fields
            $('#' + checkId).closest('.sc_apms_fieldset').find('.sc_apm_fields input').each(function(){
                var apmField = $(this);

                if (
                    typeof apmField.attr('pattern') != 'undefined'
                    && apmField.attr('pattern') !== false
                    && apmField.attr('pattern') != ''
                ) {
                    var regex = new RegExp(apmField.attr('pattern'), "i");

                    // SHOW error
                    if(apmField.val() == '' || regex.test(apmField.val()) == false) {
                        apmField.parent('.apm_field').find('.apm_error') .removeClass('error_info hide');

                        formValid = false;
                    }
                    else {
                        apmField.parent('.apm_field').find('.apm_error') .addClass('hide');
                    }
                }
                else if(apmField.val() == '') {
                    formValid = false;
                }
            });

            if(!formValid) {
                scFormFalse();
                return;
            }

            // direct APMs can use the SDK
            if($('#sc_payment_method_' + selectedPM).attr('data-nuvei-is-direct') == 'true') {
                console.log('payment sdk upo');
                
                // create payment with WebSDK
                sfc.createPayment(nuveiPaymentParams, function(resp){
                    nuveiAfterSdkResponse(resp);
                });

                return;
            }

            $('form#safechargesubmit').submit();
            return;
        }
	}
	
    function nuveiAfterSdkResponse(resp) {
        console.log(resp);

        if(resp.hasOwnProperty('result')) {
            if(resp.result == 'APPROVED' && resp.hasOwnProperty('transactionId')) {
                $('#sc_transaction_id').val(resp.transactionId);
                $('form#safechargesubmit').submit();

                return;
            }
            
            if(resp.result == 'DECLINED') {
                scFormFalse("<?= $data['nuvei_order_declined']; ?>");
                return;
            }
            
            scOpenNewOrder();

            if(resp.hasOwnProperty('errorDescription') && resp.errorDescription !== '') {
                scFormFalse(resp.errorDescription);
                $('#nuvei_blocker').hide();
                return;
            }
            
            if(resp.hasOwnProperty('reason') && '' !== resp.reason) {
                scFormFalse(resp.reason);
                $('#nuvei_blocker').hide();
                return;
            }
            
            scFormFalse("<?= $data['nuvei_order_error']; ?>");
            $('#nuvei_blocker').hide();
            return;
        }
        
        scOpenNewOrder();

        alertDiv.find('span').html("<?= $data['nuvei_order_error']; ?>");
        alertDiv.removeClass('hide');

        console.error('Error with SDK response: ' + resp);

        $('#nuvei_blocker').hide();
    }
    
	function scOpenNewOrder(doNotCreateFields = false) {
        console.log('scOpenNewOrder(), doNotCreateFields:', doNotCreateFields);
    
		$.ajax({
			url: 'index.php?route=<?= $data['ctr_path']; ?>',
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
		if (typeof _errorMsg == 'undefined') {
			_errorMsg = "<?= $data['error_fill_all_fields']; ?>";
		}
		
		alertDiv.find('span').html(_errorMsg);
		alertDiv.removeClass('hide');

		document.getElementById('sc_payment_method_' + selectedPM).scrollIntoView();
		
        $('#nuvei_blocker').hide();
	}

	$(function() {
		//createSCFields();

		// when click on APM payment method
		$('body').on('change', 'input[name="payment_method_sc"]', function() {
			selectedPM	= $('input[name="payment_method_sc"]:checked').val();
            
            createSCFields();
            
            if(isNaN(selectedPM)) {
                $('#nuvei_save_upo_cont').show();
            }
            else {
                $('#nuvei_save_upo_cont').hide();
            }
		});

		// when change Payment Country and not click on Continue button show warning!
		$('#collapse-payment-address').on('change', '#input-payment-country', function(){
			$('#reload_apms_warning').removeClass('hide');
		});

		$('input[name="payment_method_sc"]').on('change', function() {
			$('.sc_apm_fields').addClass('collapse');
			$('input[name="payment_method_sc"]:checked').closest('.sc_apms_fieldset').find('.sc_apm_fields').removeClass('collapse');
		});
        
        $('body').on('change', '#nuvei_save_upo', function() {
            var _self = $(this);
            _self.val(_self.is(':checked') ? 1 : 0);
        });
	});
	// document ready function END
</script>
