HUBZILLA-PLUGIN
 * Name: Bittorrent Server
 * Description: A Bittorrent Server for hubzilla. Alpha. Unstable. Proof of Concept. For testing.
 * Version: 0.2
 * Depends: Core, libtorrent (Python), Python3
 * Recommends: None
 * Category: Torrents
 * Author: ROERE <webmaster@roederstein.de>
 * Maintainer: ROERE <webmaster@roederstein.de>


INSTALL
 - you need to have Python3 installed 
 - install libtorrent (python)
 - switch to [hubzilla-path]/addon
 - run: git clone https://github.com/roere/bittorrentserver.git
 - make the folders 'bittorrentserver' and 'bittorrentserver/media' writable for the webserver

USE

On activating the plugin, the server starts on port [6881..6891].
The plugin settings can be changed on the plugins admin page. You can add seeding files or remove them. If you just give a file name, the plugin expects the file in bittorrent/media. You can give an absolute file path, too, but must make sure that the media folder is writable for the webserver, because the torrent-file will be stored there.
The plugin comes with a standard list of trackers. You can edit that list. Currently, the standard list is reloaded every time the plugin is activated.
You find a list of the magnet links generated from the given files, at the bottom of the admin page. After saving the settings, the list is not updated immediatly. Just push the reload button to update the list. 
Below the magnet links you can see the server status. This includes the process id, the listening port and the list of files that are beeing seeded. Hit the Ping button to update the status.

ISSUES

Architecture

I couldn't find a suitable PHP library (necessary functions, under active development, e.g.). Libtorrent is written in C and provides Python-bindings. So currently the plugin starts a Phyton script that acts as the seeder. The script looks for changes in the bittorrentserver.cfg (seeding files, trackers) and reacts on them. 

Webtorrent-Support

The idea behind this plugin was to use Webtorrents in Hubzilla. Libtorrent does not support Webtorrent yet, also it is planned since quite some time: https://github.com/arvidn/libtorrent/issues/223

Seeding Files from Hubzilla Cloud

For a good integration it should be possible to choose files from the Hubzilla cloud. Files can currently be selected from the Hubzilla Cloud and are added to the libtorrent session by using the hashed os path under [hubzilla]/store.
