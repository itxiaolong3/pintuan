<?php
global $_GPC, $_W;
$GLOBALS['frames'] = $this->getMainMenu2();
if($_GPC['lmid']){
    setcookie("storeid",$_GPC['lmid']);
    $cur_store = $this->getStoreById($_GPC['lmid']);
    $storeid=$_GPC['lmid'];
}else{
    $storeid=$_COOKIE["storeid"];
    $cur_store = $this->getStoreById($storeid);
}
$type=pdo_getall('pintuan_bighome',array('uniacid'=>$_W['uniacid']));
$where=" WHERE a.uniacid=:uniacid and a.zid=:store_id";
$data[':uniacid']=$_W['uniacid'];
$data[':store_id']=$storeid;
//关键字查询
if($_GPC['keywords']){
    $where .=" and a.Title LIKE :name ";
    $op=$_GPC['keywords'];
    $data[':name']="%$op%";

}
//所在大厦
if($_GPC['type_id']){
    $where .=" and a.StallsName=:type_id";
    $data[':type_id']=$_GPC['type_id'];
}
//上下架
if($_GPC['is_show2']){
    $where .=" and a.Statu=:cid";
    $data[':cid']=$_GPC['is_show2'];
}

$pageindex = max(1, intval($_GPC['page']));
$pagesize=15;
$sql="select a.* ,b.hname,c.Name as typename,c.ID from " . tablename("pintuan_goodmy") . " a"
    . " left join " . tablename("pintuan_bighome")
    . " b on b.bid=a.StallsName". " left join " . tablename("pintuan_typetwomy")
    . " c on c.ID=a.tid ".$where." and a.isdelete=0 order by a.Statu asc";
$select_sql =$sql." LIMIT " .($pageindex - 1) * $pagesize.",".$pagesize;

$list = pdo_fetchall($select_sql,$data);
$total=pdo_fetchcolumn("select count(*) from " . tablename("pintuan_goodmy") . " a"  . " left join " . tablename("pintuan_bighome") . " b on b.bid=a.StallsName".$where,$data);
$pager = pagination($total, $pageindex, $pagesize);
if($_GPC['id']){
    $data2['Statu']=$_GPC['is_show'];

    $res=pdo_update('pintuan_goodmy',$data2,array('gID'=>$_GPC['id']));
    if($res){
        message('设置成功',$this->createWebUrl('dishes2',array('page'=>$_GPC['page'],'keywords'=>$_GPC['keywords'],'type_id'=>$_GPC['type_id'],'is_show2'=>$_GPC['is_show2'])),'success');
    }else{
        message('设置失败','','error');
    }
}
if($_GPC['op']=='delete'){
    $result = pdo_delete('pintuan_goodmy', array('gID'=>$_GPC['delid']));
    if($result){
        message('删除成功',$this->createWebUrl('dishes2',array()),'success');
    }else{
        message('删除失败','','error');
    }
}

if(checksubmit('submit2')){
    $url=$_W['attachurl'];
    $filename = $_FILES['file_stu']['name'];
    $tmp_name = $_FILES['file_stu']['tmp_name'];
    $filePath = IA_ROOT . '/addons/pintuan/excel/';
    include 'phpexcelreader/reader.php';
    $data = new Spreadsheet_Excel_Reader();
    $data->setOutputEncoding('utf-8');

    //注意设置时区
    $time = date("y-m-d-H-i-s"); //去当前上传的时间
    $extend = strrchr ($filename, '.');
    //上传后的文件名
    $name = $time . $extend;
    $uploadfile = $filePath . $name; //上传后的文件名地址
    //@move_uploaded_file($tmp_name, $uploadfile);
    if (copy($tmp_name, $uploadfile)) {
        if (!file_exists($filePath)) {
            echo '文件路径不存在.';
            return;
        }
        if (!is_readable($uploadfile)) {
            echo("文件为只读,请修改文件相关权限.");
            return;
        }
        if(!in_array($extend, array('.xls')))
            //检查文件类型
        {
            message('文件类型不符',$this->createWebUrl('dishes2',array()),'error');
            exit;
        }

        $data->read($uploadfile);
        $num=count($data->sheets[0]['cells']);
        error_reporting(E_ALL ^ E_NOTICE);
        $count = 0;
        for ($i = 2; $i <=  $num; $i++) { //$=2 第二行开始
            $row = $data->sheets[0]['cells'][$i];
            //message($data->sheets[0]['cells'][$i][1]);
            //开始处理数据库
            $insert['zid'] = $row[1];
            $insert['tid'] = $row[2];
            $insert['StallsName'] = $row[3];
            $insert['Title'] = $row[4];
            $insert['DealCount'] = $row[5];
            $insert['TotalQty'] = $row[6];
            $insert['Price'] = $row[7];
            $insert['Videos'] = $row[8];
             if(empty($row[9])){
                  $insert['Colors']='如图';
              }else{
                  $insert['Colors'] = $row[9];
              }
          	if(empty($row[10])){
                  $insert['Sizes']='均码';
              }else{
                 $insert['Sizes'] = $row[10];
              }
            $insert['Tag'] = $row[11];
            $insert['Statu'] = $row[12];
            if(strstr($row[13],'http')){
                $insert['Itemcover'] =$row[13];
            }else{
                $insert['Itemcover'] = $url.$row[13];
            }
            $insert['Images'] = $row[14];
            $insert['uniacid'] = $_W['uniacid'];
            $res= pdo_insert('pintuan_goodmy',$insert);
            $count = $count + $res;
        }
    }
    //unlink($uploadfile); //删除文件
    if ($count == 0) {
        message('导入失败',$this->createWebUrl('dishes2',array()),'error');

    } else {
        message('导入成功',$this->createWebUrl('dishes2',array()),'success');
    }

}

include $this->template('web/dishes2');
