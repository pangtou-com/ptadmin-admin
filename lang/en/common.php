<?php

declare(strict_types=1);

return [
    'success' => 'Operation completed successfully',
    'auth' => [
        'super_admin_role_name' => 'Super Administrator',
        'default_role_description' => 'Default initialized role',
    ],
    'system_config' => [
        'group' => [
            'system_title' => 'System Settings',
        ],
        'section' => [
            'basic_title' => 'Basic Settings',
            'basic_intro' => 'Basic site information and common switch settings',
            'upload_title' => 'Upload Settings',
            'upload_intro' => 'File upload and object storage related settings',
        ],
        'field' => [
            'site_title_title' => 'Site Title',
            'site_title_intro' => 'Used for the browser title and admin display',
            'site_description_title' => 'Site Description',
            'site_description_intro' => 'Used for site introduction and SEO description',
            'login_captcha_title' => 'Login Captcha',
            'login_captcha_intro' => 'Controls whether the admin login shows a captcha',
            'storage_driver_title' => 'Upload Storage Driver',
            'storage_driver_intro' => 'local uses the local disk; if a storage inject code from an addon is provided, addon upload will be used',
            'storage_code_title' => 'Upload Storage Code',
            'storage_code_intro' => 'Optional. Explicitly specify the storage inject code; if empty, the upload storage driver is used by default',
            'storage_disk_title' => 'Upload Storage Disk',
            'storage_disk_intro' => 'Object storage disk identifier, such as oss, cos, or s3',
            'storage_bucket_title' => 'Upload Storage Bucket',
            'storage_bucket_intro' => 'Object storage bucket name. Leave empty if not needed',
            'storage_visibility_title' => 'Upload Visibility',
            'storage_visibility_intro' => 'Default visibility of files in object storage',
            'storage_meta_title' => 'Upload Extra Parameters',
            'storage_meta_intro' => 'Extra parameters passed to the addon storage inject, in JSON object format',
        ],
        'option' => [
            'visibility_public' => 'Public',
            'visibility_private' => 'Private',
        ],
    ],
    'resource' => [
        'top_level' => 'Top Level',
    ],
    'command' => [
        'admin_init_success' => 'Administrator account initialized successfully',
        'admin_init_summary' => 'Administrator account information:',
        'admin_init_username' => 'Username: :username',
        'admin_init_password' => 'Password: :password',
        'admin_init_keep_safe' => 'Keep your account information safe and do not disclose it to others',
        'admin_auth_bound' => 'Role [:role] has been assigned to admin user [:user_id].',
        'admin_auth_done' => 'Default role [:role] has been initialized.',
        'admin_auth_resource_count' => 'Granted resource count: :count',
    ],
    'login_notice' => [
        'title' => 'Admin login required',
        'description' => 'The current request has not been authenticated. If you opened an admin API address directly in the browser, this page is only a troubleshooting hint.',
        'api_path' => 'Admin login API:',
        'web_entry' => 'Admin frontend entry:',
        'redirect_target' => 'Original requested path:',
        'open_admin' => 'Open admin frontend',
        'view_login_api' => 'View login API path',
    ],
];
