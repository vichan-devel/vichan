<?php



class Statistic {



    // Gets statics for past days
    static public function get_stat() {

    }


    static public function get_stat_24h($boardName = false, $realtime = true, $boards = false) {
        global $config, $pdo;

        // SELECT SUM(count) as count, hour FROM (
        //     SELECT COUNT(*) as count, HOUR(FROM_UNIXTIME(time)) AS hour FROM posts_mlpol WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 1 DAY) GROUP BY hour
        //     UNION ALL 
        //     SELECT COUNT(*) as count, HOUR(FROM_UNIXTIME(time)) AS hour FROM posts_qa WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 1 DAY) GROUP BY hour 
        //     UNION ALL 
        //     SELECT COUNT(*) as count, HOUR(FROM_UNIXTIME(time)) AS hour FROM posts_Test WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 1 DAY) GROUP BY hour
        // ) AS deriv_all GROUP BY hour ORDER BY hour DESC

        // SQL Query
        $query = "";

        if(!$boardName) {
            // Get list of all boards
            if($boards === false)
                $boards = listBoards();

            // Get post count by hour for the last day
            $query = "SELECT SUM(count) AS count, hour FROM (";
            foreach ($boards as $board) {
                if($realtime)
                    $query .= sprintf("SELECT COUNT(*) AS count, HOUR(FROM_UNIXTIME(time)) AS hour FROM posts_%s WHERE DATE(FROM_UNIXTIME(time)) = CURDATE() GROUP BY hour UNION ALL ", $board['uri']);
                else
                    $query .= sprintf("SELECT COUNT(*) AS count, HOUR(FROM_UNIXTIME(time)) AS hour FROM posts_%s WHERE DATE(FROM_UNIXTIME(time)) = DATE(NOW() - INTERVAL 1 HOUR) AND HOUR(FROM_UNIXTIME(time)) <= HOUR(NOW() - INTERVAL 1 HOUR) GROUP BY hour UNION ALL ", $board['uri']);
                // $query .= sprintf("SELECT * FROM (SELECT COUNT(*) as count, HOUR(FROM_UNIXTIME(time)) AS hour FROM posts_%s WHERE DATE(FROM_UNIXTIME(time)) = CURDATE() GROUP BY hour) AS deriv_%s UNION ALL ", $board['uri'], $board['uri']);
            }
            // Remove the last "UNION ALL" seperator and complete the query
            $query = preg_replace('/UNION ALL $/', ') AS deriv_all GROUP BY hour ORDER BY hour ASC', $query);
        } else {
            if($realtime)
                $query = sprintf("SELECT COUNT(*) AS count, HOUR(FROM_UNIXTIME(time)) AS hour FROM posts_%s WHERE DATE(FROM_UNIXTIME(time)) = CURDATE() GROUP BY hour", $boardName);
            else
                $query = sprintf("SELECT COUNT(*) AS count, HOUR(FROM_UNIXTIME(time)) AS hour FROM posts_%s WHERE DATE(FROM_UNIXTIME(time)) = DATE(NOW() - INTERVAL 1 HOUR) AND HOUR(FROM_UNIXTIME(time)) <= HOUR(NOW() - INTERVAL 1 HOUR) GROUP BY hour", $boardName);
        }

        // Execute Query
        $query = query($query) or error(db_error($query));
        $query_result = $query->fetchAll(PDO::FETCH_ASSOC);

        // Get 24h array over post count
        $statistics_hour = array_fill(0,24,0);
        foreach ($query_result as &$hour_data) {
            $statistics_hour[$hour_data['hour']] = $hour_data['count'];
        }

        // Set last varuables to "null" for javescript
        $last_hour = end($query_result)['hour'];
        if($last_hour != 23)
            for($i=$last_hour+1; $i<24; $i++)
                $statistics_hour[$i] = 'null';

        // Make string for JS
        $statistics_hour = implode(",", $statistics_hour);

        return $statistics_hour;
    }



