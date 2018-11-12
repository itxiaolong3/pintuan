<?php
global $_GPC, $_W;
$data['tid']=$_GPC['tid'];
$data['Title']=$_GPC['Title'];

if (!strstr($_GPC['Itemcover'],'http')){
    if(!empty($_GPC['Itemcover'])){
        $data['Itemcover']=$_W['attachurl'].$_GPC['Itemcover'];
    }
}else{
    $data['Itemcover']=$_GPC['Itemcover'];
}
if (!strstr($_GPC['Videos'],'http')){
   if(!empty($_GPC['Videos'])){
        $data['Videos']=$_W['attachurl'].$_GPC['Videos'];
    }
}else{
    $data['Videos']=$_GPC['Videos'];
}
$data['Price']=$_GPC['Price'];
$data['DealCount']=$_GPC['DealCount'];
$data['StallsName']=$_GPC['StallsName'];
$data['Statu']=$_GPC['Statu'];
$data['TotalQty']=$_GPC['TotalQty'];

if(empty($_GPC['Colors'])){
    $data['Colors']='如图';
}else{
    $data['Colors']=$_GPC['Colors'];
}
if(empty($_GPC['Sizes'])){
    $data['Sizes']='均码';
}else{
    $data['Sizes']=$_GPC['Sizes'];
}
$data['Tag']=$_GPC['Tag'];
$data['uniacid']=$_W['uniacid'];
//图片集
$images=$_GPC['Images'];
foreach ($images as $k=>$v){
    $geturl=$v;
    if (!strstr($geturl,'http')){
      if(!empty($geturl)){
        $images[$k]=$_W['attachurl'].$v;
    }
       
    }
}
$images=implode(',',$images);
$data['Images']=$images;
$getid=$_GPC['good_id'];
if(!$_GPC['Title']){
    message('请填写商品名称!','','error');
}
if(!$_GPC['Itemcover']){
    message('请上传商品封面!','','error');
}

if(!$_GPC['Price']||$_GPC['Price']<0){
    message('商品价格不可为空并且大于0','','error');
}
if (empty($getid)){
  $data['zid']=$_GPC['storeid'];
    if(!$_GPC['storeid']){
        message('不知哪个栏目的商品，无法操作','','error');
    }
    $res=pdo_insert('pintuan_goodmy',$data);
    if (!empty($res)) {
        message('添加成功',$this->createWebUrl('dishes2',array()),'success');
    } else {
        message("添加失败", referer(), 'error');
    }
}else{
    $res=pdo_update('pintuan_goodmy',$data,array('gID'=>$getid));
    if (!empty($res)) {
        if($_GPC['allgood']){
            message('编辑成功！', $this->createWebUrl('allgood'), 'success');
        }else{
            message('编辑成功！', $this->createWebUrl('dishes2'), 'success');
        }
    } else {
        message("编辑失败", '', 'error');
    }
}