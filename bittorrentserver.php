<?php
#!/usr/bin/php
/**
 *
 * Name: Bittorrent Server
 * Description: A Bittorrent Server for hubzilla. Alpha. Unstable. For testing.
 * Version: 0.1
 * Depends: Core, libtorrent (Python)
 * Recommends: None
 * Category: Torrents
 * Author: ROD <webmaster@roederstein.de>
 * Maintainer: ROD <webmaster@roederstein.de>
 */
/**
 * Install plugin
 * Install python module libtorrent
 */
function bittorrentserver_install () {
	$appName = "bittorrentserver";
	$basePath = "./addon/".$appName."/";
	$configFileName = $appName.".cfg";
	
	if (!$init = parse_ini_file($basePath.$configFileName, true)) {
		logger("Unable to load config file:".$basePath.$configFileName, LOGGER_DEBUG);
	}
	
	$init = parse_ini_file("./addon/".$appName."/".$appName.".cfg", true);
	
	$init["File"]=$init["File-Default"];
	$init["Tracker"]=$init["Tracker-Default"];
	
	$init["Controller"]["sigterm"]=0;
	$init["Controller"]["sigreload"]=1;
	write_php_ini ($init, "./addon/".$appName."/".$appName.".cfg");
	
	$trackerList = get_config ($appName, 'trackerList');
	$trackerList = "";
	foreach ($init["Tracker-Default"] as $key => $value) {
		$trackerList= $trackerList."\n".$value; #create comma seperated list
	}
	$trackerList= substr($trackerList, 1); #remove first comma
	set_config($appName, 'trackerList', $trackerList);

	$fileList= "";
	foreach ($init["File-Default"] as $key => $value) {
		$fileList = $fileList."\n".$value; #create comma seperated list
	}
	$fileList = substr($fileList, 1); #remove first comma
	set_config($appName, 'fileList', $fileList);
	
	bittorrentserver_run_server();
	logger("Starting server: bittorrentserver_run_server", LOGGER_DEBUG);
	
	return;
}
/**
 *
 */
function bittorrentserver_init () {
}
/**
 *
 */
function bittorrentserver_load(){
	$appName = "bittorrentserver";
	logger("Start bittorrent_run_server", LOGGER_DEBUG);
	register_hook('load_pdl', 'addon/'.$appName.'/'.$appName.'.php', $appName.'_load_pdl');
	register_hook('feature_settings', 'addon/'.$appName.'/'.$appName.'.php', $appName.'_settings');
	register_hook('feature_settings_post', 'addon/'.$appName.'/'.$appName.'.php', $appName.'_settings_post');
	
	/*register_hook('prepare_body', 'addon/bittorrentserver/wtserver.php', 'wtserver_prepare_body', 10);*/
}
/**
 *
 */
function bittorrentserver_unload(){
	$appName = "bittorrentserver";
	logger("Unload bittorrentserver", LOGGER_DEBUG);
	unregister_hook('load_pdl', 'addon/'.$appName.'/'.$appName.'.php', $appName.'_load_pdl');
	unregister_hook('feature_settings', 'addon/'.$appName.'/'.$appName.'.php', $appName.'_settings');
	unregister_hook('feature_settings_post', 'addon/'.$appName.'/'.$appName.'.php', $appName.'_settings_post');
	
	$init = parse_ini_file("./addon/".$appName."/".$appName.".cfg", true);
	$init["Controller"]["sigterm"]=1;
	write_php_ini ($init, "./addon/".$appName."/".$appName.".cfg");
	
	
	#bak
	/*unregister_hook('prepare_body', 'addon/bittorrentserver/bittorrentserver.php', 'bittorrentserver_prepare_body');*/
}
/**
 *
 * Callback from the settings post function.
 * $post contains the global $_POST array.
 * We will make sure we've got a valid user account
 * and that only our own submit button was clicked
 * and if so set our configuration setting for this person.
 *
 */
