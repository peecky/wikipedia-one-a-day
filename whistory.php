<?php
require_once("common.php");
require_once(LIB_PATH."cache/cache_db.php");

$cacheIDs = array(
	"ahko" => "wikipedia_oneaday_ahko_history",
	"ko" => "wikipedia_oneaday_ko_history",
	"ko2" => "wikipedia_oneaday_ko2_history",
	"en" => "wikipedia_oneaday_en_history"
);
$titles = array(
	"ahko" => "엔하위키 하루 하나 히스토리",
	"ko" => "한국어 위키백과 하루 하나 히스토리",
	"ko2" => "백괴사전 하루 하나 히스토리",
	"en" => "the History of Wikipedia day by day article(English)"
);
$rssUrls = array(
	"ahko" => "http://feeds.feedburner.com/angelhalowiki_oneaday",
	"ko" => "http://feeds.feedburner.com/wikipedia_oneaday_kr",
	"ko2" => "http://feeds.feedburner.com/uncyclopedia_oneaday_kr",
	"en" => "http://feeds.feedburner.com/wikipedia_oneaday_en"
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
$values["cacheID"] = $cacheIDs[$values["language"]];
$values["title"] = $titles[$values["language"]];
$values["rssUrl"] = $rssUrls[$values["language"]];

$dbCache = new DBCache();
$historyData = $dbCache->loadCacheAnyway($values["cacheID"]);
if($historyData === false) {
	exit("-_ -a");
}
ksort($historyData);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta http-equiv='Content-type' content='text/html; charset=utf-8' />
<title><?= $values["title"] ?></title>
<link rel="alternate" type="application/rss+xml" href="<?= $values["rssUrl"] ?>" />
</head>
<body>
<h1><?= $values["title"] ?></h1>
<ul>
<?php foreach($historyData as $key => $value) : ?>
<li><?= $value["year"].$key ?>: <a href="<?= $value["url"] ?>"><?= $value["title"] ?></a></li>
<?php endforeach; ?>
</ul>
<p>참고: <a href="http://crimsonpi.egloos.com/4819267">하루에 하나씩 위키백과 정복하기</a></p>
</body>
</html>
