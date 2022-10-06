// be sure those consts are same as in php NUVEI_CLASS
const nuveiControllerPath   = 'extension/payment/nuvei';
const nuveiTokenName        = 'user_token';
var nuveiVars               = {};

// for the URL use NUVEI_CONTROLLER_PATH and NUVEI_TOKEN_NAME
nuveiVars.ajaxUrl = 'index.php?route=' + nuveiControllerPath + '&' + nuveiTokenName + '=';

function scOrderActions(confirmQusetion, action, orderId) {
	console.log(action);

	if('refund' == action) {
		var refAm	= $('#refund_amount').val().replace(',', '.');
		var reg		= new RegExp(/^\d+(\.\d{1,2})?$/); // match integers and decimals

		if(action == 'refund' || action == 'refundManual') { 
			if(!reg.test(refAm) || isNaN(refAm) || refAm <= 0) {
				alert(nuveiVars.refundAmountError);
				return;
			}
		}
	}

	if(confirm(confirmQusetion + ' #' + orderId + '?')) {
		var spinnerId = (action == 'void' ? 'void' : 'refund') + '_spinner';
		$('#' + spinnerId).removeClass('hide');
		
		// disable sc custom buttons
		$('.sc_order_btns').each(function(){
			$(this).attr('disabled', true);
		});
		
		console.log('before ajax');
		
		$.ajax({
			url: nuveiVars.ajaxUrl,
			type: 'post',
			dataType: 'json',
			data: {
				orderId: orderId
				,action: action
				,amount: $('#refund_amount').val()
			}
		})
		.done(function(resp) {
			console.log('done', resp)
	
			if(resp.hasOwnProperty('status')) {
				if(resp.status == 1) {
					window.location.href = window.location.toString().replace('/info', '');
					return;
				}
				
				if(resp.status == 0) {
					if(resp.hasOwnProperty('msg')) {
						alert(resp.msg);
					}
					else {
						alert(nuveiVars.nuveiUnexpectedError);
					}
					

					$('#' + spinnerId).addClass('hide');
					
					// enable sc custom buttons
					$('.sc_order_btns').each(function(){
						$(this).attr('disabled', false);
					});
					
					return;
				}
			}
			else {
				alert(nuveiVars.nuveiUnexpectedError);
			}
		})
		.fail(function(resp) {
			console.error('ajax response error:', resp);
		});
	}
}

function deleteManualRefund(id, amount, orderId) {
	if(confirm(nuveiVars.nuveiOrderConfirmDelRefund)) {
		$('#sc_refund_' + id).find('.fa-circle-o-notch').removeClass('hide');
		$('#sc_refund_' + id).find('button').addClass('hide');

		$.ajax({
			url: nuveiVars.ajaxUrl,
			type: 'post',
			dataType: 'json',
			data: {
				refId: id
                ,orderId: orderId
				,action: 'deleteManualRefund'
				,amount: amount
			}
		})
		.done(function(resp) {
            if(resp.hasOwnProperty('status') && 1 == resp.status) {
				$('#sc_refund_' + id).remove();

				$('#nuveiRemainigTotal').text(nuveiVars.currSymbolLeft
                    + (nuveiVars.orderTotal + amount).toFixed(2) + nuveiVars.currSymbolRight);
			}
			else {
				$('#sc_refund_' + id).find('.fa-circle-o-notch').addClass('hide');
				$('#sc_refund_' + id).find('button').removeClass('hide');

				alert(nuveiVars.nuveiUnexpectedError);
			}
		});
	}
}

