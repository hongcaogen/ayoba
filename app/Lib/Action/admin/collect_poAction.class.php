<?php

class collect_poAction extends backendAction {

    private $_tbconfig = null;

    public function _initialize() {
        parent::_initialize();
        $api_config = M('item_site')->where(array('code' => 'taobao'))->getField('config');
        $this->_tbconfig = unserialize($api_config);
    }

    /**
     * PinOpen
     */
    public function index() {
        //判断CURL
        if (!function_exists("curl_getinfo")) {
            $this->error(L('curl_not_open'));
        }
        //获取商品分类
        $item_cate = $this->_get_pocats();
        $this->assign('item_cate', $item_cate);
        $this->display();
    }
    
    public function search() {
        //搜索结果
        $po_item_list = array();
        if ($this->_get('search')) {
            $map['keyword'] = $this->_get('keyword', 'trim'); //关键词
            $map['cid'] = $this->_get('cid', 'intval'); //分类ID
            $p = $this->_get('p', 'intval', 1);
            if (!$map['keyword'] && !$map['cid']) {
                $this->error(L('select_cid_or_keyword'));
            }
            $map['start_price'] = $this->_get('start_price', 'trim'); //价格下限
            $map['end_price'] = $this->_get('end_price', 'trim'); //价格上限
            $map['like_init'] = $this->_get('like_init', 'trim');

            $result = $this->_get_list($map, $p);
            //分页
            $pager = new Page(count($result), 20);
            $page = $pager->show();
            $this->assign("page", $page);
            //列表内容
            $po_item_list = $result;
        }
        $po_item_list && F('po_item_list', $po_item_list);
        $this->assign('list', $po_item_list);
        $this->assign('list_table', true);
        $this->display();
    }

