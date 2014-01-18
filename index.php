<?
//TODO
//add to github
//change debug to proper logging
//at end of station .reload results in null null and nothing is played

//xbox always resets volume after reload
//xbox sometimes starts at 00:00 rather than $timeDiff, reload on same page starts at right time though.

//Auto add new files in dir option for station
//Nicer Add Files Interface
//Ability to add multiple cover arts to a station
//Show demos for a station?
//directories need to be clickable - click on dir loads and browses that dir
//??? - device support re file type, eg xbone wont play m4a
//Edit Stations / Add Files / Remove Files
//Record number of station plays in station and sort by popularity
//tabs for stations?
//Don't show System Volume Information when listing directories
//Page will reload if adding a station losing selections.
//Bug with Jay Z Linkin Park Dirt off Shoulder, Kanye We Dont Care, song length is over estimated (4:56 vs 3:59), Jay Z and Nas is underestimating play length? When song is played get actual time from audio element and re save it?
//If no year in ID3 tags don't show () next to title
//If no title / CD OR title / Cd is junk then show prent dir + file name 
//Warning: Division by zero in /var/www/nplay/index.php on line 716 when adding work directory
//Add: Mute button

//Misc:
//Loading stations footer for the first time interupts music playback each load on chrome sometimes, xbone fine

error_reporting(E_ALL);
ini_set("display_errors", 1);

//this is for large files, getting bitrate and reading id3 tags from end
ini_set('memory_limit', '1024M');

session_start();
$errors = array();

if(isset($_POST["method"]) && $_POST["method"] == "addingStation")
{
	if(isset($_POST["includeSubDirs"]) && $_POST["includeSubDirs"] == "on")
		$includeSubDirs = true;
	else
		$includeSubDirs = false;

	if(isset($_POST["showStationTitle"]) && $_POST["showStationTitle"] == "on")
		$showStationTitle = 1;
	else
		$showStationTitle = 0;

	if(isset($_POST["autoAddNewFiles"]) && $_POST["autoAddNewFiles"] == "on")
		$autoAddNewFiles = 1;
	else
		$autoAddNewFiles = 0;

	if(!AddStation($_POST["stationName"], $_POST["mainArtFile"], $_POST["files"], $includeSubDirs, $showStationTitle, $autoAddNewFiles))
	{
		unset($_REQUEST["stationName"]);
		$errors[] = "Error adding station";
	}
} 

$stationsArray = LoadStations();

if(count($stationsArray) == 0)
{
	$nowPlayingDisplay = "none";
	$footerDisplay = "block";

	$errors[] = "Could not load stations\n";
}
else
{
	if((!isset($_REQUEST["stationName"]) && !isset($_REQUEST["stationID"]) && !isset($_SESSION["stationID"])) || 
		($_SESSION["stationID"] == null))
		$stationID = rand(0, count($stationsArray) - 1);
	else
	{
		if(isset($_REQUEST["stationName"]))
		{
			for($i = 0; $i < count($stationsArray); $i++)
			{
				if($stationsArray[$i][0] == $_REQUEST["stationName"])
				{
					$stationID = $i;
					break;
				}
			}
		}
		else if(isset($_REQUEST["stationID"]))
		{
			if(isset($stationsArray[$_REQUEST["stationID"]]))
				$stationID = $_REQUEST["stationID"];
			else
				$stationID = rand(0, count($stationsArray) - 1);
		}
		else if(isset($_SESSION["stationID"]))
			$stationID = $_SESSION["stationID"];
	}

	$_SESSION["stationID"] = $stationID;
	$station = $stationsArray[$stationID];

	$nowPlayingDisplay = "block";
	$footerDisplay = "none";

	//Get total play time
	$stationPlayTime = 0;
	for($i = 0; $i < count($station[3]); $i++)
	{
		list($fullPath, $seconds) = $station[3][$i];
		$stationPlayTime += $seconds;
	}

	list($createDate, $playedDiff, $timeDiff) = $station[2];

	$timeDiff = mktime() - $timeDiff + $playedDiff;
	
	if($timeDiff > $stationPlayTime)
		$timeDiff = $timeDiff % $stationPlayTime;

	//NB - this was adding time diff to the background, fixing it broke playing from right point
	//$station[1][2] = $timeDiff;
	//$stationsArray[$stationID] = $station;
	//WriteStations($stationsArray);

	$mins = floor($timeDiff / 60);
	$secs = $timeDiff % 60;

	$playTime = 0;
	$lastPlayTime = 0;

	for($i = 0; $i < count($station[3]); $i++)
	{
		list($fileName, $seconds) = $station[3][$i];
		$playTime += $seconds;

		if($timeDiff < $playTime)
		{
			$fileIndex = $i;
	
			if($i > 0)
				$timeDiff = $timeDiff - $lastPlayTime;

			break;
		}
		else if($timeDiff > $lastPlayTime && $timeDiff < $playTime)
		{
			$fileIndex = $i;
			$timeDiff = $timeDiff - $lastPlayTime;
			break;
		}

		$lastPlayTime = $playTime;
	}

	if(file_exists($station[1]) && filesize($station[1]) > 0)
		$background = $station[1];
}

