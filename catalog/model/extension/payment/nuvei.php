<?php

require_once DIR_SYSTEM . 'library' . DIRECTORY_SEPARATOR . 'nuvei' 
    . DIRECTORY_SEPARATOR . 'NUVEI_CLASS.php';

class ModelExtensionPaymentNuvei extends Model
{
	public function getMethod($address, $total)
    {
        $settigs_prefix = NUVEI_SETTINGS_PREFIX;
		$this->language->load(NUVEI_CONTROLLER_PATH);
		
		$query = $this->db->query(
            "SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone "
            ."WHERE geo_zone_id = '". (int)$this->config->get($settigs_prefix . 'geo_zone_id') . "' "
                ."AND country_id = '" . (int)$address['country_id'] . "' "
                ."AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')"
        );
		
		if (
            $this->config->get($settigs_prefix . 'total') > 0
            && $this->config->get($settigs_prefix . 'total') > $total
        ) {
			$status = false;
		}
        elseif (!$this->config->get($settigs_prefix . 'geo_zone_id')) {
			$status = true;
		}
        elseif ($query->num_rows) {
			$status = true;
		}
        else {
			$status = false;
		}
		
		$method_data = array();
	
		if ($status) {  
      		$method_data = array( 
        		'code'       => NUVEI_PLUGIN_CODE,
        		'title'      => NUVEI_PLUGIN_TITLE,
				'terms'      => '',
				'sort_order' => $this->config->get($settigs_prefix . 'sort_order')
      		);
    	}
   
    	return $method_data;
  	}
}
