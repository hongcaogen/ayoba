<?php
class article_commentAction extends backendAction
{
    public function _initialize() {
        parent::_initialize();
    }

    public function _before_index() {
        $p = $this->_get('p','intval',1);
        $this->assign('p',$p);
    }

    protected function _search() {
        $map = array();
        ($time_start = $this->_request('time_start', 'trim')) && $map['add_time'][] = array('egt', strtotime($time_start));
        ($time_end = $this->_request('time_end', 'trim')) && $map['add_time'][] = array('elt', strtotime($time_end)+(24*60*60-1));
        ($status = $this->_request('status', 'trim')) && $map['status'] = $status;
        ($keyword = $this->_request('keyword', 'trim')) && $map['info'] = array('like', '%'.$keyword.'%');
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
}