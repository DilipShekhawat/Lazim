<?php

use Aws\S3\S3Client;
use App\Models\Features;
use App\Models\MasterUser;
use App\Models\JsonRequest;
use App\Models\Notifications;
use App\Models\GlobalSettings;
use App\Models\TrainingCourse;
use App\Models\FeatureSettings;
use App\Models\RolePermissions;
use App\Models\whiteLabelSetting;
use App\Models\MasterUserInviteLinks;
use App\Models\TrainingCourseModules;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Config;
use Intervention\Image\Facades\Image as Image;
use App\Models\TrainingCourseSubmoduleDetails;

if (!function_exists('setErrorResponse')) {

    function setErrorResponse($message = '', $meta = null, $serverErr = false) {
	if ($serverErr) {
	    \Log::info("Exception : Try/Catch = " . json_encode($message));
	    $message = __('validation.something_wrong');
	}
	$response = [];
	$response['errors']['message'] = $message;
	$response['errors']['meta'] = (object) $meta;
	return $response;
    }

}
if (!function_exists('setResponse')) {

    function setResponse($data = null, $meta = null) {
	$response = [];
	$response['data'] = $data;
	$response['extra_meta'] = (object) $meta;
	return $response;
    }

}

if (!function_exists('setError')) {

    function setError($error = null) {
	$response = [];
	$response['extra_meta']['errors'] = (object) $error;
	return $response;
    }

}

if (!function_exists('str_slug')) {

    function str_slug($title, $separator = '-', $language = 'en') {
// Convert all dashes/underscores into separator
	$flip = $separator == '-' ? '_' : '-';
	$title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);
// Replace @ with the word 'at'
	$title = str_replace('@', $separator . 'at' . $separator, $title);
// Remove all characters that are not the separator, letters, numbers, or whitespace.
	$title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', $title);
// Replace all separator characters and whitespace by a single separator
	$title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);
	$title = strtolower($title);
	return trim($title, $separator);
    }

}

if (!function_exists('bcrypt')) {

    function bcrypt($data) {
	return app('hash')->make($data);
    }

}

if (!function_exists('request')) {

    /**
     * Get an instance of the current request or an input item from the request.
     *
     * @param  array|string  $key
     * @param  mixed   $default
     * @return \Illuminate\Http\Request|string|array
     */
    function request($key = null, $default = null) {
	if (is_null($key)) {
	    return app('request');
	}

	if (is_array($key)) {
	    return app('request')->only($key);
	}

	$value = app('request')->__get($key);

	return is_null($value) ? value($default) : $value;
    }

}

if (!function_exists('array_flatten')) {

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  array  $array
     * @param  int  $depth
     * @return array
     */
    function array_flatten($array, $depth = INF) {
//        return Arr::flatten($array, $depth);
	return flatten($array, $depth);
    }

}

/**
 * Flatten a multi-dimensional array into a single level.
 *
 * @param  array  $array
 * @param  int  $depth
 * @return array
 */
function flatten($array, $depth = INF) {
    $result = [];

    foreach ($array as $item) {
	$item = $item instanceof Collection ? $item->all() : $item;

	if (!is_array($item)) {
	    $result[] = $item;
	} elseif ($depth === 1) {
	    $result = array_merge($result, array_values($item));
	} else {
	    $result = array_merge($result, flatten($item, $depth - 1));
	}
    }

    return $result;
}

/**
 * convert image into multiple sizes
 *
 * @param  array  $array
 * @param  int  $depth
 * @return array
 */
function uploadImage($imgObject, $imgPath = "", $filesystem = 'public') {
    try {
	//data:image/png;base64,
	//image size patterm
	$imgSizes = config('constants.images.thumbnail');
	$prefix = time() . "_" . randomString(8);
	//croped image name pattern
	$cropImageName = $prefix . '.' . $imgObject->getClientOriginalExtension();
	//crop image into sizes
	$image = Image::make($imgObject)->resize($imgSizes['height'], $imgSizes['width'])->encode('jpg');
	//upload cropped image
	$path = $imgPath . "/" . $cropImageName;
	Storage::disk($filesystem)->put($path, (string) $image, 'public');
	return $cropImageName;
    } catch (\Exception $e) {

    }
}

/**
 * file upload on s3 browser
 *
 * @param  array  $array
 * @param  int  $depth
 * @return array
 */
if (!function_exists('S3BucketFileUpload')) {

    function S3BucketFileUpload($file, $path = "") {
		try {
			$fileName = microtime(true) . '_' . $file->getClientOriginalName();
			Storage::disk('s3')->put($path . "/" . $fileName, file_get_contents($file), 'public');
			return $fileName;
		} catch (\Exception $e) {

		}
    }
}
if (!function_exists('MicroLearningFileUpload')) {

    function MicroLearningFileUpload($file, $path = "") {
		try {
			$fileName = microtime(true) . '_' . $file->getClientOriginalName();
			Storage::disk('s3')->put($path. "/" . $fileName, file_get_contents($file), 'public');
			return $fileName;
		} catch (\Exception $e) {

		}
    }
}

if (!function_exists('uploadBlobImage')) {

    function uploadBlobImage($imgBlobUrl, $imgPath = "") {
	$imageName = time() . "_" . randomString(8) . '.jpeg';
	$path = $imgPath . '/' . $imageName;
	Storage::disk('s3')->put($path, file_get_contents($imgBlobUrl), 'public', fopen($imgBlobUrl, 'r+'));
	return ['imageUrl' => getS3ImageUrl($path),
	    'imageName' => $imageName,
	];
	return getS3ImageUrl($path);
    }

}
if (!function_exists('getS3ImageUrl')) {

    function getS3ImageUrl($imgPath = null) {
	$path = ($imgPath != null) ? $imgPath : 'profile.png';
	return $url = Storage::disk('s3')->url($path, \Carbon\Carbon::now()->addMinutes(10));
    }

}
if (!function_exists('S3BucketFileRemove')) {

    function S3BucketFileRemove($filePath) {
	if (Storage::disk('s3')->exists($filePath)) {
	    Storage::disk('s3')->delete($filePath);
	}
    }

}

//generate random alphanumeric lowercase string
if (!function_exists('randomString')) {

    function randomString($length = "") {
	$length = ($length != "") ? $length : 8;
	$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
	// Output: 54esmdr0qf
	return substr(str_shuffle($permitted_chars), 0, $length);
    }

}

//assessor UniqueId Generate
if (!function_exists('assessorUniqueIdGenerate')) {

    function assessorUniqueIdGenerate() {
	$randomNumber = random_int(10000, 99999);
	$assessorUniqueId = 'AID' . $randomNumber;
	return $assessorUniqueId;
    }

}

//assessor UniqueId Generate
if (!function_exists('scormUniqueIdGenerate')) {
    function scormUniqueIdGenerate() {
	$randomNumber = random_int(10000, 99999);
	$scormUniqueId = 'SCR' . $randomNumber;
	return $scormUniqueId;
    }
}

if (!function_exists('format_amount')) {

    /**
     * @param int|float $amount
     * @param int    $decimals
     * @param string $dec_point
     * @param string $thousands_sep
     * @param string $prefix
     * @param string $suffix
     *
     * @return string
     */
    function format_amount($amount, $decimals = 2, $dec_point = ".", $thousands_sep = ",") {
	return number_format($amount, $decimals, $dec_point, $thousands_sep);
    }

}

if (!function_exists('exportCsv')) {

    /**
     *
     * @param array|json $data
     * @param array $columns
     * @return string filename
     */
    function exportCsv($data, $columns, $encColumns = null) {
	$output = [];
	$data = is_array($data) ? $data : json_decode($data, true);
	$fileName = '../storage/' . time() . '-' . randomString() . '.csv';
	$fp = fopen($fileName, 'w');
	fputcsv($fp, $columns);
	foreach ($data as $raw) {
	    foreach ($raw as $key => $value) {
		if ($encColumns && in_array($key, $encColumns)) {
		    $output[$key] = decryption($value);
		} else {
		    $output[$key] = $value;
		}
	    }
	    fputcsv($fp, $output);
	}
	return $fileName;
    }

}

if (!function_exists('csvHeaders')) {

    /**
     *
     * @descriotion return csv headers globally
     */
    function csvHeaders() {
	return $headers = [
	    "Content-type" => "application/csv",
	    "Content-Disposition" => "attachment; filename=" . time() . ".csv",
	    "Pragma" => "no-cache",
	    "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
	    "Expires" => "0",
	];
    }

}
if (!function_exists('format_date')) {

    /**
     * @param $date
     * @param $format
     * @return false|string
     */
    function format_date($microseconds, $format = null) {
	return date($format ?? config('constants.date_format.us.date'), ($microseconds / 1000));
    }

}

