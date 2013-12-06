<?php
class darenAction extends backendAction
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('daren');
    }

    protected function _search() {
        $map = array();
        ($cate_id = $this->_request('cate_id', 'trim')) && $map['cate_id'] = array('eq', $cate_id);
        ($keyword = $this->_request('keyword', 'trim')) && $map['uname'] = array('like', '%'.$keyword.'%');
        $this->assign('search', array(
            'keyword' => $keyword,
            'cate_id' => $cate_id,
        ));
        return $map;
    }

    public function _before_index() {
        $big_menu = array(
            'title' => L('添加达人'),
            'iframe' => U('daren/add'),
            'id' => 'add',
            'width' => '500',
            'height' => '260'
        );
        $this->assign('big_menu', $big_menu);
        $this->list_relation = true;
        $this->_before_add();

        $this->assign('img_dir',$this->_get_imgdir());

        //默认排序
        $this->sort = 'id';
        $this->order = 'DESC';
    }

    public function _before_add() {
        $cate_list = D('daren_cate')->where(array('status'=>1))->select();
        $this->assign('cate_list',$cate_list);
    }
    
    public function _before_insert($data){
    	$uid = $data['uname'];
    	$uname = M('user')->where(array('id'=>$data['uname']))->find();
    	$data['uid'] = $uid;
    	$data['uname'] = $uname['username']; 
    	$data['add_time'] = time();	
    	return $data;
    }

    public function _before_edit()
    {
        $this->_before_add();
        $this->assign('img_dir',$this->_get_imgdir());
    }

    public function ajax_upload_img() {
        //上传图片
        if (!empty($_FILES['img']['name'])) {
            $result = $this->_upload($_FILES['img'], 'daren');
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
        $name = $this->_get('uname', 'trim');
        $id = $this->_get('id', 'intval');
        if ($this->_mod->name_exists($name, $id)) {
            $this->ajaxReturn(0, '达人已经存在');
        } else {
            $this->ajaxReturn();
        }
    }
    
    /**
     * 用户表获取用户信息
     */
    public function get_user(){
    	$uname = $this->_get('uname','trim');
    	$map['username'] = array('like',"%".$uname."%");
    	$user = M('user')->where($map)->select();
    	$option = "<option>请选择会员</option>";
    	foreach($user as $ukey=>$uval){
    		$option .= "<option value=".$uval['id'].">".$uval['username']."</option>";
    	}
    	$this->ajaxReturn(1, '',$option);
    }

    /**
     * 达人图片上传目录
     *
     * @staticvar null $dir
     * @return string
     */
    private function _get_imgdir() {
        static $dir = null;
        if ($dir === null) {
            $dir = './data/upload/daren/';
        }
        return $dir;
    }
}