    static public function get_stat_week($previous_week = false, $boardName = false, $realtime = true, $hour_realtime = true, $boards = false) {
        global $config, $pdo;

        // SQL Query
        $query = "";

        if(!$boardName) {
            // Get list of all boards
            if($boards === false)
                $boards = listBoards();

            // Get post count by hour for the last week
            $query = "SELECT SUM(count) AS count, day FROM (";
            foreach ($boards as $board) {
                if($previous_week) {
                    if($realtime)
                        $query .= sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(DATE_SUB(NOW(), INTERVAL 1 WEEK), 1) GROUP BY day UNION ALL ", $board['uri']);
                    else if($hour_realtime)
                        $query .= sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(DATE_SUB(NOW() - INTERVAL 1 HOUR, INTERVAL 1 WEEK), 1) GROUP BY day UNION ALL ", $board['uri']);
                    else
                        $query .= sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(DATE_SUB(NOW() - INTERVAL 1 DAY, INTERVAL 1 WEEK), 1) GROUP BY day UNION ALL ", $board['uri']);
                } else {
                    if($realtime)
                        $query .= sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(NOW(), 1) GROUP BY day UNION ALL ", $board['uri']);
                    else if($hour_realtime)
                        $query .= sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(NOW() - INTERVAL 1 HOUR, 1)  AND ( (DATE(FROM_UNIXTIME(time)) = DATE(NOW() - INTERVAL 1 HOUR) AND HOUR(FROM_UNIXTIME(time)) <= HOUR(NOW() - INTERVAL 1 HOUR)) OR (DATE(FROM_UNIXTIME(time)) < DATE(NOW() - INTERVAL 1 HOUR)) ) GROUP BY day UNION ALL ", $board['uri']);
                        // $query .= sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(NOW() - INTERVAL 1 HOUR, 1) AND WEEKDAY(FROM_UNIXTIME(time)) <= WEEKDAY(NOW() - INTERVAL 1 HOUR) GROUP BY day UNION ALL ", $board['uri']);
                    else
                        $query .= sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(NOW() - INTERVAL 1 DAY, 1) AND WEEKDAY(FROM_UNIXTIME(time)) <= WEEKDAY(NOW() - INTERVAL 1 DAY) GROUP BY day UNION ALL ", $board['uri']);
                }
            }
            // Remove the last "UNION ALL" seperator and complete the query
            $query = preg_replace('/UNION ALL $/', ') AS deriv_all GROUP BY day ORDER BY day ASC', $query);
        } else {
            if($previous_week) {
                if($realtime)
                    $query = sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(DATE_SUB(NOW(), INTERVAL 1 WEEK), 1) GROUP BY day", $boardName);
                else if($hour_realtime)
                    $query = sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(DATE_SUB(NOW() - INTERVAL 1 HOUR, INTERVAL 1 WEEK), 1) GROUP BY day", $boardName);
                else
                    $query = sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(DATE_SUB(NOW() - INTERVAL 1 DAY, INTERVAL 1 WEEK), 1) GROUP BY day", $boardName);
            } else {
                if($realtime)
                    $query .= sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(NOW(), 1) GROUP BY day", $boardName);
                else if($hour_realtime)
                    $query .= sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(NOW() - INTERVAL 1 HOUR, 1)  AND ( (DATE(FROM_UNIXTIME(time)) = DATE(NOW() - INTERVAL 1 HOUR) AND HOUR(FROM_UNIXTIME(time)) <= HOUR(NOW() - INTERVAL 1 HOUR)) OR (DATE(FROM_UNIXTIME(time)) < DATE(NOW() - INTERVAL 1 HOUR)) ) GROUP BY day", $boardName);
                    // $query .= sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(NOW() - INTERVAL 1 HOUR, 1)  AND ( (DATE(FROM_UNIXTIME(time)) = DATE_SUB(CURDATE(), INTERVAL 1 HOUR) AND HOUR(FROM_UNIXTIME(time)) <= HOUR(NOW() - INTERVAL 1 HOUR)) OR (DATE(FROM_UNIXTIME(time)) < DATE_SUB(CURDATE(), INTERVAL 1 HOUR)) ) GROUP BY day", $boardName);
                    // $query .= sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(NOW() - INTERVAL 1 HOUR, 1) AND WEEKDAY(FROM_UNIXTIME(time)) <= WEEKDAY(NOW() - INTERVAL 1 HOUR) GROUP BY day", $boardName);
                else
                    $query .= sprintf("SELECT COUNT(*) AS count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(NOW() - INTERVAL 1 DAY, 1) AND WEEKDAY(FROM_UNIXTIME(time)) <= WEEKDAY(NOW() - INTERVAL 1 DAY) GROUP BY day", $boardName);
            }
        }

        // Execute Query
        $query = query($query) or error(db_error($query));
        $query_result = $query->fetchAll(PDO::FETCH_ASSOC);

        // Get week array over post count
        $statistics_week = array_fill(0,7,0);
        foreach ($query_result as &$day_data) {
            $statistics_week[$day_data['day']] = $day_data['count'];
        }

        return $statistics_week;
    }

    static public function get_stat_week_labels($week_data) {
        return 	sprintf("'Monday\\n(%d)', 'Tuesday\\n(%d)', 'Wednesday\\n(%d)', 'Thursday\\n(%d)', 'Friday\\n(%d)', 'Saturday\\n(%d)', 'Sunday\\n(%d)'", $week_data[0], $week_data[1], $week_data[2], $week_data[3], $week_data[4], $week_data[5], $week_data[6]);
    }
    static public function get_stat_week_jsdata($week_data) {
        // Make string for JS
        return implode(",", $week_data);
    }







}


?>