<?php
class shopAction extends backendAction
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('shop');
    }

    protected function _search() {
        $map = array();
        ($keyword = $this->_request('keyword', 'trim')) && $map['name'] = array('like', '%'.$keyword.'%');
        $cate_id = $this->_request('cate_id', 'intval');
        $selected_ids = '';
        if ($cate_id) {
            $id_arr = D('shop_cate')->get_child_ids($cate_id, true);
            $map['cate_id'] = array('IN', $id_arr);
            $spid = D('shop_cate')->where(array('id'=>$cate_id))->getField('spid');
            $selected_ids = $spid ? $spid . $cate_id : $cate_id;
        }
        $this->assign('search', array(
            'time_start' => $time_start,
            'time_end' => $time_end,
            'cate_id' => $cate_id,
            'selected_ids' => $selected_ids,
            'status'  => $status,
            'keyword' => $keyword,
        ));
        return $map;
    }

    public function _before_index() {
//        $big_menu = array(
//            'title' => L('add_shop'),
//            'iframe' => U('shop/add'),
//            'id' => 'add',
//            'width' => '500',
//            'height' => '260'
//        );
//        $this->assign('big_menu', $big_menu);
        //$this->list_relation = true;
        $this->_before_add();

        $this->assign('img_dir',$this->_get_imgdir());

        //默认排序
        $this->sort = 'ordid';
        $this->order = 'ASC';
    }

    public function _before_add() {
        $res = D('shop_cate')->field('id,name')->select();
        $cate_list = array();
        foreach ($res as $val) {
            $cate_list[$val['id']] = $val['name'];
        }
        $this->assign('cate_list', $cate_list);
    }
    
    public  function _before_insert($data){
    	$data['add_time'] = time();
    	$data['tag_cache'] = serialize(explode(' ',$_POST['tag']));
    	//var_dump($data);exit;
    	
    	//上传图片
        if (!empty($_FILES['img']['name'])) {
            $art_add_time = date('ym/d/');
            $result = $this->_upload($_FILES['img'], 'shop/' . $art_add_time, array('width'=>'130', 'height'=>'100', 'remove_origin'=>true));
            if ($result['error']) {
                $this->error($result['info']);
            } else {
                $ext = array_pop(explode('.', $result['info'][0]['savename']));
                $data['img'] = $art_add_time .'/'. str_replace('.' . $ext, '_thumb.' . $ext, $result['info'][0]['savename']);
            }
        }
    	return $data;
    }
    
    public function _before_update($data){
    	$data['tag_cache'] = serialize(explode(' ',$_POST['tag']));
    	if (!empty($_FILES['img']['name'])) {
            $art_add_time = date('ym/d/');
            //删除原图
            $old_img = $this->_mod->where(array('id'=>$data['id']))->getField('img');
            $old_img = $this->_get_imgdir() . $old_img;
            is_file($old_img) && @unlink($old_img);
            //上传新图
            $result = $this->_upload($_FILES['img'], 'shop/' . $art_add_time, array('width'=>'130', 'height'=>'100', 'remove_origin'=>true));
            if ($result['error']) {
                $this->error($result['info']);
            } else {
                $ext = array_pop(explode('.', $result['info'][0]['savename']));
                $data['img'] = $art_add_time .'/'. str_replace('.' . $ext, '_thumb.' . $ext, $result['info'][0]['savename']);
            }
        } else {
            unset($data['img']);
        }
    	return $data;
    }

    public function _before_edit()
    {
        $this->_before_add();
        $this->assign('img_dir',$this->_get_imgdir());
        $id = $this->_get('id','intval');
        $shop = $this->_mod->field('id,cate_id,tag_cache')->where(array('id'=>$id))->find();
        $spid = D('shop_cate')->where(array('id'=>$shop['cate_id']))->getField('spid');
        if( $spid==0 ){
            $spid = $shop['cate_id'];
        }else{
            $spid .= $shop['cate_id'];
        }
        $this->assign('selected_ids',$spid);
        $info['tag'] = implode(' ',unserialize($shop['tag_cache']));
        $this->assign('infos',$info);
    }

    public function ajax_upload_img() {
        //上传图片
        if (!empty($_FILES['img']['name'])) {
            $result = $this->_upload($_FILES['img'], 'shop');
            if ($result['error']) {
                $this->ajaxReturn(0, $result['info']);
            } else {
                $data['img'] = $result['info'][0]['savename'];
                $this->ajaxReturn(1, L('operation_success'), $data['img']);
            }
        } else {
            $this->ajaxReturn(0, L('illegal_parameters'));
        }
    }

    public function ajax_check_name()
    {
        $name = $this->_get('name', 'trim');
        $id = $this->_get('id', 'intval');
        if ($this->_mod->name_exists($name, $id)) {
            $this->ajaxReturn(0, '好店名称已经存在');
        } else {
            $this->ajaxReturn();
        }
    }

    /**
     * 好店图片上传目录
     *
     * @staticvar null $dir
     * @return string
     */
    private function _get_imgdir() {
        static $dir = null;
        if ($dir === null) {
            $dir = './data/upload/shop/';
        }
        return $dir;
    }
}