<?php



// Wrap functions in a class so they don't interfere with normal Tinyboard operations
class Announcements {


    static public function new_announcement($announcement, $mod_id = false) {
		global $mod, $config;
		
		if ($mod_id === false) {
			$mod_id = isset($mod['id']) ? $mod['id'] : -1;
		}

        
		$query = prepare("INSERT INTO ``announcements`` VALUES (NULL, :mod, :time, :text)");
		$query->bindValue(':mod', $mod_id);
        $query->bindValue(':time', time());
        if ($announcement !== '') {
			$announcement = escape_markup_modifiers($announcement);
			markup($announcement);
			$query->bindValue(':text', $announcement);
		} else
			error(sprintf($config['error']['required'], "Announcement"));

		$query->execute() or error(db_error($query));
		
        
		modLog("Created a new Annoucement: " . utf8tohtml($announcement));
        self::RebuildAnnouncementPages();
    }



    static public function edit_announcement($id, $announcement) {
		global $mod, $config;
		
		$query = prepare(sprintf("UPDATE ``announcements`` SET `text` = :text WHERE `id` = %d", (int)$id));
        if ($announcement !== '') {
			$teannouncementxt = escape_markup_modifiers($announcement);
			markup($announcement);
			$query->bindValue(':text', $announcement);
		} else
			error(sprintf($config['error']['required'], "Announcement"));
        
		$query->execute() or error(db_error($query));


		modLog("Edited Annoucement #" + (int)$id + " - New Text: " . utf8tohtml($announcement));
        self::RebuildAnnouncementPages();
    }



    static public function delete_announcement($id)
    {
        $query = prepare(sprintf("DELETE FROM ``announcements`` WHERE `id` = %d", (int)$id));
		$query->execute() or error(db_error($query));

		modLog("Deleted Annoucement #" + (int)$id);
        self::RebuildAnnouncementPages();
    }



    static public function RebuildAnnouncementPages()
    {
        global $config;

        // Generate json files for announcements in header of pages
        if($config['announcements']['show'])
            file_write($config['dir']['home'] . $config['announcements']['file_json_small'], self::gen_public_json($config['announcements']['date_format'], $config['announcements']['show_count']));

        // Generate Page for full list of Announcements
        if($config['announcements']['page'])
        {
            // Generate JSON file for full list of Announcements
            file_write($config['dir']['home'] . $config['announcements']['file_json'], self::gen_public_json($config['announcements']['date_format'], false));

            // Generate Page for full list of Announcements
            $announcement_page = Element('page.html', array(
                                'config' => $config,
                                'mod' => false,  
                                'hide_dashboard_link' => true,
                                'boardlist' => createBoardList(false),
                                'title' => _("Announcements"),
                                'subtitle' => "",
                                'nojavascript' => true,
                                'body' => Element('mod/announcements_list.html', array(
                                    'mod' => false,
                                    'token_json' => false,
                                    'uri_json' => $config['root'] . $config['announcements']['file_json'],
                                ))
                        ));
            file_write($config['dir']['home'] . $config['announcements']['page_html'], $announcement_page);
        }
    }



	static public function stream_json($out = false, $filter_staff = false, $date_format = "%m/%d/%Y", $count = false) {
        $query = query("SELECT ``announcements``.*, `username` FROM ``announcements`` 
                        LEFT JOIN ``mods`` ON ``mods``.`id` = `creator`
                        ORDER BY `date` DESC" . (($count === false)?"":" LIMIT " . (int)$count)) or error(db_error($query));
        $announcements = $query->fetchAll(PDO::FETCH_ASSOC);


		$out ? fputs($out, "[") : print("[");

        // Last entry for json end check
		$end = end($announcements);

        foreach ($announcements as &$announce) {

            if($filter_staff)
                $announce['username'] = '?';

            $announce['date_formated'] = strftime($date_format, $announce['date']);

            $json = json_encode($announce);
			$out ? fputs($out, $json) : print($json);

			if ($announce['id'] != $end['id']) {
				$out ? fputs($out, ",") : print(",");
			}
		}

        $out ? fputs($out, "]") : print("]");
	}



    // Returns json content to be written to json file.
    static public function gen_public_json($date_format = "%m/%d/%Y", $count = false) {
        ob_start();
        self::stream_json(false, true, $date_format, $count);
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }



};



?>