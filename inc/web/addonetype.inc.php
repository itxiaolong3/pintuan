<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$info = pdo_get('pintuan_typeonemy',array('ID'=>$_GPC['id']));
if(checksubmit('submit')){
    if($info['Cover']!=$_GPC['Cover']){
        $data['Cover']=$_W['attachurl'].$_GPC['Cover'];
    }
    if($info['Cover2']!=$_GPC['Cover2']){
        $data['Cover2']=$_W['attachurl'].$_GPC['Cover2'];
    }
    $data['Name']=$_GPC['Name'];
    $data['uniacid']=$_W['uniacid'];
    if($_GPC['id']==''){
        $res=pdo_insert('pintuan_typeonemy',$data);
        if($res){
            message('添加成功！', $this->createWebUrl('onetype'), 'success');
        }else{
            message('添加失败！','','error');
        }
    }else{
        $res=pdo_update('pintuan_typeonemy',$data,array('ID'=>$_GPC['id']));
        if($res){
            message('编辑成功！', $this->createWebUrl('onetype'), 'success');
        }else{
            message('编辑失败！','','error');
        }
    }
}
include $this->template('web/addonetype');