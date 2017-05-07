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
function wtserver2_install () {
	$appName = "bittorrentserver";
	$basePath = "./addon/".$appName."/";
	$configFileName = $appName.".cfg";
		
	if (!$init = parse_ini_file($basePath.$configFileName, true)) {
		logger("Unable to load config file:".$basePath.$configFileName, LOGGER_DEBUG);
	}
	
	$init = parse_ini_file("./addon/".$appName."/".$appName.".cfg", true);
	$init["Controller"]["sigterm"]=0;
	$init["Controller"]["sigreload"]=1;
	write_php_ini ($init, "./addon/".$appName."/".$appName.".cfg");
	
	$trackerList = get_config ($appName, 'trackerList');
	if (($trackerList=="")||($trackerList==0)) {
		$trackerList = "";
		foreach ($init["Tracker-Default"] as $key => $value) {
			$trackerList= $trackerList."\n".$value; #create comma seperated list
		}
		$trackerList= substr($trackerList, 1); #remove first comma
		set_config($appName, 'trackerList', $trackerList);
	}	
	
	if (!$fileList = get_config ($appName, 'fileList')) {
		$fileList= "";
		foreach ($init["File-Default"] as $key => $value) {
			$fileList = $fileList."\n".$value; #create comma seperated list
		}
		$fileList = substr($fileList, 1); #remove first comma
		set_config($appName, 'fileList', $fileList);
	}
	
	bittorrentserver_run_server();
	logger("Starting server: bittorrentserver_run_server", LOGGER_DEBUG);
	
	return;
}
/**
 * 
 */
function wtserver2_init () {	
}
/**
 * 
 */
function wtserver2_load(){
	$appName = "bittorrentserver";
	logger("Start bittorrent_run_server", LOGGER_DEBUG);
	register_hook('load_pdl', 'addon/'.$appName.'/'.$appName.'.php', $appName.'_load_pdl');
	register_hook('feature_settings', 'addon/'.$appName.'/'.$appName.'.php', $appName.'_settings');
	register_hook('feature_settings_post', 'addon/'.$appName.'/'.$appName.'.php', $appName.'_settings_post');
			
	/*register_hook('prepare_body', 'addon/wtserver2/wtserver.php', 'wtserver_prepare_body', 10);*/
}
/**
 * 
 */
