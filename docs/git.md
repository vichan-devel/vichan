Using Git
=========

Tinyboard is still beta software and it’s not going to come out of beta any time soon. As there are often many months between releases yet changes and bug fixes are very frequent, it’s recommended that users use our git repository instead. Using git makes upgrading much easier.

To use git, simply clone our git repository with:
```
% git clone git://github.com/savetheinternet/Tinyboard.git
```
To pull the latest changes at any time, use:
```
% git pull
```
And it will output something like this:
```
remote: Counting objects: 21, done.
remote: Compressing objects: 100% (4/4), done.
remote: Total 15 (delta 11), reused 15 (delta 11)
Unpacking objects: 100% (15/15), done.
From git://github.com/savetheinternet/Tinyboard
   6c3a52a..818da9f  master     -> origin/master
Updating 6c3a52a..818da9f
Fast-forward
 inc/cache.php     |  211 +++++++++++++++++++++++++++--------------------------
 inc/filters.php   |  135 ++++++++++++++++++++++++++++++++++
 inc/functions.php |   65 ++++++++--------
 mod.php           |   11 ++-
 post.php          |  123 ++++++-------------------------
 5 files changed, 304 insertions(+), 241 deletions(-)
 create mode 100644 inc/filters.php
```
You may have to navigate to [`install.php`](../install.php) when there are version changes or the database will likely be incompatible.
