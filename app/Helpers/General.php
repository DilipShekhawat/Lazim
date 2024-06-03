<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use VideoThumbnail;

class General {

    public static $defaultUploadPath = '';
    // File Driver
    public static $fileDriver;
    public static $localDriverOptions;
    // File Object
    public static $file;
    // Image file path
    public static $imagename;
    // Folder Path/Folder Name
    public static $folderPath;
    // true = If thumb require to store, false = Don't want to store thumb
    public static $is_thumb = false;
    // Default Thumb folder path
    public static $thumbFolder;
    // default thumb image height
    public static $thumbHeight = '150';
    // default thumb image width
    public static $thumbWidth = '150';
    // public path
    public static $public_path;
    // storage path
    public static $storage_path;
    // Old File Name
    public static $old_file;
    public static $jobFolder = 'poc_jobs/video_preview/';

    public function __construct() {
        self::$fileDriver = config('filesystems.default');
        self::$public_path = public_path() . '/';
        self::$storage_path = storage_path() . '/';
        self::$thumbFolder = self::$folderPath . 'thumb/';
        General::$localDriverOptions = config('filesystems.localdriveroptions');
    }

    /**
     * @description Upload file and create thumbnail
     * @param array $files array of files
     * @param boolean $is_thumbnail true|false
     * @param string $old_file  name of old image to be removed if any
     * @return array file path
     *
     */
    public static function copyFiles($oldPath, $newPath) {
        try {
            return \Storage::disk('s3')->copy($oldPath, $newPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function uploadImage($file, $folder, $is_thumbnail = false, $old_file = '') {
        try {
            $path = self::$public_path . $folder . '/';

            if (isset($old_file) && !empty($old_file)) {
                $main = $path . '/main/' . $old_file;
                $small = $path . '/small/' . $old_file;
                $thumbnail = $path . '/thumbnail/' . $old_file;
                $documents = $path . '/documents/' . $old_file;
                if (file_exists($main)) {
                    unlink($main);
                }
                if (file_exists($small)) {
                    unlink($small);
                }
                if (file_exists($thumbnail)) {
                    unlink($thumbnail);
                }
                if (file_exists($documents)) {
                    unlink($documents);
                }

                self::deleteS3Object($folder . '/main/' . $old_file);
                self::deleteS3Object($folder . '/small/' . $old_file);
                self::deleteS3Object($folder . '/thumbnail/' . $old_file);
                self::deleteS3Object($folder . '/video/' . $old_file);
            }

            $name = str_replace([' ', ':'], '-', Carbon::now()->toDateTimeString() . '-' . $file->getClientOriginalName());
            $imagetype = explode('/', $file->getClientMimeType());
            if (in_array($imagetype[count($imagetype) - 1], array('jpeg', 'bmp', 'jpg', 'gif', 'png', 'PNG', 'JPG', 'JPEG'))) {
                $type = 'images';
            } else if (in_array($imagetype[count($imagetype) - 1], array('pdf'))) {
                $type = 'documents';
            } else if (in_array($imagetype[count($imagetype) - 1], array('3gp', 'mp4', 'mp3', 'MP4', 'MP3'))) {
                $type = 'video';
            } else {
                return response()->json(['error' => 'FileType not supported']);
            }

            if (!is_dir($path)) {
                mkdir($path);
                chmod($path, 0777);
            }

            if ($type == 'images') {
                $mainPath = $path . 'main/';
                if (!is_dir($mainPath)) {
                    mkdir($mainPath);
                    chmod($mainPath, 0777);
                }
                $file->move($path . '/main', $name);
                $filepath = $path . '/main/' . $name;
                self::uploadFileToS3($filepath, $folder . '/main/' . $name);

                if ($is_thumbnail == true) {
                    $smallPath = $path . 'small/';
                    if (!is_dir($smallPath)) {
                        mkdir($smallPath);
                        chmod($smallPath, 0777);
                    }

                    $thumbnailPath = $path . 'thumbnail/';
                    if (!is_dir($thumbnailPath)) {
                        mkdir($thumbnailPath);
                        chmod($thumbnailPath, 0777);
                    }

                    //small image
                    $smallfilepath = $path . '/small/' . $name;
                    $img = \Image::make($path . '/main/' . $name);
                    $img->resize(640, 480);
                    $img->save($smallfilepath);
                    self::uploadFileToS3($smallfilepath, $folder . '/small/' . $name);

                    //thumbnail image
                    $thumbfilepath = $path . '/thumbnail/' . $name;
                    $img = \Image::make($path . '/main/' . $name);
                    $img->resize(248, 198);
                    $img->save($thumbfilepath);
                    self::uploadFileToS3($thumbfilepath, $folder . '/thumbnail/' . $name);

                    unlink($filepath);
                    unlink($smallfilepath);
                    unlink($thumbfilepath);
                }
            }
            if ($type == 'video') {
                $mainPath = $path . 'video/';
                if (!is_dir($mainPath)) {
                    mkdir($mainPath);
                    chmod($mainPath, 0777);
                }
                $file->move($path . '/video', $name);
                $filepath = $path . '/video/' . $name;
                self::uploadFileToS3($filepath, $folder . '/video/' . $name);
            }

            return $name;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * @description Upload file to S3
     * @param array $file file object
     * @return array file patha
     */
    public static function uploadFileToS3($file_path, $file) {
        try {
            $file_path = self::$defaultUploadPath . $file_path;
            $file_contents = file_get_contents($file);
            $s3 = \Storage::disk('s3')->put($file_path, $file_contents, 'public');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @description Delete file from s3 bucket
     * @param string $file File name with path
     * @return boolean true|false
     */
    public static function deleteS3Object($file) {
        try {
            $file = self::$defaultUploadPath . $file;
            $s3 = \Storage::disk('s3');
            if ($s3->exists($file)) {
                $s3->delete($file);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get s3 image url
     * @param string $file Objecte name
     * @param string $path Directory path of s3 bucket
     * @param bool $check_image Check if image exists or not. Default true
     * @return string Returns image url
     */
    public static function getS3ObjectURL($file, $path = '', $check_image = false) {
        $return_image = asset('assets/images/noimage.png');
        $path = self::$defaultUploadPath . $path;
        if ($file != '') {
            try {
                if ($check_image) {
                    $s3 = \Storage::disk('s3');
                    if ($s3->exists(trim($path, '/') . '/' . $file)) {
                        $return_image = 'https://s3-' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/' . env('AWS_BUCKET') . '/' . trim($path, '/') . '/' . $file;
                    }
                } else {
                    $return_image = 'https://s3-' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/' . env('AWS_BUCKET') . '/' . trim($path, '/') . '/' . $file;
                }
            } catch (\Exception $e) {
                return asset('assets/images/noimage.png');
            }
        }
        return $return_image;
    }

    /**
     * Get s3 image url
     * @param string $path Directory path of s3 bucket
     * @return string Returns image url
     */
    public static function getS3URL($path = '') {
        return 'https://s3-' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/' . env('AWS_BUCKET') . '/' . trim($path, '/');
    }

    /**
     * Send mail general function
     * @param string $to receiver email
     * @param string $templatePath template path
     * @param string $subject Subject of mail
     * @param array $data data array
     * @param string $attachment Specify file path to be attached
     * @param string $from_email from email
     * @param string $from_name from name
     */
    public static function sendMail($to, $templatePath, $subject = '', $data = [], $attachment = '', $from_email = '', $from_name = '') {

        $from_email = $from_email == '' ? config('bmc.admin_from_email') : $from_email;
        $from_name = $from_name == '' ? config('bmc.admin_from_name') : $from_name;
        $data['siteName'] = config('bmc.app_name');

        $templatePath = resource_path('views') . '/' . str_replace('.', '/', $templatePath) . '.blade.php';
        $content = \App\EmailTemplate::getEmailTemplateData($templatePath);

        $tmp_file = time() . rand();
        $file = resource_path('views/emails/tmp') . '/' . $tmp_file . '.blade.php';
        $bytes_written = \File::put($file, \General::emailheaderfooter($content['body']));
        if ($bytes_written !== false) {
            $mail = [
                'from_email' => $from_email,
                'from_name' => $from_name,
                'to' => $to,
                'subject' => $content['subject'],
            ];

            \Mail::queue('emails.tmp.' . $tmp_file, $data, function ($m) use ($mail, $attachment) {
                $m->from($mail['from_email'], $mail['from_name']);
                $m->to($mail['to']);
                $m->subject($mail['subject']);
                if (!empty($attachment)) {
                    $m->attach($attachment);
                }
            });
        }
    }

    public static function emailheaderfooter($body = '') {

        return '<html><body><table border="0" width="100%" align="center" cellspacing="0" cellpadding="0">
          <tbody><tr>
            <td style="min-height:100px;background: #5b5b5b;text-align:center;align-content: center;">
              <a class="navbar-brand" href="' . url('/') . '" style="text-align: center;">
                &nbsp;<img src="' . url('/') . '/dashboard/images/logo.png" alt="" style="align-self: center;">&nbsp;</a>
            </td>
          </tr></tbody></table>
            <div class="table-responsive" width="100%">' . $body . '</div>
          <table border="0" width="100%" align="center" cellspacing="0" cellpadding="0">
          <tbody>
          <tr>
            <td align="center" style="margin-top:50px; display:block; background: #5b5b5b; color:#fff; padding:10px 0;"><p>Copyright © 2015-' . date('Y') . ' Bookmycranes.com - All Rights Reserved</p>
            </td>
          </tr>
        </tbody></table></body></html>';
    }

    /**
     * Image Upload function
     * @param array
     *
     */
    public static function imageFileUpload($imageObj = array()) {
        self::$fileDriver = $imageObj->driver ?? self::$fileDriver;

        self::$file = $imageObj->file;

        self::$folderPath = $imageObj->folder;
        // Set Old File variable
        self::$old_file = $imageObj->old_file ?? self::$old_file;
        // Set is_thumb variable
        if (!empty($imageObj->is_thumb)) {
            self::$is_thumb = $imageObj->is_thumb;
            self::$thumbFolder = $imageObj->thumb_folder ?? self::$thumbFolder;
            self::$thumbHeight = $imageObj->thumb_height ?? self::$thumbHeight;
            self::$thumbWidth = $imageObj->thumb_width ?? self::$thumbWidth;
        }

        if (in_array(self::$fileDriver, General::$localDriverOptions)) {
            return self::uploadLocalImage();
        } else if (self::$fileDriver === 's3') {
            return self::uploadS3Image(self::$file, self::$folderPath, self::$thumbFolder, self::$is_thumb, self::$thumbHeight, self::$thumbWidth);
        }
    }

    public static function generateImageFileName($file) {
        $extension = $file->getClientOriginalExtension();
        $fileName = md5(str_replace([' ', ':'], '-', Carbon::now()->timestamp . '-' . $file->getFileName())) . '.' . $extension;
        return $fileName;
    }

    /**
     * Image Upload to local/public drive.
     * @return string `File Name`
     *
     */
    public static function uploadLocalImage() {
        try {
            $file = self::$file;
            $extension = $file->getClientOriginalExtension();
            $fileName = General::generateImageFileName($file);
            if (in_array($extension, ['jpeg', 'bmp', 'jpg', 'gif', 'png', 'PNG', 'JPG', 'JPEG'])) {
                // Remove Old Image #####
                if (!empty(self::$old_file) && Storage::exists(self::$folderPath . self::$old_file)) {
                    Storage::delete(self::$folderPath . self::$old_file);
                    if (Storage::exists(self::$thumbFolder . self::$old_file)) {
                        Storage::delete(self::$thumbFolder . self::$old_file);
                    }
                }
                $image = \Image::make($file)->stream();
                Storage::put(self::$folderPath . $fileName, $image->__toString());
                if (self::$is_thumb == true) {
                    //store image in resizesize
                    $thumb_image = \Image::make($file)->resize(self::$thumbHeight, self::$thumbWidth, function ($constraint) {
                                $constraint->aspectRatio();
                            }) // exact resize image without cropping, which will affect the size
                            //->fit(self::$thumbHeight, self::$thumbWidth)// exact fit to given size without affecting the resolution, crop the image
                            ->stream();
                    Storage::put(self::$thumbFolder . $fileName, $thumb_image->__toString());
                }
            } else {
                // move the uploaded file to the storage folder
                $resultPath = str_replace('//', '/', (Storage::putFile(self::$folderPath, $file)));
                $fileName = substr($resultPath, strrpos($resultPath, '/') + 1);
                $pdfName = Str::before($fileName, '.pdf') . '.png';

                // create image from the store pdf
                $pdf = new \Spatie\PdfToImage\Pdf(storage_path('app/public/' . $resultPath));
                $pdf->setOutputFormat('png')->saveImage(self::$public_path . 'storage/' . self::$folderPath . $pdfName);

                // resize the image to a width of 150 and constrain aspect ratio (auto height)
                $image = Image::make(self::$public_path . 'storage/' . self::$folderPath . $pdfName);
                $image->resize(150, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->save();
            }
            return $fileName;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Common method to fetch the Image URL
     * @param object
     * @return string `image URL`
     *
     * * */
    public function getImageURL($imageObj = array()) {
        try {
            self::$fileDriver = $imageObj->driver ?? self::$fileDriver;
            self::$imagename = $imageObj->filename;
            self::$folderPath = $imageObj->folder;
            // Set is_thumb variable
            if (!empty($imageObj->is_thumb)) {
                self::$is_thumb = $imageObj->is_thumb;
                // set thumb folder path
                self::$thumbFolder = $imageObj->thumb_folder ?? self::$thumbFolder;
                self::$folderPath = $imageObj->thumb_folder;
            }

            if (in_array(self::$fileDriver, General::$localDriverOptions)) {
                return General::getLocalImageURL();
            } else if (self::$fileDriver === 's3') {
                return General::getS3ObjectURL(self::$imagename, self::$folderPath, true);
            }
            return '';
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Common method to fetch the Image URL
     * @param object
     * @return string `image URL`
     *
     * * */
    public function getAbsoluteImageURL($imageObj = array()) {
        try {
            self::$fileDriver = $imageObj->driver ?? self::$fileDriver;
            self::$imagename = $imageObj->filename;
            self::$folderPath = $imageObj->folder;
            // Set is_thumb variable
            if (!empty($imageObj->is_thumb)) {
                self::$is_thumb = $imageObj->is_thumb;
                // set thumb folder path
                self::$thumbFolder = $imageObj->thumb_folder ?? self::$thumbFolder;
            }

            if (in_array(self::$fileDriver, General::$localDriverOptions)) {
                return General::getLocalAbsoluteImageURL();
            } else if (self::$fileDriver === 's3') {
                return General::getS3ObjectURL(self::$imagename, self::$folderPath, true);
            }
            return '';
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show Image to local/public drive.
     * @return string `Image Name`
     *
     */
    public static function getLocalImageURL() {
        $folder_path = self::$folderPath;
        if (self::$is_thumb === true) {
            $folder_path = self::$thumbFolder;
        }
        if (!empty(self::$imagename) && Storage::exists($folder_path . self::$imagename)) {
            return Storage::url($folder_path . self::$imagename);
        }
        return asset('assets/images/noimage.png');
    }

    /**
     * Show Image to local/public drive.
     * @return string `Image Name`
     *
     */
    public static function getLocalAbsoluteImageURL() {
        $folder_path = self::$folderPath;
        if (self::$is_thumb === true) {
            $folder_path = self::$thumbFolder;
        }

        if (!empty(self::$imagename) && Storage::exists($folder_path . self::$imagename)) {
            return Storage::url($folder_path . self::$imagename);
        }

        if (strpos($folder_path, 'users') !== false) {
            return '';
        } else {
            return asset('assets/images/noimage.png');
        }
    }

    public static function getLocalFileType($file) {
        $extension = $file->getClientOriginalExtension();
        if (in_array($extension, ['jpeg', 'bmp', 'jpg', 'gif', 'png', 'PNG', 'JPG', 'JPEG'])) {
            return 'image';
        } else {
            return 'video';
        }
    }

    /**
     * @description Upload file and create thumbnail
     * @param array $files array of files
     * @param boolean $is_thumbnail true|false
     * @param string $old_file  name of old image to be removed if any
     * @return array file path
     *
     */
    public static function uploadS3Image() {
        try {
            $file = General::$file;
            $folder = General::$folderPath;
            $is_thumbnail = General::$is_thumb;
            $thumb_height = General::$thumbHeight;
            $thumb_width = General::$thumbWidth;
            $old_file = self::$old_file;
            $thumb_folder = General::$thumbFolder;
            $path = $folder;
            if (isset($old_file) && !empty($old_file)) {
                self::deleteS3Object($folder . $old_file);
                self::deleteS3Object($thumb_folder . $old_file);
            }

            $name = General::generateImageFileName($file);
            $imagetype = explode('/', $file->getClientMimeType());
            $extension = $file->getClientOriginalExtension();

            if (in_array($extension, array('jpeg', 'bmp', 'jpg', 'gif', 'png', 'PNG', 'JPG', 'JPEG'))) {
                $type = 'images';
            } else if (in_array($imagetype[count($imagetype) - 1], array('pdf'))) {
                $type = 'documents';
            } else if (in_array($extension, array('3gp', 'mp3', 'mp4'))) {
                $type = 'video';
            } else {
                return response()->json(['error' => 'FileType not supported.']);
            }

            if ($type == 'images') {
                $filepath = $folder . $name;

                self::uploadFileToS3($filepath, $file);
                if ($is_thumbnail == true) {

                    //thumbnail image
                    $thumbfilepath = $thumb_folder . $name;
                    self::upload_thumb_image($file, $thumbfilepath, $thumb_width, $thumb_height);
                }
            }

            if ($type == 'documents') {
                $filepath = $folder . $name;
                self::uploadFileToS3($filepath, $file);
            }

            if ($type == 'video') {
                $filepath = $folder . $name;

                $path = self::$public_path . 'storage/jobs/';
                self::uploadFileToS3($filepath, $file);

                $videoPath = $path . 'video/';
                $thumbPath = $path . 'thumb/';

                if (!is_dir($videoPath)) {
                    mkdir($videoPath, 0777, true);
                    chmod($videoPath, 0777);
                }

                if (!is_dir($thumbPath)) {
                    mkdir($thumbPath, 0777, true);
                    chmod($thumbPath, 0777);
                }
                $file->move($videoPath, $name);
                $videoUrl = self::$storage_path . 'app/public/jobs/video/' . $name;
                $storageUrl = self::$storage_path . 'app/public/jobs/thumb';

                $thumb_name = str_replace($extension, '', $name);
                VideoThumbnail::createThumbnail($videoUrl, $storageUrl, $thumb_name . 'jpg', 0, $width = 440, $height = 280);

                $videoimgfilepath = self::$jobFolder . $thumb_name . 'jpg';

                self::uploadFileToS3($videoimgfilepath, $storageUrl . '/' . $thumb_name . 'jpg');

                if (file_exists($videoUrl)) {
                    unlink($videoUrl);
                }
                if (file_exists($storageUrl . '/' . $thumb_name . 'jpg')) {
                    unlink($storageUrl . '/' . $thumb_name . 'jpg');
                }
            }
            return $name;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public static function deleteFolder($imageObj) {
        try {
            self::$folderPath = $imageObj->folder;
            self::$thumbFolder = $imageObj->thumb_folder;
            if (in_array(self::$fileDriver, General::$localDriverOptions)) {
                if (Storage::exists(self::$folderPath)) {
                    Storage::deleteDirectory(self::$folderPath);
                }
                if (Storage::exists(self::$thumbFolder)) {
                    Storage::deleteDirectory(self::$thumbFolder);
                }
            } else if (self::$fileDriver === 's3') {
                $s3 = \Storage::disk('s3');
                if ($s3->exists($imageObj->folder)) {
                    $s3->deleteFolder(self::$folderPath);
                }
                return true;
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Upload thumb image in S3
     * @param array $image
     * @param string $path
     */
    public static function upload_thumb_image($image, $path, $width, $height) {
        try {
            $file_path = self::$defaultUploadPath . $path;
            $thumb_image = \Image::make($image)->resize(150, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $thumb_image = $thumb_image->stream();
            $s3 = \Storage::disk('s3')->put($file_path, $thumb_image->__toString(), 'public');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function dateFormat($date) {
        return date('m/d/Y', strtotime($date));
    }

    public static function dateTimeFormat($date) {
        return date('Y-m-d h:i A', strtotime($date));
    }

    public static function array_search_partial($arr, $keyword) {
        foreach ($arr as $index => $string) {
            if (strpos($string, $keyword) !== FALSE) {
                return $index;
            }
        }
    }

}