if (!function_exists('format_datetime')) {

    /**
     * @param $date
     * @param $format
     * @return false|string
     */
    function format_datetime($date, $format = null) {
	return \Carbon\Carbon::parse($date)->format($format ?? config('constants.date_format.us.datetime'));
    }

}
if (!function_exists('globalSetting')) {

    /**
     * @param $date
     * @param $format
     * @return false|string
     */
    function globalSetting($name) {
	$value = GlobalSettings::select('value')->where('name', '=', $name)->first();
	return $value->value ?? '';
    }

}
//expiry_duration
if (!function_exists('mail_setting_configure')) {

    function mail_setting_configure() {
	if (\Schema::hasTable('global_settings')) {
	    $mail = \DB::table('global_settings')
			    ->whereIn('name', ['mail_smtp_driver', 'mail_smtp_host', 'mail_smtp_port', 'mail_smtp_mail_from_name', 'mail_smtp_mail_from_email', 'mail_smtp_encryption', 'mail_smtp_username', 'mail_smtp_password'])
			    ->pluck('value', 'name')->toArray();
	    if (count($mail) > 0) { //checking if table is not empty
		$smtp = [
		    'transport' => $mail['mail_smtp_driver'],
		    'host' => $mail['mail_smtp_host'],
		    'port' => $mail['mail_smtp_port'],
		    'encryption' => $mail['mail_smtp_encryption'],
		    'username' => $mail['mail_smtp_username'],
		    'password' => $mail['mail_smtp_password'],
		];
		$from = [
		    'name' => $mail['mail_smtp_mail_from_name'],
		    'address' => $mail['mail_smtp_mail_from_email'],
		];
		config(['mail.mailers.smtp' => $smtp]); //update mailers value
		config(['mail.from' => $from]); //update maile from value
	    }
	}
    }

}

if (!function_exists('getImageObject')) {
    /*
     * AWS Image Object
     */

    function getImageObject($file, $folderPath, $oldFile = '', $isThumb = true) {
	return (object) [
		    'file' => $file,
		    'folder' => $folderPath,
		    'old_file' => $oldFile ?: '',
		    'is_thumb' => $isThumb,
		    'thumb_folder' => $folderPath . '/thumb/',
		    'thumb_height' => config('constants.images.thumbnail.height'),
		    'thumb_width' => config('constants.images.thumbnail.width'),
	];
    }

}

if (!function_exists('getUsersPath')) {
    /*
     * Users Folder Path
     */

    function getUsersPath() {
	return config('constants.aws.users');
    }

}

if (!function_exists('getNewFileName')) {
    /*
     * Create new file name from old while copying file
     */

    function getNewFileName($fileName) {
	$imageInfo = explode('.', $fileName);
	return md5(rand() . time()) . '.' . $imageInfo[1];
    }

}

if (!function_exists('deleteFileFromS3')) {
    /*
     * Delete File From S3 Bucket
     */

    function deleteFileFromS3($filePath) {
	if (\Storage::disk('s3')->exists($filePath)) {
	    \Storage::disk('s3')->delete($filePath);
	    return true;
	}
	return false;
    }

}

if (!function_exists('moveFileToResource')) {
    /*
     * Move File From Temp To Resource
     */

    function moveFileToResource($fileName) {
	$sourceFilePath = config('constants.aws.training_course') . '/temp/' . $fileName;
	$destinationFilePath = getResourcePath() . $fileName;
	if (\Storage::disk('s3')->exists($sourceFilePath)) {
	    \Storage::disk('s3')->move($sourceFilePath, $destinationFilePath);
	    return true;
	}
	return false;
    }

}

if (!function_exists('moveFileToCertificate')) {
    /*
     * Move File From Temp To Resource
     */

    function moveFileToCertificate($fileName) {
	$sourceFilePath = config('constants.aws.certificate') . '/temp/' . $fileName;
	$destinationFilePath = getResourcePath() . $fileName;
	if (\Storage::disk('s3')->exists($sourceFilePath)) {
	    \Storage::disk('s3')->move($sourceFilePath, $destinationFilePath);
	    return true;
	}
	return false;
    }

}

if (!function_exists('moveFileToNews')) {
    /*
     * Move File From Temp To News
     */

    function moveFileToNews($fileName) {
	$sourceFilePath = config('constants.aws.training_course') . '/temp/' . $fileName;
	$destinationFilePath = getNewsPath() . $fileName;
	if (\Storage::disk('s3')->exists($sourceFilePath)) {
	    \Storage::disk('s3')->move($sourceFilePath, $destinationFilePath);
	    return true;
	}
	return false;
    }

}

if (!function_exists('moveFileToProduct')) {
    /*
     * Move File From Temp To Product
     */

    function moveFileToProduct($fileName) {
	$sourceFilePath = config('constants.aws.training_course') . '/temp/' . $fileName;
	$destinationFilePath = getProductPath() . $fileName;
	if (\Storage::disk('s3')->exists($sourceFilePath)) {
	    \Storage::disk('s3')->move($sourceFilePath, $destinationFilePath);
	    return true;
	}
	return false;
    }

}

if (!function_exists('moveFileToProductFeedback')) {
    /*
     * Move File From Temp To Product Feedback
     */

    function moveFileToProductFeedback($fileName) {
	$sourceFilePath = config('constants.aws.training_course') . '/temp/' . $fileName;
	$destinationFilePath = getProductFeedbackPath() . $fileName;
	if (\Storage::disk('s3')->exists($sourceFilePath)) {
	    \Storage::disk('s3')->move($sourceFilePath, $destinationFilePath);
	    return true;
	}
	return false;
    }

}

if (!function_exists('moveFileToTrainingCourse')) {
    /*
     * Move File From Temp To Training Course
     */

    function moveFileToTrainingCourse($fileName, $destinationFilePath) {
	$sourceFilePath = config('constants.aws.training_course') . '/temp/' . $fileName;
	if (\Storage::disk('s3')->exists($sourceFilePath)) {
	    \Storage::disk('s3')->move($sourceFilePath, $destinationFilePath);
	    return true;
	}
	return false;
    }

}

if (!function_exists('moveFileToModules')) {
    /*
     * Move File From Temp To Modules
     */

    function moveFileToModules($fileName, $trainingCourseId) {
	return moveFileToTrainingCourse($fileName, config('constants.aws.training_course') . '/' . $trainingCourseId . '/modules/' . $fileName);
    }

}

if (!function_exists('moveFileToSubModules')) {
    /*
     * Move File From Temp To Sub Modules
     */

    function moveFileToSubModules($fileName, $trainingCourseId) {
	return moveFileToTrainingCourse($fileName, config('constants.aws.training_course') . '/' . $trainingCourseId . '/submodules/' . $fileName);
    }

}

if (!function_exists('moveFileToScormSubModules')) {
    /*
     * Move File From Temp To Sub Modules
     */

    function moveFileToScormSubModules($fileName, $trainingCourseId) {
		return moveFileToTrainingCourse($fileName, config('constants.aws.training_course') . '/' . $trainingCourseId . '/submodules/scorm/' . $fileName);
    }

}

if (!function_exists('getTrainingCoursePath')) {
    /*
     * Training Course Path
     */

    function getTrainingCoursePath($trainingCourseId = '') {
	if ($trainingCourseId != '') {
	    return config('constants.aws.training_course') . '/' . $trainingCourseId;
	} else {
	    return config('constants.aws.training_course') . '/temp';
	}
    }

}

if (!function_exists('getResourcePath')) {
    /*
     * Resource Path
     */

    function getResourcePath() {
	return config('constants.aws.resources') . '/';
    }

}

if (!function_exists('getProductPath')) {
    /*
     * Product Path
     */

    function getProductPath() {
	return config('constants.aws.products') . '/';
    }

}

if (!function_exists('getProductFeedbackPath')) {
    /*
     * Product Feedback Path
     */

    function getProductFeedbackPath() {
	return config('constants.aws.products') . '/feedback/';
    }

}

if (!function_exists('getNewsPath')) {
    /*
     * News Path
     */

    function getNewsPath() {
	return config('constants.aws.news') . '/';
    }

}

if (!function_exists('getTrainingCourseModulePath')) {
    /*
     * Training Course Path
     */

    function getTrainingCourseModulePath($trainingCourseId = '') {
	if ($trainingCourseId != '') {
	    return config('constants.aws.training_course') . '/' . $trainingCourseId . '/' . 'modules';
	} else {
	    return config('constants.aws.training_course') . '/temp';
	}
    }

}
if (!function_exists('getPushPath')) {
    /*
     * Training Course Path
     */

    function getPushPath($operatorId = '') {
	if ($operatorId != '') {
	    return config('constants.aws.push') . '/' . $operatorId;
	} else {
	    return config('constants.aws.push') . '/temp';
	}
    }

}
if (!function_exists('getJsonPath')) {
    /*
     * Training Course Path
     */

    function getJsonPath($operatorId = '') {
	if ($operatorId != '') {
	    return config('constants.aws.json') . '/' . $operatorId;
	} else {
	    return config('constants.aws.json') . '/temp';
	}
    }

}
if (!function_exists('getEmailLogoPath')) {
    /*
     * Email Templete Logo Path As Per Specific Operator If Email Settings Enabled
     */

    function getEmailLogoPath($operatorId = '') {
		if ($operatorId != '') {
			return config('constants.aws.email_logo') . '/' . $operatorId;
		} else {
			return config('constants.aws.email_logo') . '/temp';
		}
    }
}

