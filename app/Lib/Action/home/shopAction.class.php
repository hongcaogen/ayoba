<?php
class shopAction extends frontendAction {
	public function index(){
		//店铺分类
		$cate_list = D('shop_cate')->where(array('pid'=>0))->select();
		foreach($cate_list as $ckey=>$cval){
			$cate_list[$ckey]['chrid'] = D('shop_cate')->where(array('pid'=>$cval['id']))->select();
		}
		$this->assign('cate_list',$cate_list);
		$cate_id = $this->_get('cate_id',intval);
		if(isset($cate_id)){
        	$count = M('shop')->where(array('cate_id'=>$cate_id,'status'=>1))->count();
            $pager = new Page($count, 10);
	        $shop_list = M('shop')->where(array('cate_id'=>$cate_id,'status'=>1))->order("add_time desc")->limit($pager->firstRow.','.$pager->listRows)->select();
			foreach($shop_list as $skey=>$sval){
				
				$shop_list[$skey]['item'] = M('item')->where(array('shop_id'=>$sval['id']))->limit("0,3")->select();
				//统计每个店铺被分享数
				$shop_list[$skey]['share_count'] = M('item')->where(array('shop_id'=>$sval['id']))->count();
			}
	        $this->assign('shop_list', $shop_list);
	        $page = $pager->show();
            $this->assign("page", $page);
	        $this->assign('cate_id', $cate_id);
	        $cate_name = M('shop_cate')->find($cate_id);
	        $cate_name['pid'] = M('shop_cate')->where(array('id'=>$cate_name['pid']))->find();
	        $this->assign('cate_name',$cate_name);
        }else{
        	//店铺列表
			$count = D('shop')->where(array('status'=>1))->count('id');
			$pager = $this->_pager($count, 4);
			$shop_list = D('shop')->where(array('status'=>1))->order('ordid desc')->limit($pager->firstRow . ',' . $pager->listRows)->select();
			
			foreach($shop_list as $skey=>$sval){
				
				$shop_list[$skey]['item'] = M('item')->where(array('shop_id'=>$sval['id']))->limit("0,3")->select();
				//统计每个店铺商品被分享数
				$shop_list[$skey]['share_count'] = M('item')->where(array('shop_id'=>$sval['id']))->count();
			}
			$page = $pager->fshow();
			$this->assign('page',$page);
			$this->assign('shop_list',$shop_list);
        }
		$this->display();
	}
	
	public function lists(){
		$cate_id = $this->_get('cate_id','intval');
        
        if(!empty($cate_id)){
        	$count = M('shop')->where(array('cate_id'=>$cate_id,'status'=>1))->count();
            $pager = new Page($count, 10);
	        $shop_list = M('shop')->where(array('cate_id'=>$cate_id,'status'=>1))->order("add_time desc")->limit($pager->firstRow.','.$pager->listRows)->select();
	        $this->assign('shop_list', $shop_list);
	        $page = $pager->show();
            $this->assign("page", $page);
	        $this->assign('cate_id', $cate_id);
	        $cate_name = M('shop_cate')->find($cate_id);
	        $cate_name['pid'] = M('shop_cate')->where(array('id'=>$cate_name['pid']))->find();
	        $this->assign('cate_name',$cate_name);
        }else{
        	$count = M('shop')->where(array('cate_id'=>3,'status'=>1))->count();
            $pager = new Page($count, 10);
        	$shop_list = M('shop')->where(array('cate_id'=>3,'status'=>1))->order("add_time desc")->limit($pager->firstRow.','.$pager->listRows)->select();
	        $this->assign('shop_list', $shop_list);
	        $page = $pager->show();
            $this->assign("page", $page);
	        $this->assign('cate_id', $cate_id);
        }
        $cate_list = M('shop_cate')->where(array('pid'=>0))->select();
        foreach($cate_list as $ckey=>$cval){
        	$cate_list[$ckey]['chrid']= M('shop_cate')->where(array('pid'=>$cval['id']))->select();
        }
        $this->assign('cate_list',$cate_list);
    	$this->_config_seo();
    	$this->display();
	}
	
	public function detail(){
		$id = $this->_get('id', 'intval');
   		!$id && $this->redirect('shop/index');
   		$info = D('shop')->find($id);	
   		$info['tag'] = unserialize($info['tag_cache']);
   		$this->assign('info',$info);
   		//var_dump($info);
   		//读取 大家推荐的商品
   		$count = M('item')->where(array('shop_id'=>$id,'status'=>1))->count();
        $pager = new Page($count, 10);
   		$shop_item = M('item')->where(array('shop_id'=>$id,'status'=>1))->limit($pager->firstRow.','.$pager->listRows)->select();
   		$this->assign('item_list',$shop_item);
   		$page = $pager->show();
        $this->assign("page", $page);
   		
		$this->display();
	}
	
	public function see(){
		
	}
	
}