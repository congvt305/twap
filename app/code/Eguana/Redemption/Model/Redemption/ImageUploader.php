<?php
/**
 * @author Eguana Team
 * @copyriht Copyright (c) 2020 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: arslan
 * Date: 16/10/20
 * Time: 12:00 PM
 */
namespace Eguana\Redemption\Model\Redemption;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Uploader;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Feature image uploader
 *
 */
class ImageUploader
{
    /**#@+
     * Constants for Image Path and Image Tmp Path
     */
    const IMAGE_TMP_PATH = 'redemption/tmp/feature';
    const IMAGE_PATH = 'redemption/feature';
    /**#@-*/

    /**
     * Core file storage database
     *
     * @var Database
     */
    protected $coreFileStorageDatabase;

    /**
     * Media directory object (writable).
     *
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * Uploader factory
     *
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Base tmp path
     *
     * @var string
     */
    protected $baseTmpPath;

    /**
     * Base path
     *
     * @var string
     */
    protected $basePath;

    /**
     * Allowed extensions
     *
     * @var string
     */
    protected $allowedExtensions;

    /**
     * List of allowed image mime types
     *
     * @var string[]
     */
    protected $allowedMimeTypes;

    /**
     * ImageUploader constructor
     *
     * @param Database $coreFileStorageDatabase
     * @param Filesystem $filesystem
     * @param UploaderFactory $uploaderFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param string $baseTmpPath
     * @param string $basePath
     * @param string[] $allowedExtensions
     * @param string[] $allowedMimeTypes
     * @throws FileSystemException
     */
    public function __construct(
        Database $coreFileStorageDatabase,
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        $baseTmpPath = self::IMAGE_TMP_PATH,
        $basePath = self::IMAGE_PATH,
        $allowedExtensions = [],
        $allowedMimeTypes = []
    ) {
        $this->coreFileStorageDatabase = $coreFileStorageDatabase;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->uploaderFactory = $uploaderFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->baseTmpPath = $baseTmpPath;
        $this->basePath = $basePath;
        $this->allowedExtensions = $allowedExtensions;
        $this->allowedMimeTypes = $allowedMimeTypes;
    }

    /**
     * Set base tmp path
     *
     * @param string $baseTmpPath
     * @return void
     */
    public function setBaseTmpPath($baseTmpPath)
    {
        $this->baseTmpPath = $baseTmpPath;
    }

    /**
     * Set base path
     *
     * @param string $basePath
     * @return void
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * Set allowed extensions
     *
     * @param string[] $allowedExtensions
     * @return void
     */
    public function setAllowedExtensions($allowedExtensions)
    {
        $this->allowedExtensions = $allowedExtensions;
    }

    /**
     * Retrieve base tmp path
     *
     * @return string
     */
    public function getBaseTmpPath()
    {
        return $this->baseTmpPath;
    }

    /**
     * Retrieve base path
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Retrieve allowed extensions
     *
     * @return string[]
     */
    public function getAllowedExtensions()
    {
        return $this->allowedExtensions;
    }

    /**
     * Retrieve path
     *
     * @param $path
     * @param $imageName
     * @return string
     */
    public function getFilePath($path, $imageName)
    {
        return rtrim($path, '/') . '/' . ltrim($imageName, '/');
    }

    /**
     * Checking file for moving and move it
     *
     * @param $imageName
     * @return string
     * @throws LocalizedException
     */
    public function moveFileFromTmp($imageName)
    {
        $baseTmpPath = $this->getBaseTmpPath();
        $basePath = $this->getBasePath();

        $baseImagePath = $this->getFilePath(
            $basePath,
            Uploader::getNewFileName(
                $this->mediaDirectory->getAbsolutePath(
                    $this->getFilePath($basePath, $imageName)
                )
            )
        );
        $baseTmpImagePath = $this->getFilePath($baseTmpPath, $imageName);

        try {
            $this->coreFileStorageDatabase->copyFile(
                $baseTmpImagePath,
                $baseImagePath
            );
            $this->mediaDirectory->renameFile(
                $baseTmpImagePath,
                $baseImagePath
            );
        } catch (Exception $e) {
            throw new LocalizedException(
                __('Something went wrong while saving the file(s).')
            );
        }
        return $imageName;
    }

    /**
     * Checking file for save and save it to tmp dir
     *
     * @param string $fileId
     * @return string[]
     * @throws LocalizedException
     */
    public function saveFileToTmpDir($fileId)
    {
        $baseTmpPath = $this->getBaseTmpPath();
        $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
        $uploader->setAllowedExtensions($this->getAllowedExtensions());
        $uploader->setAllowRenameFiles(true);

        $result = $uploader->save($this->mediaDirectory->getAbsolutePath($baseTmpPath));

        if (!$result) {
            throw new LocalizedException(
                __('File can not be saved to the destination folder.')
            );
        }

        /**
         * Workaround for prototype 1.7 methods "isJSON", "evalJSON" on Windows OS
         */
        $result['tmp_name'] = str_replace('\\', '/', $result['tmp_name']);
        $result['url'] = $this->storeManager
                ->getStore()
                ->getBaseUrl(
                    UrlInterface::URL_TYPE_MEDIA
                ) . $this->getFilePath($baseTmpPath, $result['file']);
        $result['name'] = $result['file'];

        if (isset($result['file'])) {
            try {
                $relativePath = rtrim($baseTmpPath, '/') . '/' . ltrim($result['file'], '/');
                $this->coreFileStorageDatabase->saveFile($relativePath);
            } catch (Exception $e) {
                $this->logger->critical($e);
                throw new LocalizedException(
                    __('Something went wrong while saving the file(s).')
                );
            }
        }
        return $result;
    }
}
