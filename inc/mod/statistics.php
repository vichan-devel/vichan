<?php




class Statistic {


    // Gets statics for past days
    static public function get_stat() {

    }


    static public function get_stat_24h($boardName = false) {
        global $config, $pdo;

        // SELECT SUM(count) as count, hour FROM (
        //     SELECT * FROM (SELECT COUNT(*) as count, HOUR(FROM_UNIXTIME(time)) AS hour FROM posts_mlpol WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 1 DAY) GROUP BY hour) AS deriv_tmlpol 
        //     UNION ALL 
        //     SELECT * FROM (SELECT COUNT(*) as count, HOUR(FROM_UNIXTIME(time)) AS hour FROM posts_qa WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 1 DAY) GROUP BY hour) AS deriv_tqa 
        //     UNION ALL 
        //     SELECT * FROM (SELECT COUNT(*) as count, HOUR(FROM_UNIXTIME(time)) AS hour FROM posts_Test WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 1 DAY) GROUP BY hour) AS deriv_tTest
        // ) AS deriv_all GROUP BY hour ORDER BY hour DESC

        // SQL Query
        $query = "";

        if(!$boardName) {
            // Get list of all boards
            $boards = listBoards();

            // Get post count by hour for the last day
            $query = "SELECT SUM(count) as count, hour FROM (";
            foreach ($boards as $board) {
                $query .= sprintf("SELECT * FROM (SELECT COUNT(*) as count, HOUR(FROM_UNIXTIME(time)) AS hour FROM posts_%s WHERE DATE(FROM_UNIXTIME(time)) = CURDATE() GROUP BY hour) AS deriv_%s UNION ALL ", $board['uri'], $board['uri']);
            }
            // Remove the last "UNION ALL" seperator and complete the query
            $query = preg_replace('/UNION ALL $/', ') AS deriv_all GROUP BY hour ORDER BY hour ASC', $query);
        } else {
            $query = sprintf("SELECT COUNT(*) as count, HOUR(FROM_UNIXTIME(time)) AS hour FROM posts_%s WHERE DATE(FROM_UNIXTIME(time)) = CURDATE() GROUP BY hour", $boardName);
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



    static public function get_stat_week($previous_week = false, $boardName = false) {
        global $config, $pdo;

        // SQL Query
        $query = "";

        if(!$boardName) {
            // Get list of all boards
            $boards = listBoards();

            // Get post count by hour for the last week
            $query = "SELECT SUM(count) as count, day FROM (";
            foreach ($boards as $board) {
                if($previous_week)
                    $query .= sprintf("SELECT * FROM (SELECT COUNT(*) as count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(DATE_SUB(NOW(), INTERVAL 1 WEEK), 1) GROUP BY day) AS deriv_%s UNION ALL ", $board['uri'], $board['uri']);
                else
                    $query .= sprintf("SELECT * FROM (SELECT COUNT(*) as count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(NOW(), 1) GROUP BY day) AS deriv_%s UNION ALL ", $board['uri'], $board['uri']);
            }
            // Remove the last "UNION ALL" seperator and complete the query
            $query = preg_replace('/UNION ALL $/', ') AS deriv_all GROUP BY day ORDER BY day ASC', $query);
        } else {
            if($previous_week)
                $query = sprintf("SELECT COUNT(*) as count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(DATE_SUB(NOW(), INTERVAL 1 WEEK), 1) GROUP BY day", $boardName);
            else
                $query .= sprintf("SELECT COUNT(*) as count, WEEKDAY(FROM_UNIXTIME(time)) AS day FROM posts_%s WHERE YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(NOW(), 1) GROUP BY day", $boardName);

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





// SELECT(
// 	(SELECT
// 		COUNT(*),
// 		HOUR(FROM_UNIXTIME(time)) AS HH
// 	FROM posts_mlpol
// 	WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 7 DAY)
// 	GROUP BY HH)
// 	+
// 	(SELECT
// 		COUNT(*),
// 		HOUR(FROM_UNIXTIME(time)) AS HH
// 	FROM posts_qa
// 	WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 7 DAY)
// 	GROUP BY HH)
// )
// ORDER BY HH





// SELECT(
//     SELECT
// 		COUNT(*),
// 		HOUR(FROM_UNIXTIME(time)) AS HH
// 	FROM posts_mlpol
// 	WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 7 DAY)
// 	GROUP BY HH
// 	+
// 	(SELECT
// 		COUNT(*),
// 		HOUR(FROM_UNIXTIME(time)) AS HH
// 	FROM posts_qa
// 	WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 7 DAY)
// 	GROUP BY HH)
// )
// ORDER BY HH



//     // SELECT
// 	// 	COUNT(*),
// 	// 	HOUR(FROM_UNIXTIME(time)) AS HH
// 	// FROM posts_mlpol
// 	// WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 7 DAY)
// 	// GROUP BY HH



//     SELECT
// 		COUNT(*),
// 		HOUR(FROM_UNIXTIME(time)) AS HH
// 	FROM posts_mlpol
// 	WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 7 DAY)
// 	GROUP BY HH
// LEFT JOIN 
//     SELECT
// 		COUNT(*),
// 		HOUR(FROM_UNIXTIME(time)) AS HH
// 	FROM posts_qa
// 	WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 7 DAY)
// 	GROUP BY HH
// ON posts_mlpol.HH = posts_qa.HH
// ORDER BY HH






// SELECT PostingHour, SUM(PostCount)
// FROM (
//     SELECT
// 		COUNT(*) PostCount,
// 		HOUR(FROM_UNIXTIME(time)) AS PostingHour
// 	FROM posts_mlpol mlpol

//     JOIN posts_qa qa
//     ON mlpol.PostingHour = qa.PostingHour

// 	WHERE time > UNIX_TIMESTAMP(CURRENT_TIMESTAMP - INTERVAL 7 DAY)
// 	GROUP BY PostingHour
// )
// ORDER BY PostingHour



// SELECT CommunityName , SUM(MessageCount), SUM(BlogCount), SUM(EventCount)
// FROM (
//     SELECT      c.CommunityName CommunityName , COUNT(*) MessageCount, 0 BlogCount, 0 EventCount
//     FROM        Community c with(NOLOCK)
//     JOIN        messages_ m with(NOLOCK)
//     ON          c.ListKey = m.ListKey
//     GROUP BY    c.CommunityName

//     UNION

//     SELECT      c.CommunityName, 0, COUNT(*), 0
//     FROM        Community c with(NOLOCK)
//     JOIN        Blog b with(NOLOCK)
//     ON          c.CommunityKey = b.CommunityKey
//     GROUP BY    c.CommunityName

//     UNION

//     SELECT      c.CommunityName, 0, 0, COUNT(*)
//     FROM        Community c with(NOLOCK)
//     JOIN        CalendarEvent ce with(NOLOCK)
//     ON          c.CommunityKey = ce.CommunityKey
//     WHERE       ce.StartDateTime >= GETDATE()
//     GROUP BY    c.CommunityName
//   ) CountsTable
// GROUP BY CountsTable.CommunityName





// SELECT
//     c.CommunityName,
//     SUM(case when m.ListKey IS NOT NULL then 1 else 0 end) as Posts,
//     SUM(case when b.CommunityKey IS NOT NULL then 1 else 0 end) as Blogs,
//     SUM(case when ce.CommunityKey IS NOT NULL then 1 else 0 end) as Events
// FROM
//     Community c WITH(NOLOCK)
//         LEFT JOIN
//     messages_ m WITH(NOLOCK)
//         ON c.ListKey = m.ListKey
//         LEFT JOIN
//     Blog b WITH(NOLOCK)
//         ON c.CommunityKey = b.CommunityKey
//         LEFT JOIN
//     CalendarEvent ce WITH(NOLOCK)
//         ON c.CommunityKey = ce.CommunityKey
// WHERE
//     ce.StartDateTime >= GETDATE()
// GROUP BY
//     c.CommunityName








}


?>