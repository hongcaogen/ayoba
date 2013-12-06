<?php
class local_imgAction extends backendAction
{
    public function index() {
        if (IS_POST) {
            $num = $this->_post('num', 'intval', 10);
            $status = $this->_post('status');
            $this->redirect('local_img/dojump', array('num'=>$num, 'status'=>$status));
        } else {
            $item_total = M('item')->count();
            $item_check_total = M('item')->where(array('status'=>1))->count();
            $item_local_total = M('item')->where(array('is_localimg'=>1))->count();
            $item_local_check_total = M('item')->where(array('is_localimg'=>1, 'status'=>1))->count();
            $this->assign('item_total', $item_total);
            $this->assign('item_check_total', $item_check_total);
            $this->assign('item_local_total', $item_local_total);
            $this->assign('item_local_check_total', $item_local_check_total);
            $this->display();
        }
    }
    
    public function dojump() {
        $num = $this->_get('num', 'intval', 10); //每批个数
        $status = $this->_get('status');
        $p = $this->_get('p', 'intval', 0); //批次
        $where = array('is_localimg'=>0);
        !empty($status) && $where['status'] = $status;
        $start = $p*$num;
        $item_list = M('item')->field('id,img')->where($where)->limit($start,$num)->select();
        $i = 0;
        foreach ($item_list as $val) {
            $local_img = $this->_save_img($val['img']);
            M('item')->where(array('id'=>$val['id']))->save(array('img'=>$local_img, 'is_localimg'=>1));
            //相册图片
            $item_img = M('item_img')->where(array('item_id'=>$val['id']))->select();
            foreach ($item_img as $img) {
                $_url = $this->_save_img($img['url']);
                M('item_img')->where(array('id'=>$img['id']))->save(array('url'=>$_url));
            }
            $i++;
        }
        if ($i < $num) {
            $this->assign('p', 0);
            $this->assign('jump_url', U('local_img/index'));
            $this->display();
        } else {
            $this->assign('p', $p);
            $this->assign('jump_url', U('local_img/dojump', array('num'=>$num, 'status'=>$status, 'p'=>$p+1)));
            $this->display();
        }
    }
    
    /**
     * 下载图片到本地 
     */
    private function _save_img($url) {
        $urlinfo = pathinfo($url);
        $img_dir = C('pin_attach_path') . 'item/';
        $date_dir = date('ym/d/'); //上传目录
        $save_path = $img_dir . $date_dir;
        if(!is_dir($save_path)) {
            // 尝试创建目录
            if(!mkdir($save_path, 0777, true)){
                exit('上传目录'.$save_path.'不存在');
            }
        }else {
            if(!is_writeable($save_path)) {
                exit('上传目录'.$save_path.'不可写');
            }
        }
        $file_name = uniqid().'.'.$urlinfo['extension'];
        $file_content = file_get_contents($url);
        file_put_contents($img_dir . $date_dir . $file_name, $file_content);
        //其他规格图片 _b _m _s
        $file_content_b = file_get_contents(get_thumb($url, '_b'));
        file_put_contents($img_dir . $date_dir . get_thumb($file_name, '_b'), $file_content_b);
        $file_content_m = file_get_contents(get_thumb($url, '_m'));
        file_put_contents($img_dir . $date_dir . get_thumb($file_name, '_m'), $file_content_m);
        $file_content_s = file_get_contents(get_thumb($url, '_s'));
        file_put_contents($img_dir . $date_dir . get_thumb($file_name, '_s'), $file_content_s);
        return $date_dir . $file_name;
    }
}