//Need to work out res (width) of device and work out the number of stations that can be shown in a row.
$footerHeight = "360";
?>
<html>
<head>
	<title>Nplay</title>
	<script src="jquery-2.0.3.min.js"></script>

</head>

<style>
html {
  background: url(<?echo $background;?>) no-repeat center center fixed; 
  -webkit-background-size: cover;
  -moz-background-size: cover;
  -o-background-size: cover;
  background-size: cover;
}

* {margin:0;padding:0;} 

#wrap {
	height: 100%;
	width: 100%;
	overflow:auto;
}

#main {
	overflow:auto;
	padding-bottom: 180px;
}  /* must be same height as the footer */

#nowplaying {
	position: relative;
	margin-top: -180px; /* negative value of footer height */
	height: 180px;
	width: 100%;
	clear:both;
	background: -moz-linear-gradient(45deg,  rgba(0,0,0,0.7) 0%, rgba(0,6,6,0.5) 26%, rgba(0,7,7,0.48) 29%, rgba(0,7,7,0) 100%); /* FF3.6+ */
	background: -webkit-gradient(linear, left bottom, right top, color-stop(0%,rgba(0,0,0,0.7)), color-stop(26%,rgba(0,6,6,0.5)), color-stop(29%,rgba(0,7,7,0.48)), color-stop(100%,rgba(0,7,7,0))); /* Chrome,Safari4+ */
	background: -webkit-linear-gradient(45deg,  rgba(0,0,0,0.7) 0%,rgba(0,6,6,0.5) 26%,rgba(0,7,7,0.48) 29%,rgba(0,7,7,0) 100%); /* Chrome10+,Safari5.1+ */
	background: -o-linear-gradient(45deg,  rgba(0,0,0,0.7) 0%,rgba(0,6,6,0.5) 26%,rgba(0,7,7,0.48) 29%,rgba(0,7,7,0) 100%); /* Opera 11.10+ */
	background: -ms-linear-gradient(45deg,  rgba(0,0,0,0.7) 0%,rgba(0,6,6,0.5) 26%,rgba(0,7,7,0.48) 29%,rgba(0,7,7,0) 100%); /* IE10+ */
	background: linear-gradient(45deg,  rgba(0,0,0,0.7) 0%,rgba(0,6,6,0.5) 26%,rgba(0,7,7,0.48) 29%,rgba(0,7,7,0) 100%); /* W3C */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#b3000000', endColorstr='#00000707',GradientType=1 ); /* IE6-9 fallback on horizontal gradient */
	display: <?echo $nowPlayingDisplay;?>;
}

#stationsButton {
	position: absolute; 
	bottom:5px; 
	right:5px;
}

.nowPlayingP, .nowPlayingArtist, .nowPlayingSong, .nowPlayingInfo, .stationInfo {
	text-shadow:2px 1px 3px rgba(0,0,0,1);
	font-weight:normal;
	color:#FFFFFF;
	letter-spacing:1pt;
	word-spacing:2pt;
	font-size:25px;
	text-align:left;
	font-family:helvetica, sans-serif;
	margin-left:30px;
	padding:0px;
	line-height:1.33;
}

.nowPlayingArtist {
	margin-top:15px;
	font-size:50px;
}

.nowPlayingSong {
	font-size:45px;
}

.nowPlayingInfo {
	font-size:40px;
}

