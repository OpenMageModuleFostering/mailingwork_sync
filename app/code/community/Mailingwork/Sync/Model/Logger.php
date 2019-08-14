<?php
/**
 * mailignwork GmbH
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com and you will be sent a copy immediately.
 *
 * @category   mailingwork
 * @package    Mailingwork_Sync
 * @copyright  Copyright (c) 2016 mailingwork GmbH (http://mailingwork.de)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mailingwork_Sync_Model_Logger
{
	protected static $logFile = 'mailingwork_sync.log';

	public static function log_debug($message) {
		self::log($message, Zend_Log::DEBUG, self::$logFile);
	}

	public static function log_info($message) {
		self::log($message, Zend_Log::INFO, self::$logFile);
	}

	public static function log_error($message) {
		self::log($message, Zend_Log::ERR, self::$logFile);
	}

	/**
     * log facility (??)
     *
     * @param string $message
     * @param integer $level
     * @param string $file
     * @param bool $forceLog
     */
    public static function log($message, $level = null, $file = '', $forceLog = false)
    {
        static $loggers = array();

        $level  = is_null($level) ? Zend_Log::DEBUG : $level;

        try {
            if (!isset($loggers[$file])) {
                $logDir  = Mage::getBaseDir('var') . DS . 'log';
                $logFile = $logDir . DS . $file;

                if (!is_dir($logDir)) {
                    mkdir($logDir);
                    chmod($logDir, 0750);
                }

                if (!file_exists($logFile)) {
                    file_put_contents($logFile, '');
                    chmod($logFile, 0640);
                }

                $format = '%timestamp% %priorityName% (%priority%): %message%' . PHP_EOL;
                $formatter = new Zend_Log_Formatter_Simple($format);
                $writerModel = (string)Mage::getConfig()->getNode('global/log/core/writer_model');
                if (!Mage::app() || !$writerModel) {
                    $writer = new Zend_Log_Writer_Stream($logFile);
                }
                else {
                    $writer = new $writerModel($logFile);
                }
                $writer->setFormatter($formatter);
                $loggers[$file] = new Zend_Log($writer);
            }

            if (is_array($message) || is_object($message)) {
                $message = print_r($message, true);
            }

            $loggers[$file]->log($message, $level);
        }
        catch (Exception $e) {
        }
    }

    public function getLast50Lines() {
        $logFile = Mage::getBaseDir('var') . DS . 'log' . DS . self::$logFile;
        $lines = 50;

        $f = @fopen($logFile, "r");
        if ($f === false) return false;
        fseek($f, -1, SEEK_END);
        if (fread($f, 1) != "\n") $lines -= 1;
        $output = '';
        $chunk = '';
        while (ftell($f) > 0 && $lines >= 0) {
            $seek = min(ftell($f), 4096);
            fseek($f, -$seek, SEEK_CUR);
            $output = ($chunk = fread($f, $seek)) . $output;
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            $lines -= substr_count($chunk, "\n");
        }
        while ($lines++ < 0) {
            $output = substr($output, strpos($output, "\n") + 1);
        }
        fclose($f);
        return trim($output);
    }
}