function wtserver2_unload(){
	$appName = "bittorrentserver";
	logger("Unload bittorrentserver", LOGGER_DEBUG);
	unregister_hook('load_pdl', 'addon/'.$appName.'/'.$appName.'.php', $appName.'_load_pdl');
	unregister_hook('feature_settings', 'addon/'.$appName.'/'.$appName.'.php', $appName.'_settings');
	unregister_hook('feature_settings_post', 'addon/'.$appName.'/'.$appName.'.php', $appName.'_settings_post');
	
	#$init = parse_ini_file($basePath.$configFileName, true);
	$init = parse_ini_file("./addon/".$appName."/".$appName.".cfg", true);
	$init["Controller"]["sigterm"]=1;
	write_php_ini ($init, "./addon/".$appName."/".$appName.".cfg");
	
	
	#bak
	/*unregister_hook('prepare_body', 'addon/wtserver2/wtserver.php', 'wtserver_prepare_body');*/
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
function wtserver2_settings_post($a,$post) {
	if(! local_channel())
		return;
		if($_POST['wtserver2-submit'])
			set_pconfig(local_channel(),'wtserver2','enable',intval($_POST['wtserver2']));
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
function wtserver2_settings(&$a,&$s) {
	
	if(! local_channel())
		return;
		
		/* Add our stylesheet to the page so we can make our settings look nice */
		#head_add_css('/addon/randplace/randplace.css');
		
		/* Get the current state of our config variable */
		$enabled = get_pconfig(local_channel(),'wtserver2','enable');
		
		$checked = (($enabled) ? ' checked="checked" ' : '');
		
		/* Add some HTML to the existing form */
		
		$s .= '<div class="settings-block">';
		$s .= '<h3>' . t('wtserver2 Settings') . '</h3>';
		$s .= '<div id="wtserver2-enable-wrapper">';
		$s .= '<label id="wtserver2-enable-label" for="wtserver2-checkbox">' . t('Enable wtserver2 Plugin') . '</label>';
		$s .= '<input id="wtserver2-checkbox" type="checkbox" name="wtserver2" value="1" ' . $checked . '/>';
		$s .= '</div><div class="clear"></div>';
		
		/* provide a submit button */
		
		$s .= '<div class="settings-submit-wrapper" ><input type="submit" name="wtserver2-submit" class="settings-submit" value="' . t('Submit') . '" /></div></div>';
				
}
/**
 * Save admin settings
 * @param unknown $a
 */
function wtserver2_plugin_admin_post(&$a) {
	$appName = "wtserver2";
	
	$trackerList = ((x($_POST, 'trackerList')) ? $_POST['trackerList'] : '');
	$fileList = ((x($_POST, 'fileList')) ? ($_POST['fileList']) : '');
	set_config($appName, 'fileList', $fileList);
	set_config($appName, 'trackerList', $trackerList);
	
	$tA = explode("\n",$trackerList);
	$fA = explode("\n",$fileList);
	for ($i=0;$i<count($tA);$i++) {
		$tA[$i] = trim($tA[$i]);
	}
	for ($i=0;$i<count($fA);$i++) {
		$fA[$i] = trim($fA[$i]," \t\n\r\0\x0B\"");
	}
		
	$init = parse_ini_file("./addon/wtserver2/wtserver2.cfg", true);
	$init["Tracker"]=$tA;
	$init["File"]=$fA;
	$init["Controller"]["sigreload"]=1;
	write_php_ini ($init, "./addon/wtserver2/wtserver2.cfg");
	
	info(t('Settings updated.') . EOL);
}
/**
 * $o: Placeholder Array
 * 
 * @param unknown $a
 * @param unknown $o
 */
function wtserver2_plugin_admin(&$a, &$o) {
	$t = get_markup_template("admin.tpl", "addon/wtserver2/");	
	$trackerList = get_config ('wtserver2', 'trackerList');
	$fileList = get_config ('wtserver2', 'fileList');
	$fName = "./addon/wtserver2/magnetURI.out";
	$magnetURIList="";
	if ($fp = fopen($fName, 'r')) {
		flock($fp, LOCK_SH);
		$data = array();
		while ($row = fgets($fp)) {
			$data[] = $row;
			$magnetURIList = $magnetURIList.$row."\n";
		}
		flock($f, LOCK_UN);
		fclose($f);
	}
	
	$o = replace_macros($t, array(
						'$submit' => t('Submit Settings'),
						'$reloadMagnetLinks' => t('Reload Magnetlinks'),
			            '$fileList' => array('fileList', t('Seed-Dateiliste'), $fileList, t('Pfadangaben relativ zum Basisverzeichnis '.$basePath)),
			            '$trackerList' => array('trackerList', t('Tracker-Liste'), $trackerList, t('Liste der Bittorrent-Tracker.')),
	));	
	// info text field
	$o .= '<h3>MagnetURI</h3><div id="magnetLink"><span style="word-wrap: break-word; word-break: break-all;"><pre>'.$magnetURIList.'</pre></span></div>';
}
/**
 * function unclear!
 * @param unknown $a
 * @param unknown $b
 */
function wtserver2_load_pdl($a, &$b) {
	if ($b['module'] === 'wtserver2') {
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
function wtserver2_run_server () {
	logger("wtserver: Run server", LOGGER_DEBUG);
	
	session_start();
	
	$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("file", "error-output.txt", "a")
	);
	
	$python = "python3";
	$cwd = "./addon/wtserver2";
	$pScript = "wtserver.py";
	
	$env = array('some_string' => 'aeiou', 'some_int' => 123);
	print $python." ".$cwd."/".$pScript;
	logger("About to start wtserver.py...", LOGGER_DEBUG);
	$process = proc_open($python." ".$cwd."/".$pScript, $descriptorspec, $pipes, null, $env);
	logger("JUST startet wtserver.py...", LOGGER_DEBUG);
	logger("Resourc Type:".get_resource_type($process));
	
	#$_SESSION ['process'] = $process;
	
	if (is_resource($process)) {
		
		fwrite ($pipes[0], "[ADD_FILE]./addon/wtserver2/media,sintel.mp4\n");
		#print fgets($pipes[1]);
		$fBack = fgets($pipes[1]);
		logger("Feedback1:".$fBack, LOGGER_DEBUG);
		fwrite ($pipes[0], "[ADD_FILE]./addon/wtserver2/media,positionen.mp4\n");
		#print fgets($pipes[1]);
		$fBack = fgets($pipes[1]);
		logger("Feedback2:".$fBack, LOGGER_DEBUG);
		fwrite ($pipes[0], "[ADD_TRACKER]www:Shittracker,lll.PPPTrackr\n");
		$fBack = fgets($pipes[1]);
		logger("Feedback3:".$fBack, LOGGER_DEBUG);
		
		#$run = true;
		#while($run) {
		for ($i=0;$i<10;$i++) {
			print fgets($pipes[1]); #Print Status
		}
		
		#fclose($pipes[1]);
		#fclose($pipes[0]);
		#$return_value = proc_close($process);
		
		#echo "command returned $return_value\n";
		logger("NOW: wtserver.py as Tracker running...", LOGGER_DEBUG);
	} else {
		echo "No resource available";
		logger("wtserver: Error in run_server", LOGGER_DEBUG);
	}
}
/**
 * 
 * @param unknown $a
 * @param unknown $b
 */
function wtserver2_prepare_body(&$a,&$b) {
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