.stationInfo {
	margin-left:0px;
	text-align:center;
	font-size:20px;
}


#footer {
	position: relative;
	overflow: auto;
	margin-top: -180px; /* negative value of footer height */
	height: 180px;
	width: 100%;
	clear: both;
	background: rgba(10, 10, 10, 0.7);
	display: <?echo $footerDisplay;?>;
}

.station-tile {
	float: left;
	height: 160px;
	width: 160px;
	margin-top: 15px;
	margin-bottom: 15px;
	margin-left: 25px;
	background: black;
	overflow: hidden;
}

.station-tile-content {
	height: 154px;
	width: 154px;
	margin-top: 3px;
	margin-bottom: 3px;
	margin-left: 3px;
	margin-right: 3px;
}

.station-settings-tile {
        float: left;
        height: 160px;
        width: 160px;
	margin-left: 25px;
        margin-top: 15px;
        margin-bottom: 15px;
        margin-right: 25px;
        background: black;
	overflow: hidden;
}

.station-settings-content {
	height: 150px;
	width: 150px;
	margin-top: 5px;
	margin-bottom: 5px;
	margin-left: 5px;
	margin-right: 5px;
}

</style>

<body>
<div id="wrap">
	<div id="main">
		<div id="errors">
<?
		for($i = 0; $i < count($errors); $i++)
			echo "<p>$errors[$i]</p>";
?>
		</div>
<?
	if(isset($station))
	{
		$directory = dirname(__FILE__);
		$id3 = GetID3($station[3][$fileIndex][0]);

		if($station[3][$fileIndex][1] > 3600)
			$dispLength = gmdate("H:i:s", $station[3][$fileIndex][1]);
		else
			$dispLength = gmdate("i:s", $station[3][$fileIndex][1]);
		

		$station[3][$fileIndex][0] = str_replace($directory."/", "", $station[3][$fileIndex][0]);

?>
		<center>
			<audio id="nplayer" oncanplay="Play();">
			  	<source src="<?echo $station[3][$fileIndex][0];?>" type="audio/mpeg">
				Your browser does not support the audio element.
			</audio>
		</center>
<?
	}

	if(isset($_REQUEST["addStation"]))
	{
?>
		<center>
		<div id="addStation">
			<form name='addStation' method='POST' action='index.php' onSubmit='return CheckAddStation();'>
				<input type='hidden' name='method' value='addingStation'>
				<input type='hidden' name='stationID' value='<?echo $stationID;?>'>
				Name: <input type="text" name="stationName" value="">
				Art: <input type="text" name="mainArtFile" value="">
				Show Title On Station: <input type='checkbox' name='showStationTitle'>
				Include Subdirectories: <input type='checkbox' name='includeSubDirs'>
				Auto Add New Files In Directory: <input type='checkbox' name='autoAddNewFiles'>
				Files:<br/>
					<? ShowFiles($_REQUEST["currDir"]); ?>

				<input type="submit" name="submit" value="Add Station">
			</form>
		</div>
		</center>
<?
	}
?>
	</div>
</div>


<div id="footer">
<?
	for($i = 0; $i < count($stationsArray); $i++)
	{	
		list($stationName, $stationBackground, $accessArray, $filesArray, $showStationTitle, $includeSubDirs, $autoAddNewFiles) = $stationsArray[$i];

		if($stationBackground == "")
?>
		<div class="station-tile">
			<div class="station-tile-content" id='<?echo "station-".$i;?>' 
				style='background-image: url(<?echo $stationBackground;?>);
					-webkit-background-size: cover;
					-moz-background-size: cover;
					-o-background-size: cover;
					background-size: cover;' 
				onClick='$("#nplayer").animate({volume: 0}, 500); setTimeout(function(){window.location.assign("index.php?stationID=<?echo $i;?>");}, 400);'>

<?				if($showStationTitle)
					echo "<p class='stationInfo'>$stationName</p>";
?>
			</div>
		</div>
<?
	}
?>
	<div class="station-settings-tile">
		<div class="station-settings-content" id="addTile" style="background-image: url(add.jpg);" onClick='window.location.assign("index.php?addStation=1");'>
		</div>
	</div>
</div>

