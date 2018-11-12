<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$info = pdo_get('pintuan_searchtag',array('ID'=>$_GPC['id']));
if(checksubmit('submit')){
    $data['Name']=$_GPC['Name'];
    if($_GPC['id']==''){
        $res=pdo_insert('pintuan_searchtag',$data);
        if($res){
            message('添加成功！', $this->createWebUrl('seachtag'), 'success');
        }else{
            message('添加失败！','','error');
        }
    }else{
        $res=pdo_update('pintuan_searchtag',$data,array('ID'=>$_GPC['id']));
        if($res){
            message('编辑成功！', $this->createWebUrl('seachtag'), 'success');
        }else{
            message('编辑失败！','','error');
        }
    }
}
include $this->template('web/addseachtag');