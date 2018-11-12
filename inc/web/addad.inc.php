<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$info=pdo_get('pintuan_banner',array('id'=>$_GPC['id']));
$areainfo=pdo_getall('pintuan_areatype',array('uniacid'=>$_W['uniacid']));
$lanmuinfo=pdo_getall('pintuan_lanmu',array('uniacid'=>$_W['uniacid']),array('QsID', 'Name'));

if(checksubmit('submit')){
    if($info['ImageUrl']!=$_GPC['logo']){
    $data['ImageUrl']=$_W['attachurl'].$_GPC['logo'];
  }
        $data['aid']=$_GPC['aid'];
        $data['Content']=$_GPC['lmid'];
        $data['uniacid']=$_W['uniacid'];
  		$data['comment']=$_GPC['comment'];
     if($_GPC['id']==''){  
        $res=pdo_insert('pintuan_banner',$data);
        if($res){
             message('添加成功！', $this->createWebUrl('ad'), 'success');
        }else{
             message('添加失败！','','error');
        }
    }else{
        $res=pdo_update('pintuan_banner',$data,array('id'=>$_GPC['id']));
        if($res){
             message('编辑成功！', $this->createWebUrl('ad'), 'success');
        }else{
             message('编辑失败！','','error');
        }
    }
}
include $this->template('web/addad');