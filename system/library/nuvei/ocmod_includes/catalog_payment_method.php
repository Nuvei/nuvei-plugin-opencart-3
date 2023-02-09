<?php

require_once DIR_SYSTEM . 'library' . DIRECTORY_SEPARATOR  . 'nuvei' . DIRECTORY_SEPARATOR . 'NUVEI_CLASS.php';

$rebilling_data     = $this->cart->getRecurringProducts();
$remove_providers   = false;
        
if (count($rebilling_data) > 0) {
    foreach ($rebilling_data as $reb) {
        $recurring_id = (int) $reb['recurring']['recurring_id'];
        
        $plan_names = $this->db->query(
            'SELECT name '
            . 'FROM ' . DB_PREFIX . 'recurring_description '
            . 'WHERE recurring_id = ' . $recurring_id
        );
        
        foreach ($plan_names->rows as $names) {
            if (!empty($names['name']) && strpos(strtolower($names['name']), 'nuvei') !== false) {
                $remove_providers = true;
                break;
            }
        }
    }
}

if ($remove_providers && array_key_exists('nuvei', $data['payment_methods'])) {
    $data['payment_methods'] = [
        'nuvei' => $data['payment_methods']['nuvei']
    ];
}
