


# Update bans table to accept hashed ip 
ALTER TABLE `bans` CHANGE `ipstart` `ipstart`  VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL;
ALTER TABLE `bans` CHANGE `ipend` `ipend`  VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL;



# update ip_notes table to accept hashed ip
ALTER TABLE `ip_notes` CHANGE `ip` `ip`  VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL;


# update custom_goip table to accept hashed ip
ALTER TABLE `custom_geoip` CHANGE `ip` `ip`  VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL;



# update flood table to accept hashed ip
ALTER TABLE `flood` CHANGE `ip` `ip`  VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL;


# update modlogs table to accept hashed ip
ALTER TABLE `modlogs` CHANGE `ip` `ip`  VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL;

# update mutes table to accept hashed ip
ALTER TABLE `mutes` CHANGE `ip` `ip`  VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL;


# ALREADY ON THIS update all posts_* table to accept hashed ip
# ALTER TABLE `posts_*` CHANGE `ip` `ip`  VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL;

# ALREADY ON THIS update reports table to accept hashed ip
# ALTER TABLE `reports` CHANGE `ip` `ip`  VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL;


# update mutes table to accept hashed ip
ALTER TABLE `search_queries` CHANGE `ip` `ip`  VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL;

# update warnings table to accept hashed ip
ALTER TABLE `warnings` CHANGE `ip` `ip`  VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL;