function bittorrentserver_settings_post($a,$post) {
	if(! local_channel())
		return;
		if($_POST['bittorrentserver-submit'])
			set_pconfig(local_channel(),'bittorrentserver','enable',intval($_POST['bittorrentserver']));
}
/**
 *
 * Called from the Feature Setting form.
 * The second argument is a string in this case, the HTML content region of the page.
 * Add our own settings info to the string.
 *
 * For uniformity of settings pages, we use the following convention
 *     <div class="settings-block">
 *       <h3>title</h3>
 *       .... settings html - many elements will be floated...
 *       <div class="clear"></div> <!-- generic class which clears all floats -->
 *       <input type="submit" name="pluginnname-submit" class="settings-submit" ..... />
 *     </div>
 */
function bittorrentserver_settings(&$a,&$s) {
	
	if(! local_channel())
		return;
		
		/* Add our stylesheet to the page so we can make our settings look nice */
		#head_add_css('/addon/randplace/randplace.css');
		
		/* Get the current state of our config variable */
		$enabled = get_pconfig(local_channel(),'bittorrentserver','enable');
		
		$checked = (($enabled) ? ' checked="checked" ' : '');
		
		/* Add some HTML to the existing form */
		
		$s .= '<div class="settings-block">';
		$s .= '<h3>' . t('bittorrentserver Settings') . '</h3>';
		$s .= '<div id="bittorrentserver-enable-wrapper">';
		$s .= '<label id="bittorrentserver-enable-label" for="bittorrentserver-checkbox">' . t('Enable bittorrentserver Plugin') . '</label>';
		$s .= '<input id="bittorrentserver-checkbox" type="checkbox" name="bittorrentserver" value="1" ' . $checked . '/>';
		$s .= '</div><div class="clear"></div>';
		
		/* provide a submit button */
		
		$s .= '<div class="settings-submit-wrapper" ><input type="submit" name="bittorrentserver-submit" class="settings-submit" value="' . t('Submit') . '" /></div></div>';
		
}
/**
 * Save admin settings
 * @param unknown $a
 */
function bittorrentserver_plugin_admin_post(&$a) {
	$appName = "bittorrentserver";
	
	$trackerList = ((x($_POST, 'trackerList')) ? $_POST['trackerList'] : '');
	$fileList = ((x($_POST, 'fileList')) ? ($_POST['fileList']) : '');
	set_config($appName, 'fileList', $fileList);
	set_config($appName, 'trackerList', $trackerList);
	
	$tA = explode("\n",$trackerList);
	$fA = explode("\n",$fileList);
	
	$init = parse_ini_file("./addon/bittorrentserver/bittorrentserver.cfg", true);
	$init["Tracker"]=$tA;
	$init["File"]=$fA;
	$init["Controller"]["sigreload"]=1;
	write_php_ini ($init, "./addon/bittorrentserver/bittorrentserver.cfg");
	
	info(t('Settings updated.') . EOL);
}
/**
 * $o: Placeholder Array
 *
 * @param unknown $a
 * @param unknown $o
 */
function bittorrentserver_plugin_admin(&$a, &$o) {
	$t = get_markup_template("admin.tpl", "addon/bittorrentserver/");
	$trackerList = get_config ('bittorrentserver', 'trackerList');
	$fileList = get_config ('bittorrentserver', 'fileList');
	$fName = "./addon/bittorrentserver/magnetURI.out";
	$pName = "./addon/bittorrentserver/bittorrentserver.ping";
	$magnetURIList = "";
	$pingMessage = "";
	if ($fp = fopen($fName, 'r')) {
		flock($fp, LOCK_SH);
		while ($row = fgets($fp)) {
			$magnetURIList = $magnetURIList.$row."\n";
		}
		flock($f, LOCK_UN);
		fclose($f);
	}
	
	if ($fp = fopen($pName, 'r')) {
		flock($fp, LOCK_SH);
		while ($row = fgets($fp)) {
			$pingMessage = $pingMessage.$row."\n";
		}
		flock($f, LOCK_UN);
		fclose($f);
	}
	
	$o = replace_macros($t, array(
			'$submit' => t('Submit Settings'),
			'$fileList' => array('fileList', t('Seed-Dateiliste'), $fileList, t('Pfadangaben relativ zum Basisverzeichnis '.$basePath)),
			'$trackerList' => array('trackerList', t('Tracker-Liste'), $trackerList, t('Liste der Bittorrent-Tracker.')),
	));
	// info text field
	$o .= '<h3>MagnetURI</h3><div id="magnetLink"><span style="word-wrap: break-word; word-break: break-all;"><pre>'.$magnetURIList.'</pre></span></div>';
	$o .= '<h3>Server Ping</h3><div id="magnetLink"><span style="word-wrap: break-word; word-break: break-all;"><pre>'.$pingMessage.'</pre></span></div>';
}
/**
 * function unclear!
 * @param unknown $a
 * @param unknown $b
 */
