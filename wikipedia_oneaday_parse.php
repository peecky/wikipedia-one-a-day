<?php
require_once("common.php");
define("MAX_LOOP", 8);
define("USER_AGENT", "Opera/9.80 (X11; Linux i686; U; en) Presto/2.7.62 Version/11.00");

require_once(LIB_PATH."connect/liblion.php");
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

$requestUrls = array(
	"ahko" => "http://mirror.enha.kr/random",
	"ko" => "http://ko.wikipedia.org/wiki/특수:임의문서",
	"ko2" => "http://uncyclopedia.kr/wiki/특수기능:임의문서",
	"en" => "http://en.wikipedia.org/wiki/Special:Random"
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
$values["pubDate"] = time();
$values["todaymd"] = date("md", $values["pubDate"]);
$values["todayYear"] = date("Y", $values["pubDate"]);
$values["requestUrl"] = $requestUrls[$values["language"]];
$values["cacheID"] = $cacheIDs[$values["language"]];
switch($values["language"]) {
	case "ahko":
		$values["regex_parse_article_title"] = '/<h1\s+class="wikiTitle"\s*?>[\n\s]*?<a\s+.*?href=.*?>(.*?)<\/a>/i';
		break;
	case "ko":
	case "en":
	case "ko2":
		$values["regex_parse_article_title"] = '/<h1.*?>\n*.*<span.*?>(.*?)<\/span>\n*.*<\/h1>/i';
		break;
	default:
		$values["regex_parse_article_title"] = '/<h1.*?>(.*?)<\/h1>/i';
		break;
}
$needToFetchNewItem = false;

$dbCache = new DBCache();
$historyData = $dbCache->loadCacheAnyway($values["cacheID"]);
if($historyData === FALSE) $historyData = array();

if(!isset($historyData[$values["todaymd"]]) ||
	$historyData[$values["todaymd"]]["year"] != $values["todayYear"]) {
	$needToFetchNewItem = true;
}

if($needToFetchNewItem) {
	set_time_limit(600);
	$loops = 0;
	$lion = new Lion();
	do {
		$randomUrl = getRandomUrl($values["requestUrl"]);
		$loops++;
		if($loops > MAX_LOOP) exit;
	} while(!isValidUrl($randomUrl));

	$lion->parseGetParams(array("url" => $randomUrl));
	$lion->httpHeaderInfo["User-Agent"] = USER_AGENT;
	$lion->open(false, false);
	if(preg_match($values["regex_parse_article_title"], $lion->receivedBody, $matches)) {
		$itemTitle = $matches[1];
		// log history
		$historyData[$values["todaymd"]] = array(
			"url" => $randomUrl,
			"year" => $values["todayYear"],
			"title" => $itemTitle
		);
		$dbCache->saveCache($values["cacheID"], $historyData);
	}
}

function getRandomUrl($requestUrl) {
	$lion = &$GLOBALS["lion"];
	$lion->parseGetParams(array("url" => $requestUrl));
	$lion->httpHeaderInfo["User-Agent"] = USER_AGENT;
	$lion->open(false, false);
	if(!empty($lion->httpHeaderInfo2["Location"]))
		return $lion->httpHeaderInfo2["Location"];
	// check redirect by meta tag
	if(preg_match('/<meta.*http-equiv.*URL=(.*?)"\/>/i', $lion->receivedBody, $matches)) {
		if(strpos($matches[1], array("http://", "https://")) !== 0) {
			$urlInfo = parse_url($requestUrl);
			$newUrl = $urlInfo["scheme"]."://".$urlInfo["host"];
			if(!empty($urlInfo["port"])) $newUrl .= ":".$urlInfo["port"];
			if($matches[1]{0} == '/') {
				$newUrl .= $matches[1];
			}
			else {
				$newUrl .= pathinfo($urlInfo["path"], PATHINFO_DIRNAME). "/". $matches[1];
			}
		}
		else {
			$newUrl = $matches[1];
		}
		return $newUrl;
	}
	
	return false;
}

// check empty, duplicate
function isValidUrl($url) {
	if($url === false || empty($url)) return false;

	$historyData = &$GLOBALS["historyData"];
	foreach($historyData as $oldUrlInfo) {
		if($oldUrlInfo["url"] == $url) return false;
	}
	return true;
}
?>
