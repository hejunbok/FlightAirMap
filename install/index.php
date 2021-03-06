<?php
@session_start();
header('Content-Encoding: none;');
if (ob_get_level() == 0) ob_start();
ob_implicit_flush(true);
ob_end_flush();
require_once('class.create_db.php');
require_once('class.update_schema.php');
require_once('class.settings.php');
$title="Install";
require('header.php');
require('../require/settings.php');

if ($globalInstalled && !isset($_SESSION['install'])) {
	print '<div class="info column"><p>You need to change $globalInstalled in settings.php to FALSE if you want to access setup again.</p></div>';
	require('../footer.php');
	exit;
}

$writable = false;
if (!is_writable('../require/settings.php')) {
	print '<div class="info column"><p><strong>The file <i>require/settings</i> must be writable.</strong></p></div>';
	require('../footer.php');
	exit;
}
if (!set_time_limit(0)) {
	print '<div class="info column"><p><strong>You may need to update the maximum execution time.</strong></p></div>';
}
if (preg_match('/nginx/',$_SERVER["SERVER_SOFTWARE"])) {
	print '<div class="info column"><p><strong>You seems to use nginx. This can cause some problem when populating DB, if this fail, you should use <i>install/install_db.php</i> or <i>install/install_db.sh</i> to finish installation.</strong></p></div>';
}
/*
if (!function_exists('pcntl_fork')) {
	print '<div class="info column"><p><strong>pcntl_fork is not available. Schedules will not be fetched.</strong></p></div>';
}
*/
$error = array();
if (!extension_loaded('SimpleXML')) {
	$error[] = "SimpleXML is not loaded.";
}
if (!extension_loaded('dom')) {
	$error[] = "Dom is not loaded. Needed for aircraft schedule";
}
if (!extension_loaded('PDO')) {
	$error[] = "PDO is not loaded.";
}
if (!extension_loaded('pdo_sqlite')) {
	$error[] = "PDO SQLite is not loaded. Needed to populate database for SBS.";
}
if (!extension_loaded('zip')) {
	$error[] = "ZIP is not loaded. Needed to populate database for SBS.";
}
if (!extension_loaded('json')) {
	$error[] = "Json is not loaded. Needed for aircraft schedule and bitly.";
}
if (!extension_loaded('curl')) {
	$error[] = "Curl is not loaded.";
}

if (count($error) > 0) {
	print '<div class="info column"><ul>';
	foreach ($error as $err) {
		print '<li>'.$err.'</li>';
	}
	print '</ul>You <strong>must</strong> add these modules.</div>';
	require('../footer.php');
        exit;
}

