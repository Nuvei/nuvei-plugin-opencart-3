<?php
// Heading
$_['heading_title']     = 'Nuvei Checkout';

// Text 					
$_['text_extension']    = 'Extensions';
$_['text_payment']      = 'Payment';
$_['text_success']		= 'Success: You have modified the Nuvei details.';
$_['text_edit']         = 'Edit Nuvei Checkout extension';
$_['text_nuvei']        = '<a href="http://nuvei.com/" target="_blank"><img src="view/image/payment/nuvei.png" alt="Nuvei" title="Nuvei" /></a>';
	
// Settings tabs
$_['text_general_tab']  = 'General';
$_['text_advanced_tab'] = 'Advanced';
$_['text_tools_tab']    = 'Help Tools';

$_['text_select_option']    = 'Please, select an option...';
$_['text_yes']              = 'Yes';
$_['text_no']               = 'No';
$_['text_sale_flow']        = 'Authorize and Capture';
$_['text_auth_flow']        = 'Authorize';
$_['text_single_file']      = 'A Single file';
$_['text_daily_files']      = 'Daily files';
$_['text_both_files']       = 'Daily files and a Single file';
$_['text_use_upos']         = 'Use UPOs';
$_['text_dont_use_upos']    = 'Do NOT use UPOs';
$_['text_btn_amount']       = 'Shows the amount';
$_['text_btn_pm']           = 'Shows the payment method';
$_['text_log_level_help']   = '0 for "No logging"';
$_['text_last_download']    = 'Last download';
$_['text_no_active_subscr'] = 'There is no active Recurring for this Order.';
$_['text_accordion']        = 'Accordion';
$_['text_tiles']            = 'Tiles';
$_['text_horizontal']       = 'Horizontal';
$_['text_new_tab']          = 'New tab';
$_['text_redirect']         = 'Redirect';
$_['text_popup']            = 'Popup';
$_['text_apm_popup_help']   = 'Works only when APM window type is "New tab".';

$_['text_sdk_transl_help']  = 'This filed is the only way to translate Checkout SDK strings. Put the translations for all desired languages as shown in the placeholder. For examples <a href="https://docs.nuvei.com/documentation/accept-payment/simply-connect/ui-customization/#text-and-translation" target="_blank">check the Documentation</a>.';

$_['text_block_cards_help'] = 'For examples <a href="https://docs.nuvei.com/documentation/accept-payment/simply-connect/payment-customization/#Card_Processing" target="_blank"> check the Documentation.</a>';

$_['text_block_pms_help']   = 'For examples <a href="https://docs.nuvei.com/documentation/accept-payment/simply-connect/payment-customization/#apm-whitelisting-blacklisting" target="_blank"> check the Documentation.</a>';

$_['text_plan_id_help']     = 'For Rebilling you need at least one Rebilling Plan. Creat it, and get its number from the CPanel.';

$_['text_change_order_status']  = 'Change the Order status to Pending on successful UpdateOrder request. By default OpenCart keep not finished Orders hidden and we do not recommend using Yes option on Prod.';
$_['text_plugin_version']       = 'Plugin version is different from the Modification version.';

$_['text_no_github_plugin_version']     = 'Nuvei message - can not find the plugin version into github changelog file.';
$_['text_github_plugin_same_version']   = 'Git version is same as the current plugin version.';
$_['text_github_new_plugin_version']    = 'There is <a href="https://github.com/SafeChargeInternational/nuvei_checkout_opencart3/blob/main/CHANGELOG.md" target="_blank">newer version</a> for Nuvei Checkout plugin.';

// Entry					
$_['entry_merchantId']      = 'Merchant ID:';
$_['entry_merchantSiteId']  = 'Merchant Site ID:';
$_['entry_ppp_Payment_URL'] = 'Payment URL:';
$_['entry_secret']		    = 'Merchant Secret Key:';
$_['entry_hash']            = 'Merchant Hash type:';
$_['entry_payment_action']  = 'Payment Action:';

$_['entry_option_cashier']  = 'Hosted Payment Page';
$_['entry_option_rest']     = 'Nuvei API';

$_['entry_test_mode']		    = 'Sandbox mode:';
$_['entry_use_upos']		    = 'Enable UPOs:';
$_['entry_force_http']		    = 'Use http Notify URL:';
$_['entry_create_logs']		    = 'Save Logs:';