if (!function_exists('getBackgroundImagePath')) {
    /*
     * Email Templete Logo Path As Per Specific Operator If Email Settings Enabled
     */

    function getBackgroundImagePath($operatorId = '') {
		if ($operatorId != '') {
			return config('constants.aws.background_images') . '/' . $operatorId;
		} else {
			return config('constants.aws.background_images') . '/temp';
		}
    }
}

if (!function_exists('getPDFLogoPath')) {
    /*
     * PDF logo Path
     */
	function getPDFLogoPath($operatorId = '') {
		if ($operatorId != '') {
			return config('constants.aws.pdf_logo') . '/' . $operatorId;
		} else {
			return config('constants.aws.pdf_logo') . '/temp';
		}
    }
}


if (!function_exists('getWhitelabelEmailLogoPath')) {
    /*
     * Email Templete Logo Path As Per Specific Operator If Email Settings Enabled
     */

    function getWhitelabelEmailLogoPath($appName = '') {
	if ($appName != '') {
	    $Operator = whiteLabelSetting::select('operator_id', 'logo_name')->where('is_white_label_feature_on', 1)->where('app_name', base64_decode($appName))->first();
	    if (isset($Operator) && !empty($Operator->logo_name)) {
		return env('CDN_URL') . config('constants.aws.email_logo') . '/' . $Operator->operator_id . '/' . $Operator->logo_name;
	    } else {
		return false;
	    }
	} else {
	    return false;
	}
    }

}

if (!function_exists('getWhitelabelPDFLogoPath')) {
    /*
     * PDF Logo Path As Per Specific Operator If Email Settings Enabled
     */
    function getWhitelabelPDFLogoPath($settings) {
		$logoURL = env('CDN_URL') . config('constants.aws.pdf_logo') . '/' . $settings->operator_id . '/';
		$shortLogo =  ($settings->short_logo) ? $logoURL.$settings->short_logo : null;
	    $sidebarLogo = ($settings->sidebar_logo) ? $logoURL . $settings->sidebar_logo : null;
		$faviconLogo = ($settings->favicon_logo) ? $logoURL . $settings->favicon_logo : null;
		$logoName = ($settings->logo_name) ? env('CDN_URL') . config('constants.aws.email_logo') . '/' . $settings->operator_id . '/' . $settings->logo_name : null;
		$footerBackgroundImage = ($settings->footer_background_image) ? $logoURL . $settings->footer_background_image : null;
		return ['short_logo_url'=>$shortLogo,'sidebar_logo_url' => $sidebarLogo, 'favicon_logo_url'=>$faviconLogo,'logo_url' => $logoName,'footer_background_image_url'=>$footerBackgroundImage];
	}
}

if (!function_exists('getTrainingCourseSubmodulePath')) {
    /*
     * Training Course Path
     */

    function getTrainingCourseSubmodulePath($trainingCourseId = '') {
	if ($trainingCourseId != '') {
	    return config('constants.aws.training_course') . '/' . $trainingCourseId . '/' . 'submodules';
	} else {
	    return config('constants.aws.training_course') . '/temp';
	}
    }

}

if (!function_exists('getTrainingCourseSubmoduleVideoPath')) {
    /*
     * Training Course Path
     */

    function getTrainingCourseSubmoduleVideoPath($trainingCourseId = '') {
	if ($trainingCourseId != '') {
	    return config('constants.aws.training_course') . '/' . $trainingCourseId . '/' . 'submodules/video/';
	} else {
	    return config('constants.aws.training_course') . '/temp';
	}
    }

}

if (!function_exists('getTrainingCourseSubmoduleScormPath')) {
    /*
     * Training Course Path
     */

    function getTrainingCourseSubmoduleScormPath($trainingCourseId = '') {
	if ($trainingCourseId != '') {
	    return config('constants.aws.training_course') . '/' . $trainingCourseId . '/' . 'submodules/scorm';
	} else {
	    return config('constants.aws.training_course') . '/temp';
	}
    }

}

if (!function_exists('getTrainingCourseSubmoduleMicroLearningPath')) {
    /*
     * Micro Learning Submodule HTML file path
     */
    function getTrainingCourseSubmoduleMicroLearningPath($trainingCourseId = '') {
		if ($trainingCourseId != '') {
			return config('constants.aws.training_course') . '/' . $trainingCourseId . '/' . 'submodules/micro_learning';
		} else {
			return config('constants.aws.training_course') . '/temp';
		}
    }
}

if (!function_exists('getTrainingCourseSubmoduleDocumentPath')) {
    /*
     * Training Course Path
     */

    function getTrainingCourseSubmoduleDocumentPath($trainingCourseId = '') {
	if ($trainingCourseId != '') {
	    return config('constants.aws.training_course') . '/' . $trainingCourseId . '/' . 'submodules/document/';
	} else {
	    return config('constants.aws.training_course') . '/temp';
	}
    }

}

if (!function_exists('convertBase64toImage')) {
    /*
     * Convert base64 string to Image
     */

    function convertBase64toImage($base64Encoded) {
	preg_match("/data:image\/(.*?);/", $base64Encoded, $imageExtension); // extract the image extension
	$base64Encoded = preg_replace('/data:image\/(.*?);base64,/', '', $base64Encoded); // remove the type part
	$base64Encoded = str_replace(' ', '+', $base64Encoded);
	$imageName = md5(rand() . time()) . '.' . $imageExtension[1]; //generating unique file name;
	return [
	    'image' => base64_decode($base64Encoded),
	    'name' => $imageName,
	];
    }

}

if (!function_exists('generate_password')) {

    /**
     * @param $length
     * @return string
     */
    function generate_password($length = 12) {
	$password = '';
	$allowedCharacters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ~`!@#$%^&*()_{}[]';
	$max = mb_strlen($allowedCharacters, '8bit') - 1;
	for ($i = 0; $i < $length; ++$i) {
	    $password .= $allowedCharacters[random_int(0, $max)];
	}
	return ((bool) preg_match('/(?=.*([A-Z]))(?=.*([a-z]))(?=.*([0-9]))(?=.*([~`\!@#\$%\^&\*\(\)_\{\}\[\]]))/', $password)) ?
		$password :
		generate_password($length);
    }

}

if (!function_exists('createDefaultUserNotifications')) {

    /**
     * @param App\Models\User $user
     * @return boolean true
     */
    function createDefaultUserNotifications($user) {
	$notifications = App\Models\NotificationSettings::user()->get();
	foreach ($notifications as $notification) {
	    \App\Models\NotificationUser::create([
		'notification_id' => $notification->id,
		'user_id' => $user->id,
		'is_on' => 1,
	    ]);
	}
	return true;
    }

}

if (!function_exists('createDefaultOperatorNotifications')) {

    /**
     * @param App\Models\MasterUser $operator
     * @return boolean true
     */
    function createDefaultOperatorNotifications($operator) {
	$notifications = App\Models\NotificationSettings::operator()->get();
	foreach ($notifications as $notification) {
	    \App\Models\NotificationOperator::create([
		'notification_id' => $notification->id,
		'master_user_id' => $operator->id,
	    ]);
	}
	return true;
    }

}

if (!function_exists('createDefaultFeatures')) {

    /**
     * @param App\Models\MasterUser $operator
     * @return boolean true
     */
    function createDefaultFeatures($operator) {
	$features = App\Models\Features::get();
	foreach ($features as $feature) {
	    \App\Models\FeatureSettings::create([
		'feature_id' => $feature->id,
		'master_user_id' => $operator->id,
		'is_feature_on' => 0,
	    ]);
	}
	return true;
    }

}

if (!function_exists('generateQRCode')) {

    /**
     * @param $data
     * @param $format - Supported format must be either png, eps, svg
     * @return false|string
     */
    function generateQRCode($data, $format = 'svg') {
	$fileName = md5(rand() . time()) . "." . $format;
	$path = storage_path('/qrcodes/');
	if (!file_exists($path)) {
	    mkdir($path);
	    chmod($path, 0777);
	}
	SimpleSoftwareIO\QrCode\Facades\QrCode::format($format)
		->size(200)
		->margin(2)
		->errorCorrection('H')
		->generate($data, $path . $fileName);
	return $fileName;
    }

}

