<?php
global $_GPC, $_W;
$action = 'start';
//$GLOBALS['frames'] = $this->getMainMenu2();
$uid=$_COOKIE["uid"];
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$GLOBALS['frames'] = $this->getNaveMenu($storeid, $action,$uid);
$list = pdo_getall('pintuan_type',array('uniacid' => $_W['uniacid'],'store_id'=>$storeid), array() , '' , 'order_by ASC');
if($_GPC['op']=='del'){
	$rst=pdo_getall('pintuan_goods',array('type_id'=>$_GPC['id']));
		if(!$rst){
		$result = pdo_delete('pintuan_type', array('id'=>$_GPC['id']));
		if($result){
			message('删除成功',$this->createWebUrl2('dldishestype',array()),'success');
		}else{
		message('删除失败','','error');
		}
	}else{
		message('该分类下有菜品无法删除','','error');
	}
}
if($_GPC['op']=='upd'){
	$res=pdo_update('pintuan_type',array('is_open'=>$_GPC['state']),array('id'=>$_GPC['id']));
	if($res){
			message('修改成功',$this->createWebUrl2('dldishestype',array()),'success');
		}else{
		message('修改失败','','error');
		}
}

include $this->template('web/dldishestype');