$_['entry_total']             	= '<span data-toggle="tooltip" title="" data-original-title="The checkout total the order must reach before this payment method becomes active.">Minimum Total:</span>';
$_['entry_order_status']     	= 'Order Status:';
$_['entry_pending_status']    	= 'Pending Status :';
$_['entry_canceled_status']   	= 'Canceled Status:';
$_['entry_failed_status']       = 'Failed Status:';
$_['entry_refunded_status']     = 'Refunded Status:';
$_['entry_geo_zone']            = 'Geo Zone:';
$_['entry_status']            	= 'Status:';
$_['entry_sort_order']       	= 'Sort Order:';
$_['entry_preselect_nuvei']     = 'Preselect Nuvei on checkout:';
//$_['entry_sdk_version']         = 'SDK version:';
$_['entry_prod']                = 'Prod';
$_['entry_dev']                 = 'Dev';
$_['entry_sdk_theme']           = 'SimplyConnect theme:';
$_['entry_dcc']                 = 'Use currency conversion:';
$_['entry_enable']              = 'Enable';
$_['entry_dcc_force']           = 'Enabled and expanded';
$_['entry_dcc_disable']         = 'Disable';
$_['entry_block_cards']         = 'Block Cards:';
$_['entry_block_none']          = 'None';
$_['entry_block_pms']           = 'Block Payment methods:';
$_['entry_upos']                = 'UPOs:';
$_['entry_pay_button']          = 'Choose the Text on the Pay button:';
$_['entry_auto_expand_pms']     = 'Auto expand PMs:';
$_['entry_sdk_log_level']       = 'Checkout Log level:';
$_['entry_sdk_transl']          = 'SDK translations:';
$_['entry_donwload_p_plans']    = 'Download Payment Plans:';
$_['entry_dmn_url']             = 'Notification (DMN) URL:';
$_['entry_change_order_status'] = 'Auto change the Order status:';
$_['entry_plugin_version']      = 'Plugin Version:';
$_['entry_rebilling_plan_id']   = 'Rebilling Plan ID:';
$_['entry_mask_user_details']   = 'Mask users details in the log:';

$_['entry_apm_window_type']         = 'APM window type:';
$_['entry_auto_close_apm_popup']    = 'Auto close APM popup:';
$_['nuvei_btn_remove_logs']         = 'Remove logs:';
$_['nuvei_btn_remove_help']         = 'Only the oldest logs will be removed. The logs for last 30 days will be kept.';
$_['nuvei_remove_log_confirm']      = 'Are you sure, you want to delete the logs?';
$_['entry_sdk_version_help']        = 'It is not recommended to use Dev version on Production sites.';

// Error					
$_['error_permission']	      		= 'Warning: You do not have permission to modify Nuvei!'; 
$_['error_missing_nuvei_data']	    = 'Missing Nuvei data for this Order'; 
$_['error_missing_subscr_data']	    = 'Missing Nuvei Subscription data for this Order'; 
$_['The request faild.']		    = 'The request faild.';
$_['Invalid request response.']     = 'Invalid request response.';

$_['Cancel requrest for Subscription ID ']      = 'Cancel requrest for Subscription ID ';
$_['failed.']                                   = 'failed.';
$_['was declined.']                             = 'was declined.';
$_['Cancel Rebiling requrest/s was/were sent.'] = 'Cancel Rebiling requrest/s was/were sent.';

$_['nuvei_unexpected_error']            = 'Unexpected error.';
$_['nuvei_order_confirm_cancel']        = 'Are you sure, you want to Cancel Order';
$_['nuvei_btn_void']                    = 'Void';
$_['nuvei_btn_cancel_subscr']           = 'Cancel Recurring';
$_['nuvei_order_confirm_settle']        = 'Are you sure, you want to Settle Order';
$_['nuvei_order_confirm_cancel_subscr'] = 'Are you sure, you want to Cancel this Subscription';
$_['nuvei_btn_settle']                  = 'Settle';
$_['nuvei_order_confirm_refund']        = 'Are you sure, you want to Refund Order';
$_['nuvei_order_confirm_del_refund']    = 'Are you sure you want to delete this Manual Refund?';
$_['nuvei_btn_refund']                  = 'Refund';
$_['nuvei_btn_manual_refund']           = 'Manual Refund';
$_['nuvei_create_refund']               = 'Create Refund';
$_['nuvei_more_actions']                = 'Nuvei Actions';
$_['nuvei_refund_amount_error']         = 'The refund amount must be a number bigger than 0!';
$_['nuvei_total_refund']                = 'Total Refund';
$_['nuvei_refund_id']                   = 'Refund ID';
$_['nuvei_date']                        = 'Date';
$_['nuvei_amount']                      = 'Amount';
$_['nuvei_remaining_total']             = 'Remaining Total';
