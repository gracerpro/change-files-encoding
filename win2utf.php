<?php

try {
    $converter = new Win2Utf8Converter();
    $converter->run();
} catch (Win2Utf8ConverterException $ex) {
    echo 'Error: ' . $ex->getMessage() . "\n";
} catch (\Exception $ex) {
    echo 'ERROR: ' . $ex->getMessage() . "\n";
}


/**
 * 1) Must be installed git and iconv, and PHP of course
 * 2) Copy this file to project directory aer (cd ...nd move to h)
 * 3) Clear history, combine the commits of task to one commit
 * 4) Run `php ./win2utf.php`
 * 5) Commit changes
 * 6) Merge with main branch (master)
 * 7) [optional] resolve conflicts
 */
class Win2Utf8Converter
{
    /** @var string */
    private $sourceDir;

    /** @var string */
    private $gitDiffExpression = 'HEAD~1 HEAD';

    /** @var string[] */
    private $excludeExtensions;

    public function __construct()
    {
        $this->sourceDir = __DIR__;
        $this->excludeExtensions = [
            'jpg', 'jpeg', 'gif', 'png', 'ico', 'cur', 'bmp',
        ];
    }

    /**
     * @return string[]
     * @throws Exception
     */
    private function getFileNames()
    {
        $files = [];
        $result = 0;
        exec('git diff ' . $this->gitDiffExpression . ' --name-only', $files, $result);
        if ($result) {
            throw new Exception('Git error');
        }
        return $files;
    }

    /**
     * @return string Without slashes on the end
     */
    public function getSourceDir()
    {
        return $this->sourceDir;
    }

    public function run()
    {
        $files = $this->getFileNames();
        if (empty($files)) {
            throw new Win2Utf8ConverterException('Empty files');
        }
        $sourceDir = $this->getSourceDir();
        foreach ($files as $file) {
            echo "iconv $file\n";
            if ($this->isSkipFile($file)) {
                echo "  skip\n";
                continue;
            }
            $sourceFilePath = $sourceDir . '/' . $file;
            $backupFilePath = $sourceFilePath . '.bak';
            if (!is_file($sourceFilePath)) {
                echo "  skip, file not found\n";
                continue;
            }

            if (!$this->convertFileEncoding($sourceFilePath, $backupFilePath)) {
                throw new Win2Utf8ConverterException('Could not convert encoding of the file ' . $file);
            }
            copy($backupFilePath, $sourceFilePath);
            unlink($backupFilePath);
        }
    }

    /**
     * @param string $fileName
     * @return boolean
     */
    private function isSkipFile($fileName)
    {
        if (empty($fileName)) {
            return true;
        }
        $ext = '';
        if ($pos = strrpos($fileName, '.')) {
            $ext = substr($fileName, $pos + 1);
        }
        return in_array($ext, $this->excludeExtensions);
    }

    /**
     * @param string $sourceFile
     * @param string $targetFile
     * @return boolean
     */
    private function convertFileEncoding($sourceFile, $targetFile)
    {
        $command = "iconv -f cp1251 -t utf-8 $sourceFile > $targetFile";
        $output = [];
        $result = 0;
        exec($command, $output, $result);
        return $result == 0;
    }
}

class Win2Utf8ConverterException extends \Exception {}
