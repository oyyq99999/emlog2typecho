<?php
require_once 'MysqliDb.php';

$db_host = '127.0.0.1';
$db_port = 3306;
$db_user = 'user';
$db_passwd = 'password';

$emlogDb = 'emlog';
$emlogPrefix = 'emlog_';

$typechoDb = 'typecho';
$typechoPrefix = 'typecho_';

/////////////////////
// 从emlog读取数据 //
/////////////////////
$db = new MysqliDb($db_host, $db_user, $db_passwd, $emlogDb, $db_port);
$db->setPrefix($emlogPrefix);
$contentsData = prepareDataForContents();
function prepareDataForContents() {
	global $db;
	$fields = array(
		'`gid` AS `cid`',
		'`title`',
		'CASE WHEN `alias`<>"" THEN `alias` ELSE `gid` END AS `slug`',
		'`date` AS `created`',
		'`date` AS `modified`',
		'`content` AS `text`',
		'0 AS `order`',
		'`author` AS `authorId`',
		'NULL AS `template`',
		'CONCAT(CASE WHEN `type`="blog" THEN "post" ELSE "page" END, CASE WHEN `hide`="y" THEN "_draft" ELSE "" END) AS `type`',
		'CASE WHEN `checked`="n" THEN "waiting" ELSE "publish" END AS `status`',
		'CASE WHEN `password`="" THEN NULL ELSE `password` END AS `password`',
		'`comnum` AS `commentsNum`',
		'CASE WHEN `allow_remark`="y" THEN 1 ELSE 0 END AS `allowComment`',
		'1 AS `allowPing`',
		'1 AS `allowFeed`',
		'0 AS `parent`',
	);
	$contents = $db->orderBy('cid', 'asc')->get('blog', null, $fields);
	return $contents;
}
list($metasData, $relationshipsData) = prepareDataForMetasAndRelationships();
function prepareDataForMetasAndRelationships() {
	global $db;
	$fields = array(
		'`sid`',
		'`sortname`',
		'`alias`',
		'CASE WHEN `description`="" THEN NULL ELSE `description` END AS `description`',
	);
	$categories = $db->get('sort', null, $fields);
	$tags = $db->get('tag');
	$blogCategory = $db->get('blog', null, array('`gid`', '`sortid`', '`hide`', '`checked`'));

	$metas = array();
	$relationships = array();
	$maxMetasId = -1;
	$relationshipsId = 0;

	function countCategory($sid, $blogCategory) {
		$count = 0;
		foreach ($blogCategory as $oneBlog) {
			if ($oneBlog['sortid'] === $sid && $oneBlog['hide'] == 'n' && $oneBlog['checked'] == 'y') {
				$count++;
			}
		}
		return $count;
	}

	function countTag($tag, $blogCategory) {
		$count = 0;
		$blogIds = array_filter(explode(',', $tag['gid']));
		foreach ($blogIds as $blogId) {
			foreach ($blogCategory as $blogInfo) {
				if ($blogInfo['gid'] === intval($blogId)) {
					if ($blogInfo['hide'] == 'n' && $blogInfo['checked'] == 'y') {
						$count++;
					}
				}
			}
		}
		return $count;
	}
	function compareForRelationships($a, $b) {
		if ($a['cid'] > $b['cid']) {
			return 1;
		}
		if ($a['cid'] < $b['cid']) {
			return -1;
		}
		return $a['mid'] - $b['mid'];
	}

	foreach ($categories as $category) {
		$oneMeta = array(
			'mid' => $category['sid'],
			'name' => $category['sortname'],
			'slug' => $category['alias'],
			'type' => 'category',
			'description' => $category['description'],
			'count' => countCategory($category['sid'], $blogCategory),
			'order' => 0,
		);
		$metas[] = $oneMeta;
		if ($oneMeta['mid'] > $maxMetasId) {
			$maxMetasId = $oneMeta['mid'];
		}
	}
	foreach ($blogCategory as $blogInfo) {
		if ($blogInfo['sortid'] > 0) {
			$relationships[] = array(
				'cid' => $blogInfo['gid'],
				'mid' => $blogInfo['sortid'],
			);
		}
	}
	foreach ($tags as $tag) {
		$oneMeta = array(
			'mid' => ++$maxMetasId,
			'name' => $tag['tagname'],
			'slug' => $tag['tagname'],
			'type' => 'tag',
			'description' => null,
			'count' => countTag($tag, $blogCategory),
			'order' => 0,
		);
		$metas[] = $oneMeta;
		$blogIds = array_filter(explode(',', $tag['gid']));
		foreach ($blogIds as $blogId) {
			$relationships[] = array(
				'cid' => intval($blogId),
				'mid' => $maxMetasId,
			);
		}
	}
	usort($relationships, 'compareForRelationships');
	return array($metas, $relationships);
}
$commentsData = prepareDataForComments();
function prepareDataForComments() {
	global $db;
	$fields = array(
		'`cid` AS `coid`',
		'`gid` AS `cid`',
		'`date` AS `created`',
		'`poster` AS `author`',
		'0 AS `authorId`',
		'1 AS `ownerId`',
		'`mail`',
		'`url`',
		'`ip`',
		'NULL AS `agent`',
		'`comment` AS `text`',
		'"comment" AS `type`',
		'CASE WHEN `hide`="y" THEN "waiting" ELSE "approved" END AS `status`',
		'`pid` AS `parent`',
	);
	$comments = $db->orderBy('coid', 'asc')->get('comment', null, $fields);
	return $comments;
}
$optionsData = prepareDataForOptions();
function prepareDataForOptions() {
	global $db;
	$fields = array(
		'`option_name` AS `name`',
		'`option_value` AS `value`',
	);
	$booleanFields = array(
		'`option_name` AS `name`',
		'CASE WHEN `option_value`="y" THEN "1" ELSE "0" END AS `value`',
	);
	// 两边值一样的配置项
	$sameOptionNamesMap = array(
		'blogname' => 'title',
		'bloginfo' => 'description',
		'site_key' => 'keywords',
		'blogurl' => 'siteUrl',
		'index_lognum' => 'pageSize',
		'index_comnum' => 'commentsListSize',
		'index_newlognum' => 'postsListSize',
		'comment_pnum' => 'commentsPageSize',
		'comment_interval' => 'commentsPostInterval',
		'att_type' => 'attachmentTypes',
	);
	// 布尔类型值配置项
	$booleanOptionNamesMap = array(
		'rss_output_fulltext' => 'feedFullText',
		'isgravatar' => 'commentsAvatar',
		'comment_paging' => 'commentsPageBreak',
		'ischkcomment' => 'commentsRequireModeration',
		'isgzipenable' => 'gzip',
	);
	// 其他需要特殊处理值的配置项
	$otherOptionNamesMap = array(
		'comment_order' => 'commentsOrder',
		'timezone' => 'timezone',
	);
	$emlogSameOptions = $db->where('option_name' , array_keys($sameOptionNamesMap), 'IN')->get('options', null, $fields);
	$typechoSameOptions = array();
	foreach ($emlogSameOptions as $oneOption) {
		$typechoSameOptions[] = array(
			'name' => $sameOptionNamesMap[$oneOption['name']],
			'value'	=> $oneOption['value'],
		);
	}
	$emlogBooleanOptions = $db->where('option_name' , array_keys($booleanOptionNamesMap), 'IN')->get('options', null, $booleanFields);
	$typechoBooleanOptions = array();
	foreach ($emlogBooleanOptions as $oneOption) {
		$typechoBooleanOptions[] = array(
			'name' => $booleanOptionNamesMap[$oneOption['name']],
			'value'	=> $oneOption['value'],
		);
	}
	$emlogOtherOptions = $db->where('option_name' , array_keys($otherOptionNamesMap), 'IN')->get('options', null, $fields);
	$typechoOtherOptions = array();
	foreach ($emlogOtherOptions as $oneOption) {
		$value = null;
		switch ($oneOption['name']) {
		case 'comment_order':
			$value = ($oneOption['value'] == 'newer' ? 'DESC' : 'ASC');
			break;
		case 'timezone':
			$value = '' . intval($oneOption['value']) * 3600;
			break;
		}
		if (isset($value)) {
			$typechoOtherOptions[] = array(
				'name' => $otherOptionNamesMap[$oneOption['name']],
				'value'	=> $value,
			);
		}
	}
	$options = array_merge($typechoSameOptions, $typechoBooleanOptions, $typechoOtherOptions);
	return $options;
}