function loadNuveiExtras() {
	// 1.set the changes in Options table
	var scPlaceOne          = $('#content .container-fluid .row .col-md-4:nth-child(3)').find('table tbody');
    var scRefundBtnsHtml    = '';
    var scVoidSettleButton  = '';

	if(scPlaceOne.length > 0) {
		// 1.1.place Refund and Void buttons
		if(1 == nuveiVars.nuveiAllowRefundBtn) {
			scRefundBtnsHtml = 
                '<div class="input-group" style="margin-bottom: 2px; margin-top: 2px;">'
                    + '<input type="text" class="form-control" id="refund_amount" value="" style="max-height: 22px;">'
                    + '<span class="input-group-btn">'
                        + '<button class="btn btn-danger sc_order_btns btn-xs" type="button" onclick="scOrderActions(\''+ nuveiVars.nuveiOrderConfirmRefund +'\', \'refund\', '+ nuveiVars.nuveiOrderId +')">'+ nuveiVars.nuveiBtnRefund +'</button>'
                    + '</span>';
            
            // add Void button
            if(1 == nuveiVars.nuveiAllowVoidBtn) {
                scRefundBtnsHtml +=
                    '<span class="input-group-btn" style="left: 2px;">'
                        + '<button class="btn btn-danger btn-xs sc_order_btns" style="margin-left: 2px;" onclick="scOrderActions(\''+ nuveiVars.nuveiOrderConfirmCancel +'\', \'void\', '+ nuveiVars.nuveiOrderId +')">'+ nuveiVars.nuveiBtnVoid +'</button>'
                    + '</span>';
            }
            
            scRefundBtnsHtml +=
                '</div>';
		}
		// 1.2.set Void button only
		else if(1 == nuveiVars.nuveiAllowVoidBtn) {
			scVoidSettleButton += '<button class="btn btn-danger btn-xs sc_order_btns" style="margin-left: 2px;" onclick="scOrderActions(\''+ nuveiVars.nuveiOrderConfirmCancel +'\', \'void\', '+ nuveiVars.nuveiOrderId +')">'+ nuveiVars.nuveiBtnVoid +'</button>';
		}

		// 1.3.set Settle btn
		if('1' == nuveiVars.nuveiAllowSettleBtn) {
			scVoidSettleButton += '<button class="btn btn-success btn-xs sc_order_btns" style="margin-left: 2px;" onclick="scOrderActions(\''+ nuveiVars.nuveiOrderConfirmSettle +'\', \'settle\', '+ nuveiVars.nuveiOrderId +')">'+ nuveiVars.nuveiBtnSettle +'</button>';
		}

		// 1.4.place buttons
		scPlaceOne.append(
			'<tr>'
				+ '<td>'
					+ '<span>'+ nuveiVars.nuveiMoreActions +'&nbsp;&nbsp;<i id="void_spinner" class="fa fa-circle-o-notch fa-spin hide"></i></span>'
				+ '</td>'
				+ '<td colspan="2" class="text-right col-xs-6">'
                    + scRefundBtnsHtml
                    + ('' != scVoidSettleButton ? '<div class="btn-group" role="group">' + scVoidSettleButton + '</div>' : '')
				+ '</td>'
			+ '</tr>'
		);
	}
	// set the changes in Options table END

	// 2.add SC Refunds
	var scPlaceTwo = $('#content .container-fluid').children('div').eq(2).find('table:nth-child(2) tbody');

	if(scPlaceTwo.length > 0) {
		if(nuveiVars.nuveiRefunds != 'undefined' && nuveiVars.nuveiRefunds.length > 0) {
			// 2.1 collect Refunds
			var scRefundsRows = '';

			for(var i in nuveiVars.nuveiRefunds) {
				scRefundsRows += 
					'<tr id="sc_refund_'+ nuveiVars.nuveiRefunds[i].clientUniqueId +'">'
						+ '<td class="text-left">'
							+ nuveiVars.nuveiRefunds[i].clientUniqueId;

				if('' == nuveiVars.nuveiRefunds[i].transactionId) {
					scRefundsRows +=
							'<button type="button" class="btn btn-danger btn-xs pull-right" onclick="deleteManualRefund(\''+ nuveiVars.nuveiRefunds[i].clientUniqueId +'\', '+ nuveiVars.nuveiRefunds[i].totalAmount +', ' + nuveiVars.nuveiOrderId + ')"><i class="fa fa-trash"></i></button>'
							+ '<i class="fa fa-circle-o-notch fa-spin hide pull-right"></i>'
				}
				
				scRefundsRows +=
						'</td>'
						+ '<td class="text-left">'+ nuveiVars.nuveiRefunds[i].transactionId +'</td>'
						+ '<td class="text-left">'+ nuveiVars.nuveiRefunds[i].responseTimeStamp +'</td>'
						+ '<td class="text-right" colspan="2">'+ nuveiVars.nuveiRefunds[i].amount_curr +'</td>'
					+ '</tr>';
			}
			// 2.1 collect Refunds END

			// 2.2.place Refunds
			scPlaceTwo.append(
				'<tr>'
					+ '<td class="text-left"><strong>'+ nuveiVars.nuveiRefundId +'</strong></td>'
					+ '<td class="text-left"><strong>Transaction ID</strong></td>'
					+ '<td class="text-left"><strong>'+ nuveiVars.nuveiDate +'</strong></td>'
					+ '<td class="text-right" colspan="2"><strong>Refund Amount</strong></td>'
				+ '</tr>'

				+ scRefundsRows

				+ '<tr>'
					+ '<td class="text-right" colspan="4"><strong>'+ nuveiVars.nuveiRemainingTotal +'</strong></td>'
					+ '<td class="text-right"><strong id="nuveiRemainigTotal">'+ nuveiVars.nuveiRemainingTotalCurr +'</strong></td>'
				+ '</tr>'
			);
		}
	}
	// 2.add SC Refunds END
}