if (!function_exists('generateQRCodeAssessor')) {

    /**
     * @param $data
     * @param $format - Supported format must be either png, eps, svg
     * @return false|string
     */
    function generateQRCodeAssessor($data, $format = 'png') {
	$fileName = md5(rand() . time()) . "." . $format;
	$path = storage_path('/qrcodes/');
	if (!file_exists($path)) {
	    mkdir($path);
	    chmod($path, 0777);
	}
	SimpleSoftwareIO\QrCode\Facades\QrCode::format($format)
		->size(500)
		->margin(2)
		->errorCorrection('H')
		->generate($data, $path . $fileName);
	return $fileName;
    }

}

if (!function_exists('checkTrainingCourse')) {

    /**
     * @param $data
     * @param $format - Check training course is available or not
     * @return false|string
     */
    function checkTrainingCourse($subModule, $type = null) 
	{
		$course = TrainingCourse::where('id', $subModule->training_course_id)->whereNull('deleted_at')->first();
		$flag = 0;
		if (empty($course)) {
			$flag = 1;
		} else {
			if ($course->publish_now == 0 || $course->status == 'Inactive') {
				$flag = 1;
			}
		}
		$trainingCourseIds = (new TrainingCourse)->getUserAssignCourseIds();
		if (!in_array($subModule->training_course_id, $trainingCourseIds)) {
			$flag = 1;
		}
		if ($type != 'module') {
			$module = TrainingCourseModules::where('id', $subModule->module_id)->where('status', 'Active')->first();
			if (empty($module)) {
				$flag = 2;
			}
		}
		return $flag;
    }
}

if (!function_exists('generateFirebaseDeepLink')) {

    /**
     * @param $url
     * @return false|string
     */
    function generateFirebaseDeepLink($url, $operatorId = '') {
	$operatorData = MasterUser::find($operatorId);
	if ($operatorData && $operatorData->whiteLabelSettings) {
	    /* Get oprerator setting if feature is on otherwise get default settings. */
	    $whiteLabelSettings = $operatorData->whiteLabelSettings;
	    /* Download PEM file from S3 to local Start */
	    $localDirectory = storage_path('app/public/json/' . $whiteLabelSettings->operator_id . '/');
	    $localFilePath = $localDirectory . $whiteLabelSettings->firebase_json;
	    if (!file_exists($localFilePath)) {
		if (!is_dir($localDirectory)) {
		    mkdir($localDirectory, 0755, true);
		}
		$fileContent = file_get_contents(env('CDN_URL') . 'json/' . $whiteLabelSettings->operator_id . '/' . $whiteLabelSettings->firebase_json);
		file_put_contents($localFilePath, $fileContent);
	    }
	    /* Download PEM file from S3 to local End */
	    Config::set('firebase.projects.app.dynamic_links.default_domain', $whiteLabelSettings->domain_uri_prefix);
	    Config::set('firebase.projects.app.credentials.file', 'storage/app/public/json/' . $whiteLabelSettings->operator_id . '/' . $whiteLabelSettings->firebase_json);
	    $domainUriPrefix = $whiteLabelSettings->domain_uri_prefix;
	    $androidPackageName = $whiteLabelSettings->android_package_name;
	    $iosBundleId = $whiteLabelSettings->ios_package_name;
	    $iosAppStoreId = $whiteLabelSettings->ios_app_store_id;
	} else {
	    /* Get default settings to generete deeplink */
	    $domainUriPrefix = env('FIREBASE_DYNAMIC_LINKS_DEFAULT_DOMAIN');
	    $androidPackageName = env('ANDROID_PACKAGE_NAME');
	    $iosBundleId = env('IOS_BUNDLE_ID');
	    $iosAppStoreId = env('IOS_APP_STORE_ID');
	}

	// New query parameters to append
	$playStoreRedirectParams = [
	    'apn' => $androidPackageName,
	    'ibi' => $iosBundleId,
	    'isi' => $iosAppStoreId
	];

	// Parse the original URL
	$parts = parse_url($url);
	// Extract and parse the existing query parameters
	parse_str($parts['query'] ?? '', $existingParams);
	// Merge the existing and new query parameters
	$queryParams = array_merge($existingParams, $playStoreRedirectParams);
	// Rebuild the query string
	$queryString = http_build_query($queryParams);
	$newUrl = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . $queryString;

	if (strpos($newUrl, '{{hash}}') !== false) {
	    // Replace {{hash}} with the new value
	    $newUrl = str_replace('{{hash}}', "#", $url);
	}
	$dynamicLinks = app('firebase.dynamic_links');
	$parameters = [
	    'dynamicLinkInfo' => [
		'domainUriPrefix' => $domainUriPrefix,
		'link' => $newUrl,
		'androidInfo' => [
		    'androidPackageName' => $androidPackageName,
		],
		'iosInfo' => [
		    'iosBundleId' => $iosBundleId,
		    "iosAppStoreId" => $iosAppStoreId // Newly Added Param
		],
	    // 'navigationInfo' => [
	    // 	'enableForcedRedirect' => true,
	    // ],
	    ],
		// 'suffix' => [
		// 'option' => 'UNGUESSABLE',
		// ],
	];
	$link = $dynamicLinks->createDynamicLink($parameters);
	if ($link) {
	    return $link->__toString();
	}
	return false;
    }

}

if (!function_exists('public_path')) {

    /**
     * Get the path to the public folder.
     *
     * @param  string  $path
     * @return string
     */
    function public_path($path = '') {
	return rtrim(app()->basePath('public/' . $path), '/');
    }

}

if (!function_exists('getCurrentUserGuard')) {

    /**
     * Get the current login user guard.
     */
    function getCurrentUserGuard() {
	$guards = array_keys(config('auth.guards'));
	foreach ($guards as $guard) {
	    if (Auth::guard($guard)->check()) {
		return $guard;
	    }
	}
    }

}

if (!function_exists('getTrainingCourseSubmoduleQuizPath')) {
    /*
     * Training Course Quiz
     */

    function getTrainingCourseSubmoduleQuizPath($trainingCourseId) {
	return config('constants.aws.training_course') . '/' . $trainingCourseId . '/submodules/quiz/';
    }

}

if (!function_exists('getTrainingCourseSubmodulePAPath')) {
    /*
     * Temporary based storage of PA PDF files
     */

    function getTrainingCourseSubmodulePAPath($pdfName, $type = '') {
		if ($type == 'pathOnly') {
			return '/practical_assessment/' . $pdfName;
		} else {
			return env('CDN_URL') . 'practical_assessment/' . $pdfName;
		}
    }
}

if (!function_exists('getPracticalAssessmentMediaPath')) {
    /*
     * Temporary based storage of PA PDF files
     */
    function getPracticalAssessmentMediaPath($id) {
		return 'practical_assessment/results/' .$id;
    }
}

if (!function_exists('getCertificatePath')) {

    function getCertificatePath($pdfName, $type = '') {
	if ($type == 'pathOnly') {
	    return '/generated_certificate/' . $pdfName;
	} else {
	    return env('CDN_URL') . 'generated_certificate/' . $pdfName;
	}
    }

}

if (!function_exists('getUserCourseCertificatePath')) {

    function getUserCourseCertificatePath($name, $userId, $courseId, $type = '') {
	if ($type == 'pathOnly') {
	    return str_replace(['{user_id}', '{course_id}'], [$userId, $courseId], config('constants.aws.user_course_certificate')) . '/' . $name;
	} else {
	    return env('CDN_URL') . str_replace(['{user_id}', '{course_id}'], [$userId, $courseId], config('constants.aws.user_course_certificate')) . '/' . $name;
	}
    }

}

if (!function_exists('getTrainingCourseSubmoduleFeedbackPath')) {
    /*
     * Training Course Quiz
     */

    function getTrainingCourseSubmoduleFeedbackPath($trainingCourseId) {
	return config('constants.aws.training_course') . '/' . $trainingCourseId . '/submodules/feedback/';
    }

}

if (!function_exists('getTrainingCourseSubmoduleThumbnailPath')) {
    /*
     * Training Course Path
     */

    function getTrainingCourseSubmoduleThumbnailPath($trainingCourseId = '') {
	if ($trainingCourseId != '') {
	    return config('constants.aws.training_course') . '/' . $trainingCourseId . '/' . 'submodules/thumbnail';
	} else {
	    return config('constants.aws.training_course') . '/' . 'submodules/thumbnail';
	}
    }

}

if (!function_exists('checkAssessment')) {
    /*
     * checkAssessment
     */
    function checkAssessment() {
		$guard = request()->segment(2);
		if(isset($guard) && $guard == 'operator'){
			$manager = auth()->guard('operator')->user();
			if (!empty($manager)) {
				$features = getAccessFeatures($manager->id, $manager->parent_id);
				$flag = 0;
				if (!empty($features) && (isset($features['assessment_portal']) && $features['assessment_portal'] == 0)) {
				$flag = 1;
				}
			} else {
				$flag = 0;
			}
			return $flag;
		}
		else{
			return 0;
		}

    }
}