///////////////////////
// 写入typecho数据库 //
///////////////////////
$db = new MysqliDb($db_host, $db_user, $db_passwd, $typechoDb, $db_port);
$db->setPrefix($typechoPrefix);
flushContentsData();
function flushContentsData() {
	global $db, $contentsData, $typechoPrefix;
	$db->delete('contents');
	$db->rawQuery('ALTER TABLE `'.$typechoPrefix.'contents` AUTO_INCREMENT=1');
	foreach ($contentsData as $oneContent) {
		if ($db->insert('contents', $oneContent) === false) {
			var_dump($db->getLastError());
		}
	}
}
flushMetasData();
function flushMetasData() {
	global $db, $metasData, $typechoPrefix;
	$db->delete('metas');
	$db->rawQuery('ALTER TABLE `'.$typechoPrefix.'metas` AUTO_INCREMENT=1');
	foreach ($metasData as $oneMeta) {
		if ($db->insert('metas', $oneMeta) === false) {
			var_dump($db->getLastError());
		}
	}
}
flushRelationshipsData();
function flushRelationshipsData() {
	global $db, $relationshipsData;
	$db->delete('relationships');
	foreach ($relationshipsData as $oneRelationship) {
		if ($db->insert('relationships', $oneRelationship) === false) {
			var_dump($db->getLastError());
		}
	}
}
flushCommentsData();
function flushCommentsData() {
	global $db, $commentsData, $typechoPrefix;
	$db->delete('comments');
	$db->rawQuery('ALTER TABLE `'.$typechoPrefix.'comments` AUTO_INCREMENT=1');
	foreach ($commentsData as $oneComment) {
		if ($db->insert('comments', $oneComment) === false) {
			var_dump($db->getLastError());
		}
	}
}
flushOptionsData();
function flushOptionsData() {
	global $db, $optionsData;
	foreach ($optionsData as $oneOption) {
		$data = array(
			'value' => $oneOption['value'],
		);
		if ($db->where('name', $oneOption['name'])->update('options', $data) === false) {
			$lastError = $db->getLastError();
			if (trim($lastError) !== '') {
				var_dump($lastError);
			}
		}
	}
}