$(function(){
    console.log('nuvei_orders loaded');
    
    // get user_token
    var nuveiGetParams = window.location.toString().split('&');
    
    for(var i in nuveiGetParams) {
        // get user token
        if(nuveiGetParams[i].search(nuveiTokenName) == 0) {
            nuveiVars.ajaxUrl += nuveiGetParams[i].replace(nuveiTokenName + '=', '');
        }
        // get order id
        if(nuveiGetParams[i].search('order_id') == 0) {
            nuveiVars.nuveiOrderId = nuveiGetParams[i].replace('order_id=', '');
        }
    }
    
	$.ajax({
        url: nuveiVars.ajaxUrl,
        type: 'post',
        dataType: 'json',
        data: {
            action: 'getNuveiVars',
            orderId: nuveiVars.nuveiOrderId
        }
    })
    .done(function(resp) {
        console.log('ajax call response', resp)

        // set nuvei variables
        if(typeof resp != 'undefined') {
            if(!resp.hasOwnProperty('isNuveiOrder') || 0 == resp.isNuveiOrder) {
                console.log('This is not a Nuvei order');
                return;
            }
            
            nuveiVars.refundAmountError             = resp.nuveiRefundAmountError;
            nuveiVars.nuveiUnexpectedError          = resp.nuveiUnexpectedError;
            nuveiVars.nuveiOrderConfirmDelRefund    = resp.nuveiOrderConfirmDelRefund;
            nuveiVars.nuveiCreateRefund             = resp.nuveiCreateRefund;
            nuveiVars.nuveiOrderConfirmRefund       = resp.nuveiOrderConfirmRefund;
            nuveiVars.nuveiBtnManualRefund          = resp.nuveiBtnManualRefund;
            nuveiVars.nuveiBtnRefund                = resp.nuveiBtnRefund;
            nuveiVars.nuveiBtnVoid                  = resp.nuveiBtnVoid;
            nuveiVars.nuveiOrderConfirmCancel       = resp.nuveiOrderConfirmCancel;
            nuveiVars.nuveiBtnSettle                = resp.nuveiBtnSettle;
            nuveiVars.nuveiOrderConfirmSettle       = resp.nuveiOrderConfirmSettle;
            nuveiVars.nuveiMoreActions              = resp.nuveiMoreActions;
            nuveiVars.nuveiAllowRefundBtn           = resp.nuveiAllowRefundBtn;
            nuveiVars.nuveiAllowVoidBtn             = resp.nuveiAllowVoidBtn;
            nuveiVars.nuveiAllowSettleBtn           = resp.nuveiAllowSettleBtn;
            nuveiVars.nuveiRefunds                  = JSON.parse(resp.nuveiRefunds);
            nuveiVars.nuveiRefundId                 = resp.nuveiRefundId;
            nuveiVars.nuveiDate                     = resp.nuveiDate;
            nuveiVars.nuveiRemainingTotal           = resp.nuveiRemainingTotal;
            nuveiVars.nuveiRemainingTotalCurr       = resp.remainingTotalCurr;
            nuveiVars.orderTotal                    = resp.orderTotal;
            nuveiVars.currSymbolRight               = resp.currSymbolRight;
            nuveiVars.currSymbolLeft                = resp.currSymbolLeft;

            loadNuveiExtras();
            return;
        }

        alert('Unexpected error');
        return;
    })
    .fail(function(resp) {
        console.error('Nuvei Ajax response error:', resp);

        alert('Unexpected error');
        return;
    });
});