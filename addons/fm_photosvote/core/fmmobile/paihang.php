<?php
/**
 * 女神来了模块定义
 *
 * @author 微赞科技
 * @url http://bbs.012wz.com/
 */
defined('IN_IA') or exit('Access Denied');

		if ($rdisplay['ipannounce'] == 1) {
			$announce = pdo_fetchall("SELECT nickname,content,createtime,url FROM " . tablename($this->table_announce) . " WHERE rid= '{$rid}' ORDER BY id DESC");
		}
		//赞助商
		if ($rdisplay['ispaihang'] == 1) {
			$advs = pdo_fetchall("SELECT advname,link,thumb FROM " . tablename($this->table_advs) . " WHERE enabled=1 AND ismiaoxian = 0 AND rid= '{$rid}' ORDER BY displayorder ASC");
		}
		
		if(!empty($from_user)) {
		    $mygift = pdo_fetch("SELECT * FROM ".tablename($this->table_users)." WHERE from_user = :from_user and rid = :rid", array(':from_user' => $from_user,':rid' => $rid));
		}
		
		$tags = pdo_fetchall("SELECT * FROM ".tablename($this->table_tags)." WHERE rid = :rid ORDER BY id DESC", array(':rid' => $rid));
		$tagsarr = array();
		foreach ($tags as $key => $value) {
			$tags[$key]['piaoshu'] = pdo_fetchcolumn("SELECT sum(photosnum) FROM ".tablename($this->table_users)." WHERE tagid= ".$value['id']." AND rid= ".$rid."") + pdo_fetchcolumn("SELECT sum(xnphotosnum) FROM ".tablename($this->table_users)." WHERE tagid= ".$value['id']." AND rid= ".$rid."");//累计投票
			$tags[$key]['hits'] = pdo_fetchcolumn("SELECT sum(hits) FROM ".tablename($this->table_users)." WHERE tagid= ".$value['id']." AND rid= ".$rid."") + pdo_fetchcolumn("SELECT sum(xnhits) FROM ".tablename($this->table_users)." WHERE tagid= ".$value['id']." AND rid= ".$rid."");//累计投票
			//$value['title'] = $tags[$key]['piaoshu'];
			
			$tagsarr[$value['title']] = $tags[$key]['piaoshu'] + $tags[$key]['hits'];
			
		}
		$midn = 1;
		arsort($tagsarr);
		
		if ($_GPC['votelog'] == 1) {//投票人
			$tuser = pdo_fetch("SELECT avatar,nickname,realname,from_user FROM ".tablename($this->table_users)." WHERE from_user = :from_user and rid = :rid", array(':from_user' => $tfrom_user,':rid' => $rid));
			
			$pindex = max(1, intval($_GPC['page']));
			$psize = empty($rdisplay['phbtpxz']) ? 10 : $rdisplay['phbtpxz'];
			$m = ($pindex-1) * $psize+1;
			//取得用户列表
			$where = '';			
			
			if (!empty($tfrom_user)) {				
				$where .= " AND tfrom_user = '".$tfrom_user."'";				
			}
			
			$userlist = pdo_fetchall('SELECT * FROM '.tablename($this->table_log).' WHERE rid = :rid '.$where.' ORDER BY `id` DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize, array(':rid' => $rid));
			$total = pdo_fetchcolumn('SELECT COUNT(1) FROM '.tablename($this->table_log).' WHERE rid = :rid '.$where.'', array(':rid' => $rid));
			$total_pages = ceil($total/$psize);	
			$pager = paginationm($total, $pindex, $psize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));
			$username = $this->getusernames($tuser['realname'], $tuser['nickname'], '6');
			$title = $username . ' 的投票用户 - ' . $rbasic['title']; 
			$sharetitle = $username . '正在参加'. $rbasic['title'] .'，快来为'.$username.'投一票吧！';
			$sharecontent = $username . '正在参加'. $rbasic['title'] .'，快来为'.$username.'投一票吧！';
			$sharephoto = !empty($mygift['photo']) ? toimage($mygift['photo']) : toimage($tuser['avatar']);
			
			 $_share['title'] = $username . '正在参加'. $rbasic['title'] .'，快来为'.$username.'投一票吧！';
			$_share['content'] = $username . '正在参加'. $rbasic['title'] .'，快来为'.$username.'投一票吧！';
			$_share['imgUrl'] =  !empty($mygift['photo']) ? toimage($mygift['photo']) : toimage($tuser['avatar']);
		}else {//排行榜用户

			$tagid = $_GPC['category']['childid'];
			$tagpid = $_GPC['category']['parentid'];
			$tags = pdo_fetchall("SELECT * FROM ".tablename($this->table_tags)." WHERE uniacid = :uniacid AND rid = :rid ORDER BY id DESC", array(':uniacid' => $uniacid, ':rid' => $rid));
			$tagname = $this->gettagname($tagid,$tagpid,$rid);
			
			$pindex = max(1, intval($_GPC['page']));
			$psize =  empty($rdisplay['phbtpxz']) ? 10 : $rdisplay['phbtpxz'];
			$m = ($pindex-1) * $psize+1;
			

			//取得用户列表
			$where = '';			
			
			$where .= " AND status = '1'";
			if (!empty($tagid)) {
				$where .= " AND tagid = ".$tagid." ";
			}
		
			if ($rdisplay['indexpx'] == '0') {
				$where .= " ORDER BY `photosnum` + `xnphotosnum` DESC";
			}elseif ($rdisplay['indexpx'] == '1') {
				$where .= " ORDER BY `createtime` DESC";
				
			}elseif ($rdisplay['indexpx'] == '2') {
				$where .= " ORDER BY `hits` + `xnhits` DESC";
			}else{
				$where .= " ORDER BY `photosnum` + `xnphotosnum` DESC";
			}
			$userlist = pdo_fetchall('SELECT * FROM '.tablename($this->table_users).' WHERE rid = :rid '.$where.' LIMIT ' . ($pindex - 1) * $psize . ',' . $psize, array(':rid' => $rid));
			
			$total = pdo_fetchcolumn('SELECT COUNT(1) FROM '.tablename($this->table_users).' WHERE rid = :rid '.$where.'', array(':rid' => $rid));
			$total_pages = ceil($total/$psize);	
			$pager = paginationm($total, $pindex, $psize, '', array('before' => 0, 'after' => 0, 'ajaxcallback' => ''));
			
			$userlistm = pdo_fetchall('SELECT from_user,photosnum,xnphotosnum,hits,xnhits FROM '.tablename($this->table_users).' WHERE rid = :rid '.$where.' ', array(':rid' => $rid));
			$pmsarr = array();
			foreach ($userlistm as $key => $value) {
				
				$pmsarr[$value['from_user']] = $value['photosnum'] + $value['xnphotosnum'];
				
			}
			arsort($pmsarr);
			
			//print_r($pmsarr);


			$title = $rbasic['title'] . ' 排行榜 - ' . $_W['account']['name']; 
						
			$_share['title'] = $rbasic['title'] . ' 排行榜 - ' . $_W['account']['name']; 
			$_share['content'] = $rbasic['title'] . ' 排行榜 - ' . $_W['account']['name']; 
			$_share['imgUrl'] = toimage($rshare['sharephoto']);	
			
		}

				
		//整理数据进行页面显示		
		//$reply['sharetitle']= $this->get_share($uniacid,$rid,$from_user,$reply['sharetitle']);
		//$reply['sharecontent']= $this->get_share($uniacid,$rid,$from_user,$reply['sharecontent']);
		
		//$myavatar = $avatar;
		//$mynickname = $nickname;
		//$shareurl = $_W['siteroot'] .'app/'.$this->createMobileUrl('shareuserview', array('rid' => $rid,'duli' => 3,'fromuser' => $from_user));//分享URL
		//$regurl = $_W['siteroot'] .'app/'.$this->createMobileUrl('reg', array('rid' => $rid));//关注或借用直接注册页
		//$guanzhu = $reply['shareurl'];//没有关注用户跳转引导页
		//$mygifturl = $_W['siteroot'] .'app/'.$this->createMobileUrl('photosvote', array('rid' => $rid));//我的页面
		
		$_share['link'] = $_W['siteroot'] .'app/'.$this->createMobileUrl('shareuserview', array('rid' => $rid,'duli' => 3,'fromuser' => $from_user));//分享URL		
		
				
		
		
		$templatename = $rbasic['templates'];
		if ($templatename != 'default' && $templatename != 'stylebase') {
			require FM_CORE. 'fmmobile/tp.php';
		}
		$toye = $this->templatec($templatename,$_GPC['do']);
		include $this->template($toye);
		