<div id="nowplaying">
<?
	if(count($id3) > 0)
	{
?>
		<p id="songInfo" class='nowPlayingArtist'><?echo $id3["artist"];?></p>
		<p id="songInfo" class='nowPlayingSong'><?echo $id3["title"]." ($dispLength)";?></p>
		<p id="songInfo" class='nowPlayingInfo'><?echo $id3["album"]." (".$id3["year"].")";?></p>
<?
	}
?>

	<div id='stationsButton'>
		<button>Stations</button>
	</div>
</div>

<script>

$(document).ready(function(){

	width = $(window).width();
	height = $(window).height();

	stationWidth = 185;
	footerRowHeight = 185;

	stationCount = <?$total = count($stationsArray) + 1; echo $total;?>; //add one for the add button
	stationsPerRow = Math.floor(width / stationWidth);
	rows = Math.ceil(stationCount / stationsPerRow);
	
	footerTotalSize = footerRowHeight * rows;

	//prevent stations scrolling off screen
	if(footerTotalSize > height)
		footerTotalSize = height;

	$("#footer").css("margin-top", "-"+footerTotalSize+"px");
	$("#footer").css("height", footerTotalSize+"px");

	$(".station-tile").click(function(e){
		e.stopPropagation();
	});

	$(".station-settings-tile").click(function(e){
		e.stopPropagation();
	});

	$("#footer").click(function(e){
		if($("#footer").is(":hidden"))
		{
			//only hide after slide is done
			$("#footer").slideDown('slow');
			$("#nowplaying p").fadeOut("slow");
			$("#nowplaying").hide();
		}
		else
		{
			$("#footer").slideUp();
			$("#nowplaying p").fadeIn("slow");
			$("#nowplaying").show();
			window.scrollTo(0,document.body.scrollHeight);
		}
	});

	$("#nowplaying").click(function(e){
		if($("#footer").is(":hidden"))
		{
			$("#footer").slideDown('slow');
			$("#nowplaying p").fadeOut("slow");
			$("#nowplaying").hide();
		}
		else
		{
			$("#footer").slideUp();
			$("#nowplaying p ").fadeIn("slow");
			$("#nowplaying").show();
			window.scrollTo(0,document.body.scrollHeight);
		}
	});

});

function Play()
{
	window.scrollTo(0,document.body.scrollHeight);

	var player = document.getElementById("nplayer");

	if(player)
	{
		player.currentTime = <?echo $timeDiff;?>;

		player.addEventListener("ended", function(){
			setTimeout(function(){window.location.assign("index.php?fade=0")},1000);
		});

		player.play();
<?
	if(!isset($_REQUEST["fade"]) || $_REQUEST["fade"] == 1)
	{
?>
		player.volume = 0;
		$('#nplayer').animate({volume: 1}, 2000);
<?
	}
?>
	}
}

function CheckAddStation()
{
	form = document.forms.addStation;
	errors = new Array();
	
	if(form.stationName.value == "")
		errors.push("Station must have a name");

	var filesChecked = $('.filesFile:checkbox:checked').length;
	var dirsChecked = $('.filesDir:checkbox:checked').length;
	
	if(filesChecked == 0 && dirsChecked == 0)
		errors.push("You must select at least one file or directory to add to a station.");
		
	//TODO add include sub directory / dirs only checked check
	if(errors.length > 0)
	{
		for(i = 0; i < errors.length; i++)
			$('#errors').append('<p>'+errors[i]+'</p>');

		return false;
	}
	else
		return true;
}
</script>
</body>
</html>

<?

//Quick and Dirty Get ID3 tags, doesn't work for everything, from getID3();
function GetID3($filename)
{ 
	return unpack('a3TAG/a30title/a30artist/a30album/a4year/a28comment/c1track/c1genreid', substr(file_get_contents($filename), -128)); 
}

function LoadStations()
{
	$stations = array();
	if($stationsJSONArray = file("stations.txt"))
	{
		for($i = 0; $i < count($stationsJSONArray); $i++)
		{
			$stationJSON = $stationsJSONArray[$i];
			$stations[] = json_decode($stationJSON, true);
		}
	}

	return $stations;
}

function WriteStations($stationsArray)
{
	$stationJSON = "";
	for($i = 0; $i < count($stationsArray); $i++)
		$stationJSON .= json_encode($stationsArray[$i])."\n";

	file_put_contents("stations.txt", $stationJSON, LOCK_EX);
}

