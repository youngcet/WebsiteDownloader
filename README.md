### Website Downloader

This PHP class, `WebsiteDownloader`, is designed to download various assets (HTML files, CSS files, JavaScript files, images, etc.) from a given website. It provides methods to download specific types of assets or all assets at once.

#### Usage

Instantiate the `WebsiteDownloader` class by providing the required parameters: the domain of the website to download from and the output directory where the downloaded files will be saved.

```php
$downloader = new WebsiteDownloader([
    'domain' => 'https://example.com',
    'output_dir' => '/path/to/output/directory'
]);
```

#### Methods

- **download($asset):** Downloads the specified type of asset. If no asset type is provided, it downloads all assets. Supported asset types are 'html', 'css', 'js', and 'img'.

- **downloadAssetFromURL($url):** Downloads a specific asset from the provided URL.

#### Example
#### download($asset);
Downloading a specified type of asset from the given domain.

```php
try {
    // downloads all assets (html, css, js, images, etc)
    $downloader->download();

    // download html
    $downloader->download('html');

    // download js
    $downloader->download('js');
    
    // download css (this will include favicons if found)
    $downloader->download('css');

    // download images
    $downloader->download('img');

} catch (InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage();
}
```

#### downloadAssetFromURL($href);
Downloads a specific asset from the provided URL

```php
// downloads the index.html file
// you can pass any file in the url to download such as images, js, css, etc
$downloader->downloadAssetFromURL('https://example.com/index.html');
```

#### Requirements

- PHP 7.0 or higher
- cURL extension enabled

#### Note

- This class uses cURL to download files from the website. Make sure cURL extension is enabled in your PHP configuration.
- Be cautious while using this class to avoid downloading files from unauthorized websites or violating terms of service.
- Downloading files from a website may not always be possible due to restrictions set by the website owner or technical limitations in its design.

#### Author

This PHP class was created by Yung Cet (Cedric Maenetja).

#### License

This PHP class is released under the MIT License. See the [LICENSE](https://github.com/youngcet/WebsiteDownloader/blob/master/LICENSE) file for details.