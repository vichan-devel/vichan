Why Tinyboard?
==============

### Written from scratch
Tinyboard was written from scratch, meaning it’s not based on any of the ancient and defective projects such as Futaba and Kusaba.

### Performance
Tinyboard is built for speed and has proven to be incredibly fast compared to alternatives. You can make it even faster by [enabling cache](config/cache.md).

### Configurability
Tinyboard is almost entirely customizable. You can [create advanced configuration files](config.md) for the entire imageboard, or for specific boards.

#### Cool config sutff
* [Custom flood filters](config/flood_filters.md)
* [Markup syntax](config/markup.md)
* [Events](events.md)

### Security
Tinyboard was written with security as a priority. It’s not vulnerable to any of the [countless](https://web.archive.org/web/20121017012402/https://github.com/frankusrs/Kusaba-X-Threadshitter) [exploits](https://web.archive.org/web/20121017012402/https://github.com/savetheinternet/kusabax-idcrack) and design flaws affecting alternatives such as Kusaba X.

### User-friendly
Tinyboard has a clean and incredibly easy to use [moderator interface](mod_interface.md) with lots of features. Unlike some other imageboard engines, you don’t need Javascript enabled to use it.

![1](img/mod/1.png) ![2](img/mod/2.png) ![3](img/mod/3.png) ![4](img/mod/4.png) ![5](img/mod/5.png) ![6](img/mod/6.png) ![7](img/mod/7.png) ![8](img/mod/8.png) ![9](img/mod/9.png) ![10](img/mod/10.png) ![11](img/mod/11.png) ![12](img/mod/12.png) ![13](img/mod/13.png) ![14](img/mod/14.png) ![15](img/mod/15.png) ![16](img/mod/16.png) ![17](img/mod/17.png) ![18](img/mod/18.png)

### Tracks quotes
Tinyboard can track post citations (“>>1234” links) so that when posts are deleted, the links are automatically removed.

### IPv6
Tinyboard has full support for IPv6 clients.

### DNS Blacklists
Tinyboard makes blocking open proxies and other malicious clients easy with [DNS Blacklists (DNSBL)](config/dnsbl.md).

### Anti-spam measures
Tinyboard uses [unique and harder-to-defeat anti-spam measures](your_request_looks_automated.md) that keeps all generic bots out and gives attackers a hard time.

### Javascript
Tinyboard comes with extensible [Javascript modules](../js) comparable to browser extensions such as [4chan X](https://web.archive.org/web/20121017012402/http://mayhemydg.github.com/4chan-x/). It can pack all enabled scripts into one file and minify it, [if you tell it to](config.md).

####Some cool Javascript stuff
* [Quick reply](../js/quick-reply.js)
* [Thread auto-updating](../js/auto-reload.js)
* [Post hovering](../js/post-hover.js)
* [Inline thread expanding](../js/expand.js)
* [Client-side forced anonymous](../js/forced-anon.js)

### You can switch over to Tinyboard from Kusaba X without losing any data
[A script](https://web.archive.org/web/20121017012402/https://github.com/savetheinternet/Tinyboard-Tools/blob/master/migration/kusabax.php) was made for this. All you need to do is [install Tinyboard](installation.md) (which should take less than a minute), drop the script in your Tinyboard directory and tell it where your Kusaba X config.php is located. It should figure out the rest on its own.

### More
Tinyboard is packed with features, most of which aren’t listed here. See [`inc/config.php`](../inc/config.php).
