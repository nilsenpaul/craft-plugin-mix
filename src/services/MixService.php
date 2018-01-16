<?php
/**
 * Craft Mix
 *
 * @author    mister bk! GmbH
 * @copyright Copyright (c) 2017 mister bk! GmbH
 * @link      https://www.mister-bk.de/
 */

namespace misterbk\mix\services;

use misterbk\mix\Mix;

use Craft;
use craft\base\Component;

use Exception;

class MixService extends Component
{
    /**
     * Path to the root directory.
     *
     * @var string
     */
    protected $rootPath;

    /**
     * Path to the public directory.
     *
     * @var string
     */
    protected $publicPath;

    /**
     * Path to the asset directory.
     *
     * @var string
     */
    protected $assetPath;

    /**
     * Path of the manifest file.
     *
     * @var string
     */
    protected $manifest;


    /**
     * @inheritdoc
     */
    public function init()
    {
        $settings = Mix::$plugin->getSettings();

        $this->rootPath = rtrim(CRAFT_BASE_PATH, '/');
        $this->publicPath = trim($settings->publicPath, '/');
        $this->assetPath = trim($settings->assetPath, '/');
        $this->manifest = implode('/', [
            $this->rootPath,
            $this->publicPath,
            $this->assetPath,
            'mix-manifest.json'
        ]);
    }

    /**
     * Find the files version.
     *
     * @param  string  $file
     * @return string
     */
    public function version($file)
    {
        try {
            $manifest = $this->readManifestFile();
        } catch (Exception $e) {
            Craft::info('Mix: ' . printf($e->getMessage()), __METHOD__);
        }

        if ($manifest) {
            $file = $manifest['/' . ltrim($file, '/')];
        }

        return '/' . $this->assetPath . '/' . ltrim($file, '/');
    }

    /**
     * Returns the files version with the appropriate tag.
     *
     * @param  string  $file
     * @param  bool  $inline  (optional)
     * @return string
     */
    public function withTag($file, $inline = false)
    {
        $versionedFile = $this->version($file);
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        if ($inline) {
            $versionedFile = strtok($versionedFile, '?');
            $absoluteFile = implode('/', [
                $this->rootPath,
                $this->publicPath,
                ltrim($versionedFile, '/')
            ]);
            if (file_exists($absoluteFile)) {
                $content = file_get_contents($absoluteFile);

                if ($extension === 'js') {
                    return '<script>' . $content . '</script>';
                }

                return '<style>' . $content . '</style>';
            }
        }

        if ($extension === 'js') {
            return '<script src="' . $versionedFile . '"></script>';
        }

        return '<link rel="stylesheet" href="' . $versionedFile . '">';
    }

    /**
     * Locate manifest and convert to an array.
     *
     * @return array|bool
     */
    protected function readManifestFile()
    {
        if (file_exists($this->manifest)) {
            return json_decode(
                file_get_contents($this->manifest),
                true
            );
        }

        return false;
    }
}
