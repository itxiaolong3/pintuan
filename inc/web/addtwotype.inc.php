<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$getonetype=pdo_getall('pintuan_typeonemy',array('uniacid'=>$_W['uniacid']));
$info = pdo_get('pintuan_typetwomy',array('ID'=>$_GPC['id']));
if(checksubmit('submit')){
    if($info['Cover']!=$_GPC['Cover']){
        $data['Cover']=$_W['attachurl'].$_GPC['Cover'];
    }
    $data['Name']=$_GPC['Name'];
    $data['Pid']=$_GPC['Pid'];
    $data['uniacid']=$_W['uniacid'];
    if($_GPC['id']==''){
        $res=pdo_insert('pintuan_typetwomy',$data);
        if($res){
            message('添加成功！', $this->createWebUrl('twotype'), 'success');
        }else{
            message('添加失败！','','error');
        }
    }else{
        $res=pdo_update('pintuan_typetwomy',$data,array('ID'=>$_GPC['id']));
        if($res){
            message('编辑成功！', $this->createWebUrl('twotype'), 'success');
        }else{
            message('编辑失败！','','error');
        }
    }
}
include $this->template('web/addtwotype');