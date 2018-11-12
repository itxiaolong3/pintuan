<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu();
$where="WHERE a.uniacid=:uniacid";
$data[':uniacid']=$_W['uniacid'];
$sql="select a.*,b.QsID,b.Name as lmname,c.Name as areaname,c.Cid from " . tablename("pintuan_banner") . " a"
    . " left join " . tablename("pintuan_lanmu")
    . " b on a.Content=b.QsID " . " left join " . tablename("pintuan_areatype")
    . " c on c.Cid=a.aid ".$where." order by a.id asc";
$list=pdo_fetchall($sql,$data);
if($_GPC['op']=='delete'){
	$res=pdo_delete('pintuan_banner',array('id'=>$_GPC['id']));
	if($res){
		 message('删除成功！', $this->createWebUrl('ad'), 'success');
		}else{
			  message('删除失败！','','error');
		}
}
include $this->template('web/ad');