<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary File Upload
    |--------------------------------------------------------------------------
    |
    | Livewire handles file uploads by storing the file in a temporary
    | directory before the form is submitted. These settings control
    | validation, disk, and cleanup for those temporary uploads.
    |
    | The max size (102400 KB = 100 MB) matches the existing vendor report
    | upload validation in UploadVendorReportRequest.
    |
    | IMPORTANT: The web server's PHP must also be configured with:
    |   upload_max_filesize = 105M
    |   post_max_size       = 210M   (must exceed upload_max_filesize)
    |   memory_limit        = 256M
    |
    */

    'temporary_file_upload' => [
        'disk'            => null,
        'rules'           => 'required|file|max:102400',
        'directory'       => null,
        'middleware'       => null,
        'preview_mimes'   => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 10,
        'cleanup'         => true,
    ],

];
