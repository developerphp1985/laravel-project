<?php

namespace App\Helpers;

use App\Models\Logs;
use Illuminate\Support\Facades\Auth;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerHelper

{
    function __construct()
    {
    }

    /**
     * The method to write event when existing balance.
     *
     * @var username - username who performed the operation
     * @var logType - the type of log i.e balance update, user login etc
     * @var key - column name to be updated
     * @var value - value to be subtracted
     */

    public static function writeEventInfo($username, $logType, $value, $key)
    {
        // Create the logger
        $logger = new Logger('lendoio_logger');

        // Now add some handlers nad create file day wise
        $logger->pushHandler(new StreamHandler(__DIR__ . '/Logs/info_' . date("Y_m_d") . '.log', Logger::INFO));

        //we can now use your logger to log the message
        if ($logType == "BalanceUpdate") {
            $logger->info(' USER ' . $username . ' changed ' . $key . ' to ' . $value);
        } else if ($logType == "Register") {

            $logger->info(' USER ' . $username . ' has registered to the system ');
        } else if ($logType == "Login") {

            $logger->info(' USER ' . $username . ' has logged in to system ');
        } else if ($logType == "perfectMoney") {

            $logger->info(' USER ' . $username . ' has done transaction with ' . $key . ' to ' . $value);
        } else if ($logType == "transaction") {

            $logger->info(' USER ' . $username . ' has done transaction with ' . $key . ' to ' . $value);
        } else {

        }
    }


    /**
     * The method to write event when existing balance.
     *
     * @var key - column name to be updated
     * @var value - value to be subtracted
     */
    public static function writeEventError($value, $key, $username, $logType)
    {
        // Create the logger
        $logger = new Logger('lendoio_logger');

        // Now add some handlers nad create file day wise
        $logger->pushHandler(new StreamHandler(__DIR__ . '/Logs/error_' . date("Y_m_d") . '.log', Logger::Error));

        // we can now use your logger to log the message
        $logger->info(' USER ' . $username . ' changed ' . $key . ' to ' . $value);
    }

    /**
     *
     * Write to data base
     */

    public static function writeDB(array $record)

    {
        $request = request();

        if (isset($record['userId'])) {
            $record['user_id'] = $record['userId'];
        } else {
            $record['user_id'] = Auth::id() > 0 ? Auth::id() : null;
        }

        $record['env'] = env('APP_ENV', 'production');                      // local or production
        $record['extra']['serve'] = $request->server('SERVER_ADDR');
        $record['extra']['host'] = $request->getHost();
        $record['extra']['uri'] = $request->getPathInfo();
        $record['extra']['remote_addr'] = isset($_SERVER['REMOTE_ADDR']) ? ip2long($_SERVER['REMOTE_ADDR']) : null;
        $record['extra']['user_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $record['extra']['created_by'] = Auth::id() > 0 ? Auth::id() : null;
        $record['extra']['request'] = $request->all();

        Logs::write($record);
    }

}

?>