// be sure those consts are same as in php NUVEI_CLASS
const nuveiControllerPath   = 'extension/payment/nuvei';
const nuveiTokenName        = 'user_token';
var nuveiVars               = {};

// for the URL use NUVEI_CONTROLLER_PATH and NUVEI_TOKEN_NAME
nuveiVars.ajaxUrl = 'index.php?route=' + nuveiControllerPath + '&' + nuveiTokenName + '=';

function scOrderActions(confirmQusetion, action, orderId) {
	console.log(action);
    
    var reqData = {
        orderId: orderId
        ,action: action
    };

	if('refund' == action) {
		var refAm   = $('#refund_amount').val().replace(',', '.');
		var reg		= new RegExp(/^\d+(\.\d{1,2})?$/); // match integers and decimals

        if(!reg.test(refAm) || isNaN(refAm) || refAm <= 0) {
            alert(nuveiVars.nuveiRefundAmountError);
            return;
        }
        
        reqData.amount = refAm;
	}
    
    if ('cancelSubscr' != action) {
        confirmQusetion += ' #' + orderId;
    }
    
    confirmQusetion += '?';

	if(confirm(confirmQusetion)) {
		$('#nuvei_spinner').removeClass('hide');
		
		// disable sc custom buttons
		$('.sc_order_btns').each(function(){
			$(this).attr('disabled', true);
		});
		
		console.log('before ajax');
		
		$.ajax({
			url: nuveiVars.ajaxUrl,
			type: 'post',
			dataType: 'json',
			data: reqData
		})
		.done(function(resp) {
			console.log('done', resp);
	
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
					

					$('#nuvei_spinner').addClass('hide');
					
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
	var scPlaceOne      = $('#content .container-fluid .row .col-md-4:nth-child(3)').find('table tbody');
    var nuveiButtons    = '';

	if(scPlaceOne.length > 0) {
        nuveiButtons +=
            '<div class="input-group pull-right" style="">';
    
        if('1' == nuveiVars.nuveiAllowSettleBtn) {
			nuveiButtons += '<button class="btn btn-success btn-xs sc_order_btns" style="margin-bottom: 2px; margin-left: 2px;" onclick="scOrderActions(\''+ nuveiVars.nuveiOrderConfirmSettle +'\', \'settle\', '+ nuveiVars.nuveiOrderId +')">'+ nuveiVars.nuveiBtnSettle +'</button>';
		}
    
        if(1 == nuveiVars.nuveiAllowVoidBtn) {
            nuveiButtons += '<button class="btn btn-danger btn-xs sc_order_btns" style="margin-bottom: 2px; margin-left: 2px;" onclick="scOrderActions(\''+ nuveiVars.nuveiOrderConfirmCancel +'\', \'void\', '+ nuveiVars.nuveiOrderId +')">'+ nuveiVars.nuveiBtnVoid +'</button>';
        }
        
        if (1 == nuveiVars.nuveiAllowCancelSubsBtn) {
            nuveiButtons += '<button class="btn btn-danger btn-xs sc_order_btns" style="margin-bottom: 2px; margin-left: 2px;" onclick="scOrderActions(\''+ nuveiVars.orderConfirmCancelSubscr +'\', \'cancelSubscr\', '+ nuveiVars.nuveiOrderId +')">'+ nuveiVars.btnCancelSubscr +'</button>';
        }
        
        nuveiButtons +=
            '</div>';

        if(1 == nuveiVars.nuveiAllowRefundBtn) {
            nuveiButtons +=
                '<div class="input-group pull-right" style="display: inline-block; margin-bottom: 2px;">'
                    + '<input type="text" class="form-control" id="refund_amount" value="" style="max-height: 22px; max-width: 70px;">'
                    + '<span class="input-group-btn">'
                        + '<button class="btn btn-danger sc_order_btns btn-xs pull-right" type="button" onclick="scOrderActions(\''+ nuveiVars.nuveiOrderConfirmRefund +'\', \'refund\', '+ nuveiVars.nuveiOrderId +')">'+ nuveiVars.nuveiBtnRefund +'</button>'
                    + '</span>'
                    
                + '</div>';
        }
        
        scPlaceOne.append(
			'<tr>'
				+ '<td>'
					+ '<span>'+ nuveiVars.nuveiMoreActions +'&nbsp;&nbsp;<i id="nuvei_spinner" class="fa fa-circle-o-notch fa-spin hide"></i></span>'
				+ '</td>'
				+ '<td colspan="2" class="text-right col-xs-6">'
                    + '<div class="d-flex">' + nuveiButtons + '</div>'
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
					+ '<td class="text-right"><strong id="nuveiRemainigTotal">'+ nuveiVars.remainingTotalCurr +'</strong></td>'
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
            
            nuveiVars               = {...nuveiVars, ...resp};
            nuveiVars.nuveiRefunds  = JSON.parse(nuveiVars.nuveiRefunds);

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
