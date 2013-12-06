<?php
class darenModel extends RelationModel
{
    protected $_link = array(
        'daren_cate' => array(
            'mapping_type' => BELONGS_TO,
            'class_name' => 'daren_cate',
            'foreign_key' => 'cate_id',
            'mapping_fields'=> 'name',
        ),
        'user' => array(
            'mapping_type' => BELONGS_TO,
            'class_name' => 'user',
            'foreign_key' => 'uid',
            'mapping_fields'=> 'username',
        )
        
        
    );

    /**
     * 检测达人是否存在
     *
     * @param string $name
     * @param int $pid
     * @return bool
     */
    public function name_exists($name, $id=0)
    {
        $pk = $this->getPk();
        $where = "uid='" . $name . "'  AND ". $pk ."<>'" . $id . "'";
        $result = $this->where($where)->count($pk);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}