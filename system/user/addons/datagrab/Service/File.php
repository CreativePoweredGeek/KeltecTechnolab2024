<?php

namespace BoldMinded\DataGrab\Service;

use BoldMinded\DataGrab\Dependency\Litzinger\Basee\App;

class File
{
    private $baseUploadDestinationId;
    private $createSubDirs;
    private $fileName;
    private $fileDir;
    private $fetchUrl;
    private $logger;
    private $parentDirectoryId;

    /**
     * @param string            $fileName
     * @param int|float|string  $fileDir
     * @param bool              $fetchUrl
     * @param bool              $createSubDirs
     * @param Logger            $logger
     */
    public function __construct(
        string $fileName,
        $fileDir = 1,
        $fetchUrl = false,
        $createSubDirs = false,
        $logger = null
    ){
        $this->fileName = $fileName;
        $this->fileDir = $fileDir;
        $this->fetchUrl = $fetchUrl;
        $this->createSubDirs = $createSubDirs;
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function fetch(): string
    {
        if (App::isLtEE7()) {
            return $this->getFileLegacy();
        }

        // Is it in the correct format already?
        if (preg_match('/{filedir_([0-9]+)}/', $this->fileName, $matches)) {
            return $this->fileName;
        }

        // EE7+ Filemanager
        if (preg_match('/{file:([0-9]+):url}/', $this->fileName, $matches)) {
            return $this->fileName;
        }

        // Is it a filename?
        if (preg_match('/http+/', $this->fileName, $matches) === false) {
            return "{filedir_" . $this->fileDir . "}" . $this->fileName;
        }

        // It's an external/full URL
        $url = parse_url($this->fileName);

        if (isset($url["scheme"]) && $this->fetchUrl === true) {
            ee()->load->library('filemanager');
            ee()->load->library('upload');

            $basename = basename($this->fileName);
            if (strpos($basename, "?")) {
                $basename = substr($basename, 0, strpos($basename, "?"));
            }

            if (!isset($content)) {
                $content = @file_get_contents($this->fileName);
            }
            if ($content === false) {
                return '';
            }

            // Borrowed this little bit from EE core somewhere
            $dir_ids = explode('.', $this->fileDir);
            $upload_destination_id = (int) $dir_ids[0];
            $subdirectory_id = isset($dir_ids[1]) ? (int) $dir_ids[1] : 0;

            $uploadPrefs = ee()->filemanager->fetch_upload_dir_prefs($upload_destination_id, true);
            /** @var \ExpressionEngine\Library\Filesystem\Filesystem $fs */
            $fs = $uploadPrefs['directory']->getFilesystem();

            // For some reason this is needed
            if ($fs->isLocal()) {
                ee()->upload->upload_path = $uploadPrefs['server_path'];
            }

            $basename = $this->cleanFileName($basename);
            $fileSystemEntityBasePath = '';

            // Create sub-directories recursively
            if ($url['path'] !== $basename && $this->createSubDirs) {
                $subDirs = array_filter(explode('/', $url['path']));
                array_pop($subDirs);

                if (!empty($subDirs)) {
                    $subDirPath = implode('/', $subDirs);
                    ee()->upload->upload_path .= $subDirPath;

                    // _baseServerPath and _subfolderPath in FileSystemEntity are private properties, so we're
                    // constructing the full file path instead of using getAbsolutePath() in the exists() call below.
                    $fileSystemEntityBasePath = $subDirPath .'/';

                    $subdirectory_id = $this->createSubDirectoriesRecursive($subDirPath, $upload_destination_id);
                }
            }

            /** @var \ExpressionEngine\Model\File\FileSystemEntity $file */
            $file = ee('Model')->make('FileSystemEntity');
            $fileData = [
                'upload_location_id' => $upload_destination_id,
                'directory_id' => $subdirectory_id,
                'file_name' => $basename,
            ];
            $file->set($fileData);

            $existsOnFileSystem = $fs->exists(
                $file->getBaseServerPath() .
                $fileSystemEntityBasePath .
                $file->file_name
            );

            $existsInFileManager = ee('Model')->get('File')
                ->filter('title', $basename)
                ->filter('upload_location_id', $upload_destination_id)
                ->filter('directory_id', $subdirectory_id)
                ->first();

            $result = [];
            $saveFile = true;

            ee()->upload->overwrite = true;
            ee()->upload->upload_destination = $uploadPrefs['directory'];
            // Apparently even setting this key if it's blank causes it not to work, so only set it if we have a value.
            // Also don't actually use the ee()->upload->initialize() method and pass in a clean array of arguments
            // b/c that too will mess this up. Apparently setting the properties directly is the only way.
            if ($file->getSubfoldersPath() !== '') {
                ee()->upload->upload_path = $file->getSubfoldersPath();
            }

            if($existsOnFileSystem && $existsInFileManager) {
                $result = [
                    'file_id' => $existsInFileManager->getId()
                ];
                $saveFile = false;
                $this->logger->log(
                    sprintf(
                        '%s already exists on the file system, and in the File Manager, no changes necessary.',
                        $basename
                    )
                );
            } elseif ($existsOnFileSystem && !$existsInFileManager) {
                $fileData['file_size'] = $fs->getSize($file->getAbsolutePath());
                $this->logger->log(
                    sprintf(
                        '%s already exists on the file system, but not in the File Manager.',
                        $basename
                    )
                );
            } else {
                $this->logger->log(sprintf('Attempting raw file upload of %s.', $basename));
                $rawUploadResult = ee()->upload->raw_upload($basename, $content);

                if ($rawUploadResult) {
                    $this->logger->log(sprintf('Raw file upload of %s was a success.', $basename));
                } else {
                    $this->logger->log(sprintf('Raw file upload of %s failed.', $basename));
                }

                if (!$existsOnFileSystem && $existsInFileManager && $rawUploadResult) {
                    $fileData['file_id'] = $existsInFileManager->getId();
                    $this->logger->log(
                        sprintf(
                            '%s does not exist on the file system, but exists File Manager.',
                            $basename
                        )
                    );
                } elseif (!$existsOnFileSystem && !$existsInFileManager && $rawUploadResult) {
                    // ee()->upload->file_size is available, but it's converted to kb, need original bytes here
                    $fileData['file_size'] = filesize(ee()->upload->file_temp);
                    $this->logger->log(
                        sprintf(
                            '%s does not exist on the file system or in the File Manager.',
                            $basename
                        )
                    );
                }
            }

            if ($saveFile) {
                ee()->load->library('filemanager');
                $result = ee()->filemanager->save_file(
                    $file->getAbsolutePath(),
                    $upload_destination_id,
                    $fileData,
                    false
                );

                $this->logger->log(sprintf('Attempting to save %s.', $file->getAbsolutePath()));

                if ($result && isset($result['file_id'])) {
                    $this->logger->log(sprintf('%s saved to the File Manager.', $file->getAbsolutePath()));
                }
            }

            // Use the new EE7 format if possible
            if ($result && isset($result['file_id']) && !bool_config_item('file_manager_compatibility_mode')) {
                return sprintf('{file:%d:url}', $result['file_id']);
            }

            return '{filedir_' . $upload_destination_id . '}' . $basename;
        }

        return '';
    }

    public function createSubDirectoriesRecursive(string $fullPath, int $baseUploadDestinationId): int {
        $folders = array_values(array_filter(explode('/', $fullPath)));

        $this->baseUploadDestinationId = $baseUploadDestinationId;
        $this->parentDirectoryId = 0;

        foreach ($folders as $folder) {
            $this->createSubDirectory($folder);
        }

        return $this->parentDirectoryId;
    }

    private function createSubDirectory(string $directoryName)
    {
        $uploadDirectory = ee('Model')->get('UploadDestination', $this->baseUploadDestinationId)->first();

//        if (!ee('Permission')->can('upload_new_files') ||
//            !$uploadDirectory->memberHasAccess(ee()->session->getMember()) ||
//            bool_config_item('file_manager_compatibility_mode') || !$uploadDirectory->allow_subfolders
//        ) {
//            show_error(lang('unauthorized_access'), 403);
//        }

        if ($this->parentDirectoryId !== 0) {
            $directory = ee('Model')->get('Directory', $this->parentDirectoryId)
                ->filter('upload_location_id', $this->baseUploadDestinationId)
                ->filter('model_type', 'Directory')
                ->first();

            if (empty($directory)) {
                $this->logger->log('Can\'t find parent directory id %d ' . $this->parentDirectoryId);
            }

            $filesystem = $directory->getFilesystem();
        } else {
            $filesystem = $uploadDirectory->getFilesystem();
        }

        $existing = ee('Model')->get('Directory')
            ->filter('directory_id', $this->parentDirectoryId)
            ->filter('upload_location_id', $this->baseUploadDestinationId)
            ->filter('model_type', 'Directory')
            ->filter('title', $directoryName)
            ->first();

        if ($existing) {
            $this->parentDirectoryId = $existing->file_id;
            return;
        }

        $subdir = ee('Model')->make('Directory');
        $subdir->file_name = $directoryName;
        $subdir->upload_location_id = $this->baseUploadDestinationId;
        $subdir->directory_id = $this->parentDirectoryId;
        $subdir->site_id = $uploadDirectory->site_id;

        $validation = $subdir->validate();

        if (!$validation->isValid()) {
            $this->logger->log('Invalid subdirectory creation attempt: %s ' . implode(' ', $validation->failed()));
            return;
        }

        // Directory does not exist, so attempt to create it
        $created = $filesystem->mkDir($directoryName);

        if (!$created) {
            $this->logger->log('Can\'t create directory %s ' . $directoryName);
        }

        if ($subdir->save()) {
            $this->parentDirectoryId = $subdir->file_id;
        } else {
            $this->logger->log('Can\'t save directory %s ' . $directoryName);
        }
    }

    /**
     * The raw_upload function fails if the file has more than 1 . in the name, e.g. some.file.jpg
     * Borrowed from Upload->_prep_filename(), except we're not doing any mimetype checks here.
     *
     * @param string $fileName
     * @return string
     */
    private function cleanFileName(string $fileName): string
    {
        if (strpos($fileName, '.') === false) {
            return $fileName;
        }

        $parts = explode('.', $fileName);
        $ext = array_pop($parts);
        $fileName = array_shift($parts);

        foreach ($parts as $part) {
            $fileName .= '_' . $part;
        }

        $fileName .= '.' . $ext;

        return $fileName;
    }

    /**
     * @return string
     */
    private function getFileLegacy(): string
    {
        // Is it in the correct format already?
        if (preg_match('/{filedir_([0-9]+)}/', $this->fileName, $matches)) {
            return $this->fileName;
        }

        // Is it a filename?
        if (!preg_match('/http+/', $this->fileName, $matches)) {
            return "{filedir_" . $this->fileDir . "}" . $this->fileName;
        }

        // It's an external/full URL
        $url = parse_url($this->fileName);

        if (isset($url["scheme"])) {
            ee()->load->library('filemanager');
            ee()->filemanager->xss_clean_off();

            $basename = basename($this->fileName);
            if (strpos($basename, "?")) {
                $basename = substr($basename, 0, strpos($basename, "?"));
            }
            $basetitle = $basename;

            $file_path = ee()->filemanager->clean_filename(
                $basename,
                $this->fileDir,
                array('ignore_dupes' => true)
            );

            if (file_exists($file_path)) {
                return '{filedir_' . $this->fileDir . '}' . $basename;
            }

            if ($this->fetchUrl === true) {
                if (!isset($content)) {
                    $content = @file_get_contents($this->fileName);
                }
                if ($content === false) {
                    return '';
                }

                if (file_put_contents($file_path, $content) === false) {
                    $this->logger('Can\'t copy file to ' . $file_path);
                    return '';
                }

                $result = ee()->filemanager->save_file(
                    $file_path,
                    $this->fileDir,
                    array(
                        'title' => $basetitle,
                        'path' => dirname($file_path),
                        'file_name' => $basename
                    )
                );

                if ($result['status'] === false) {
                    return '';
                }

                return '{filedir_' . $this->fileDir . '}' . $basename;
            }

        }

        return '';
    }

    /**
     * @param string $url
     * @return array
     */
    private function getRemoteFileDetails(string $url): array
    {
        $uh = curl_init();
        curl_setopt($uh, CURLOPT_URL, $url);

        // set NO-BODY to not receive body part
        curl_setopt($uh, CURLOPT_NOBODY, 1);

        // set HEADER to be false, we don't need header
        curl_setopt($uh, CURLOPT_HEADER, 0);

        // retrieve last modification time
        curl_setopt($uh, CURLOPT_FILETIME, 1);
        curl_exec($uh);

        // assign filesize into $filesize variable
        $filesize = curl_getinfo($uh, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        // assign file modification time into $filetime variable
        $filetime = curl_getinfo($uh, CURLINFO_FILETIME);
        curl_close($uh);

        return [
            'size' => $filesize,
            'time' => $filetime,
        ];
    }
}
