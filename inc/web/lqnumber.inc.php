<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu2();
$storeid=$_COOKIE["storeid"];
$cur_store = $this->getStoreById($storeid);
$pageindex = max(1, intval($_GPC['page']));
$pagesize=20;
//$list=pdo_getall('pintuan_number',array('uniacid'=>$_W['uniacid'],'num'=>$_GPC['num']),array(),'','id desc');
$sql=" select * from" . tablename("pintuan_number") ." where uniacid={$_W['uniacid']} and store_id={$storeid} and num='{$_GPC['num']}' and state !=4 order by id asc ";
$select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;
$list = pdo_fetchall($select_sql);	   
$total=pdo_fetchcolumn("select count(*) from " . tablename("pintuan_number")."where uniacid={$_W['uniacid']} and store_id={$storeid} and num='{$_GPC['num']}' and state !=4  group by num ");
foreach($list as $key => $value){
	if($value['state']==1){
		$newsql=" select count(id) as count from  ".tablename('pintuan_number')." where uniacid={$_W['uniacid']} and store_id={$storeid}  and num='{$value['num']}' and state=1  and id<{$value['id']}";
		$res=pdo_fetch($newsql);
	}	
	if($res){
		$list[$key]['pdrs']=$res['count'];		
	}else{
		$list[$key]['pdrs']='0';
	}
}
$pager = pagination($total, $pageindex, $pagesize);
if($_GPC['op']=='delete'){
	$res=pdo_delete('pintuan_number',array('id'=>$_GPC['id']));
	if($res){
		message('删除成功！', $this->createWebUrl('lqnumber'), 'success');
	}else{
		message('删除失败！','','error');
	}
}
include $this->template('web/lqnumber');