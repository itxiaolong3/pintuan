<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$info = pdo_get('pintuan_warehome',array('fid'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
if(checksubmit('submit')){
    $data['Name']=$_GPC['Name'];
    $data['emsprice']=$_GPC['emsprice'];
    $data['uniacid']=$_W['uniacid'];
    if($_GPC['id']==''){
        $res=pdo_insert('pintuan_warehome',$data);
        if($res){
            message('添加成功！', $this->createWebUrl('warehome'), 'success');
        }else{
            message('添加失败！','','error');
        }
    }else{
        $res=pdo_update('pintuan_warehome',$data,array('fid'=>$_GPC['id']));
        if($res){
            message('编辑成功！', $this->createWebUrl('warehome'), 'success');
        }else{
            message('编辑失败！','','error');
        }
    }
}
include $this->template('web/addwarehome');