<?php

class Zip
{
    /**
     * 压缩
     *
     * @param string $path 要压缩的文件或文件夹
     * @param string $zipName 压缩包路径, 不指定时自动命名
     * @param string $encoding 文件名编码, 不指定时使用系统编码
     * @return boolean
     */
    public function sendZip($path, $zipName = null, $encoding = null)
    {
        $tmpFile = $path . '/tmp.zip';
        $sysEncoding = $this->getSystemEncoding();
        if (!isset($encoding)) {
            $encoding = $sysEncoding;
        }
        $path = $this->conv($this->correctPath($path), $sysEncoding);
        if (isset($zipName)) {
            $zipName = $this->conv($this->correctPath($zipName), $sysEncoding);
        } else {
            if (is_dir($path)) {
                $zipName = $path . '.zip';
            } else {
                $zipName = pathinfo($path, PATHINFO_FILENAME) . '.zip';
            }
        }
        $zip = new ZipArchive();
        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
        $a = $zip->open($tmpFile, ZipArchive::CREATE);
        $this->addZip($zip, $path, $path);
        $b = $zip->close();
        $c = rename($tmpFile, $zipName);
        return $a && $b && $c;
    }

    /**
     * 解压
     *
     * @param string $zipName 要解压的压缩包
     * @param string $path 解压路径, 不指定时自动命名
     * @return int
     */
    public function unzip($zipName, $path = null)
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $tmpFile = $path . '/tmp.zip';
        $sysEncoding = $this->getSystemEncoding();
        $zipName = $this->conv($this->correctPath($zipName), $sysEncoding);
        if (isset($path)) {
            $path = $this->conv($this->correctPath($path), $sysEncoding);
        } else {
            $path = pathinfo($zipName, PATHINFO_DIRNAME) . '/' . pathinfo($zipName, PATHINFO_FILENAME);
        }
        copy($zipName, $tmpFile);
        $zip = new ZipArchive();

        if ($zip->open($tmpFile) !== true) {
            return 0;
        }

        $numFiles = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $statInfo = $zip->statIndex($i);
            $arr = explode('/', $statInfo['name']);
            $arr = array_slice($arr, 0, count($arr) - 1);
            $dir = $path . '/' . implode('/', $arr);
            if (!empty($dir) && !file_exists($dir)) {
                mkdir($dir, 0777, true);
                // continue;
            }
            $res = @copy('zip://' . $tmpFile . '#' . $statInfo['name'], $path . '/' . $statInfo['name']);
            if ($res) {
                $numFiles++;
            }
        }
        $zip->close();
        unlink($tmpFile);
        return $numFiles;
    }

    /**
     * 递归将文件加入压缩包
     *
     * @param ZipArchive $zip ZipArchive实例
     * @param string $basePath 基础路径
     * @param string $path 文件路径
     * @return void
     */
    private function addZip($zip, $basePath, $path)
    {
        if (is_dir($path)) {
            $handle = opendir($path);
            pathinfo($basePath, PATHINFO_DIRNAME);
            $dir = str_replace($basePath, '', $path);
            if ($dir[0] == '/' || $dir[0] == '\\') {
                $dir = substr($dir, 1);
            }
            $zip->addEmptyDir($dir);
            while (($file = readdir($handle)) !== false) {
                if (in_array($file, ['.', '..'])) {
                    continue;
                }
                $this->addZip($zip, $basePath, $path . '/' . $file);
            }
        } else if (file_exists($path)) {
            if ($basePath === $path) {
                $localName = array_pop(explode('/', $path));
            } else {
                $localName = substr($path, strlen($basePath) + 1);
                $localName = implode('/', explode('/', $localName));
            }
            $zip->addFile($path, $localName);
        }
    }

    /**
     * 获取系统编码
     *
     * @return string
     */
    private  function getSystemEncoding()
    {
        return PATH_SEPARATOR === ';' ? 'GBK' : 'UTF-8';
    }

    function correctPath($path)
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * 字符串转码(php7貌似不需要转码)
     *
     * @param string|array $str 要转码的字符串(或数组)
     * @param string $toEncoding 目标编码
     * @return string|array
     */
    private function conv($str, $toEncoding)
    {
        if (gettype($str) === "array") {
            foreach ($str as &$value) {
                $value = $this->conv($value, $toEncoding);
            }
            return $str;
        } else {
            if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
                return $str;
            } else {
                return mb_convert_encoding($str, $toEncoding, array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
            }
        }
    }
}