if (!function_exists('checkAssessmentWithId')) {
    /*
     * checkAssessmentWithId
     */

    function checkAssessmentWithId($submoduleId) {
	$manager = auth()->guard('operator')->user();
	$features = getAccessFeatures($manager->id, $manager->parent_id);
	$flag = 0;
	if (!empty($features) && (isset($features['assessment_portal']) && $features['assessment_portal'] == 0) && $submoduleId == 16) {
	    $flag = 1;
	}
	return $flag;
    }

}

if (!function_exists('getOperator')) {
    /*
     * Get main operator data
     */

    function getOperator($user = null) {
	$currentUser = $user ?? auth()->guard('operator')->user();
	return is_null($currentUser->parent_id) ? $currentUser : \App\Models\MasterUser::find($currentUser->parent_id);
    }

}

if (!function_exists('checkAssessment')) {
    /*
     * checkAssessment
     */

    function checkAssessment() {
	$manager = auth()->guard('operator')->user();
	if ($manager) {
	    $features = getAccessFeatures($manager->id, $manager->parent_id);
	}
	$flag = 0;
	if (!empty($features) && (isset($features['assessment_portal']) && $features['assessment_portal'] == 0)) {
	    $flag = 1;
	}
	return $flag;
    }

}

if (!function_exists('checkAssessmentWithId')) {
    /*
     * checkAssessmentWithId
     */

    function checkAssessmentWithId($submoduleId) {
	$manager = auth()->guard('operator')->user();
	$features = getAccessFeatures($manager->id, $manager->parent_id);
	$flag = 0;
	if (!empty($features) && (isset($features['assessment_portal']) && $features['assessment_portal'] == 0) && $submoduleId == 16) {
	    $flag = 1;
	}
	return $flag;
    }

}


if (!function_exists('getOperatorId')) {
    /*
     * Get main operator ID
     */

    function getOperatorId() {
	return getOperator()->id;
    }

}

if (!function_exists('getTeamMembers')) {
    /*
     * Get team members ID with operator
     */

    function getTeamMembers($user = null) {
	$operator = getOperator($user);
	return \App\Models\MasterUser::where('id', $operator->id)->orWhere('parent_id', $operator->id)->pluck('id')->toArray();
    }

}

if (!function_exists('setAccessPermissions')) {
    /*
     * Set permissions in Cache
     */

    function setAccessPermissions($operatorId = null, $userRoleId = '') {
	$allPermissions = [];
	$roles = $userRoleId != '' ? \App\Models\Roles::whereId($userRoleId)->get() : \App\Models\Roles::all();
	foreach ($roles as $role) {
	    $permissions = [];
	    foreach ($role->permissionList as $permission) {
		if (!empty($operatorId)) {
		    $featureId = Features::where('permission_key', $permission->module_name)->value('id');
		    $isFeature = FeatureSettings::where('master_user_id', $operatorId)->where('feature_id', $featureId)->value('is_feature_on');
		    $permissions[$permission->module_name] = [
			'view' => isset($isFeature) ? $isFeature : $permission->view,
			'add' => isset($isFeature) ? $isFeature : $permission->add,
			'edit' => isset($isFeature) ? $isFeature : $permission->edit,
			'delete' => isset($isFeature) ? $isFeature : $permission->delete,
		    ];
		} else {
		    $permissions[$permission->module_name] = [
			'view' => $permission->view,
			'add' => $permission->add,
			'edit' => $permission->edit,
			'delete' => $permission->delete,
		    ];
		}
	    }
	    $allPermissions[$role->id] = [
		'viewAllUsersData' => $role->permission,
		'permissions' => $permissions,
	    ];
	}
	\Cache::put('permissions', $allPermissions, \Carbon\Carbon::now()->addYear());
	return $allPermissions;
    }

}

if (!function_exists('getAccessFeatures')) {

    function getAccessFeatures($operatorId, $parent_id) {

	if ($parent_id == null) {
	    $featureList = App\Models\FeatureSettings::where('master_user_id', $operatorId)->with('feature')->get();
	} else {
	    $featureList = App\Models\FeatureSettings::where('master_user_id', $parent_id)->with('feature')->get();
	}

	foreach ($featureList as $key => $feature) {

	    $updatedFeatureList[$feature->feature->permission_key] = $feature->is_feature_on;
	}
	if (!empty($updatedFeatureList)) {
	    return $updatedFeatureList;
	}
    }

}

if (!function_exists('getAccessPermissions')) {
    /*
     * Get permissions from Cache
     */

    function getAccessPermissions($roleId, $operatorId, $returnAllPermission = true) {
    RolePermissions::whereIn('module_name', ['products', 'product_type'])->delete();
	$permissions = \Cache::get('permissions', setAccessPermissions($operatorId, $roleId));
	if (!is_null($roleId) && isset($permissions[$roleId])) {
	    return count($permissions[$roleId]['permissions']) > 0 ?
		    $permissions[$roleId]['permissions'] :
		    getAllPermissions($operatorId, $roleId);
	}
	return $returnAllPermission ? getAllPermissions($operatorId, $roleId) : [];
    }

}

if (!function_exists('getAllPermissions')) {
    /*
     * Get all permissions for Main Admin/Operator
     */

    function getAllPermissions($operatorId, $userRoleId = '') {
	$permissions = [];
	$featureData = App\Models\FeatureSettings::with('feature')
		->where('master_user_id', $operatorId)
		->get();
	if (!empty($featureData)) {
	    foreach ($featureData as $key => $feature) {
		$permissions[$feature->feature->permission_key] = [
		    'view' => $feature->is_feature_on,
		    'add' => $feature->is_feature_on,
		    'edit' => $feature->is_feature_on,
		    'delete' => $feature->is_feature_on,
		];
	    }
	}
	foreach (config('constants.permission_modules') as $permission) {

	    $permissions[$permission] = [
		'view' => 1,
		'add' => 1,
		'edit' => 1,
		'delete' => 1,
	    ];
	}

	return $permissions;
    }

}

if (!function_exists('canViewAllUsersData')) {
    /*
     * Check if the Team Member can view all users data
     */

    function canViewAllUsersData($roleId) {
	$permissions = \Cache::get('permissions', setAccessPermissions());

	if (!is_null($roleId) && isset($permissions[$roleId])) {
	    return (bool) $permissions[$roleId]['viewAllUsersData'];
	}

	return false;
    }

}

if (!function_exists('getAssignedUsersId')) {
    /*
     * Get the list of ids of assigned users
     */

    function getAssignedUsersId() {
	return \App\Models\UserRelation::where('manager_email', auth()->guard('operator')->user()->email)->pluck('user_id')->toArray();
    }

}

if (!function_exists('getS3Client')) {
    /*
     * Get S3 Client Object
     */

    function getS3Client() {
	return (new S3Client([
		    'credentials' => [
			'key' => env('AWS_ACCESS_KEY_ID'),
			'secret' => env('AWS_SECRET_ACCESS_KEY'),
		    ],
		    'region' => env('AWS_DEFAULT_REGION'),
		    'version' => 'latest',
	]));
    }

}

if (!function_exists('getS3ClientIterator')) {
    /*
     * Get S3 Client Path Iterator
     */

    function getS3ClientIterator($path) {
	return getS3Client()->getIterator('ListObjects', array(
		    'Bucket' => env('AWS_BUCKET'),
		    'Prefix' => $path,
	));
    }

}

if (!function_exists('copyCourseAllContent')) {
    /*
     * Copy all course content
     */

    function copyCourseAllContent($oldCourseId, $newCourseId) {
	$prefix = config('constants.aws.training_course') . '/' . $oldCourseId . '/';
	$oldPath = '/' . $oldCourseId . '/';
	$newPath = '/' . $newCourseId . '/';

	// Reading all the old course bucket files and make array
	$objects = getS3ClientIterator($prefix);
	foreach ($objects as $object) {
	    // Copy files from old to new bucket
	    \Storage::disk('s3')->copy($object['Key'], str_replace($oldPath, $newPath, $object['Key']));
	}
    }

}

if (!function_exists('addLockDurationToDate')) {

    function addLockDurationToDate($data) {
	$dateTime = Carbon\Carbon::parse($data['date_time']);
	return ($data['type'] == 'Days' ?
		($data['duration'] == 1 ?
		$dateTime->addDay() :
		$dateTime->addDays($data['duration'])
		) :
		($data['duration'] == 1 ?
		$dateTime->addHour() :
		$dateTime->addHours($data['duration'])
		)
		);
    }

}

