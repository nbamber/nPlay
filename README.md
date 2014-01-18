nPlay
=====

nPlay - Streaming Music Player for Xbox One and Other Networked Devices.

//Install

Install Apache (2.2.22) and PHP (5.3.10) for your platform 
Add index.php to the directory containing your MP3s and configure Apache to access it
Create a writable stations.txt
Point your Xbone w IE / Any HTML5 device to your IP
Create Stations.

//TODO

Auto add new files in dir option for station
Nicer Add Files Interface
Ability to add multiple cover arts to a station
directories need to be clickable - click on dir loads and browses that dir
??? - device support re file type, eg xbone wont play m4a
Edit Stations / Add Files / Remove Files
Record number of station plays in station and sort by popularity
tabs for stations?
Don't show System Volume Information when listing directories
Page will reload if adding a station losing selections.
Bug with Jay Z Linkin Park Dirt off Shoulder, Kanye We Dont Care, song length is over estimated (4:56 vs 3:59), Jay Z and Nas is underestimating play length? When song is played get actual time from audio element and re save it?
If no year in ID3 tags don't show () next to title
If no title / CD OR title / Cd is junk then show prent dir + file name 
Warning: Division by zero in /var/www/nplay/index.php on line 716 when adding work directory
Add: Mute button

//Misc:
Loading stations footer for the first time interupts music playback each load on chrome sometimes, xbone fine
at end of station .reload results in null null and nothing is played
xbox always resets volume after reload
xbox sometimes starts at 00:00 rather than $timeDiff, reload on same page starts at right time though.
