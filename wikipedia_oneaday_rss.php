<?php
require_once("common.php");

require_once(LIB_PATH."print/print_rss.php");
require_once(LIB_PATH."cache/cache_db.php");

/*
 * cache data struct
 *
 * array(
 *	"mmdd" => array(
 *		"year" => YYYY
 *		"url" => "http://..."
 *		"title" => "..."
 *	)
 *	...
 * )
 */

$mainTitles = array(
	"ahko" => "엔하위키 하루 하나",
	"ko" => "위키백과 하루 하나",
	"ko2" => "백괴사전 하루 하나",
	"en" => "Wikipedia day by day(English)"
);
$siteUrls = array(
	"ahko" => "http://mirror.enha.kr/wiki/FrontPage",
	"ko" => "http://ko.wikipedia.org/wiki/",
	"ko2" => "http://uncyclopedia.kr/wiki/",
	"en" => "http://en.wikipedia.org/wiki/"
);
$mainDescriptions = array(
	"ahko" => "하루에 하나씩 엔하위키",
	"ko" => "하루에 하나씩 위키백과",
	"ko2" => "하루에 내용 없는 글 하나씩",
	"en" => "wikipedia articles one a day"
);
$cacheIDs = array(
	"ahko" => "wikipedia_oneaday_ahko_history",
	"ko" => "wikipedia_oneaday_ko_history",
	"ko2" => "wikipedia_oneaday_ko2_history",
	"en" => "wikipedia_oneaday_en_history"
);

$values = array();
if(!isset($_GET["lang"])) $values["language"] = "ko";
switch($_GET["lang"]) {
	case "ahko":
	case "en":
	case "ko":
	case "ko2":
		$values["language"] = $_GET["lang"];
		break;

	default:
		$values["language"] = "ko";
}
$values["mainTitle"] = $mainTitles[$values["language"]];
$values["description"] = $mainDescriptions[$values["language"]];
$values["description"] .= ",\nhistory: http://1up.so/whistory.php?lang=".$values["language"].",\nRSS provider: http://crimsonpi.egloos.com/4819267";
$values["cacheID"] = $cacheIDs[$values["language"]];
$dbCache = new DBCache();
$historyData = $dbCache->loadCacheAnyway($values["cacheID"]);
if($historyData === FALSE) $historyData = array();
$values["pubDate"] = $dbCache->cachedTime($values["cacheID"]);
$values["todaymd"] = date("md", $values["pubDate"]);
$values["siteMainUrl"] = $siteUrls[$values["language"]];

// print latest five items
include(LIB_PATH."header/xmlheader_utf8.php");
echoRSSHead($values["mainTitle"], $values["siteMainUrl"], $values["description"], date("r", $values["pubDate"]));
if(isset($historyData[$values["todaymd"]])) {
	$iStart = 0;
	$iEnd = 5;
}
else {
	$iStart = 1;
	$iEnd = 6;
}
for($i = $iStart, $iLimit = count($historyData); $i < $iEnd && $i < $iLimit; $i++) {
	$oldPubDate = $values["pubDate"] - $i*86400;
	$oldMD = date("md", $oldPubDate);
	if(isset($historyData[$oldMD])) {
		$randomUrl = $historyData[$oldMD]["url"];
		if(isset($historyData[$oldMD]["title"])) $itemTitle = $historyData[$oldMD]["title"];
		else $itemTitle = "";
		echoRSSItem(htmlspecialchars(strip_tags($itemTitle)), $randomUrl, htmlspecialchars($itemTitle), $randomUrl, date("r", $oldPubDate));
	}
}
echoRSSTail();
?>