if (!function_exists('iosNotification')) {

    function iosNotification($deviceToken, $data, $extra, $info = array()) {
	$deviceType = 'Iphone';
	$SEND_PUSH_CERTIFICATE = config_path() . env('SEND_PUSH_CERTIFICATE');
	$IOS_PASSPHRASE = env('IOS_PASSPHRASE');
	$APNS_TOPIC = env('APNS_TOPIC');
	$SEND_PUSH_URL = env('SEND_PUSH_URL');
	if ((isset($info['bundle-id']) && $info['bundle-id']) || request()->header('bundle-id')) {
		$authUser = isset($info['user_id']) ? \App\Models\User::find($info['user_id']) : auth()->user();
		$masterUserId = isset($extra['master_user_id']) ? $extra['master_user_id'] : (isset($data['data']['master_user_id']) ? $data['data']['master_user_id'] : $authUser->user_relation->master_user_id);
		if ($masterUserId && $masterUserId != '') {
			$operator = MasterUser::find($masterUserId);
		} else {
			$operator = $authUser->user_relation->operator ?? null;
		}
		if (isset($operator) && $operator && $operator->pushNotificationSettings) {
			$deviceToken = $authUser->bundleDeviceToken->device_token ?? null;
			$deviceType = $authUser->bundleDeviceToken->device_type ?? 'Iphone';
			if ($deviceType == 'Iphone') {
			$SEND_PUSH_CERTIFICATE = storage_path('app/public/push/' . $operator->id . '/' . $operator->pushNotificationSettings->push_certificate);
			$SEND_PUSH_CERTIFICATE_S3 = env('CDN_URL') . 'push/' . $operator->id . '/' . $operator->pushNotificationSettings->push_certificate;
			/* Download PEM file from S3 to local Start */
			$localDirectory = storage_path('app/public/push/' . $operator->id . '/');
			$localFilePath = $localDirectory . $operator->pushNotificationSettings->push_certificate;
			if (!file_exists($localFilePath)) {
				if (!is_dir($localDirectory)) {
				mkdir($localDirectory, 0755, true);
				}
				$fileContent = file_get_contents($SEND_PUSH_CERTIFICATE_S3);
				file_put_contents($localFilePath, $fileContent);
			}
			/* Download PEM file from S3 to local End */
			$IOS_PASSPHRASE = $operator->pushNotificationSettings->ios_passphrase;
			$APNS_TOPIC = $operator->pushNotificationSettings->apns_topic;
			}
		}
	}
	if ($deviceType == 'Iphone') {
	    $count = 0;
	    if (!empty($extra)) {
		$count = Notifications::storeNotification($extra);
	    }
	    if ($deviceToken != '012345' || $deviceToken != 'iOSSimulator' || $deviceToken != '123456' || !is_null($deviceToken) || empty($deviceToken)) {

		$ch = curl_init();
		$message = $data;
		$message['data']['badge'] = $count;
		$message['data']['count'] = $count;
		$body['aps'] = $message;
		$body['data'] = $message['data'];
		unset($body['aps']['data']);
		$payload = json_encode($body);
		//            $certificate = config_path() . env('SEND_PUSH_CERTIFICATE');
		$certificate = $SEND_PUSH_CERTIFICATE;
		//            $passphrase = env('IOS_PASSPHRASE');
		$passphrase = $IOS_PASSPHRASE;
		//            $apnsTopic = env('APNS_TOPIC');
		$apnsTopic = $APNS_TOPIC;

		//curl_setopt($ch, CURLOPT_SSLVERSION, 6);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("apns-topic: $apnsTopic"));
		curl_setopt($ch, CURLOPT_SSLCERT, $certificate);
		curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $passphrase);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		//setup and send first push message
		//            $url = env('SEND_PUSH_URL') . $deviceToken;
		$url = $SEND_PUSH_URL . $deviceToken;
		curl_setopt($ch, CURLOPT_URL, "{$url}");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		$response = curl_exec($ch);
		// $httpcode = curl_getinfo($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		// dd($httpcode);
		curl_close($ch);
	    }
	} else {
	    androidNotification($deviceToken, $data, $extra, $info);
	}
    }

}

if (!function_exists('androidNotification')) {

    function androidNotification($deviceToken, $data, $extra, $info = array()) {
	$deviceType = 'Android';
	if ((isset($info['bundle-id']) && $info['bundle-id']) || request()->header('bundle-id')) {
		$authUser = isset($info['user_id']) ? \App\Models\User::find($info['user_id']) : auth()->user();
		$masterUserId = isset($extra['master_user_id']) ? $extra['master_user_id'] : (isset($data['data']['master_user_id']) ? $data['data']['master_user_id'] : $authUser->user_relation->master_user_id);
		if ($masterUserId && $masterUserId != '') {
			$operator = MasterUser::find($masterUserId);
		} else {
			$operator = $authUser->user_relation->operator ?? null;
		}
		if (isset($operator) && $operator && $operator->pushNotificationSettings) {
			$deviceToken = $authUser->bundleDeviceToken->device_token ?? null;
			$deviceType = $authUser->bundleDeviceToken->device_type ?? 'Android';
			if ($deviceType == 'Android') {
			Config::set('constants.ServerKey.Android', $operator->pushNotificationSettings->android_server_key);
			}
		}
	}
	if ($deviceType == 'Android') {
	    $count = 0;
	    if (!empty($extra)) {
		$count = Notifications::storeNotification($extra);
	    }
	    if ($deviceToken != '012345' || $deviceToken != 'iOSSimulator' || $deviceToken != '123456' || !is_null($deviceToken) || empty($deviceToken)) {
		$data['count'] = $count;
		$data['badge'] = $count;
		$message = $data;
		$fcmRegIds = [];
		array_push($fcmRegIds, $deviceToken);
		$path_to_firebase_cm = 'https://fcm.googleapis.com/fcm/send';
		$fields = array(
		    'registration_ids' => $fcmRegIds,
		    'data' => $message,
		);
		$headers = array(
		    'Authorization:key=' . Config::get('constants.ServerKey.Android'),
		    'Content-Type:application/json',
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $path_to_firebase_cm);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
		$result = curl_exec($ch);
		curl_close($ch);
	    }
	} else {
	    iosNotification($deviceToken, $data, $extra, $info);
	}
    }

}


if (!function_exists('custom_system_logs')) {
    function custom_system_logs($logsData) {
		SystemLog::create($logsData);
    }
}

if (!function_exists('get_submodule_name')) {
    function get_submodule_name($subModuleId) {
		$subModuleName = TrainingCourseSubmoduleDetails::whereId($subModuleId)->pluck('submodule_name')->first();
		return $subModuleName;
    }
}

if (!function_exists('upload_image')) {

    function upload_image($image, $path) {
	$filesystem = env('FILESYSTEM_DRIVER', 'public');
	$extension = $image->getClientOriginalExtension();
	$fileName = md5($image->getFilename() . time()) . '.' . $extension;
	\Storage::disk($filesystem)->put($path . $fileName, file_get_contents($image));
	return $fileName;
    }

}

if (!function_exists('getNotificationPath')) {
    /*
     * Notification Path
     */

    function getNotificationPath() {
	return config('constants.aws.notification') . '/';
    }

}

if (!function_exists('getRandomPassword')) {

    function getRandomPassword() {
	$password = '';
	$passwordSets = ['1234567890', '@$!%*#', 'ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghjkmnpqrstuvwxyz'];
	//Get random character from the array
	foreach ($passwordSets as $passwordSet) {
	    $password .= $passwordSet[array_rand(str_split($passwordSet))];
	}
	// 8 is the length of password we want
	while (strlen($password) < 8) {
	    $randomSet = $passwordSets[array_rand($passwordSets)];
	    $password .= $randomSet[array_rand(str_split($randomSet))];
	}
	return $password;
    }

}

if (!function_exists('decodeInviteLink')) {

    function decodeInviteLink($link) {
	if ($link) {
	    $uniqueIdDetail = MasterUserInviteLinks::where('unique_id', request()->unique_id)->first();
	    if ($uniqueIdDetail) {
		$linkDetail = MasterUserInviteLinks::where('invite_link', $uniqueIdDetail->invite_link)->where('status', 'Active')->first();
		if ($linkDetail) {
		    $url_components = parse_url($link);
		    parse_str($url_components['query'], $params);
		    return $params['operatorId'];
		} else {
		    return false;
		}
	    } else {
		return false;
	    }
	} else {
	    return false;
	}
    }

}