function ShowFiles($directory=null)
{
	if($directory == null)
		$directory = dirname(__FILE__);

	echo "Current Directory - $directory<br/>";

	$files = array();
	$directories = array();

	if($dirHandle = opendir($directory))
	{
		while($fileName = readdir($dirHandle))
		{
			$fullPath = $directory."/".$fileName;
			$fileType = filetype($fullPath);
			if($fileType == "dir" && $fileName != ".." && $fileName != ".")
				$directories[] = array($fileName, $fullPath);
			else
			{
				if(AddableFile($fileName))
					$files[] = array($fileName, $fullPath);
			}
		}
	}

	for($i = 0; $i < count($directories); $i++)
	{
		list($directory, $fullPath) = $directories[$i];
		echo "<p><input type='checkbox' class='filesDir' name='files[]' value='$fullPath' id='$fullPath'><label for='$fullPath'>$directory</label></p>";
	}
	
	for($i = 0; $i < count($files); $i++)
	{
		list($fileName, $fullPath) = $files[$i];
		echo "<p><input type='checkbox' class='filesFile' name='files[]' value='$fullPath' id='$fullPath'><label for='$fullPath'>$fileName</label></p>";
	}
}

function AddStation($stationName, $mainCover, $filesArray, $includeSubDirs, $showStationTitle, $autoAddNewFiles)
{
	$stationArray = array();

	$stationArray[0] = $stationName;
	$stationArray[1] = $mainCover;

	//Create Date, last last access, last access
	$stationArray[2] = array(date("Y-m-d H:i:s"), 0, mktime());

	$stationFileArray = array();

	$stationArray[3] = AddFiles($filesArray, $includeSubDirs, $stationFileArray);
	$stationArray[4] = $showStationTitle;
	$stationArray[5] = $includeSubDirs;
	$stationArray[6] = $autoAddNewFiles;

	if(count($stationArray[3]) > 0)
	{
		$jsonStation = json_encode($stationArray);
	
		if(file_put_contents("stations.txt", $jsonStation."\n", FILE_APPEND | LOCK_EX))
			return true;
		else
			return false;
	}
	else
		return false;
}

function AddFiles($filesArray, $includeSubDirs, $stationFileArray)
{
	//Add support for single dir/file
	if(!is_array($filesArray))
	{
		$currFile = $filesArray;
		$filesArray = array();
		$filesArray[0] = $currFile;
	}

	for($i = 0; $i < count($filesArray); $i++)
	{
		$currFile = $filesArray[$i];

		if(is_dir($currFile) && strpos($currFile, "/.") === false)
		{
			if($dirHandle = opendir($currFile))
			{
				while($fileName = readdir($dirHandle))
				{
					$fullPath = $currFile."/".$fileName;
					$fileType = filetype($fullPath);

					if($fileType == "dir" && $includeSubDirs)
						$stationFileArray = AddFiles($fullPath, $includeSubDirs, $stationFileArray);
					else
					{
						if(AddableFile($fullPath))
						{
							$length = GetMp3LengthInSeconds($fullPath);

							$stationFileArray[] = array($fullPath, $length);
						}
					}
				}
				
				closedir($dirHandle);
			}
			else
				echo "Couldn't open $directory<br/>";
		}
		else
		{
			if(Addablefile($currFile))
			{
				$length = GetMp3LengthInSeconds($currFile);

				$stationFileArray[] = array($currFile, $length);
			}	
		}
	}

	return $stationFileArray;
}

function AddableFile($fileName)
{
	if(file_exists($fileName) && filesize($fileName) > 0)
	{
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$type = finfo_file($finfo, $fileName);
		finfo_close($finfo);

		$ext = explode(".", strrev($fileName));
		$ext = strrev(strtolower($ext[0]));

		if($type == "audio/mpeg" || $type == "audio/mp4" || $ext == "m4a" || $ext == "mp3")
			return true;
		else
			return false;
	}
	else
		return false;
}

//Approximate - under play rather than overplay
//CBRs are mostly accurate, VBRs can be a bit off 
function GetMp3LengthInSeconds($fileName)
{
	$bitRateSampleRate = GetMP3BitRateSampleRate($fileName);
	$bitRate = $bitRateSampleRate["bitRate"];

	$fileSize = filesize($fileName);
	$fileSizeKbits = floor(($fileSize / 1024) * 8);
	$length = floor($fileSizeKbits / $bitRate);

	return $length;
}