if (!isset($_SESSION['install']) && !isset($_POST['dbtype']) && (count($error) == 0)) {
	?>
	<div class="info column">
	<form method="post">
		<fieldset>
		<legend>Database configuration</legend>
		<p>
			<label for="dbtype">Database type</label>
			<select name="dbtype" id="dbtype">
			<option value="mysql" <?php if (isset($globalDBdriver) && $globalDBdriver == 'mysql') { ?>selected="selected" <?php } ?>>MySQL</option>
			<option value="pgsql" <?php if (isset($globalDBdriver) && $globalDBdriver == 'pgsql') { ?>selected="selected" <?php } ?>>PostgreSQL (alpha support)</option>
			</select>
		</p>
		<p>
			<label for="createdb">Create database</label>
			<input type="checkbox" name="createdb" id="createdb" value="createdb" onClick="create_database_js()" />
		</p>
		<div id="createdb_data">
		<p>
			<label for="dbroot">Database admin user</label>
			<input type="text" name="dbroot" id="dbroot" />
		</p>
		<p>
			<label for="dbrootpass">Database admin password</label>
			<input type="password" name="dbrootpass" id="dbrootpass" />
		</p>
		</div>
		<p>
			<label for="dbhost">Database hostname</label>
			<input type="text" name="dbhost" id="dbhost" value="<?php if (isset($globalDBhost)) print $globalDBhost; ?>" />
		</p>
		<p>
			<label for="dbname">Database name</label>
			<input type="text" name="dbname" id="dbname" value="<?php if (isset($globalDBname)) print $globalDBname; ?>" />
		</p>
		<p>
			<label for="dbuser">Database user</label>
			<input type="text" name="dbuser" id="dbuser" value="<?php if (isset($globalDBuser)) print $globalDBuser; ?>" />
		</p>
		<p>
			<label for="dbuserpass">Database user password</label>
			<input type="password" name="dbuserpass" id="dbuserpass" value="<?php if (isset($globalDBpass)) print $globalDBpass; ?>" />
		</p>
		</fieldset>
		<fieldset>
		<legend>Site configuration</legend>
		<p>
			<label for="sitename">Site name</label>
			<input type="text" name="sitename" id="sitename" value="<?php if (isset($globalName)) print $globalName; ?>" />
		</p>
		<p>
			<label for="siteurl">Site URL</label>
			<input type="text" name="siteurl" id="siteurl" value="<?php if (isset($globalURL)) print $globalURL; ?>" />
			Can be null.
		</p>
		<p>
			<label for="timezone">Timezone</label>
			<input type="text" name="timezone" id="timezone" value="<?php if (isset($globalTimezone)) print $globalTimezone; ?>" />
			ex : UTC, Europe/Paris,...
		</p>
		<p>
			<label for="language">Language</label>
			<input type="text" name="language" id="language" value="<?php if (isset($globalLanguage)) print $globalLanguage; ?>" />
			Used only when link to wikipedia for now. Can be EN,DE,FR,...
		</p>
		</fieldset>
		<fieldset>
		<legend>Map provider</legend>
		<p>
			<label for="mapprovider">map Provider</label>
			<select name="mapprovider" id="mapprovider" onClick="map_provider_js()";>
			<option value="OpenStreetMap" <?php if (isset($globalMapProvider) && $globalMapProvider == 'OpenStreetMap') { ?>selected="selected" <?php } ?>>OpenStreetMap</option>
			<option value="Mapbox" <?php if (isset($globalMapProvider) && $globalMapProvider == 'Mapbox') { ?>selected="selected" <?php } ?>>Mapbox</option>
			<option value="MapQuest-OSM" <?php if (isset($globalMapProvider) && $globalMapProvider == 'MapQuest-OSM') { ?>selected="selected" <?php } ?>>MapQuest-OSM</option>
			<option value="MapQuest-Aerial" <?php if (isset($globalMapProvider) && $globalMapProvider == 'MapQuest-Aerial') { ?>selected="selected" <?php } ?>>MapQuest-Aerial</option>
			</select>
		</p>
		<div id="mapbox_data">
		<p>
			<label for="mapboxid">Mapbox id</label>
			<input type="text" name="mapboxid" id="mapboxid" value="<?php if (isset($globalMapboxId)) print $globalMapboxId; ?>" />
		</p>
		<p>
			<label for="mapboxtoken">Mapbox token</label>
			<input type="text" name="mapboxtoken" id="mapboxtoken" value="<?php if (isset($globalMapboxToken)) print $globalMapboxToken; ?>" />
		</p>
		</div>
		</fieldset>
		<fieldset>
		<legend>Coverage area</legend>
		<p>
			<label for="latitudemax">The maximum latitude (north)</label>
			<input type="text" name="latitudemax" id="latitudemax" value="<?php if (isset($globalLatitudeMax)) print $globalLatitudeMax; ?>" />
		</p>
		<p>
			<label for="latitudemin">The minimum latitude (south)</label>
			<input type="text" name="latitudemin" id="latitudemin" value="<?php if (isset($globalLatitudeMin)) print $globalLatitudeMin; ?>" />
		</p>
		<p>
			<label for="longitudemax">The maximum longitude (west)</label>
			<input type="text" name="longitudemax" id="longitudemax" value="<?php if (isset($globalLongitudeMax)) print $globalLongitudeMax; ?>" />
		</p>
		<p>
			<label for="longitudemin">The minimum longitude (east)</label>
			<input type="text" name="longitudemin" id="longitudemin" value="<?php if (isset($globalLongitudeMin)) print $globalLongitudeMin; ?>" />
		</p>
		<p>
			<label for="latitudecenter">The latitude center</label>
			<input type="text" name="latitudecenter" id="latitudecenter" value="<?php if (isset($globalCenterLatitude)) print $globalCenterLatitude; ?>" />
		</p>
		<p>
			<label for="longitudecenter">The longitude center</label>
			<input type="text" name="longitudecenter" id="longitudecenter" value="<?php if (isset($globalCenterLongitude)) print $globalCenterLongitude; ?>" />
		</p>
		<p>
			<label for="squawk_country">Country for squawk usage</label>
			<input type="text" name="squawk_country" id="squawk_country" value="<?php if (isset($globalSquawkCountry)) print $globalSquawkCountry; ?>" />
			UK, FR or let it blank for now
		</p>
		</fieldset>
		<fieldset>
		<legend>Zone of interest</legend>
		<p><i>Only put in DB flights that are inside a circle</i></p>
		<p>
			<label for="latitude">Center latitude</label>
			<input type="text" name="zoilatitude" id="latitude" value="<?php if (isset($globalDistanceIgnore['latitude'])) echo $globalDistanceIgnore['latitude']; ?>" />
		</p>
		<p>
			<label for="longitude">Center longitude</label>
			<input type="text" name="zoilongitude" id="longitude" value="<?php if (isset($globalDistanceIgnore['longitude'])) echo $globalDistanceIgnore['longitude']; ?>" />
		</p>
		<p>
			<label for="Distance">Distance (in km)</label>
			<input type="text" name="zoidistance" id="distance" value="<?php if (isset($globalDistanceIgnore['distance'])) echo $globalDistanceIgnore['distance']; ?>" />
		</p>
		</fieldset>

		<fieldset>
		<legend>Data source</legend>
		<p>
			<label>Choose data source</label>
<!--
			<input type="radio" name="datasource" id="flightaware" value="flightaware" onClick="datasource_js()" <?php if (isset($globalFlightAware) && $globalFlightAware) { ?>checked="checked" <?php } ?>/>
			<label for="flightaware">FlightAware (not tested, no more supported no data feed available for test)</label>
-->
			<input type="radio" name="datasource" id="sbs" value="sbs" onClick="datasource_js()" <?php if (isset($globalSBS1) && $globalSBS1) { ?>checked="checked" <?php } ?> />
			<label for="sbs">ADS-B, SBS-1 format (dump1090 or SBS-1 compatible format)</label>
			<input type="checkbox" name="acars" id="acars" value="acars" onClick="datasource_js()" <?php if (isset($globalACARS) && $globalACARS) { ?>checked="checked" <?php } ?> />
			<label for="acars">ACARS</label>
		</p>
<!--
		<div id="flightaware_data">
		<p>
			<label for="flightawareusername">FlightAware username</label>
			<input type="text" name="flightawareusername" id="flightawareusername" value="<?php if (isset($globalFlightAwareUsername)) print $globalFlightAwareUsername; ?>" />
		</p>
		<p>
			<label for="flightawarepassword">FlightAware password/API key</label>
			<input type="text" name="flightawarepassword" id="flightawarepassword" value="<?php if (isset($globalFlightAwarePassword)) print $globalFlightAwarePassword; ?>" />
		</p>
		</div>
-->
		<div id="sbs_data">
		<?php
		    $displaysbs = true;
		    if (isset($globalSBS1Hosts)) {
			if (! is_array($globalSBS1Hosts)) {
			    $hostport = explode(':',$globalSBS1Hosts);
			    if (count($hostport) == 2) {
				$globalSBS1Host = $hostport[0];
				$globalSBS1Port = $hostport[1];
			    }
			} elseif (count($globalSBS1Hosts) == 1) {
			    if (filter_var($globalSBS1Hosts[0],FILTER_VALIDATE_URL)) {
			        $globalSBS1url = $globalSBS1Hosts[0];
			    } else {
				$hostport = explode(':',$globalSBS1Hosts[0]);
				if (count($hostport) == 2) {
				    $globalSBS1Host = $hostport[0];
				    $globalSBS1Port = $hostport[1];
				}
			    }
			} else $displaysbs = false;
		    }
		    
		    if ($displaysbs) {
		?>
		<p>
			<label for="sbshost">SBS-1 host</label>
			<input type="text" name="sbshost" id="sbshost" value="<?php if (isset($globalSBS1Host)) print $globalSBS1Host; ?>" />
		</p>
		<p>
			<label for="sbsport">SBS-1 port</label>
			<input type="text" name="sbsport" id="sbsport" value="<?php if (isset($globalSBS1Port)) print $globalSBS1Port; ?>" />
		</p>
		<p>
			<label for="sbstimeout">SBS-1 timeout</label>
			<input type="text" name="sbstimeout" id="sbstimeout" value="<?php if (isset($globalSBS1TimeOut)) print $globalSBS1TimeOut; ?>" />
		</p>
		<b>OR</b>
		<p>
			<label for="sbsurl">SBS-1 URL</label>
			<input type="text" name="sbsurl" id="sbsurl" value="<?php if (isset($globalSBS1url)) print $globalSBS1url; ?>" />
			 (can be deltadb.txt or aircraftlist.json url to Radarcape, or <i>/action.php/acars/data</i> of phpvms, or wazzup file format)
		</p>
		<?php
		    }
		?>
		</div>
		<div id="acars_data">
		<p>
			<label for="acarshost">ACARS UDP host</label>
			<input type="text" name="acarshost" id="acarshost" value="<?php if (isset($globalACARSHost)) print $globalACARSHost; ?>" />
		</p>
		<p>
			<label for="acarsport">ACARS UDP port</label>
			<input type="text" name="acarsport" id="acarsport" value="<?php if (isset($globalACARSPort)) print $globalACARSPort; ?>" />
		</p>
		</div>
		</fieldset>
		
		<fieldset>
		<legend>Optional configuration</legend>
		<p>
			<label for="bitly">Bit.ly access token api (used in search page)</label>
			<input type="text" name="bitly" id="bitly" value="<?php if (isset($globalBitlyAccessToken)) print $globalBitlyAccessToken; ?>" />
		</p>
		<p>
			<label for="britishairways">British Airways API Key</label>
			<input type="text" name="britishairways" id="britishairways" value="<?php if (isset($globalBritishAirwaysKey)) print $globalBritishAirwaysKey; ?>" />
		</p>
		<p>
			<label for="waypoints">Add Waypoints and Airspace data (about 40Mio in DB) <i>Not yet available for PostgreSQL</i></label>
			<input type="checkbox" name="waypoints" id="waypoints" value="waypoints" checked="checked" />
		</p>
		<p>
			<label for="archive">Archive all SBS data</label>
			<input type="checkbox" name="archive" id="archive" value="archive"<?php if ((isset($globalArchive) && $globalArchive) || !isset($globalArchive)) { ?> checked="checked"<?php } ?> />
		</p>
		<p>
			<label for="daemon">Use cron-sbs as daemon</label>
			<input type="checkbox" name="daemon" id="daemon" value="daemon"<?php if ((isset($globalDaemon) && $globalDaemon) || !isset($globalDaemon)) { ?> checked="checked"<?php } ?> />
		</p>
		<p>
			<label for="fork">Allow processes fork</label>
			<input type="checkbox" name="fork" id="fork" value="fork"<?php if ((isset($globalFork) && $globalFork) || !isset($globalFork)) { ?> checked="checked"<?php } ?> />
		</p>
		<p>
			<label for="refresh">Show flights from xxx seconds</label>
			<input type="text" name="refresh" id="refresh" value="<?php if (isset($globalLiveInterval)) echo $globalLiveInterval; else echo '200'; ?>" />
		</p>
		<p>
			<label for="maprefresh">Live map refresh (in seconds)</label>
			<input type="text" name="maprefresh" id="maprefresh" value="<?php if (isset($globalMapRefresh)) echo $globalMapRefresh; else echo '30'; ?>" />
		</p>
		</fieldset>
		
		<input type="submit" name="submit" value="Create/Update database & write setup" />
	</form>
	<p>
	    If it fails to populate tables, you can run inside console <i>install/install_db.php</i> or <i>install/install_db.sh</i>.
	</p>
<?php
	require('../footer.php');
        exit;
}
	
