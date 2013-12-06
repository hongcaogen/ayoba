<?php
class newsAction extends frontendAction {
	public function index() {
		$articleModule = M('article');
        //特别推荐  点击数最高的一篇资讯  orderid 从小到大取6条
        $hotInfo = $articleModule->order("hits desc")->find();
        //var_dump($hotInfo);
        $hotInfo['info'] = mb_substr(strip_tags($hotInfo['info']),0,150,'utf-8');
        $this->assign('hotInfo',$hotInfo);
        $hotList = $articleModule->order("ordid asc")->limit('0,6')->select();
        $this->assign('hotList',$hotList);
		//普通资讯列表 按照添加时间倒叙取两列
		$list = $articleModule->order("add_time asc")->limit('0,12')->select();
        $this->assign('list',$list);
		//资讯分类
        $cate_list = M('article_cate')->where(array('type'=>0,'pid'=>0))->select();
        foreach($cate_list as $ckey=>$cval){
        	$cate_list[$ckey]['chrid']= M('article_cate')->where(array('type'=>0,'pid'=>$cval['id']))->select();
        }
		foreach($cate_list as $ckey=>$cval){
        	foreach($cval['chrid'] as $cckey=>$ccval){
        		$cate_list[$ckey]['chrid'][$cckey]['alist'] = $articleModule->where(array('cate_id'=>$ccval['id']))->limit("0,6")->select();
        	}
        }
        $this->assign('cate_list',$cate_list);
    	$this->_config_seo(C('pin_seo_config.article'));
    	$this->display();
    }
    
    public function lists(){
    	
        $cate_id = $this->_get('cate_id','intval');
        
        if(!empty($cate_id)){
        	$count = M('article')->where(array('cate_id'=>$cate_id,'status'=>1))->count();
            $pager = new Page($count, 10);
	        $article_list = M('article')->where(array('cate_id'=>$cate_id,'status'=>1))->order("add_time desc")->limit($pager->firstRow.','.$pager->listRows)->select();
	        $this->assign('article_list', $article_list);
	        $page = $pager->show();
            $this->assign("page", $page);
	        $this->assign('cate_id', $cate_id);
	        $cate_name = M('article_cate')->find($cate_id);
	        $cate_name['pid'] = M('article_cate')->where(array('id'=>$cate_name['pid']))->find();
	        $this->assign('cate_name',$cate_name);
        }else{
        	$count = M('article')->where(array('cate_id'=>9,'status'=>1))->count();
            $pager = new Page($count, 10);
        	$article_list = M('article')->where(array('cate_id'=>9,'status'=>1))->order("add_time desc")->limit($pager->firstRow.','.$pager->listRows)->select();
	        $this->assign('article_list', $article_list);
	        $page = $pager->show();
            $this->assign("page", $page);
	        $this->assign('cate_id', $cate_id);
        }
        $cate_list = M('article_cate')->where(array('type'=>0,'pid'=>0))->select();
        foreach($cate_list as $ckey=>$cval){
        	$cate_list[$ckey]['chrid']= M('article_cate')->where(array('type'=>0,'pid'=>$cval['id']))->select();
        }
        $this->assign('cate_list',$cate_list);
    	$this->_config_seo(C('pin_seo_config.article_cate'), array(
                'cate_name' => $cate_name,
                'seo_title' => $cate_name['seo_title'],
                'seo_keywords' => $cate_name['seo_keys'],
                'seo_description' => $cate_name['seo_desc'],
            ));
    	$this->display();
    }
    
