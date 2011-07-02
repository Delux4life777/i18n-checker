<?php
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
ob_implicit_flush(1);

require_once(realpath(dirname(__FILE__).'/../src/class.Conf.php'));
require_once(PATH_SRC.'/class.Message.php');
require_once(PATH_SRC.'/class.Language.php');
require_once(PATH_SRC.'/class.Net.php');
require_once(PATH_SRC.'/class.Checker.php');
require_once(PATH_SRC.'/class.Test.php');

header('Content-Type: text/html; charset=UTF-8');
$title = "W3C I18n Checker Tests";
$css[] = "base_ucn.css";
$js[] = "mootools-1.3.2.js";
include(PATH_TEMPLATES.'/html/head.php');

$logger = Logger::getLogger('Tests');
$logger->info("Initiating tests");

if (isset($_GET['test_file']) && $_GET['test_file'] != "")
	$testConf = Test::loadRemote($_GET['test_file']);
else
	$testConf = Test::load();

include(PATH_TEMPLATES.'/html/messages.php');
	
if ($testConf == null) {
	include(PATH_TEMPLATES.'/html/footer.php');
	exit(1);
}

$test_url = Conf::get('test_url');
$test_param_id = Conf::get('test_param_id');
$test_param_format = Conf::get('test_param_format');
$test_param_serveas = Conf::get('test_param_serveas');
$test_categories = Conf::get('test_categories');
$test_info_categories = Conf::get('test_info_categories');
$test_formats=explode(',',Conf::get('test_formats'));

if (isset($_GET['test_cat']))
	$test_categories = (array) $_GET['test_cat'];

$tests = array();
$categories = array('charset','lang');

$count = 0;
foreach ($test_categories as $category) {
	$tests[$category] = Test::getTests($category, $testConf);
	$count += count($tests[$category]);
}

$logger->info("Parsed $count tests successfully");

$startingTime = time();
$passedCount = 0;
$failedCount = 0;
?>
<div id="infos" class="tests block">
<div class="top">Succefully parsed <b><?php echo $count ?></b> tests from <?php echo isset($_GET['test_file']) && $_GET['test_file'] != "" ? 'remote file ('.$_GET['test_file'].')' : 'local test files'?>.</div>

<table>
	<tbody>
<?php 
$i = 1;
foreach ($tests as $category => $catTests) {
	if (empty($catTests))
		continue;
	$logger->info("##################################");
	$logger->info("### Starting $category section");
	$logger->info("##################################");
	echo '<tr>';
	echo '<th></th><th>', lang($category.'_category'), "</th>";
	foreach ($test_formats as $format) {
		echo '<th>', Conf::get('test_display_'.preg_replace('/:/', '_', $format)) ,'</th>';
	}
	echo '</tr>';
	// Loop through all tests in that category
	foreach ($catTests as $test) {
		$logger->info("# Starting test ".$test['name']." #");
		$logger->debug("Test data: ".print_r($test, true));
		echo '<tr>';
		echo '<td>', $i, '</td><td>', $test['name'], '</td>';
		$i++;
		$testFor = explode(',', $test['test_for']);
		foreach ($test_formats as $format) {
			if (!in_array($format, $testFor)) {
				echo '<td>-</td>';
				continue;
			}
			$logger->info("- Starting test for format $format -");
			$format = explode(':', $format);
			$uri = isset($test['url']) ? $test['url'] : Test::constructUri($test['id'], $format[0], isset($format[1]) ? $format[1] : 'html');
			$logger->info("Test file uri resolved to: $uri");
			$b = Test::startCheck($uri);
			if (!$b) {
				$logger->error("An error occured while executing test: ".Test::generateTestURL($uri));
				echo '<td class="undef" title="An error occured while running this test"><a href="', Test::generateTestURL($uri),'>✘</a></td>';
				continue;
			}
			$logger->info("Check executed successfully. Checking results...");
			$result = Test::checkResult($test);
			if ($result['success'] === 'undef') {
				$logger->warn("-> Nothing has been checked for test: ".$test['name']);
				$passedCount++;
				echo '<td class="undef" title="Nothing has been checked"><a href="', Test::generateTestURL($uri),'">✘</a></td>';
			} else if ($result['success']) {
				$logger->info("-> Test is successful");
				$passedCount++;
				echo '<td class="success" title="', $result['reason'], '"><a href="', Test::generateTestURL($uri),'">✔</a></td>';
			} else {
				$logger->info("-> Test failed");
				$failedCount++;
				echo '<td class="fail" title="', $result['reason'], '"><a href="', Test::generateTestURL($uri),'">✘</a></td>';
			}
			Information::clear();
			Report::clear();
			_flush();
			sleep(Conf::get('test_sleep_between'));
		}
		echo '</tr>';
	}
}

?>
	</tbody>
</table>

<?php echo '<div class="result">Ran a total of <b>', $passedCount + $failedCount, '</b> checks in <b>', time() - $startingTime, '</b> seconds. <b>',
	$failedCount, '</b> checks failed.</div>' ?>

</div>

<script type="text/javascript">
	var td = $$('#infos td[title]');
	td.each(function(element) {
		element.store('tip:text', element.title);
		element.removeProperty('title');
	});
	new Tips(td);
	var resText = $$('div.result');
	var intro = $$('div.top');
	intro.set('html', intro.get('html') + ' ' + resText.get('html'));
</script>

<?php
include(PATH_TEMPLATES.'/html/messages.php');

include(PATH_TEMPLATES.'/html/footer.php');

function _flush (){
    echo(str_repeat(' ',256));
    // check that buffer is actually set before flushing
    if (ob_get_length()){
        @ob_flush();
        @flush();
        @ob_end_flush();
    }
    @ob_start();
}