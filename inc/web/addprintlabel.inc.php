<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$item = pdo_get('pintuan_dytag',array('id'=>$_GPC['id']));
if(checksubmit('submit')){				
	$data['store_id']=$storeid;
	$data['tag_name']=$_GPC['tag_name'];
	$data['sort']=$_GPC['sort'];
	$data['time']=time();
	$data['uniacid']=$_W['uniacid'];
	if($_GPC['id']==''){
		$res=pdo_insert('pintuan_dytag',$data);
		if($res){
			message('添加成功',$this->createWebUrl('printlabel',array()),'success');
		}else{
			message('添加失败','','error');
		}
	}else{
		$res = pdo_update('pintuan_dytag', $data, array('id' => $_GPC['id']));
		if($res){
			message('编辑成功',$this->createWebUrl('printlabel',array()),'success');
		}else{
			message('编辑失败','','error');
		}
	}
}
include $this->template('web/addprintlabel');