    public function detail(){
    	$id = $this->_get('id', 'intval');
   		!$id && $this->redirect('news/index');
        if(!empty($id)){
        $info = M('article')->field("pin_article.*,pin_article_cate.id as cate_id,pin_article_cate.name")->join('pin_article_cate on pin_article.cate_id = pin_article_cate.id')->where(array('pin_article.id'=>$id))->find();
        $this->assign('info', $info);
        $data = array('hits'=>$info['hits']+1);
        $update = M('article')->where(array('id'=>$id))->save($data);
        $this->assign('id', $id);
        }
        $cate_list = M('article_cate')->where(array('type'=>0,'pid'=>0))->select();
    	foreach($cate_list as $ckey=>$cval){
        	$cate_list[$ckey]['chrid']= M('article_cate')->where(array('type'=>0,'pid'=>$cval['id']))->select();
        }
        $this->assign('cate_list',$cate_list);
        $cate_name = M('article_cate')->find($info['cate_id']);
	    $cate_name['pid'] = M('article_cate')->where(array('id'=>$cate_name['pid']))->find();
	    $this->assign('cate_name',$cate_name);
	    
	    //第一页评论不使用AJAX利于SEO
        $article_comment_mod = M('article_comment');
        $pagesize = 3;
        $map = array('article_id' => $id);
        $count = $article_comment_mod->where($map)->count('id');
        $pager = $this->_pager($count, $pagesize);
        $pager->path = 'comment_list';
        $pager_bar = $pager->fshow();
        //var_dump($pager_bar);
        $cmt_list = $article_comment_mod->where($map)->order('id DESC')->limit($pager->firstRow . ',' . $pager->listRows)->select();
	    $this->assign('cmt_list', $cmt_list);
	    $this->assign('page_bar', $pager_bar);
	    //var_dump($info);
    	$this->_config_seo(C('pin_seo_config.article_detail'), array(
            'article_title' => $info['title'],
            'article_intro' => $info['intro'],
            'article_cate' => $cate_name,
        ));
    	$this->display();
    }
    
	/**
     * AJAX获取评论列表
     */
    public function comment_list() {
        $id = $this->_get('id', 'intval');
        !$id && $this->ajaxReturn(0, L('无此文章'));
        $article_mod = M('article');
        $article = $article_mod->where(array('id' => $id, 'status' => '1'))->count('id');
        !$article && $this->ajaxReturn(0, L('无此文章'));
        $article_comment_mod = M('article_comment');
        $pagesize = 3;
        $map = array('article_id' => $id);
        $count = $article_comment_mod->where($map)->count('id');
        $pager = $this->_pager($count, $pagesize);
        $pager->path = 'comment_list';
        $cmt_list = $article_comment_mod->where($map)->order('id DESC')->limit($pager->firstRow . ',' . $pager->listRows)->select();
        $this->assign('cmt_list', $cmt_list);
        $data = array();
        $data['list'] = $this->fetch('comment_list');
        $data['page'] = $pager->fshow();
        $this->ajaxReturn(1, '', $data);
    }

    /**
     * 评论一篇文章
     */
    public function comment() {
        foreach ($_POST as $key=>$val) {
            $_POST[$key] = Input::deleteHtmlTags($val);
        }
        $data = array();
        $data['article_id'] = $this->_post('id', 'intval');
        !$data['article_id'] && $this->ajaxReturn(0, L('invalid_item'));
        $data['info'] = $this->_post('content', 'trim');
        !$data['info'] && $this->ajaxReturn(0, L('please_input') . L('comment_content'));
        //敏感词处理
        $check_result = D('badword')->check($data['info']);
        switch ($check_result['code']) {
            case 1: //禁用。直接返回
                $this->ajaxReturn(0, L('has_badword'));
                break;
            case 3: //需要审核
                $data['status'] = 0;
                break;
        }
        $data['info'] = $check_result['content'];
        $data['uid'] = $this->visitor->info['id'];
        $data['uname'] = $this->visitor->info['username'];
        $data['add_time'] = time();

        //验证文章
        $article_mod = M('article');
        $article = $article_mod->where(array('id' => $data['article_id'], 'status' => '1'))->find();
        !$article && $this->ajaxReturn(0, L('此文章不存在'));
        //写入评论
        $article_comment_mod = D('article_comment');
        if (false === $article_comment_mod->create($data)) {
            $this->ajaxReturn(0, $article_comment_mod->getError());
        }
        $comment_id = $article_comment_mod->add();
        if ($comment_id) {
            $this->assign('cmt_list', array(
                array(
                    'uid' => $data['uid'],
                    'uname' => $data['uname'],
                    'info' => $data['info'],
                    'add_time' => time(),
                )
            ));
            $resp = $this->fetch('comment_list');
            //评论次数加1
        	$a_data = array('comments'=>$article['comments']+1);
        	$update = M('article')->where(array('id'=>$data['article_id']))->save($a_data);
            
            $this->ajaxReturn(1, L('comment_success'), $resp);
        } else {
            $this->ajaxReturn(0, L('comment_failed'));
        }
    }
    
}