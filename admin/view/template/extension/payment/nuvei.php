<style>
    form#form .nav-tabs { margin-bottom: -1px; }
</style>

<?= $data['header'] . $data['column_left']; ?>

<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <a onclick="$('#nuvei_save_settings').trigger('click');" class="btn btn-primary" style="cursor:pointer">
                    <i class="fa fa-save"></i>
                </a>
                
                <a class="btn btn-default" title="" data-toggle="tooltip" href="<?= @$data['cancel']; ?>" data-original-title="Cancel">
                    <i class="fa fa-reply"></i>
                </a>
            </div>
            
            <h1><?= $this->language->get('heading_title'); ?></h1>
            
            <ul class="breadcrumb">
                <?php foreach ($data['breadcrumbs'] as $breadcrumb): ?>
                    <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <div class="container-fluid">
        <?php if (@$data['error_warning']): ?>
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?= $data['error_warning']; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-pencil"></i> <?= $this->language->get('text_edit'); ?></h3>
        </div>
        
        <div class="panel-body">
            <form action="<?= $data['action']; ?>" method="post" enctype="multipart/form-data" id="form" class="form-horizontal">
                <div class="form-wrapper panel-default with-nav-tabs panel-default">
                    <!-- tab buttons -->
                    <div class="panel-heading" style="padding-bottom: 0px;">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="#nuvei_general" data-toggle="tab"><?= $this->language->get('text_general_tab'); ?></a>
                            </li>

                            <li><a href="#nuvei_advanced" data-toggle="tab"><?= $this->language->get('text_advanced_tab'); ?></a></li>
                            <li><a href="#nuvei_tools" data-toggle="tab"><?= $this->language->get('text_tools_tab'); ?></a></li>
                        </ul>
                    </div>

                    <div class="panel-body">
                        <div class="tab-content">
                            <!-- General settings -->
                            <div class="tab-pane fade in active" id="nuvei_general">
                                <!-- Sandbox mode -->
                                <div class="form-group required">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_test_mode'); ?></label>
                                    <div class="col-sm-10">
                                        <select class="form-control" name="<?= NUVEI_SETTINGS_PREFIX; ?>test_mode" required="">
                                            <option value=""><?= $this->language->get('text_select_option'); ?></option>

                                            <option value="1" <?php if (@$data[NUVEI_SETTINGS_PREFIX . 'test_mode'] == '1'): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_yes'); ?></option>

                                            <option value="0" <?php if (@$data[NUVEI_SETTINGS_PREFIX . 'test_mode'] == '0'): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_no'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Merchant ID -->
                                <div class="form-group required">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_merchantId'); ?></label>
                                    <div class="col-sm-10">
                                        <input type="text" name="<?= NUVEI_SETTINGS_PREFIX; ?>merchantId" value="<?= @$data[NUVEI_SETTINGS_PREFIX . 'merchantId']; ?>" class="form-control" pattern="[0-9]+" required="" />
                                    </div>
                                </div>

                                <!-- Merchant Site ID -->
                                <div class="form-group required">
                                    <label class="col-sm-2 control-label" for="input-order-status"><?= $this->language->get('entry_merchantSiteId'); ?></label>

                                    <div class="col-sm-10">
                                        <input class="form-control" type="text" name="<?= NUVEI_SETTINGS_PREFIX; ?>merchantSiteId" value="<?= @$data[NUVEI_SETTINGS_PREFIX . 'merchantSiteId']; ?>" pattern="[0-9]+" required="" />
                                    </div>
                                </div>

                                <!-- Merchant Secret Key -->
                                <div class="form-group required">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_secret'); ?></label>
                                    <div class="col-sm-10">
                                        <input class="form-control" type="password" name="<?= NUVEI_SETTINGS_PREFIX; ?>secret" value="<?= @$data[NUVEI_SETTINGS_PREFIX . 'secret']; ?>" required="" autocomplete="false" />
                                    </div>
                                </div>

                                <!-- Merchant Hash type -->
                                <div class="form-group required">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_hash'); ?></label>
                                    <div class="col-sm-10">
                                        <select class="form-control" name="<?= NUVEI_SETTINGS_PREFIX; ?>hash" required="">
                                            <option value=""><?= $this->language->get('text_select_option'); ?></option>

                                            <option value="sha256" <?php if (@$data[NUVEI_SETTINGS_PREFIX . 'hash'] == "sha256"): ?>selected="selected"<?php endif; ?>>sha256</option>

                                            <option value="md5" <?php if (@$data[NUVEI_SETTINGS_PREFIX . 'hash'] == "md5"): ?>selected="selected"<?php endif; ?>>md5</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Payment Action -->
                                <div class="form-group required">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_payment_action'); ?></label>
                                    <div class="col-sm-10">
                                        <select class="form-control" name="<?= NUVEI_SETTINGS_PREFIX; ?>payment_action" required="">
                                            <option value=""><?= $this->language->get('text_select_option'); ?></option>

                                            <option value="Sale" <?php if (@strtolower($data[NUVEI_SETTINGS_PREFIX . 'payment_action']) == "sale"): ?> selected="selected"<?php endif; ?>><?= $this->language->get('text_sale_flow'); ?></option>

                                            <option value="Auth" <?php if (@strtolower($data[NUVEI_SETTINGS_PREFIX . 'payment_action']) == "auth"): ?> selected="selected"<?php endif; ?>><?= $this->language->get('text_auth_flow'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Save Logs -->
                                <div class="form-group required">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_create_logs'); ?></label>
                                    <div class="col-sm-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>create_logs" class="form-control" required="">
                                            <option value="" <?php if (@$data[NUVEI_SETTINGS_PREFIX . 'create_logs'] == ""): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_select_option'); ?></option>

                                            <option value="single" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'create_logs'] == "single"): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_single_file'); ?></option>

                                            <option value="daily" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'create_logs'] == "daily"): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_daily_files'); ?></option>

                                            <option value="both" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'create_logs'] == "both"): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_both_files'); ?></option>

                                            <option value="no" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'create_logs'] == "no"): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_no'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Geo Zone -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_geo_zone'); ?></label>
                                    <div class="col-sm-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>geo_zone_id" class="form-control">
                                            <?php foreach($data['geo_zones'] as $geo_zone): ?>
                                                <option value="<?= $geo_zone['geo_zone_id']; ?>" <?php if($geo_zone['geo_zone_id'] == @$data[NUVEI_SETTINGS_PREFIX . 'geo_zone_id']): ?>selected="selected"<?php endif; ?>>
                                                    <?= $geo_zone['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Plugin Status -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_status'); ?></label>

                                    <div class="col-sm-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>status" class="form-control">
                                            <option value="1" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'status'] == 1): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_enabled'); ?></option>
                                            <option value="0" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'status'] == 0): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_disabled'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Sort Order -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_sort_order'); ?></label>

                                    <div class="col-sm-10">
                                        <input type="text" name="<?= NUVEI_SETTINGS_PREFIX; ?>sort_order" value="<?= @$data[NUVEI_SETTINGS_PREFIX . 'sort_order']; ?>" class="form-control" size="3" />
                                    </div>
                                </div>
                            </div>
                            <!-- /general settings -->

                            <!-- Advanced settings -->
                            <div class="tab-pane fade" id="nuvei_advanced">
                                <!-- Minimum Total -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_total'); ?></label>
                                    <div class="col-sm-10">
                                        <input type="text" name="<?= NUVEI_SETTINGS_PREFIX; ?>total" value="<?= @$data[NUVEI_SETTINGS_PREFIX . 'total']; ?>" class="form-control"/>
                                    </div>
                                </div>

                                <!-- Complete Status -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_order_status'); ?></label>
                                    <div class="col-sm-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>order_status_id" class="form-control">
                                            <?php foreach($data['order_statuses'] as $order_status): ?>
                                                <option value="<?= $order_status['order_status_id'] ?>" <?php if($order_status['order_status_id'] == @$data[NUVEI_SETTINGS_PREFIX . 'order_status_id']): ?>selected="selected"<?php endif; ?>>
                                                    <?= $order_status['name'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Pending Status -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_pending_status'); ?></label>
                                    <div class="col-sm-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>pending_status_id" class="form-control">
                                            <?php foreach($data['order_statuses'] as $order_status): ?>
                                                <option value="<?= $order_status['order_status_id']; ?>" <?php if($order_status['order_status_id'] == @$data[NUVEI_SETTINGS_PREFIX . 'pending_status_id']):?>selected="selected"<?php endif; ?>><?= $order_status['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Canceled Status -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_canceled_status'); ?></label>
                                    <div class="col-sm-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>canceled_status_id" class="form-control">
                                            <?php foreach($data['order_statuses'] as $order_status): ?>
                                                <option value="<?= $order_status['order_status_id']; ?>" <?php if($order_status['order_status_id'] == @$data[NUVEI_SETTINGS_PREFIX . 'canceled_status_id']):?>selected="selected"<?php endif; ?>><?= $order_status['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Failed Status -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_failed_status'); ?></label>
                                    <div class="col-sm-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>failed_status_id" class="form-control">
                                            <?php foreach($data['order_statuses'] as $order_status): ?>
                                                <option value="<?= $order_status['order_status_id']; ?>" <?php if($order_status['order_status_id'] == @$data[NUVEI_SETTINGS_PREFIX . 'failed_status_id']):?>selected="selected"<?php endif; ?>><?= $order_status['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Refunded Status -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_refunded_status'); ?></label>
                                    <div class="col-sm-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>refunded_status_id" class="form-control">
                                            <?php foreach($data['order_statuses'] as $order_status): ?>
                                                <option value="<?= $order_status['order_status_id']; ?>" <?php if($order_status['order_status_id'] == @$data[NUVEI_SETTINGS_PREFIX . 'refunded_status_id']):?>selected="selected"<?php endif; ?>><?= $order_status['name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Preselect Nuvei provider -->
<!--                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_preselect_nuvei'); ?></label>
                                    <div class="col-lg-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>preselect_nuvei" class="form-control">
                                            <option value="0" <?php if (@$data[NUVEI_SETTINGS_PREFIX . 'preselect_nuvei'] == "0"): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_no'); ?></option>

                                            <option value="1" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'preselect_nuvei'] == "1"): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_yes'); ?></option>
                                        </select>
                                    </div>
                                </div>-->

                                <!-- SDK version -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_sdk_version'); ?></label>
                                    <div class="col-lg-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>sdk_version" class="form-control">
                                            <option value="prod" <?php if (@$data[NUVEI_SETTINGS_PREFIX . 'sdk_version'] == "prod"): ?>selected="selected"<?php endif; ?>><?= $this->language->get('entry_prod'); ?></option>
                                            
                                            <option value="dev" <?php if (@$data[NUVEI_SETTINGS_PREFIX . 'sdk_version'] == "dev"): ?>selected="selected"<?php endif; ?>><?= $this->language->get('entry_dev'); ?></option>
                                        </select>

                                        <span class="help-block"><?= $this->language->get('entry_sdk_version_help'); ?></span>
                                    </div>
                                </div>

                                <!-- DCC -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_dcc'); ?></label>
                                    <div class="col-lg-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>use_dcc" class="form-control">
                                            <option value="false" <?php if (@$data[NUVEI_SETTINGS_PREFIX . 'use_dcc'] == "false"): ?>selected="selected"<?php endif; ?>><?= $this->language->get('entry_dcc_disable'); ?></option>
                                            
                                            <option value="enable"  <?php if (@$data[NUVEI_SETTINGS_PREFIX . 'use_dcc'] == "enable"): ?>selected="selected"<?php endif; ?>><?= $this->language->get('entry_enable'); ?></option>
                                            
                                            <option value="force" <?php if (@$data[NUVEI_SETTINGS_PREFIX . 'use_dcc'] == "force"): ?>selected="selected"<?php endif; ?>><?= $this->language->get('entry_dcc_force'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Block cards -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_block_cards'); ?></label>
                                    <div class="col-lg-10">
                                        <input type="text" name="<?= NUVEI_SETTINGS_PREFIX; ?>block_cards" value="<?= @$data[NUVEI_SETTINGS_PREFIX . 'block_cards']; ?>" class="form-control" />
                                        
                                        <span class="help-block"><?= $this->language->get('text_block_cards_help'); ?></span>
                                    </div>
                                </div>

                                <!-- Block PMs -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_block_pms'); ?></label>
                                    <div class="col-lg-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>block_pms[]" class="form-control" multiple="">
                                            <option value=""><?= $this->language->get('entry_block_none'); ?></option>
                                            
                                            <?php if(is_array($data['nuvei_pms']) && !empty($data['nuvei_pms'])):
                                                foreach($data['nuvei_pms'] as $pm => $pm_data): ?>
                                                    <option value="<?= $pm; ?>" <?php if(1 == $pm_data['selected']): ?>selected<?php endif; ?>><?= $pm_data['name']; ?></option>
                                                <?php endforeach;
                                            endif; ?>
                                        </select>
                                        
                                        <span class="help-block"><?= $this->language->get('text_block_pms_help'); ?></span>
                                    </div>
                                </div>

                                <!-- UPOs -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_upos'); ?></label>
                                    <div class="col-lg-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>use_upos" class="form-control">
                                            <option value=""><?= $this->language->get('text_select_option'); ?></option>
                                            
                                            <option value="1" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'use_upos'] == 1): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_use_upos'); ?></option>
                                            
                                            <option value="0" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'use_upos'] == 0): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_dont_use_upos'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Pay button text -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_pay_button'); ?></label>
                                    <div class="col-lg-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>pay_btn_text" class="form-control">
                                            <option value="amountButton" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'pay_btn_text'] == 'amountButton'): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_btn_amount'); ?></option>
                                            
                                            <option value="textButton" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'pay_btn_text'] == 'textButton'): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_btn_pm'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Auto expand PMs -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_auto_expand_pms'); ?></label>
                                    <div class="col-lg-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>auto_expand_pms" class="form-control">
                                            <option value="1" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'auto_expand_pms'] == '1'): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_yes'); ?></option>
                                            
                                            <option value="0" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'auto_expand_pms'] == '0'): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_no'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Auto close APMs popup, only for Sandbox mode -->
                                <?php if(1 == @$data[NUVEI_SETTINGS_PREFIX . 'test_mode']): ?>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label"><?= $this->language->get('entry_auto_close_apm_popup'); ?></label>
                                        <div class="col-lg-10">
                                            <select name="<?= NUVEI_SETTINGS_PREFIX; ?>auto_close_apm_popup" class="form-control">
                                                <option value="1" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'auto_close_apm_popup'] == '1'): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_yes'); ?></option>
                                            
                                                <option value="0" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'auto_close_apm_popup'] == '0'): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_no'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Checkout SDK log level -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_sdk_log_level'); ?></label>
                                    <div class="col-lg-10">
                                        <select name="<?= NUVEI_SETTINGS_PREFIX; ?>sdk_log_level" class="form-control">
                                            <?php foreach(range(0,6) as $log_level): ?>
                                                <option value="<?= $log_level ?>" <?php if(@$data[NUVEI_SETTINGS_PREFIX . 'sdk_log_level'] == $log_level): ?>selected="selected"<?php endif; ?>><?= $log_level ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                        <span class="help-block"><?= $this->language->get('text_log_level_help'); ?></span>
                                    </div>
                                </div>

                                <!-- SDK transaltions -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_sdk_transl'); ?></label>
                                    <div class="col-lg-10">
                                        <textarea name="<?= NUVEI_SETTINGS_PREFIX; ?>sdk_transl" rows="5" class="form-control textarea-autosize" placeholder='{
    "de": { 
        "doNotHonor": "you dont have enough money",
        "DECLINE": "declined"
    },
    "es": { 
        "doNotHonor": "you dont have enough money",
        "DECLINE": "declined"
    }
}'></textarea>

                                        <span class="help-block"><?= $this->language->get('text_sdk_transl_help'); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Rebilling Plan ID -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_rebilling_plan_id'); ?></label>
                                    <div class="col-sm-10">
                                        <input type="text" name="<?= NUVEI_SETTINGS_PREFIX; ?>plan_id" value="<?= @$data[NUVEI_SETTINGS_PREFIX . 'plan_id']; ?>" class="form-control" pattern="[0-9]+" required="" />

                                        <span class="help-block"><?= $this->language->get('text_plan_id_help'); ?></span>
                                    </div>
                                </div>
                            </div>
                            <!-- /advanced settings -->

                            <!-- tools -->
                            <div class="tab-pane fade" id="nuvei_tools">
                                <!-- DMN URL -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_dmn_url'); ?></label>
                                    <div class="col-lg-10">
                                        <span class="help-block"><?= $data['nuvei_dmn_url']; ?></span>
                                    </div>
                                </div>
                                
                                <!-- Change Order status on Update Order -->
                                <div class="form-group">
                                    <label class="col-sm-2 control-label"><?= $this->language->get('entry_change_order_status'); ?></label>
                                    <div class="col-sm-10">
                                        <select class="form-control" name="<?= NUVEI_SETTINGS_PREFIX; ?>change_order_status">
                                            <option value="0" <?php if ((int) @$data[NUVEI_SETTINGS_PREFIX . 'change_order_status'] == 0): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_no'); ?></option>
                                            
                                            <option value="1" <?php if ((int) @$data[NUVEI_SETTINGS_PREFIX . 'change_order_status'] == 1): ?>selected="selected"<?php endif; ?>><?= $this->language->get('text_yes'); ?></option>
                                        </select>
                                        
                                        <div class="col-lg-10">
                                            <span class="help-block"><?= $this->language->get('text_change_order_status'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- tools -->
                        </div>
                    </div>
                </div><!-- /.form-wrapper -->
                
                <button id="nuvei_save_settings" type="submit" style="display: none;">Save</button>
            </form>
        </div>
    </div>
</div>

<?= $data['footer']; ?>