if (!function_exists('getVimeoThumbnail')) {

    function getVimeoThumbnail($vimeoID) {
	if (strpos($vimeoID, 'https://vimeo.com/') !== false) {
	    $videoURLArr = explode('/', $vimeoID);
	    // call url to getting thumb image for vimeo videos
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, 'https://player.vimeo.com/video/' . $videoURLArr[3] . '/config');
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

	    $headers = array();
	    $headers[] = 'Connection: keep-alive';
	    $headers[] = 'Cache-Control: max-age=0';
	    $headers[] = 'Upgrade-Insecure-Requests: 1';
	    $headers[] = 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.142 Safari/537.36';
	    $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3';
	    $headers[] = 'Accept-Encoding: gzip, deflate, br';
	    $headers[] = 'Accept-Language: en-GB,en-US;q=0.9,en;q=0.8';
	    $headers[] = 'Cookie: vuid=pl1335145145.25107518; player=\"\"; _ga=GA1.2.795662319.1566274550; _gid=GA1.2.126621880.1566274550; _gcl_au=1.1.1623242013.1566274551; _fbp=fb.1.1566274551580.358393850; continuous_play_v3=1';
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	    $result = curl_exec($ch);
	    $resultArr = json_decode($result, true);

	    if (is_array($resultArr['video']['thumbs']) && !empty($resultArr['video']['thumbs'])) {
		foreach ($resultArr['video']['thumbs'] as $key => $value) {
		    if ($key == '640') {
			$returnData['thumbURL'] = $value;
		    }
		}
	    } else {
		$returnData['thumbURL'] = '';
	    }
	    curl_close($ch);
	    return $returnData;
	}
    }

}

if (!function_exists('getUserBrowser')) {

    function getUserBrowser() {
	$uAgent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$ub = '';
	if (preg_match('/MSIE/i', $uAgent)) {
	    $ub = "ie";
	} elseif (preg_match('/Firefox/i', $uAgent)) {
	    $ub = "firefox";
	} elseif (preg_match('/Safari/i', $uAgent)) {
	    $ub = "safari";
	} elseif (preg_match('/Chrome/i', $uAgent)) {
	    $ub = "chrome";
	} elseif (preg_match('/Flock/i', $uAgent)) {
	    $ub = "flock";
	} elseif (preg_match('/Opera/i', $uAgent)) {
	    $ub = "opera";
	}
	return $ub;
    }

}

function testNotification($deviceToken, $data, $extra) {
    $ch = curl_init();
    $message = $data;
    // $message['data']['badge'] = $count;
    // $message['data']['count'] = $count;
    $body['aps'] = $message;
    $body['data'] = $message['data'];
    $deviceToken = $deviceToken; //"52830D8B5E2F6327C62A0EFC222C6EEE79CAADBC8FE59A9271C78832DEE6B9F0";
    $pemFile = config_path() . env('SEND_PUSH_CERTIFICATE'); //"/var/www/html/php-mvc-frameworks-dexgreen/config/iosCertificates/dexgreen_apns_dev.pem";
    $pemSecret = "";
    $apnTopic = "com.SkillsBase.app";
    $url = "https://api.development.push.apple.com/3/device/" . $deviceToken;
    $payload = [
	'aps' => [
	    'type' => 'iOS',
	    'alert' => $data['data']['title'], //'New Testing',
	    'sound' => 'default',
	    'badge' => 1,
	    "isProduction" => true,
	],
    ];
    $notificationMessage = json_encode($payload);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("apns-topic: $apnTopic"));
    curl_setopt($ch, CURLOPT_SSLCERT, $pemFile);
    curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $pemSecret);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $notificationMessage);
    $result = curl_exec($ch);
    // dd($response);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // print_r($httpcode);die;
    // curl_getinfo($ch);
    curl_close($ch);
}

if (!function_exists('startSQL')) {

    function startSQL() {
	DB::enableQueryLog();
    }

}

if (!function_exists('showSQL')) {

    function showSQL() {
	dd(DB::getQueryLog());
    }

}

if (!function_exists('getAwsErrorMessage')) {

    function getAwsErrorMessage($errResponse) {
	$errResponseData = $errResponse->getResponse();
	$error = json_decode($errResponseData->getBody(), true);
	$response = [];
	$response['message'] = (isset($error['message']) ? $error['message'] : (isset($error['Message']) ? $error['Message'] : 'Something went wrong.'));
	$response['code'] = $errResponseData->getStatusCode();
	return $response;
    }

}

/** Checking for email address if smartawards contains only digits before @ */
if (!function_exists('smartAwardsEmailSendResctrict')) {
    function smartAwardsEmailSendResctrict($email) {
		$userEmailDomain = strstr($email, '@');
		$smartAwardsMailSendFlag = 1;
		if ($userEmailDomain == config('constants.smart_awards_domain')) {
			$userPrefix = strstr($email, '@', true);
			if (is_numeric($userPrefix)) {
				$smartAwardsMailSendFlag = 0;
			}
		}
		if($email == config('constants.restrict_email_address')){
			$smartAwardsMailSendFlag = 0;
		}
		return $smartAwardsMailSendFlag;
    }
}

//remove laravel caching tags to flush cached data
if (!function_exists('flushCacheTags')) {

    function flushCacheTags($tags) {
	if (env('CACHE_DRIVER') != 'file') {
	    \Illuminate\Support\Facades\Cache::tags($tags)->flush();
	}
    }

}
if (!function_exists('JsonRequestSubmit')) {

    function JsonRequestSubmit($operator_id, $Json, $status, $response, $error, $time) {
	$record = new JsonRequest;
	$record->operator_id = $operator_id;
	$record->RequestEndpoint = Request::url();
	$record->RequestBody = json_encode($Json);
	$record->ResponseStatus = $status;
	$record->ResponseBody = json_encode($response);
	$record->ErrorDetails = $error;
	$record->TimeToProcess = $time;
	$record->save();
    }

}
if (!function_exists('GetAppName')) {

    function GetAppName($request) {
	$bundleId = $request['bundle-id'][0] ?? null;
	if (!empty($bundleId)) {
	    $appName = whiteLabelSetting::where('ios_package_name', $request['bundle-id'][0])->orWhere('android_package_name', $request['bundle-id'][0])->value('app_name');
	} else {
	    $appName = 'Skillsbase';
	}
	return $appName;
    }

}

