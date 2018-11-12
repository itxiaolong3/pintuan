<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$area=pdo_getall('pintuan_areatype',array('uniacid'=>$_W['uniacid']),array(),'','Cid asc');
$warehome=pdo_getall('pintuan_warehome',array('uniacid'=>$_W['uniacid']),array(),'','fid asc');
$store=pdo_getall('pintuan_lanmu',array('uniacid'=>$_W['uniacid']));
$info=pdo_get('pintuan_lanmu',array('QsID'=>$_GPC['id']));
if(checksubmit('submit')){

	$data['tid']=$_GPC['tid'];
  $data['fid']=$_GPC['fid'];
	$data['number']=$_GPC['number'];
	$data['Name']=$_GPC['Name'];
	$data['Desindex']=$_GPC['Desindex'];
	$data['Description']=$_GPC['Description'];
	$data['HasNewItems']=$_GPC['HasNewItems'];
	$data['Content']=$_GPC['Content'];
	$data['ShopUserName']=$_GPC['ShopUserName'];
    $data['uniacid']=$_W['uniacid'];
	//封面图片
	if($info['AppCover']!=$_GPC['AppCover'] and $_GPC['AppCover']){
		$data['AppCover']=$_W['attachurl'].$_GPC['AppCover'];
	}else{
		$data['AppCover']=$_GPC['AppCover'];
	}
	//栏目logo
    if($info['ShopUserID']!=$_GPC['ShopUserID'] and $_GPC['ShopUserID']){
        $data['ShopUserID']=$_W['attachurl'].$_GPC['ShopUserID'];
    }else{
        $data['ShopUserID']=$_GPC['ShopUserID'];
    }
    //封面视频
	if($info['Video']!=$_GPC['Video'] and $_GPC['Video']){
        $data['Video']=$_W['attachurl'].$_GPC['Video'];
	}else{
        $data['Video']=$_GPC['Video'];
	}

	if(!$_GPC['Name']){
		message('请填写栏目名称!','','error');
	}
	if(!$_GPC['ShopUserID']){
		message('请选择栏目LOGO!','','error');
	}
    if(!$_GPC['AppCover']){
        message('请选择栏目封面图片','','error');
    }
	if($_GPC['id']==''){
		$res=pdo_insert('pintuan_lanmu',$data);
		$storeid=pdo_insertid();
		if($res){
			message('添加成功！', $this->createWebUrl('store'), 'success');
		}else{
			message('添加失败！','','error');
		}
	}else{
		$res=pdo_update('pintuan_lanmu',$data,array('QsID'=>$_GPC['id']));
		if($res){
			message('编辑成功！', $this->createWebUrl('store'), 'success');
		}else{
			message('编辑失败！','','error');
		}
	}
}
include $this->template('web/addstore');