<?php






class ShadowDelete {

    static public function hashShadowDelFilename($filename)
    {
        global $config;

        $file = pathinfo($filename);
        return sha1($file['filename'] . $config['shadow_del']['filename_seed']) . "." . $file['extension'];
    }



    // Delete a post (reply or thread)
    static public function deletePost($id, $error_if_doesnt_exist=true, $rebuild_after=true) {
        global $board, $config;

        // Select post and replies (if thread) in one query
        $query = prepare(sprintf("SELECT `id`,`thread`,`files`,`slug` FROM ``posts_%s`` WHERE `id` = :id OR `thread` = :id", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        if ($query->rowCount() < 1) {
            if ($error_if_doesnt_exist)
                error($config['error']['invalidpost']);
            else return false;
        }

        $ids = array();
        $files = array();

        // Temporarly Delete posts and maybe replies
        while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
            event('shadow-delete', $post);
            
            // If thread 
            if (!$post['thread']) {
                // Delete thread HTML page
                file_unlink($board['dir'] . $config['dir']['res'] . link_for($post) );
                file_unlink($board['dir'] . $config['dir']['res'] . link_for($post, true) ); // noko50
                file_unlink($board['dir'] . $config['dir']['res'] . sprintf('%d.json', $post['id']));

                // Insert antispam to temp table
                $antispam_query = prepare("INSERT INTO ``shadow_antispam`` SELECT * FROM ``antispam`` WHERE `board` = :board AND `thread` = :thread");
                $antispam_query->bindValue(':board', $board['uri']);
                $antispam_query->bindValue(':thread', $post['id']);
                $antispam_query->execute() or error(db_error($antispam_query));

                // Delete Antispam entry
                $antispam_query = prepare('DELETE FROM ``antispam`` WHERE `board` = :board AND `thread` = :thread');
                $antispam_query->bindValue(':board', $board['uri']);
                $antispam_query->bindValue(':thread', $post['id']);
                $antispam_query->execute() or error(db_error($antispam_query));
            } elseif ($query->rowCount() == 1) {
                // Rebuild thread
                $rebuild = &$post['thread'];
            }
            if ($post['files']) {
                // Move files to temp storage
                foreach (json_decode($post['files']) as $i => $f) {
                    if ($f->file !== 'deleted') {
                        // Add file to array of all files
                        $files[] = $f;
                        // Move files to temp storage
                        @rename($board['dir'] . $config['dir']['img'] . $f->file, $board['dir'] . $config['dir']['shadow_del'] . $config['dir']['img'] . self::hashShadowDelFilename($f->file));
                        @rename($board['dir'] . $config['dir']['thumb'] . $f->thumb, $board['dir'] . $config['dir']['shadow_del'] . $config['dir']['thumb'] . self::hashShadowDelFilename($f->thumb));
                    }
                }
            }

            $ids[] = (int)$post['id'];
        }



        // Determin if it is an thread or just post we are deleting
        $query = prepare(sprintf("SELECT `thread` FROM ``posts_%s`` WHERE `id` = :id", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));
        $thread_id = $query->fetch(PDO::FETCH_ASSOC)['thread'];


        // Insert data into temp table
        $insert_query = prepare("INSERT INTO ``shadow_deleted`` VALUES(NULL, :board, :post_id, :del_time, :files, :cite_ids)");
        $insert_query->bindValue(':board', $board['uri'], PDO::PARAM_STR);
        $insert_query->bindValue(':post_id', $id, PDO::PARAM_INT);
        $insert_query->bindValue(':del_time', time(), PDO::PARAM_INT);
        $insert_query->bindValue(':files', json_encode($files));
        $insert_query->bindValue(':cite_ids', json_encode($ids));
        $insert_query->execute() or error(db_error($insert_query));


        // Insert post table into temp post table
        $insert_query = prepare(sprintf("INSERT INTO ``shadow_posts_%s`` SELECT * FROM ``posts_%s`` WHERE `id` = " . implode(' OR `id` = ', $ids), $board['uri'], $board['uri']));
        $insert_query->execute() or error(db_error($insert_query));

        // Delete post table entries
        $query = prepare(sprintf("DELETE FROM ``posts_%s`` WHERE `id` = :id OR `thread` = :id", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        // Insert filehash table into temp filehash table
        $insert_query = prepare("INSERT INTO ``shadow_filehashes`` SELECT * FROM ``filehashes`` WHERE `board` = :board AND (`post` = " . implode(' OR `post` = ', $ids) . ")");
        $insert_query->bindValue(':board', $board['uri'], PDO::PARAM_STR);
        $insert_query->execute() or error(db_error($insert_query));

        // Delete filehash entries for thread from filehash table
        $query = prepare(sprintf("DELETE FROM ``filehashes`` WHERE ( `thread` = :id OR `post` = :id ) AND `board` = '%s'", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));


        // Update bump order
        if (isset($thread_id))
        {
            $query = prepare(sprintf('SELECT MAX(`time`) AS `correct_bump` FROM `posts_%s` WHERE (`thread` = :thread AND NOT email <=> "sage") OR `id` = :thread', $board['uri']));
            $query->bindValue(':thread', $thread_id, PDO::PARAM_INT);
            $query->execute() or error(db_error($query));
            $correct_bump = $query->fetch(PDO::FETCH_ASSOC)['correct_bump'];

            $query = prepare(sprintf("UPDATE ``posts_%s`` SET `bump` = :bump WHERE `id` = :id", $board['uri']));
            $query->bindValue(':bump', $correct_bump, PDO::PARAM_INT);
            $query->bindValue(':id', $thread_id, PDO::PARAM_INT);
            $query->execute() or error(db_error($query));
        }

        // Update Cite Links
        $query = prepare("SELECT `board`, `post` FROM ``cites`` WHERE `target_board` = :board AND (`target` = " . implode(' OR `target` = ', $ids) . ") ORDER BY `board`");
        $query->bindValue(':board', $board['uri']);
        $query->execute() or error(db_error($query));
        while ($cite = $query->fetch(PDO::FETCH_ASSOC)) {
            if ($board['uri'] != $cite['board']) {
                if (!isset($tmp_board))
                    $tmp_board = $board['uri'];
                openBoard($cite['board']);
            }
            rebuildPost($cite['post']);
        }

        if (isset($tmp_board))
            openBoard($tmp_board);

        // Insert Cited to temp table
        $query = prepare("INSERT INTO ``shadow_cites`` SELECT * FROM ``cites`` WHERE (`target_board` = :board AND (`target` = " . implode(' OR `target` = ', $ids) . ")) OR (`board` = :board AND (`post` = " . implode(' OR `post` = ', $ids) . "))");
        $query->bindValue(':board', $board['uri']);
        $query->execute() or error(db_error($query));

        // Delete Cites
        $query = prepare("DELETE FROM ``cites`` WHERE (`target_board` = :board AND (`target` = " . implode(' OR `target` = ', $ids) . ")) OR (`board` = :board AND (`post` = " . implode(' OR `post` = ', $ids) . "))");
        $query->bindValue(':board', $board['uri']);
        $query->execute() or error(db_error($query));
        
        if (isset($rebuild) && $rebuild_after) {
            buildThread($rebuild);
            buildIndex();
        }

        // If Thread ID is set return it (deleted post within thread) this will pe a positive number and thus viewed as true for legacy purposes
        if(isset($thread_id))
            return $thread_id;

        return true;
    }








    
    // Delete a post (reply or thread)
    static public function restorePost($id, $error_if_doesnt_exist=true, $rebuild_after=true) {
        global $board, $config;

        // Select post and replies (if thread) in one query
        $query = prepare(sprintf("SELECT `id`,`thread`,`files`,`slug` FROM ``shadow_posts_%s`` WHERE `id` = :id OR `thread` = :id", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        if ($query->rowCount() < 1) {
            if ($error_if_doesnt_exist)
                error($config['error']['invalidpost']);
            else return false;
        }

        $ids = array();

        // Restore posts and maybe replies
        while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
            event('shadow-restore', $post);

            // If thread 
            if (!$post['thread']) {
                // Insert temp antispam to table
                $antispam_query = prepare("INSERT INTO ``antispam`` SELECT * FROM ``shadow_antispam`` WHERE `board` = :board AND `thread` = :thread");
                $antispam_query->bindValue(':board', $board['uri']);
                $antispam_query->bindValue(':thread', $post['id']);
                $antispam_query->execute() or error(db_error($antispam_query));

                // Delete Temp Antispam entry
                $antispam_query = prepare('DELETE FROM ``shadow_antispam`` WHERE `board` = :board AND `thread` = :thread');
                $antispam_query->bindValue(':board', $board['uri']);
                $antispam_query->bindValue(':thread', $post['id']);
                $antispam_query->execute() or error(db_error($antispam_query));
            }

            // Restore Files
            if ($post['files']) {
                // Move files from temp storage
                foreach (json_decode($post['files']) as $i => $f) {
                    if ($f->file !== 'deleted') {
                        @rename($board['dir'] . $config['dir']['shadow_del'] . $config['dir']['img'] . self::hashShadowDelFilename($f->file), $board['dir'] . $config['dir']['img'] . $f->file);
                        @rename($board['dir'] . $config['dir']['shadow_del'] . $config['dir']['thumb'] . self::hashShadowDelFilename($f->thumb), $board['dir'] . $config['dir']['thumb'] . $f->thumb);
                    }
                }
            }

            $ids[] = (int)$post['id'];
        }

        // Delete data from temp table
        $insert_query = prepare("DELETE FROM ``shadow_deleted`` WHERE `board` = :board AND `post_id` = :id");
        $insert_query->bindValue(':board', $board['uri'], PDO::PARAM_STR);
        $insert_query->bindValue(':id', $post['id'], PDO::PARAM_INT);
        $insert_query->execute() or error(db_error($insert_query));


        // Determin if it is an thread or just post we are restoring
        $query = prepare(sprintf("SELECT `thread` FROM ``shadow_posts_%s`` WHERE `id` = :id", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));
        $thread_id = $query->fetch(PDO::FETCH_ASSOC)['thread'];


        // Insert temp post table into post table
        $insert_query = prepare(sprintf("INSERT INTO ``posts_%s`` SELECT * FROM ``shadow_posts_%s`` WHERE `id` = " . implode(' OR `id` = ', $ids), $board['uri'], $board['uri']));
        $insert_query->execute() or error(db_error($insert_query));

        // Delete post table entries
        $query = prepare(sprintf("DELETE FROM ``shadow_posts_%s`` WHERE `id` = :id OR `thread` = :id", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        // Insert filehash table into temp filehash table
        $insert_query = prepare("INSERT INTO ``filehashes`` SELECT * FROM ``shadow_filehashes`` WHERE `board` = :board AND (`post` = " . implode(' OR `post` = ', $ids) . ")");
        $insert_query->bindValue(':board', $board['uri'], PDO::PARAM_STR);
        $insert_query->execute() or error(db_error($insert_query));

        // Delete filehash entries for thread from filehash table
        $query = prepare(sprintf("DELETE FROM ``shadow_filehashes`` WHERE ( `thread` = :id OR `post` = :id ) AND `board` = '%s'", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));


        // Update bump order
        if (isset($thread_id))
        {
            $query = prepare(sprintf('SELECT MAX(`time`) AS `correct_bump` FROM `posts_%s` WHERE (`thread` = :thread AND NOT email <=> "sage") OR `id` = :thread', $board['uri']));
            $query->bindValue(':thread', $thread_id, PDO::PARAM_INT);
            $query->execute() or error(db_error($query));
            $correct_bump = $query->fetch(PDO::FETCH_ASSOC)['correct_bump'];

            $query = prepare(sprintf("UPDATE ``posts_%s`` SET `bump` = :bump WHERE `id` = :id", $board['uri']));
            $query->bindValue(':bump', $correct_bump, PDO::PARAM_INT);
            $query->bindValue(':id', $thread_id, PDO::PARAM_INT);
            $query->execute() or error(db_error($query));
        }

        $query = prepare("SELECT `board`, `post` FROM ``shadow_cites`` WHERE `target_board` = :board AND (`target` = " . implode(' OR `target` = ', $ids) . ") ORDER BY `board`");
        $query->bindValue(':board', $board['uri']);
        $query->execute() or error(db_error($query));
        while ($cite = $query->fetch(PDO::FETCH_ASSOC)) {
            if ($board['uri'] != $cite['board']) {
                if (!isset($tmp_board))
                    $tmp_board = $board['uri'];
                openBoard($cite['board']);
            }
            rebuildPost($cite['post']);
        }

        if (isset($tmp_board))
            openBoard($tmp_board);

        // Insert Temp Cited to Cited Table
        $query = prepare("INSERT INTO ``cites`` SELECT * FROM ``shadow_cites`` WHERE (`target_board` = :board AND (`target` = " . implode(' OR `target` = ', $ids) . ")) OR (`board` = :board AND (`post` = " . implode(' OR `post` = ', $ids) . "))");
        $query->bindValue(':board', $board['uri']);
        $query->execute() or error(db_error($query));

        // Delete Temp Cites
        $query = prepare("DELETE FROM ``shadow_cites`` WHERE (`target_board` = :board AND (`target` = " . implode(' OR `target` = ', $ids) . ")) OR (`board` = :board AND (`post` = " . implode(' OR `post` = ', $ids) . "))");
        $query->bindValue(':board', $board['uri']);
        $query->execute() or error(db_error($query));

        if (isset($rebuild) && $rebuild_after) {
            buildThread($rebuild);
            buildIndex();
        }

        // If Thread ID is set return it (deleted post within thread) this will pe a positive number and thus viewed as true for legacy purposes
        if(isset($thread_id))
            return $thread_id;

        return true;
    }








    // Delete a post (reply or thread)
    static public function purgePost($id, $error_if_doesnt_exist=true, $rebuild_after=true) {
        global $board, $config;

        // Select post and replies (if thread) in one query
        $query = prepare(sprintf("SELECT `id`,`thread`,`files`,`slug` FROM ``shadow_posts_%s`` WHERE `id` = :id OR `thread` = :id", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        if ($query->rowCount() < 1) {
            if ($error_if_doesnt_exist)
                error($config['error']['invalidpost']);
            else return false;
        }

        $ids = array();

        // Delete files
        while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
            event('shadow-perm-delete', $post);
            if ($post['files']) {
                foreach (json_decode($post['files']) as $i => $f) {
                    if ($f->file !== 'deleted') {
                        @unlink($board['dir'] . $config['dir']['shadow_del'] . $config['dir']['img'] . self::hashShadowDelFilename($f->file));
                        @unlink($board['dir'] . $config['dir']['shadow_del'] . $config['dir']['thumb'] . self::hashShadowDelFilename($f->thumb));
                    }
                }
            }

            $ids[] = (int)$post['id'];
        }

        // Delete data from temp table
        $insert_query = prepare("DELETE FROM ``shadow_deleted`` WHERE `board` = :board AND `post_id` = :id");
        $insert_query->bindValue(':board', $board['uri'], PDO::PARAM_STR);
        $insert_query->bindValue(':id', $post['id'], PDO::PARAM_INT);
        $insert_query->execute() or error(db_error($insert_query));

        // Determin if it is an thread or just post we are restoring
        $query = prepare(sprintf("SELECT `thread` FROM ``shadow_posts_%s`` WHERE `id` = :id", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));
        $thread_id = $query->fetch(PDO::FETCH_ASSOC)['thread'];

        // Delete post table entries
        $query = prepare(sprintf("DELETE FROM ``shadow_posts_%s`` WHERE `id` = :id OR `thread` = :id", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        // Delete filehash entries for thread from filehash table
        $query = prepare(sprintf("DELETE FROM ``shadow_filehashes`` WHERE ( `thread` = :id OR `post` = :id ) AND `board` = '%s'", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        // Delete Temp Cites
        $query = prepare("DELETE FROM ``shadow_cites`` WHERE (`target_board` = :board AND (`target` = " . implode(' OR `target` = ', $ids) . ")) OR (`board` = :board AND (`post` = " . implode(' OR `post` = ', $ids) . "))");
        $query->bindValue(':board', $board['uri']);
        $query->execute() or error(db_error($query));

        // If Thread ID is set return it (deleted post within thread) this will pe a positive number and thus viewed as true for legacy purposes
        if(isset($thread_id))
            return $thread_id;

        return true;
    }






    // Delete a post (reply or thread)
    static public function purge() {
        global $config;

        // Delete data from temp table
        $query = prepare("SELECT * FROM ``shadow_deleted`` WHERE `del_time` < :del_time");
        $query->bindValue(':del_time', strtotime("-" . $config['shadow_del']['lifetime']), PDO::PARAM_INT);
        $query->execute() or error(db_error($query));

        // Temporarly Delete posts and maybe replies
        while ($shadow_post = $query->fetch(PDO::FETCH_ASSOC)) {
            event('shadow-perm-delete', $shadow_post);

            // Set Board Dir for Deletion
	        $board['dir'] = sprintf($config['board_path'], $shadow_post['board']);

            // Delete files from temp storage
            foreach (json_decode($shadow_post['files']) as $i => $f) {
                @unlink($board['dir'] . $config['dir']['shadow_del'] . $config['dir']['img'] . self::hashShadowDelFilename($f->file));
                @unlink($board['dir'] . $config['dir']['shadow_del'] . $config['dir']['thumb'] . self::hashShadowDelFilename($f->thumb));
            }

            // Delete post table entries
            $delete_query = prepare(sprintf("DELETE FROM ``shadow_posts_%s`` WHERE `id` = :id OR `thread` = :id", $shadow_post['board']));
            $delete_query->bindValue(':id', $shadow_post['post_id'], PDO::PARAM_INT);
            $delete_query->execute() or error(db_error($delete_query));

            // Delete filehash entries for thread from filehash table
            $delete_query = prepare("DELETE FROM ``shadow_filehashes`` WHERE ( `thread` = :id OR `post` = :id ) AND `board` = :board");
            $delete_query->bindValue(':id', $shadow_post['post_id'], PDO::PARAM_INT);
            $delete_query->bindValue(':board', $shadow_post['board'], PDO::PARAM_STR);
            $delete_query->execute() or error(db_error($delete_query));

            // Delete Temp Antispam entry
            $delete_query = prepare('DELETE FROM ``shadow_antispam`` WHERE `board` = :board AND `thread` = :thread');
            $delete_query->bindValue(':board', $shadow_post['board']);
            $delete_query->bindValue(':thread', $shadow_post['post_id']);
            $delete_query->execute() or error(db_error($delete_query));

            // Delete Temp Cites
            $ids = array();            
            foreach (json_decode($shadow_post['cite_ids']) as $c)
                $ids[] = $c;

            // Delete Temp Cites
            $delete_query = prepare("DELETE FROM ``cites`` WHERE (`target_board` = :board AND (`target` = " . implode(' OR `target` = ', $ids) . ")) OR (`board` = :board AND (`post` = " . implode(' OR `post` = ', $ids) . "))");
            $delete_query->bindValue(':board', $shadow_post['board']);
            $delete_query->execute() or error(db_error($delete_query));
       }

        // Delete data from temp table
        $query = prepare("DELETE FROM ``shadow_deleted`` WHERE `del_time` < :del_time");
        $query->bindValue(':del_time', strtotime("-" . $config['shadow_del']['lifetime']), PDO::PARAM_INT);
        $query->execute() or error(db_error($query));
       
        return true;
    }






}








?>