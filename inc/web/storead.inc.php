<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$list=pdo_getall('pintuan_storead',array('store_id'=>$storeid),array(),'','orderby ASC');
if($_GPC['op']=='delete'){
	$res=pdo_delete('pintuan_storead',array('id'=>$_GPC['id']));
	if($res){
		 message('删除成功！', $this->createWebUrl('storead'), 'success');
		}else{
			  message('删除失败！','','error');
		}
}
if($_GPC['status']){
	$data['status']=$_GPC['status'];
	$res=pdo_update('pintuan_storead',$data,array('id'=>$_GPC['id']));
	if($res){
		 message('编辑成功！', $this->createWebUrl('storead'), 'success');
		}else{
			  message('编辑失败！','','error');
		}
}
include $this->template('web/storead');