function bittorrentserver_load_pdl($a, &$b) {
	if ($b['module'] === 'bittorrentserver') {
		if (argc() > 1) {
			$b['layout'] = '
                                                [template]none[/template]
        ';
		}
	}
}
/**
 *
 */
function bittorrentserver_run_server () {
	logger("bittorrentserver: Run server", LOGGER_DEBUG);
	
	session_start();
	
	$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("file", "error-output.txt", "a")
	);
	
	$python = "python3";
	$cwd = "./addon/bittorrentserver";
	$pScript = "bittorrentserver.py";
	
	$env = array('some_string' => 'aeiou', 'some_int' => 123);
	print $python." ".$cwd."/".$pScript;
	logger("About to start wtserver.py...", LOGGER_DEBUG);
	$process = proc_open($python." ".$cwd."/".$pScript, $descriptorspec, $pipes, null, $env);
	logger("JUST startet wtserver.py...", LOGGER_DEBUG);
	logger("Resourc Type:".get_resource_type($process));
	
	if (is_resource($process)) {
		
		#fwrite ($pipes[0], "[ADD_FILE]./addon/bittorrentserver/media,sintel.mp4\n");
		#$fBack = fgets($pipes[1]);
		#logger("Feedback1:".$fBack, LOGGER_DEBUG);
		#fwrite ($pipes[0], "[ADD_FILE]./addon/bittorrentserver/media,positionen.mp4\n");
		#$fBack = fgets($pipes[1]);
		#logger("Feedback2:".$fBack, LOGGER_DEBUG);
		#fwrite ($pipes[0], "[ADD_TRACKER]www:Shittracker,lll.PPPTrackr\n");
		#$fBack = fgets($pipes[1]);
		#logger("Feedback3:".$fBack, LOGGER_DEBUG);
		
		#for ($i=0;$i<10;$i++) {
		#	print fgets($pipes[1]); #Print Status
		#}
		
		logger("NOW: bittorrentserver.py as Tracker running...", LOGGER_DEBUG);
	} else {
		echo "No resource available";
		logger("bittorrentserver: Error in run_server", LOGGER_DEBUG);
	}
}
/**
 *
 * @param unknown $a
 * @param unknown $b
 */
function bittorrentserver_prepare_body(&$a,&$b) {
}
/**
 * Write config file for python script
 * @param unknown $array
 * @param unknown $fileName
 */
function write_php_ini($array, $fileName)
{
	$res = array();
	foreach($array as $key => $val)
	{
		if(is_array($val))
		{
			$res[] = "[$key]";
			foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
		}
		else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
	}
	safefilerewrite($fileName, implode("\r\n", $res));
}
/**
 * Subfunction to write_php_ini
 * @param unknown $fileName
 * @param unknown $dataToSave
 */
function safefilerewrite($fileName, $dataToSave)
{    if ($fp = fopen($fileName, 'w'))
{
	$startTime = microtime(TRUE);
	do
	{            $canWrite = flock($fp, LOCK_EX);
	// If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
	if(!$canWrite) usleep(round(rand(0, 100)*1000));
	} while ((!$canWrite)and((microtime(TRUE)-$startTime) < 5));
	
	//file was locked so now we can store information
	if ($canWrite)
	{            fwrite($fp, $dataToSave);
	flock($fp, LOCK_UN);
	}
	fclose($fp);
}
}
?>
