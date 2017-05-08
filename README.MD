HUBZILLA-PLUGIN
 * Name: Bittorrent Server
 * Description: A Bittorrent Server for hubzilla. Alpha. Unstable. Proof of Concept. For testing.
 * Version: 0.1
 * Depends: Core, libtorrent (Python), Python3
 * Recommends: None
 * Category: Torrents
 * Author: ROERE <webmaster@roederstein.de>
 * Maintainer: ROERE <webmaster@roederstein.de>


INSTALL
 - you need to have Python3 installed 
 - switch to [hubzilla-path]/addon
 - run: git clone https://github.com/roere/bittorrentserver.git
 - make the folders 'bittorrentserver' and 'bittorrentserver/media' writable for the webserver

USE

On activating the plugin, the server starts on port [6881..6891].
The plugin settings can be changed on the plugins admin page. You can add seeding files or remove them. If you just give a file name, the plugin expects the file in bittorrent/media. You can give an absolute file path, too, but must make sure that the media folder is writable for the webserver, because the torrent-file will be stored there.
The plugin comes with a standard list of trackers. You can edit that list. Currently, the standard list is reloaded every time the plugin is activated.
You find a list of the magnet links generated from the given files, at the bottom of the admin page. After saving the settings, the list is not updated immediatly. Just reload the page to update the list (the reload button on the page is not working yet).

ISSUES

Architecture

I couldn't find a suitable PHP library (necessary functions, under active development, e.g.). Libtorrent is written in C and provides Python-bindings. So currently the plugin starts a Phyton script that acts as the seeder. The script looks for changes in the bittorrentserver.cfg (seeding files, trackers) and reacts on them. 

Webtorrent-Support

The idea behind this plugin was to use Webtorrents in Hubzilla. Libtorrent does not support Webtorrent yet, also it is planned since quite some time: https://github.com/arvidn/libtorrent/issues/223

Seeding Files from Database

For a better integration it should be possible to choose files from the Hubzilla cloud. Libtorrents default storage is the file system. The storage_interface (http://libtorrent.org/reference-Custom_Storage.html) allows the integration of other storages, like RAM, database, etc., but a database_storage is not yet implemented (http://stackoverflow.com/questions/43619769/use-database-as-custom-storage-with-libtorrent-python).  
