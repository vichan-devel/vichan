<?php

class logger {
    private static $log_file = "log.txt";
    private static $log_file_path = "/var/www/logs/";

    public static function log($message, $includeDate = true) {
        // check if directory exists
        if (!file_exists(self::$log_file_path)) {
            mkdir(self::$log_file_path, 0777, true);
        }

        // check if file exists
        if (!file_exists(self::$log_file_path . "/" . self::$log_file)) {
            file_put_contents(self::$log_file_path . "/" . self::$log_file, "");
        }

        if ($includeDate) {
            $date = date('Y-m-d H:i:s');
            $ip = $_SERVER['REMOTE_ADDR'];
            $log = "$date - $ip \n$message\n-------------------\n";
        } else {
            $log = "$message\n-------------------\n";
        }

        file_put_contents(self::$log_file_path . "/" . self::$log_file, $log, FILE_APPEND);
    }

    public static function arrayToLog($array, $includeDate = true) {
        $log = print_r($array, true);
        self::log($log, $includeDate);
    }

    public static function stackTraceLog( $callStack, $includeDate = true ) {
        $log = "Stack trace:\n";
        try {
            // $log .= "Got: " . $callStack[0]['file'] . " : " . $callStack[0]['line'] . "\n";
            
            foreach ($callStack as $key => $value) {
                $function = isset($value['function']) ? $value['function'] : "unknown";
                $file = isset($value['file']) ? $value['file'] : "unknown";
                $line = isset($value['line']) ? $value['line'] : "unknown";

                $message = isset($value['args']) ? print_r($value['args'], true) : "unknown";

                // if ($key == 0) {
                //     $log .= "   " . $function . " at: " . $file . " : " . $line . "\n";
                //     $log .= "   " . $message[1] . "\n";
                //     continue;
                // }
                $log .= "   " . $function . " at: " . $file . " : " . $line . "\n";
            }
        } catch (Exception $e) {
            echo '<pre>';
            echo "Original stack trace: \n";
            echo $e->getMessage();
            print_r($callStack->getTrace());
            echo '</pre>';
            echo '<pre>';
            echo 'Current stack trace: \n';
            echo $e->getMessage();
            print_r($e->getTrace());
            echo '</pre>';

            die("Unable to parse stack trace");
        }
        self::log($log, $includeDate);
    }
}