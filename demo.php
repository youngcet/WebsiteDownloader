<?php

    require ('WebsiteDownloader.php');

    $downloader = new WebsiteDownloader([
        'domain' => 'https://example.com',
        'output_dir' => 'local_files'
    ]);

    $downloader->download();
    $downloader->downloadAssetFromURL('https://example.com/index.html');

?>