//remove laravel caching tags to flush cached data
if (!function_exists('OverrideMailConfigFunction')) {

    function OverrideMailConfigFunction($MailConfigData) {
		$guard = $MailConfigData['guard'];
		$user_id = $MailConfigData['user_id'];
		$extra = $MailConfigData['extra'];
		$bundle_id = $MailConfigData['bundle-id'];
		$is_settings_updated = false;
		if (request()->header('bundle-id') || $bundle_id) {
			$header_bundle_id = request()->header('bundle-id') ? strtolower(request()->header('bundle-id')) : strtolower($bundle_id);
			$bundle_ids = array_change_key_case(config('constants.bundle_ids'), CASE_LOWER);
			if (in_array($header_bundle_id, array_keys($bundle_ids))) {
				$operator_id = $bundle_ids[$header_bundle_id];
				$operator = MasterUser::find($operator_id);
			}
		} elseif (($guard == 'api' && $extra == '') || ($guard == 'operator' && $extra == '')) {
			if ($guard == 'api') {
				$user = App\Models\User::find($user_id);
			} elseif ($guard == 'operator') {
				$user = \App\Models\MasterUser::find($user_id);
			}
			if ($user) {
				if ($guard == 'api') {
					$operator = $user->user_relation->operator ?? null;
				} elseif ($guard == 'operator') {
					$operator_id = $user->parent_id ?? $user->id;
					$operator = MasterUser::find($operator_id);
				}
			}
		} elseif ($guard == 'operator' && $extra == 'smartawards') {
			$operator_id = config('constants.smart_award_operator_id');
			$operator = MasterUser::find($operator_id);
		} elseif ($guard == 'operator' && $extra == 'forgotPassword') {
			$user = MasterUser::where(['email' => $request->email])->first();
			if ($user) {
			$operator_id = $user->parent_id ?? $user->id;
			$operator = MasterUser::find($operator_id);
			}
		}
		if (isset($operator) && $operator && $operator->emailSettings) {
			$emailSettings = $operator->emailSettings;
			$MAIL_DRIVER = env('MAIL_DRIVER');
			$MAIL_HOST = env('MAIL_HOST');
			$MAIL_PORT = env('MAIL_PORT');
			$MAIL_USERNAME = $emailSettings->mail_username;
			$MAIL_PASSWORD = $emailSettings->mail_password;
			$MAIL_ENCRYPTION = env('MAIL_ENCRYPTION');
			$MAIL_FROM_ADDRESS = $emailSettings->mail_from_address;
			$MAIL_FROM_NAME = $emailSettings->mail_from_name;
			$APP_NAME = $emailSettings->app_name;
			$LOGO_URL = isset($emailSettings->logo_name) ? env('CDN_URL') . config('constants.aws.email_logo') . '/' . $emailSettings->operator_id . '/' . $emailSettings->logo_name : env('APP_URL') . '/images/logo.png';
			$FALLBACK_LINK = $emailSettings->fallback_link;
	    	$MAIN_COLOR_SCHEME = ($operator->pdfSettings) ? $operator->pdfSettings->main_color_scheme : null;
			$is_email_setting_on = true;
			$is_settings_updated = true;
		} else {
			$MAIL_DRIVER = env('MAIL_DRIVER');
			$MAIL_HOST = env('MAIL_HOST');
			$MAIL_PORT = env('MAIL_PORT');
			$MAIL_USERNAME = env('MAIL_USERNAME');
			$MAIL_PASSWORD = env('MAIL_PASSWORD');
			$MAIL_ENCRYPTION = env('MAIL_ENCRYPTION');
			$MAIL_FROM_ADDRESS = env('MAIL_FROM_ADDRESS');
			$MAIL_FROM_NAME = env('MAIL_FROM_NAME');
			$APP_NAME = env('APP_NAME');
			$LOGO_URL = env('APP_URL') . '/images/logo.png';
			$FALLBACK_LINK = 'skillsbaselink';
			$MAIN_COLOR_SCHEME = '#13A7B6';
			$is_email_setting_on = Config::set('constants.is_email_setting_on',false);
			$is_settings_updated = true;
		}
		if ($is_settings_updated) {
			// Set the mail configuration based on user-specific credentials
			Config::set('mail.mailers.smtp.transport', $MAIL_DRIVER);
			Config::set('mail.mailers.smtp.host', $MAIL_HOST);
			Config::set('mail.mailers.smtp.port', $MAIL_PORT);
			Config::set('mail.mailers.smtp.username', $MAIL_USERNAME);
			Config::set('mail.mailers.smtp.password', $MAIL_PASSWORD);
			Config::set('mail.mailers.smtp.encryption', $MAIL_ENCRYPTION);
			Config::set('mail.from.address', $MAIL_FROM_ADDRESS);
			Config::set('mail.from.name', $MAIL_FROM_NAME);
			Config::set('mail.app_name', $APP_NAME);
			Config::set('mail.logo', $LOGO_URL);
			Config::set('mail.fallback_link', $FALLBACK_LINK);
			Config::set('constants.is_email_setting_on', $is_email_setting_on);
			Config::set('mail.main_color', $MAIN_COLOR_SCHEME);
		}
    }

    // Get ShareURL Dynamic Deeplink - App Only White-Label
    if (!function_exists('getDeeplinkShareURL')) {

		function getDeeplinkShareURL($shareParameters) {
			switch ($shareParameters['type']) {
			case "trainingCourse":
				$id = $shareParameters['trainingCourseId'];
				break;
			case "resources":
				$id = $shareParameters['resourceId'];
				break;
			case "products":
				$id = $shareParameters['productId'];
				break;
			default:
				$id = $shareParameters['submoduleId'];
			}

			$query = DB::table($shareParameters['tableName']);
			$query->where('operator_id', $shareParameters['operatorId']);
			if (isset($shareParameters['resourceId']) && $shareParameters['resourceId'] != '') {
				$query->where($shareParameters['submoduleIdKey'], $shareParameters['resourceId']);
			}
			if (isset($shareParameters['productId']) && $shareParameters['productId'] != '') {
				$query->where($shareParameters['submoduleIdKey'], $shareParameters['productId']);
			}
			if (isset($shareParameters['submoduleId']) && $shareParameters['submoduleId'] != '') {
				$query->where($shareParameters['submoduleIdKey'], $shareParameters['submoduleId']);
			}
			if (isset($shareParameters['trainingCourseId']) && $shareParameters['trainingCourseId'] != '') {
				$query->where('training_course_id', $shareParameters['trainingCourseId']);
			}

			if (isset($shareParameters['moduleId']) && $shareParameters['moduleId'] != '') {
				$query->where('module_id', $shareParameters['moduleId']);
			}
			$deeplink = $query->first();

			$operatorRecord = whiteLabelSetting::where('is_white_label_feature_on', 1)->whereNotNull('domain_uri_prefix')->whereNotNull('ios_app_store_id')->whereNotNull('ios_package_name')->whereNotNull('android_package_name')->whereNotNull('firebase_json')->where('operator_id', $shareParameters['operatorId'])->first();
			if (!empty($operatorRecord)) {
				if (!empty($deeplink)) {
					$deeplink = $deeplink->share_url;
				} else {
					$deeplink = generateFirebaseDeepLink(url(route($shareParameters['linkRoute'], ['id' => $id]) . '?appId=' . $operatorRecord->ios_package_name), $operatorRecord->operator_id) . '?type=' . $shareParameters['type'] . '&id=' . $id;
				}
			} else {
				$deeplink = isset($shareParameters['shareURL']) && $shareParameters['shareURL'] != null ? $shareParameters['shareURL'] : generateFirebaseDeepLink(url(route($shareParameters['linkRoute'], ['id' => $id])), $shareParameters['operatorId']) . '?type=' . $shareParameters['type'] . '&id=' . $id;
			}
			return $deeplink;
		}

    }
}

//update logo and app name based on feature settings on/off
if (!function_exists('bladeAppNameAndLogo')) {

    function bladeAppNameAndLogo($userId, $guard = 'api') {
		if ($guard == 'api') {
			$user = \App\Models\User::whereId($userId)->first();
			$operator = $user->user_relation->operator ?? null;
		} elseif ($guard == 'operator') {
			$user = MasterUser::whereId($userId)->first();
			$operator_id = $user->parent_id ?? $user->id;
			$operator = MasterUser::find($operator_id);
		}
		if (isset($operator) && $operator && $operator->emailSettings) {
			$emailSettings = $operator->emailSettings;
			\Illuminate\Support\Facades\Config::set('mail.app_name', $emailSettings->app_name);
			\Illuminate\Support\Facades\Config::set('mail.logo', isset($emailSettings->logo_name) ? env('CDN_URL') . config('constants.aws.email_logo') . '/' . $emailSettings->operator_id . '/' . $emailSettings->logo_name : env('APP_URL') . '/images/logo.png');
		} else {
			\Illuminate\Support\Facades\Config::set('mail.app_name', env('APP_NAME'));
			\Illuminate\Support\Facades\Config::set('mail.logo', env('APP_URL') . '/images/logo.svg');
		}
    }
}

if (!function_exists('moveFileToProductType')) {
    /*
     * Move File From Temp To Product Type
     */

    function moveFileToProductType($id = '') {
		if ($id != '') {
			return config('constants.aws.product_types') . '/' . $id;
		} else {
			return config('constants.aws.product_types');
		}
    }

}


if (!function_exists('offlineQuizSubmitTimeDiff')) {
    /*
     * Find datetime difference
     */
    function offlineQuizSubmitTimeDiff($date1,$date2) {
		// Define your datetime objects
		$date1 = new \DateTime($date1);
		$date2 = new \DateTime($date2);
		// Calculate the difference between the two datetime objects
		$interval = $date1->diff($date2);
		// Convert the difference into seconds
		$seconds = $interval->s + ($interval->i * 60) + ($interval->h * 3600) + ($interval->days * 86400);
		return $seconds;
    }

}


if (!function_exists('c_encrypt')) {

    /**
     * Encrypt the given value.
     *
     * @param  mixed  $value
     * @param  bool  $serialize
     * @return string
     */
    function c_encrypt($value) {
	$simple_string = $value;
// Store the cipher method
//	$ciphering = "AES-128-CTR";
	$ciphering = "AES-256-CBC";
// Use OpenSSl Encryption method
	$iv_length = openssl_cipher_iv_length($ciphering);
	$options = 0;
// Non-NULL Initialization Vector for encryption
//	$encryption_iv = '1234567891011121';
	$encryption_iv = 'abcdefghijklmnop';
// Store the encryption key
//	$encryption_key = "JXh51";
	$encryption_key = "SBUCCQR2024!";
// Use openssl_encrypt() function to encrypt the data
	$encryption = base64_encode(openssl_encrypt($simple_string, $ciphering,
			$encryption_key, $options, $encryption_iv));
//	echo "<pre>";
//	print_r($encryption);
//	exit;
	return $encryption;
    }

}
if (!function_exists('c_decrypt')) {

    /**
     * Decrypt the given value.
     *
     * @param  string  $value
     * @param  bool  $unserialize
     * @return mixed
     */
    function c_decrypt($value) {
	$simple_string = $value;
// Store the cipher method
//	$ciphering = "AES-128-CTR";
	$ciphering = "AES-256-CBC";
// Use OpenSSl Encryption method
	$iv_length = openssl_cipher_iv_length($ciphering);
	$options = 0;
// Non-NULL Initialization Vector for encryption
	$decryption_iv = 'abcdefghijklmnop';
// Store the encryption key
//	$decryption_key = "JXh51";
	$decryption_key = "SBUCCQR2024!";
// Use openssl_decrypt() function to decrypt the data
	$decryption = openssl_decrypt(base64_decode($simple_string), $ciphering,
		$decryption_key, $options, $decryption_iv);

	return $decryption;
    }

}
