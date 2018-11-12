<?php
global $_GPC, $_W;
$action = 'start';
$storeid=$_COOKIE["storeid"];
$uid=$_COOKIE["uid"];
$cur_store = $this->getStoreById($storeid);
$GLOBALS['frames'] = $this->getNaveMenu($storeid, $action,$uid);
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
			message('添加成功',$this->createWebUrl2('dlprintlabel',array()),'success');
		}else{
			message('添加失败','','error');
		}
	}else{
		$res = pdo_update('pintuan_dytag', $data, array('id' => $_GPC['id']));
		if($res){
			message('编辑成功',$this->createWebUrl2('dlprintlabel',array()),'success');
		}else{
			message('编辑失败','','error');
		}
	}
}
include $this->template('web/dladdprintlabel');