    /**
     * 直接入库准备
     */
    public function batch_publish() {
        if (IS_POST) {
            $cate_id = $this->_post('cate_id', 'intval');
            !$cate_id && $this->ajaxReturn(0, L('please_select') . L('publish_item_cate'));
            $auid = $this->_post('auid', 'intval');
            //必须指定用户
            !$auid && $this->ajaxReturn(0, L('please_select') . L('auto_user'));
            //商品状态
            $status = $this->_post('status', 'intval', 0);
            //采集页数
            $page_num = $this->_post('page_num', 'intval', 10);
            //获取马甲
            $auto_user_mod = M('auto_user');
            $user_mod = M('user');
            $unames = $auto_user_mod->where(array('id' => $auid))->getField('users');
            $unamea = explode(',', $unames);
            $users = $user_mod->field('id,username')->where(array('username' => array('in', $unamea)))->select();
            !$users && $this->ajaxReturn(0, L('auto_user_error'));
            //搜索条件
            $form_data = $this->_post('form_data', 'urldecode');
            parse_str($form_data, $form_data);
            //把采集信息写入缓存
            F('batch_publish_cache', array(
                'cate_id' => $cate_id,
                'status' => $status,
                'users' => $users,
                'page_num' => $page_num,
                'form_data' => $form_data,
            ));
            $this->ajaxReturn(1);
        } else {
            $auto_user = M('auto_user')->select(); //采集马甲
            $this->assign('auto_user', $auto_user);
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }

    /**
     * 开始入库
     */
    public function batch_publish_do() {
        if (false === $batch_publish_cache = F('batch_publish_cache')) {
            $this->ajaxReturn(0, L('illegal_parameters'));
        }
        $p = $this->_get('p', 'intval', 1);
        if ($p > $batch_publish_cache['page_num']) {
            $this->ajaxReturn(0, L('import_success'));
        }
        $result = $this->_get_list($batch_publish_cache['form_data'], $p);
        if ($result['item_list']) {
            foreach ($result['item_list'] as $val) {
                $val['status'] = $batch_publish_cache['status'];
                $this->_publish_insert($val, $batch_publish_cache['cate_id'], $batch_publish_cache['users']);
            }
            $this->ajaxReturn(1);
        } else {
            $this->ajaxReturn(0, L('import_success'));
        }
    }

    /**
     * 发布选择的商品
     */
    public function publish() {
        if (IS_POST) {
            $ids = $this->_post('ids', 'trim');
            $cate_id = $this->_post('cate_id', 'intval');
            !$cate_id && $this->ajaxReturn(0, L('please_select') . L('publish_item_cate'));
            $auid = $this->_post('auid', 'intval');
            //必须指定用户
            !$auid && $this->ajaxReturn(0, L('please_select') . L('auto_user'));
            //商品状态
            $status = $this->_post('status', 'intval', 0);
            //获取马甲
            $auto_user_mod = M('auto_user');
            $user_mod = M('user');
            $unames = $auto_user_mod->where(array('id' => $auid))->getField('users');
            $unamea = explode(',', $unames);
            $users = $user_mod->field('id,username')->where(array('username' => array('in', $unamea)))->select();
            !$users && $this->ajaxReturn(0, L('auto_user_error'));
            //从缓存中获取本页商品数据
            $ids_arr = explode(',', $ids);
            $taobaoke_item_list = F('taobaoke_item_list');
            foreach ($taobaoke_item_list as $key => $val) {
                if (in_array($key, $ids_arr)) {
                    $val['status'] = $status;
                    $this->_publish_insert($val, $cate_id, $users);
                }
            }
            $this->ajaxReturn(1, L('operation_success'), '', 'publish');
        } else {
            $ids = trim($this->_get('id'), ',');
            $this->assign('ids', $ids);
            //采集马甲
            $auto_user = M('auto_user')->select();
            $this->assign('auto_user', $auto_user);
            $response = $this->fetch();
            $this->ajaxReturn(1, '', $response);
        }
    }

    private function _publish_insert($item, $cate_id, $users) {
        //随机取一个用户
        $user_rand = array_rand($users);
        $item['title'] = strip_tags($item['title']);
        $item['click_url'] = Url::replace($item['click_url'], array('spm' => '2014.21069764.' . $this->_tbconfig['app_key'] . '.0'));
        $insert_item = array(
            'key_id' => 'taobao_' . $item['num_iid'],
            'taobao_sid' => $item['taobao_sid'],
            'cate_id' => $cate_id,
            'uid' => $users[$user_rand]['id'],
            'uname' => $users[$user_rand]['username'],
            'title' => $item['title'],
            'intro' => $item['title'],
            'img' => $item['pic_url'],
            'price' => $item['price'],
            'url' => $item['click_url'],
            'rates' => $item['commission_rate'] / 100,
            'likes' => $item['likes'],
            'imgs' => $item['imgs'],
            'props' => $item['props'],
            'status' => $item['status'],
        );
        //如果多图为空
        if (empty($item['imgs'])) {
            $insert_item['imgs'] = array(array(
                    'url' => $insert_item['img'],
                    ));
        }
        $result = D('item')->publish($insert_item);
        return $result;
    }

    /**
     * 获取商品列表
     */
    private function _get_list($map, $p) {
        $request_url = 'http://localhost/pinphp_open/index.php?m=items&a=get&';
	foreach ($map as $key => $val) {
	    $request_url .= "$key=" . urlencode($val) . "&";
	}
	$request_url = substr($request_url, 0, -1);
        $resp = file_get_contents($request_url);
        $item_list = json_decode($resp, true);
        foreach ($item_list as $val) {
            $val = (array) $val;
            //喜欢数
            switch ($map['like_init']) {
                case 'volume':
                    $val['likes'] = $val['volume'];
                    break;
                case '100':
                    $val['likes'] = rand(0,100);
                    break;
                case '500':
                    $val['likes'] = rand(0,500);
                    break;
                case '1000':
                    $val['likes'] = rand(0,1000);
                    break;
                case '5000':
                    $val['likes'] = rand(0,5000);
                    break;
                default:
                    $val['likes'] = 0;
                    break;
            }
            $item_list[$val['id']] = $val;
        }
        return $item_list;
    }

    private function _get_pocats($pid = 0) {
        $resp = file_get_contents('http://localhost/pinphp_open/index.php?m=itemcats&a=get&pid='.$pid);
        $item_cate = json_decode($resp, true);
        return $item_cate;
    }
    
    public function ajax_get_pocats() {
        $pid = $this->_get('pid', 'intval', 0);
        $item_cate = $this->_get_pocats($pid);
        if ($item_cate) {
            $this->ajaxReturn(1, '', $item_cate);
        } else {
            $this->ajaxReturn(0);
        }
    }

}