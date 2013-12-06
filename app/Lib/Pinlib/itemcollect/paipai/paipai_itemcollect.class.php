<?php

/**
 * 拍拍商品获取
 *
 * @author andery
 */
class paipai_itemcollect {

    private $_code = 'paipai';

    public function fetch($url) {
        $id = $this->get_id($url);
        if (!$id) {
            return false;
        }
        $key = $this->_code . '_' . $id;
        $item_site = M('item_site')->where(array('code' => $this->_code))->find();
        $api_config = unserialize($item_site['config']);
        
        require_once 'PaiPaiOpenApiOauth.php';
        $pp_top = new PaiPaiOpenApiOauth($api_config['app_key'], $api_config['app_secret'], $api_config['access_token'], $api_config['uin']);
        $pp_top->setDebugOn(FALSE);
        $pp_top->setApiPath('/item/getItem.xhtml');
        $pp_top->setMethod("get");
        $pp_top->setCharset("utf-8");
        $params = &$pp_top->getParams();
        $params["itemCode"] = $id;
        $resp = $pp_top->invoke();
        $resp = simplexml_load_string($resp);
        $item = object_to_array($resp);

        $result = array();
        $result['item']['key_id'] = $key;
        $result['item']['title'] = strip_tags($item['itemName']);
        $result['item']['price'] = $item['itemPrice']/100;
        $result['item']['img'] = $item['picLink'];
        $result['item']['url'] = 'http://auction1.paipai.com/'.$item['itemCode'];
        
        //商品相册
        $result['item']['imgs'] = array(
            'url' => $item['picLink'],
            'ordid' => 0
        );
        for ($i=1; $i<10; $i++) {
            if (isset($item['picLink'.$i])) {
                $result['item']['imgs'][] = array(
                    'url' => $item['picLink'],
                    'ordid' => $i,
                );
            }
        }
        return $result;
    }

    public function get_id($url) {
        $id = 0;
        $parse = parse_url($url);
        if (isset($parse['path'])) {
            $parse = explode('/', $parse['path']);
            $parse = end($parse);
            $parse = explode('-', $parse);
            $id = current($parse);
        }
        return $id;
    }

    public function get_key($url) {
        $id = $this->get_id($url);
        return $this->_code.'_' . $id;
    }

}