<?php

if (empty($json['error_nuvei'])) {
    $json['redirect'] = str_replace('&amp;', '&', $this->url->link(
        'product/product', 'product_id=' . $this->request->post['product_id']));
}