//From https://gist.github.com/fastest963/2357002
function GetMP3BitRateSampleRate($filename)
{
    if (!file_exists($filename)) {
        return false;
    }
 
    $bitRates = array(
                      array(0,0,0,0,0),
                      array(32,32,32,32,8),
                      array(64,48,40,48,16),
                      array(96,56,48,56,24),
                      array(128,64,56,64,32),
                      array(160,80,64,80,40),
                      array(192,96,80,96,48),
                      array(224,112,96,112,56),
                      array(256,128,112,128,64),
                      array(288,160,128,144,80),
                      array(320,192,160,160,96),
                      array(352,224,192,176,112),
                      array(384,256,224,192,128),
                      array(416,320,256,224,144),
                      array(448,384,320,256,160),
                      array(-1,-1,-1,-1,-1),
                    );
    $sampleRates = array(
                         array(11025,12000,8000), //mpeg 2.5
                         array(0,0,0),
                         array(22050,24000,16000), //mpeg 2
                         array(44100,48000,32000), //mpeg 1
                        );
    $bToRead = 1024 * 12;
 
    $fileData = array('bitRate' => 0, 'sampleRate' => 0);
    $fp = fopen($filename, 'r');
    if (!$fp) {
        return false;
    }
    //seek to 8kb before the end of the file
    fseek($fp, -1 * $bToRead, SEEK_END);
    $data = fread($fp, $bToRead);
 
    $bytes = unpack('C*', $data);
    $frames = array();
    $lastFrameVerify = null;
 
    for ($o = 1; $o < count($bytes) - 4; $o++) {
 
        //http://mpgedit.org/mpgedit/mpeg_format/MP3Format.html
        //header is AAAAAAAA AAABBCCD EEEEFFGH IIJJKLMM
        if (($bytes[$o] & 255) == 255 && ($bytes[$o+1] & 224) == 224) {
            $frame = array();
            $frame['version'] = ($bytes[$o+1] & 24) >> 3; //get BB (0 -> 3)
            $frame['layer'] = abs((($bytes[$o+1] & 6) >> 1) - 4); //get CC (1 -> 3), then invert
            $srIndex = ($bytes[$o+2] & 12) >> 2; //get FF (0 -> 3)
            $brRow = ($bytes[$o+2] & 240) >> 4; //get EEEE (0 -> 15)
            $frame['padding'] = ($bytes[$o+2] & 2) >> 1; //get G
            if ($frame['version'] != 1 && $frame['layer'] > 0 && $srIndex < 3 && $brRow != 15 && $brRow != 0 &&
                (!$lastFrameVerify || $lastFrameVerify === $bytes[$o+1])) {
                //valid frame header
 
                //calculate how much to skip to get to the next header
                $frame['sampleRate'] = $sampleRates[$frame['version']][$srIndex];
                if ($frame['version'] & 1 == 1) {
                    $frame['bitRate'] = $bitRates[$brRow][$frame['layer']-1]; //v1 and l1,l2,l3
                } else {
                    $frame['bitRate'] = $bitRates[$brRow][($frame['layer'] & 2 >> 1)+3]; //v2 and l1 or l2/l3 (3 is the offset in the arrays)
                }
 
                if ($frame['layer'] == 1) {
                    $frame['frameLength'] = (12 * $frame['bitRate'] * 1000 / $frame['sampleRate'] + $frame['padding']) * 4;
                } else {
                    $frame['frameLength'] = 144 * $frame['bitRate'] * 1000 / $frame['sampleRate'] + $frame['padding'];
                }
 
                $frames[] = $frame;
                $lastFrameVerify = $bytes[$o+1];
                $o += floor($frame['frameLength'] - 1);
            } else {
                $frames = array();
                $lastFrameVerify = null;
            }
        }
        if (count($frames) < 3) { //verify at least 3 frames to make sure its an mp3
            continue;
        }
 
        $header = array_pop($frames);
        $fileData['sampleRate'] = $header['sampleRate'];
        $fileData['bitRate'] = $header['bitRate'];
 
        break;
    }
 
    return $fileData;
}
