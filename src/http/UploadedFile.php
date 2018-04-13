<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 13:19
 */
namespace moon\http;

use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
    public function getStream()
    {
        // TODO: Implement getStream() method.
    }

    public function moveTo($targetPath)
    {
        // TODO: Implement moveTo() method.
    }

    public function getSize()
    {
        // TODO: Implement getSize() method.
    }

    public function getError()
    {
        // TODO: Implement getError() method.
    }

    public function getClientFilename()
    {
        // TODO: Implement getClientFilename() method.
    }

    public function getClientMediaType()
    {
        // TODO: Implement getClientMediaType() method.
    }

    /**
     * Create a normalized tree of UploadedFile instances from the Environment.
     *
     * @param Env $env The environment
     *
     * @return array|null A normalized tree of UploadedFile instances or null if none are provided.
     */
    public static function createFromEnvironment(Env $env)
    {
        if (is_array($env['slim.files']) && $env->has('slim.files')) {
            return $env['slim.files'];
        } elseif (isset($_FILES)) {
            return static::parseUploadedFiles($_FILES);
        }

        return [];
    }

    /**
     * Parse a non-normalized, i.e. $_FILES superglobal, tree of uploaded file data.
     *
     * @param array $uploadedFiles The non-normalized tree of uploaded file data.
     *
     * @return array A normalized tree of UploadedFile instances.
     */
    private static function parseUploadedFiles(array $uploadedFiles)
    {
        $parsed = [];
        foreach ($uploadedFiles as $field => $uploadedFile) {
            if (!isset($uploadedFile['error'])) {
                if (is_array($uploadedFile)) {
                    $parsed[$field] = static::parseUploadedFiles($uploadedFile);
                }
                continue;
            }

            $parsed[$field] = [];
            if (!is_array($uploadedFile['error'])) {
                $parsed[$field] = new static(
                    $uploadedFile['tmp_name'],
                    isset($uploadedFile['name']) ? $uploadedFile['name'] : null,
                    isset($uploadedFile['type']) ? $uploadedFile['type'] : null,
                    isset($uploadedFile['size']) ? $uploadedFile['size'] : null,
                    $uploadedFile['error'],
                    true
                );
            } else {
                $subArray = [];
                foreach ($uploadedFile['error'] as $fileIdx => $error) {
                    // normalise subarray and re-parse to move the input's keyname up a level
                    $subArray[$fileIdx]['name'] = $uploadedFile['name'][$fileIdx];
                    $subArray[$fileIdx]['type'] = $uploadedFile['type'][$fileIdx];
                    $subArray[$fileIdx]['tmp_name'] = $uploadedFile['tmp_name'][$fileIdx];
                    $subArray[$fileIdx]['error'] = $uploadedFile['error'][$fileIdx];
                    $subArray[$fileIdx]['size'] = $uploadedFile['size'][$fileIdx];

                    $parsed[$field] = static::parseUploadedFiles($subArray);
                }
            }
        }

        return $parsed;
    }

}