$settings = array();
$error = '';

if (isset($_POST['dbtype'])) {
	$dbtype = filter_input(INPUT_POST,'dbtype',FILTER_SANITIZE_STRING);
	$dbroot = filter_input(INPUT_POST,'dbroot',FILTER_SANITIZE_STRING);
	$dbrootpass = filter_input(INPUT_POST,'dbrootpass',FILTER_SANITIZE_STRING);
	$dbname = filter_input(INPUT_POST,'dbname',FILTER_SANITIZE_STRING);
	$dbuser = filter_input(INPUT_POST,'dbuser',FILTER_SANITIZE_STRING);
	$dbuserpass = filter_input(INPUT_POST,'dbuserpass',FILTER_SANITIZE_STRING);
	$dbhost = filter_input(INPUT_POST,'dbhost',FILTER_SANITIZE_STRING);

	if ($dbtype == 'mysql' && !extension_loaded('pdo_mysql')) $error .= 'Mysql driver for PDO must be loaded';
	if ($dbtype == 'pgsql' && !extension_loaded('pdo_pgsql')) $error .= 'PosgreSQL driver for PDO must be loaded';
	
	if ($error == '' && isset($_POST['createdb']) && $dbname != '' && $dbuser != '' && $dbuserpass != '') {
		if ($dbroot != '' && $dbrootpass != '') {
			$result = create_db::create_database($dbroot,$dbrootpass,$dbuser,$dbuserpass,$dbname,$dbtype,$dbhost);
			if ($result != true) $error .= $result;
		}
		if ($error == '') {
			//$error .= create_db::import_all_db('../db/');
			$settings = array_merge($settings,array('globalDBdriver' => $dbtype,'globalDBhost' => $dbhost,'globalDBuser' => $dbuser,'globalDBpass' => $dbuserpass,'globalDBname' => $dbname));
		}
	}

	$sitename = filter_input(INPUT_POST,'sitename',FILTER_SANITIZE_STRING);
	$siteurl = filter_input(INPUT_POST,'siteurl',FILTER_SANITIZE_STRING);
	$timezone = filter_input(INPUT_POST,'timezone',FILTER_SANITIZE_STRING);
	$language = filter_input(INPUT_POST,'language',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalName' => $sitename,'globalURL' => $siteurl, 'globalTimezone' => $timezone,'globalLanguage' => $language));

	$mapprovider = filter_input(INPUT_POST,'mapprovider',FILTER_SANITIZE_STRING);
	$mapboxid = filter_input(INPUT_POST,'mapboxid',FILTER_SANITIZE_STRING);
	$mapboxtoken = filter_input(INPUT_POST,'mapboxtoken',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalMapProvider' => $mapprovider,'globalMapboxId' => $mapboxid,'globalMapboxToken' => $mapboxtoken));
	
	$latitudemax = filter_input(INPUT_POST,'latitudemax',FILTER_SANITIZE_STRING);
	$latitudemin = filter_input(INPUT_POST,'latitudemin',FILTER_SANITIZE_STRING);
	$longitudemax = filter_input(INPUT_POST,'longitudemax',FILTER_SANITIZE_STRING);
	$longitudemin = filter_input(INPUT_POST,'longitudemin',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalLatitudeMax' => $latitudemax,'globalLatitudeMin' => $latitudemin,'globalLongitudeMax' => $longitudemax,'globalLongitudeMin' => $longitudemin));

	$squawk_country = filter_input(INPUT_POST,'squawk_country',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalSquawkCountry' => $squawk_country));

	$latitudecenter = filter_input(INPUT_POST,'latitudecenter',FILTER_SANITIZE_STRING);
	$longitudecenter = filter_input(INPUT_POST,'longitudecenter',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalCenterLatitude' => $latitudecenter,'globalCenterLongitude' => $longitudecenter));

	$datasource = filter_input(INPUT_POST,'datasource',FILTER_SANITIZE_STRING);
	if ($datasource == 'flightaware') {
		$settings = array_merge($settings,array('globalFlightAware' => 'TRUE','globalSBS1' => 'FALSE'));
	} else {
		$settings = array_merge($settings,array('globalFlightAware' => 'FALSE','globalSBS1' => 'TRUE'));
	}

	$acars = filter_input(INPUT_POST,'acars',FILTER_SANITIZE_STRING);
	if ($acars == 'acars') {
		$settings = array_merge($settings,array('globalACARS' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalACARS' => 'FALSE'));
	}

	$flightawareusername = filter_input(INPUT_POST,'flightawareusername',FILTER_SANITIZE_STRING);
	$flightawarepassword = filter_input(INPUT_POST,'flightawarepassword',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalFlightAwareUsername' => $flightawareusername,'globalFlightAwarePassword' => $flightawarepassword));
	
	$sbshost = filter_input(INPUT_POST,'sbshost',FILTER_SANITIZE_STRING);
	$sbsport = filter_input(INPUT_POST,'sbsport',FILTER_SANITIZE_NUMBER_INT);
	$sbstimeout = filter_input(INPUT_POST,'sbstimeout',FILTER_SANITIZE_NUMBER_INT);
	$sbsurl = filter_input(INPUT_POST,'sbsurl',FILTER_SANITIZE_URL);
	if (isset($globalSBS1Hosts) && is_array($globalSBS1Hosts) && count($globalSBS1Hosts) > 1) {
		$settings = array_merge($settings,array('globalSBS1Hosts' => $globalSBS1Hosts,'globalSBS1TimeOut' => $sbstimeout));
	} elseif ($sbshost != '') {
		$settings = array_merge($settings,array('globalSBS1Hosts' => array($sbshost.':'.$sbsport),'globalSBS1TimeOut' => $sbstimeout));
	} elseif ($sbsurl != '') {
		$settings = array_merge($settings,array('globalSBS1Hosts' => array($sbsurl)));
	}

	$acarshost = filter_input(INPUT_POST,'acarshost',FILTER_SANITIZE_STRING);
	$acarsport = filter_input(INPUT_POST,'acarsport',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalACARSHost' => $acarshost,'globalACARSPort' => $acarsport));

	$bitly = filter_input(INPUT_POST,'bitly',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalBitlyAccessToken' => $bitly));

	$zoilatitude = filter_input(INPUT_POST,'zoilatitude',FILTER_SANITIZE_STRING);
	$zoilongitude = filter_input(INPUT_POST,'zoilongitude',FILTER_SANITIZE_STRING);
	$zoidistance = filter_input(INPUT_POST,'zoidistance',FILTER_SANITIZE_NUMBER_INT);
	if ($zoilatitude != '' && $zoilongitude != '' && $zoidistance != '') {
	$settings = array_merge($settings,array('globalDistanceIgnore' => array('latitude' => $zoilatitude,'longitude' => $zoilongitude,'distance' => $zoidistance)));
	} else $settings = array_merge($settings,array('globalDistanceIgnore' => array()));

	$refresh = filter_input(INPUT_POST,'refresh',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalLiveInterval' => $refresh));
	$maprefresh = filter_input(INPUT_POST,'maprefresh',FILTER_SANITIZE_NUMBER_INT);
	$settings = array_merge($settings,array('globalMapRefresh' => $maprefresh));

	$britishairways = filter_input(INPUT_POST,'britishairways',FILTER_SANITIZE_STRING);
	$settings = array_merge($settings,array('globalBritishAirwaysKey' => $britishairways));

	// Create in settings.php keys not yet configurable if not already here
	//if (!isset($globalImageBingKey)) $settings = array_merge($settings,array('globalImageBingKey' => ''));
	if (!isset($globalDebug)) $settings = array_merge($settings,array('globalDebug' => 'TRUE'));

	$archive = filter_input(INPUT_POST,'archive',FILTER_SANITIZE_STRING);
	if ($archive == 'archive') {
		$settings = array_merge($settings,array('globalArchive' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalArchive' => 'FALSE'));
	}
	$daemon = filter_input(INPUT_POST,'daemon',FILTER_SANITIZE_STRING);
	if ($daemon == 'daemon') {
		$settings = array_merge($settings,array('globalDaemon' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalDaemon' => 'FALSE'));
	}
	$fork = filter_input(INPUT_POST,'fork',FILTER_SANITIZE_STRING);
	if ($fork == 'fork') {
		$settings = array_merge($settings,array('globalFork' => 'TRUE'));
	} else {
		$settings = array_merge($settings,array('globalFork' => 'FALSE'));
	}

	if (!isset($globalTransaction)) $settings = array_merge($settings,array('globalTransaction' => 'TRUE'));

	// Set some defaults values...
	if (!isset($globalAircraftImageSources)) {
	    $globalAircraftImageSources = array('wikimedia','deviantart','flickr','bing','jetphotos','planepictures','planespotters');
	    $settings = array_merge($settings,array('globalAircraftImageSources' => $globalAircraftImageSources));
	}

	if (!isset($globalSchedulesSources)) {
	    $globalSchedulesSources = array('flightmapper','costtotravel','flightradar24','flightaware');
    	    $settings = array_merge($settings,array('globalSchedulesSources' => $globalSchedulesSources));
    	}

	$settings = array_merge($settings,array('globalInstalled' => 'TRUE'));

	if ($error == '') settings::modify_settings($settings);
	if ($error != '') {
		print '<div class="info column">'.$error.'</div>';
		require('../footer.php');
		exit;
	} else {
		if (isset($_POST['waypoints'])) $_SESSION['waypoints'] = 1;
		$_SESSION['install'] = 'database_import';
		//require('../footer.php');
		print '<div class="info column"><ul>';
		
		if (isset($_POST['createdb'])) {
			$_SESSION['done'] = array('Create database','Write configuration');
			print '<li>Create database....<strong>SUCCESS</strong></li>';
		} else $_SESSION['done'] = array('Write configuration');
		print '<li>Write configuration....<img src="../images/loading.gif" /></li></ul></div>';
		flush();
		@ob_flush();
		sleep(10);
		print "<script>window.location = 'index.php?".rand()."&next=".$_SESSION['install']."';</script>";
//		header("Location: index.php?".rand());
//		require('../footer.php');
	}
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'database_import') {
	unset($_SESSION['install']);
	if (update_schema::check_version(false) == '0') {
		print '<div class="info column"><ul>';
		foreach ($_SESSION['done'] as $done) {
		    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
		}
		print '<li>Create and import tables....<img src="../images/loading.gif" /></li></ul></div>';
		flush();
		@ob_flush();
		if ($globalDBdriver == 'mysql') {
		    $error .= create_db::import_all_db('../db/');
		} elseif ($globalDBdriver == 'pgsql') {
		    $error .= create_db::import_all_db('../db/pgsql/');
		}
		if ($error != '') {
			print '<div class="info column"><span class="error"><strong>Error</strong>'.$error.'</span></div>';
			require('../footer.php');
                        exit;
		}
		$_SESSION['done'] = array_merge($_SESSION['done'],array('Create and import tables'));
		if ($globalSBS1) {
			$_SESSION['install'] = 'populate';
		} else $_SESSION['install'] = 'finish';
	} else {
		print '<div class="info column"><ul>';
		foreach ($_SESSION['done'] as $done) {
		    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
		}
		print '<li>Update schema....<img src="../images/loading.gif" /></li></ul></div>';
		flush();
		@ob_flush();
		$error .= update_schema::check_version(true);
		if ($error != '') {
			print '<div class="info column"><span class="error"><strong>Error</strong>'.$error.'</span></div>';
			require('../footer.php');
                        exit;
		}
		$_SESSION['done'] = array_merge($_SESSION['done'],array('Update schema'));
		$_SESSION['install'] = 'finish';
		
	}
	sleep(2);
	print "<script>window.location = 'index.php?".rand()."&next=".$_SESSION['install']."';</script>";
//	require('../footer.php');
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'waypoints') {
	unset($_SESSION['install']);
	print '<div class="info column"><ul>';
	foreach ($_SESSION['done'] as $done) {
	    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
	}
	print '<li>Populate waypoints database....<img src="../images/loading.gif" /></li></ul></div>';
	flush();
	@ob_flush();

	include_once('class.update_db.php');
	update_db::update_waypoints();
	$_SESSION['done'] = array_merge($_SESSION['done'],array('Populate waypoints database'));

	$_SESSION['install'] = 'airspace';
//	ob_end_clean();
//	header("Location: index.php?".rand());
	print "<script>window.location = 'index.php?".rand()."&next=".$_SESSION['install']."';</script>";
//	require('../footer.php');
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'airspace') {
	unset($_SESSION['install']);
	print '<div class="info column"><ul>';
	foreach ($_SESSION['done'] as $done) {
	    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
	}
	print '<li>Populate airspace database....<img src="../images/loading.gif" /></li></ul></div>';
	flush();
	@ob_flush();

	include_once('class.update_db.php');
	update_db::update_airspace();
	$_SESSION['done'] = array_merge($_SESSION['done'],array('Populate airspace database'));
	$_SESSION['install'] = 'finish';
//	require('../footer.php');
//	ob_end_clean();
//	header("Location: index.php?".rand());
	print "<script>window.location = 'index.php?".rand()."&next=".$_SESSION['install']."';</script>";
//	require('../footer.php');
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'populate') {
	unset($_SESSION['install']);
	if (!is_writable('tmp')) {
		print '<p><strong>The directory <i>install/tmp</i> must be writable.</strong></p>';
		require('../footer.php');
		exit;
	}

	print '<div class="info column"><ul>';
	foreach ($_SESSION['done'] as $done) {
	    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
	}
	print '<li>Populate aircraft_modes table with externals data....<img src="../images/loading.gif" /> <i>(Can be very slow)</i><b>If it fails, run install/install_db.php or install/install_db.sh in console, this will finish install</b></li></ul></div>';
	flush();
	@ob_flush();
	include_once('class.update_db.php');
	$globalDebug = FALSE;
	update_db::update_ModeS();
	$_SESSION['done'] = array_merge($_SESSION['done'],array('Populate aircraft_modes table with externals data'));
	$_SESSION['install'] = 'routes';
	print "<script>window.location = 'index.php?".rand()."&next=".$_SESSION['install']."';</script>";

//	require('../footer.php');
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'routes') {
	unset($_SESSION['install']);
	if (!is_writable('tmp')) {
		print '<p><strong>The directory <i>install/tmp</i> must be writable.</strong></p>';
		require('../footer.php');
		exit;
	}

	print '<div class="info column"><ul>';
	foreach ($_SESSION['done'] as $done) {
	    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
	}
	print '<li>Populate routes table with externals data....<img src="../images/loading.gif" /> <i>(Can be very slow)</i><b>If it fails, run install/install_db.php or install/install_db.sh in console, this will finish install</b></li></ul></div>';
	flush();
	@ob_flush();

	include_once('class.update_db.php');
	$globalDebug = FALSE;
	update_db::update_routes();
	$_SESSION['done'] = array_merge($_SESSION['done'],array('Populate routes table with externals data'));
	$_SESSION['install'] = 'translation';
	print "<script>window.location = 'index.php?".rand()."&next=".$_SESSION['install']."';</script>";
//	require('../footer.php');
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'translation') {
	unset($_SESSION['install']);
	if (!is_writable('tmp')) {
		print '<p><strong>The directory <i>install/tmp</i> must be writable.</strong></p>';
		require('../footer.php');
		exit;
	}

	print '<div class="info column"><ul>';
	foreach ($_SESSION['done'] as $done) {
	    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
	}
	print '<li>Populate translation table with externals data....<img src="../images/loading.gif" /> <i>(Can be very slow)</i><b>If it fails, run install/install_db.php or install/install_db.sh in console, this will finish install</b></li></ul></div>';
	flush();
	@ob_flush();

	include_once('class.update_db.php');
	$globalDebug = FALSE;
	update_db::update_translation();
	$_SESSION['done'] = array_merge($_SESSION['done'],array('Populate translation table with externals data'));

	if ($_SESSION['waypoints'] == 1) {
	    $_SESSION['install'] = 'waypoints';
	    unset($_SESSION['waypoints']);
	} else $_SESSION['install'] = 'finish';
//	require('../footer.php');
//	ob_end_clean();
//	header("Location: index.php?".rand());
	print "<script>window.location = 'index.php?".rand()."&next=".$_SESSION['install']."';</script>";
//	require('../footer.php');
} else if (isset($_SESSION['install']) && $_SESSION['install'] == 'finish') {
	unset($_SESSION['install']);
	print '<div class="info column"><ul>';
	foreach ($_SESSION['done'] as $done) {
	    print '<li>'.$done.'....<strong>SUCCESS</strong></li>';
	}
	print '</ul></div>';
	print '<p>All is now installed ! Thanks</p>';
	if ($globalSBS1) {
		print '<p>You need to run cron-sbs.php as a daemon. You can use init script in the install/init directory.</p>';
	}
	if ($globalACARS) {
		print '<p>You need to run cron-acars.php as a daemon. You can use init script in the install/init directory.</p>';
	}
	if ($globalFlightAware && ($globalFlightAwareUsername == '' || $globalFlightAwarePassword == '')) {
		print '<p>You <strong>must</strong> have a FlightAware API account to use FlightAware source</p>';
	}
	print '<p>If you want to keep external data updated, you have to add install/update_db.php in cron.</p>';
	print '</div>';
} else {
	unset($_SESSION['install']);
//	header("Location: index.php");
}
require('../footer.php');
?>