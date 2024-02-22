<?php

    require ('WebsiteDownloader.php');

    $downloader = new WebsiteDownloader([
        'domain' => 'https://example.com',
        'output_dir' => 'local_files'
    ]);

    // download all assets
    $downloader->download();

    // download asset from url
    //$downloader->downloadAssetFromURL('https://example.com/index.html');

?>