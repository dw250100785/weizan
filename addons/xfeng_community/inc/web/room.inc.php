<?php
/**
 * 微小区模块
 *
 * [微赞] Copyright (c) 2013 012wz.com
 */
/**
 * 后台房号管理
 */
global $_GPC,$_W;
$do = $_GPC['do'];
$GLOBALS['frames'] = $this->NavMenu($do);
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'list';
//判断是否是操作员
$user = $this->user();
if ($user) {
	//物业管理员
	if (!$user['regionid']) {
		$regions = pdo_fetchall("SELECT * FROM".tablename('xcommunity_region')."WHERE weid='{$_W['weid']}' AND pid=:pid",array(':pid' => $user['companyid']));

	}
}else{
	$regions = $this->regions();	
}
if($op == 'list'){
		$pindex = max(1, intval($_GPC['page']));
		$psize  = 20;
		$condition = 'uniacid=:uniacid';
		$params[':uniacid'] = $_W['uniacid'];
		if ($_GPC['regionid']) {
			$condition .= ' AND regionid=:regionid';
			$params[':regionid'] = $_GPC['regionid'];
		}
		if ($user) {
			if ($user['regionid']) {
				$condition .=" AND regionid=:regionid";
				$params[':regionid'] = $user['regionid'];
			}else{
				$condition .=" AND pid =:pid";
				$params[':pid'] = $user['companyid'];
			}
			
		}
		$sql = "SELECT * FROM".tablename('xcommunity_room')."WHERE $condition LIMIT ".($pindex - 1) * $psize.','.$psize;
		$list = pdo_fetchall($sql,$params);
		$tsql = "SELECT COUNT(*) FROM".tablename('xcommunity_room')."WHERE $condition";
		$total =pdo_fetchcolumn($tsql,$params);
		$pager  = pagination($total, $pindex, $psize);
		//删除用户
		if (checksubmit('delete')) {
			$ids=$_GPC['rid'];
			if (!empty($ids)) {
				foreach ($ids as $key => $id) {
					pdo_delete('xcommunity_room',array('id' => $id));
				}
				message('删除成功',referer(),'success');
			}
		}
		//导出用户
		if (checksubmit('export')) {
			$sql = "SELECT * FROM".tablename('xcommunity_room')."WHERE $condition ";
			$li = pdo_fetchall($sql,$params);
				$this->export($li,array(
			            "title" => "房号数据-" . date('Y-m-d-H-i', time()),
			            "columns" => array(
			            	array(
			                    'title' => '姓名',
			                    'field' => 'realname',
			                    'width' => 16
			                ),
			                array(
			                    'title' => '房号',
			                    'field' => 'room',
			                    'width' => 16
			                ),
			                array(
			                    'title' => '手机号',
			                    'field' => 'mobile',
			                    'width' => 14
			                ),
			                array(
			                    'title' => '注册码',
			                    'field' => 'code',
			                    'width' => 18
			                ),
			              
			            )
					));


		}
		//添加新业主
		if (checksubmit('submit')) {
			//是否需考虑验证生成的注册码已存在，手机号码，房号已存在
			$regionid = !empty($user['regionid']) ? $user['regionid'] : $_GPC['regionid'];
			$region = $this->region($regionid);
			$data = array(
					'uniacid' => $_W['uniacid'],
					'room' => $_GPC['room'],
					'mobile' => $_GPC['mobile'],
					'code'    => rand(1000,999999),
					'uniacid' => $_W['uniacid'],
					'regionid' => $regionid,
					'pid' => $region['pid'],
					'realname' => $_GPC['realname']
				);
			$room = pdo_fetch("SELECT * FROM".tablename('xcommunity_room')."WHERE mobile=:mobile or room=:room AND regionid =:regionid",array(':mobile' => $data['mobile'],':room' => $data['room'],':regionid' => $data['regionid']));
			if ($room) {
				message('手机号码或者房号已存在',$this->createWebUrl('room',array('op' => 'list')),'error');exit();
			}
			if (empty($room)) {
				$r = pdo_insert('xcommunity_room',$data);
				if ($r) {
					message('添加成功',$this->createWebUrl('room',array('op' => 'list')),'success');
				}
			}
			
		}

	include $this->template('web/room/list');
}elseif($op =='add'){
	//房号导入
		if (checksubmit('submit')) {
				if (!empty($_FILES['room']['name'])) {
						$tmp_file   = $_FILES['room']['tmp_name'];
						$file_types = explode(".",$_FILES['room']['name']);
						$file_type  = $file_types[count($file_types)-1];
						/*判别是不是.xls文件，判别是不是excel文件*/
						if (strtolower ( $file_type ) !="xls" && strtolower ( $file_type ) !="xlsx") 
						{
							message('类型不正确，请重新上传',referer(),'error');
						}
					  /*设置上传路径*/
					   $savePath = IA_ROOT.'/addons/xfeng_community/template/upFile/';
					  /*以时间来命名上传的文件*/
					   $str = date('Ymdhis'); 
					   $file_name = $str.".".$file_type;
					   /*是否上传成功*/
					   if (!copy($tmp_file,$savePath.$file_name)) {
					   		message('上传失败');
					     
					   }
					  $res = $this->read($savePath.$file_name);
					  $regionid = !empty($user['regionid']) ? $user['regionid'] : $_GPC['regionid'];
					  $result = pdo_fetch("SELECT * FROM".tablename('xcommunity_room')."WHERE regionid=:regionid AND uniacid=:uniacid ",array(':uniacid' => $_W['uniacid'],':regionid' => $regionid));
				  	  if ($result) {
				  	  	message('该小区已存在房号数据',referer(),'success');exit();
				  	  }
				  
				  	  
				  	  $region = $this->region($regionid);
					  /*对生成的数组进行数据库的写入*/
					  foreach ( $res as $k => $v ) {
						    if ($k != 0) {
						    	//是否需验证导入的房号，手机号码，是否已存在
						    	$data['realname'] = $v[0];
								$data['room']     = $v[2];
								$data['mobile']   = $v[1];
								$data['code']     = rand(1000,999999);
								$data['regionid'] = $regionid;
								$data['pid'] = $region['pid'];
								$data['uniacid']  = $_W['uniacid'];
								$result = pdo_insert('xcommunity_room',$data);
						    }
					  }

					  if($result){
				       		message('导入成功',referer(),'success');
				     	}
					}
				}

	include $this->template('web/room/add');
}elseif($op == 'delete'){

}elseif ($op == 'edit') {
		//房号编辑
		$rid = intval($_GPC['rid']);
		if (empty($rid)) {
			message('缺少参数',referer,'error');
		}
		if ($rid) {
			$item = pdo_fetch("SELECT room,mobile,realname FROM".tablename('xcommunity_room')."WHERE id=:id",array(':id' => $rid));
		}
		if (checksubmit('submit')) {
			//是否需考虑验证手机号码，房号已存在
			$data = array(
					'room' => $_GPC['room'],
					'mobile' => $_GPC['mobile'],
					'realname' => $_GPC['realname'],
				);
			if ($rid) {
				$r = pdo_update('xcommunity_room',$data,array('id' => $rid));
				if ($r) {
					message('修改成功',$this->createWebUrl('room',array('op' => 'list')),'success');
				}
			}
		}
		include $this->template('web/room/edit');
	}