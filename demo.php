<?php

    require ('WebsiteDownloader.php');

    $downloader = new WebsiteDownloader([
        'domain' => 'https://www.domain.co.za/',
        'output_dir' => 'local_files'
    ]);

    // download all assets
    $downloader->download();

    // download asset from url
    //$downloader->downloadAssetFromURL('https://www.spruko.com/demo/azira/dist/assets/libs/choices.js/public/assets/scripts/choices.min.js');

?>