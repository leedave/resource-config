<?php

namespace Leedch\Config;

/**
 * Creates Config Files for projects
 * @author david.lee
 */
class Update 
{
    protected static $targetConfigFolder = "configs/";
    protected static $sourceConfigFolder = __DIR__ . "/../../../updates/configs/";
    
    public function createConfigs() {
        $arrSourceFiles = self::findNewFiles();
        self::createDirectory();
        foreach ($arrSourceFiles as $file) {
            self::createConfig($file);
        }
    }
    
    protected static function createConfig(string $filePath) 
    {
        $arrNew = self::getNewConfigsFromFile($filePath);
        $arrExisting = self::getExistingConfigsFromFile($filePath);
        foreach ($arrExisting as $key => $val) {
            $arrNew[$key] = $val;
        }
        self::writeConfigFile($filePath, $arrNew);
    }
    
    protected static function createDirectory() {
        if (!file_exists(self::$targetConfigFolder)) {
            mkdir(self::$targetConfigFolder);
            self::createHtaccess();
        }
    }
    
    protected static function createHtaccess() {
        $fp = fopen(self::$targetConfigFolder . '.htaccess', 'w');
        fputs($fp, 'DENY FROM ALL');
        fclose($fp);
    }
    
    /**
     * Creates a config file using input params
     */
    protected static function writeConfigFile(
        string $fileNameSource,
        array $arrParameters
    )  
    {
        $fileName = self::getFileNameFromFullPath($fileNameSource);
        $targetFile = self::$targetConfigFolder . $fileName;
        $strDbConfig = "<?php\n";
        foreach ($arrParameters as $key => $val) {
            $bash = fopen('php://stdin', 'r');
            echo "\e[0;32;40m".$key."\e[0m [".$val."]: ";
            $input = str_replace("\n", "", fgets($bash));
            if ($input !== "") {
                $val = $input;
            }
            fclose($bash);
            $strDbConfig .= "define('".$key."', '".$val."');\n";
        }
        $fp = fopen($targetFile, 'w');
        fputs($fp, $strDbConfig);
        fclose($fp);
    }
    
    /**
     * On updates, we want to preserve settings that were previously made
     * This method uses the existing setting as default option
     * @param string $filePath
     * @return array
     */
    protected static function getExistingConfigsFromFile(string $filePath) : array
    {
        $arrResponse = [];
        $fileName = self::getFileNameFromFullPath($filePath);
        $targetFile = self::$targetConfigFolder . $fileName;
        if (!file_exists($targetFile)) {
            return $arrResponse;
        }
        $fp = fopen($targetFile, "r");
        fgets($fp);
        while (!feof($fp)) {
            $line = fgets($fp);
            if (strlen($line) < 8) {
                continue;
            }
            $lineFront = substr($line, 8); //remove define('
            $varName = substr($lineFront, 0, strpos($lineFront, '\''));
            $lineBack = substr($lineFront, strpos($lineFront, '\', \'')+4);
            $valName = substr($lineBack, 0, strpos($lineBack, '\''));
            $arrResponse[$varName] = $valName;
        }
        fclose($fp);
        
        return $arrResponse;
    }
    
    /**
     * Read the configs to be set from file
     * @param string $filePath
     * @return array
     */
    protected static function getNewConfigsFromFile(string $filePath) : array 
    {
        require $filePath;
        $arrResponse = $arrConfigs;
        return $arrResponse;
    }
    
    /**
     * Seperate the filename from the directory path
     * @param type $filePath
     * @return string
     */
    protected static function getFileNameFromFullPath($filePath) : string
    {
        $arrFile = explode("/", $filePath);
        return array_pop($arrFile);
    }
    
    /**
     * Return a list of existing config presets 
     * @param string $path
     * @return array
     */
    protected static function findNewFiles(string $path = "") : array
    {
        if ($path === "") {
            $path = self::$sourceConfigFolder;
        }
        $arrReturn = [];
        $arrFolders = scandir($path);
        $arrSkip = [
            '.',
            '..',
        ];
        foreach ($arrFolders as $file) {
            //No Folder Defaults
            if (in_array($file, $arrSkip)) {
                continue;
            }
            //Process Subfolders
            if (is_dir($path.$file)) {
                $arrSubFolderFiles = $this->findNewFiles($path.$file."/");
                $arrReturn = array_merge($arrReturn, $arrSubFolderFiles);
                continue;
            }
            $arrReturn[] = $path.$file;
        }
        return $arrReturn;
    }
}