<?php

/**
 * The WebsiteDownloader class facilitates the downloading of various assets (HTML pages, CSS files, JavaScript files, and images)
 * from a specified website. It also provides methods to modify URLs for local access and selectively download assets based on user requirements.
 */

class WebsiteDownloader {

    /** @var string $domain The domain of the website from which assets will be downloaded. */
    private $domain;

    /** @var string $outputDir The directory where downloaded assets will be saved. */
    private $outputDir;

    /** @var string $mutePrint Indicate wether to echo messages or not. */
    private $mutePrint;

    /**
     * Constructor for the WebsiteDownloader class.
     *
     * @param array $args An associative array containing 'domain' and 'output_dir' keys.
     *                    'domain': The domain of the website to download assets from.
     *                    'output_dir': The directory where downloaded assets will be saved.
     *                    'silence_console_output': Indicate wether to echo messages or not
     * @throws InvalidArgumentException If 'domain' or 'output_dir' is not provided.
     */
    public function __construct($args) {
        if (! isset($args['domain'])) {
            throw new InvalidArgumentException("Domain is required.");
        }

        if (! isset($args['output_dir'])) {
            throw new InvalidArgumentException("Output Directory is required.");
        }

        $this->mutePrint = (! isset($args['silence_console_output'])) ? true
            : $args['silence_console_output'];

        $this->domain = $args['domain'];
        $this->outputDir = $args['output_dir'];
        

        // Create the output directory if it doesn't exist
        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }
    }

    /**
     * Downloads the specified type of asset or all assets from the website.
     *
     * @param string $asset The type of asset to download. Default is 'all'.
     *                      Supported values: 'all', 'html', 'css', 'js', 'img'.
     * @throws InvalidArgumentException If an unsupported asset type is provided.
     */
    public function download($asset = 'all') {
        switch ($asset) {
            case 'all':
                $this->downloadHtml();
                $this->downloadCSSAndFavIcons();
                $this->downloadJS();     
                $this->downloadImages();
                break;
            case 'html':
                $this->downloadHtml();
                break;
            case 'css':
                $this->downloadHtml();
                $this->downloadCSSAndFavIcons();
                $this->removeHtml();
                break;
            case 'js':
                $this->downloadHtml();
                $this->downloadJS();
                $this->removeHtml();
                break;
            case 'img':
                $this->downloadHtml();
                $this->downloadImages();
                $this->removeHtml();
                break;
            default:
                throw new InvalidArgumentException("Asset $asset not supported.");
        }
    }

    /**
     * Removes downloaded HTML files and images from the output directory.
     */
    private function removeHtml(){
        $files = glob ("$this->outputDir/*.html");
        foreach ($files as $file){
            unlink($file);
        }
       
        $files = glob ("$this->outputDir/*.{jpg,png,gif}", GLOB_BRACE);
        foreach ($files as $file){
            unlink($file);
        }
    }

    /**
     * Downloads HTML files from the specified website and saves them to the output directory.
     */
    private function downloadHtml(){
        if ($this->mutePrint)
        {
            echo "---------------------------------------\n";
            echo "Downloading HTML files...\n";
            echo "---------------------------------------\n";
        }

        $content = file_get_contents($this->domain);
        $dom = new DOMDocument();
        @$dom->loadHTML($content);

        $links = $dom->getElementsByTagName("a");

        foreach ($links as $link) {
            $href = $link->getAttribute("href");
        
            if (!strpos($href, "http")) {
              $urlParts = explode("//", $href);
              $uniqueParts = array_unique($urlParts);
              $fixedUrl = implode("//", $uniqueParts);
              
              $href = ($this->startsWith ($fixedUrl, [$this->domain])) ? $fixedUrl : "$this->domain/$href"; // Handle relative links
            }

            if (!empty($href) && $href != "/") {
              $filename = basename($href);
              
              if (strpos($filename, ".") === false || strpos($filename, ".co.za") !== false || strpos($filename, ".com") !== false) $filename = "$filename.html";
          
              if (! $this->startsWith ($filename, ['#'])) {
                $filename = str_replace('?', '.', $filename);
                $this->downloadFile($this->outputDir, $href, $filename);
                if ($this->mutePrint)
                {
                    echo "$href\n";
                }
              }
            }
        }

        if ($this->mutePrint)
        {
            echo "---------------------------------------\n";
            echo "Downloading HTML Completed!\n";
            echo "---------------------------------------\n";
        }
    }

    /**
     * Downloads a specific asset from the provided URL and saves it to the output directory.
     *
     * @param string $href The URL of the asset to download.
     */
    public function downloadAssetFromURL($href){
        if (! empty($href) && $href != "/"){
            $filename = basename($href);
            $this->downloadFile($this->outputDir, $href, $filename);

            $file = "$this->outputDir/$filename";

            if ($this->mutePrint)
            {
                echo "---------------------------------------\n";
                echo "File Downloaded: $file\n";
                echo "---------------------------------------\n";
            }
        }else{
            if ($this->mutePrint)
            {
                echo "---------------------------------------\n";
                echo "Error: Invalid URL provided.\n";
                echo "---------------------------------------\n";
            }
        }
    }

    /**
     * Downloads assets referenced in the HTML content and saves them to the output directory.
     *
     * @param array $files An array of HTML files from which assets will be downloaded.
     * @param string $tagName The HTML tag name (e.g., 'link', 'script', 'img') to search for.
     * @param string $attribute The attribute name (e.g., 'href', 'src') containing asset URLs.
     */
    private function downloadAssetsFromDOM($files, $tagName, $attribute){
        foreach ($files as $file){
            $content = file_get_contents($file);

            $dom = new DOMDocument();
            @$dom->loadHTML($content);
            $links = $dom->getElementsByTagName($tagName);
            
            foreach ($links as $link) {
                $href = $link->getAttribute($attribute);
                
                if (! $this->startsWith ($href, ['http', '//'])) {
                    $href = "$this->domain/$href"; // Handle relative links
                    
                    if (! empty ($href) && $href != "/"){
                        $dirname = dirname($href);
                        $dirname = str_replace($this->domain, '', $dirname);
                        
                        $filename = basename($href);
                        // remove the version number from the filename (e.g filename.css?ver=5.15.3 to filename.css)
                        $filename = preg_replace("/\?.*/", "", $filename);
                        if ((! empty($dirname) || $dirname != '') && ! $this->startsWith ($dirname, ['https'])){
                            $fullPath = "$this->outputDir/$dirname";
                        
                            if (!file_exists($fullPath)){
                                if (! mkdir($fullPath, 0777, true)) { // Use true for recursive creation
                                    die("Failed to create folders: $fullPath"); // Handle potential errors
                                }
                            }

                            $this->downloadFile($fullPath, $href, $filename);
                        }else{
                            $this->downloadFile($this->outputDir, $href, $filename);
                        }

                        if ($this->mutePrint)
                        {
                            echo "$href\n";
                        }
                    }
                }

                if ($this->startsWith ($href, ['http'])){
                    $dirname = dirname($href);
                    $dirname = str_replace($this->domain, '', $dirname);
                    
                    $filename = basename($href);
                    // remove the version number from the filename (e.g filename.css?ver=5.15.3 to filename.css)
                    $filename = preg_replace("/\?.*/", "", $filename);
                    if ((! empty($dirname) || $dirname != '') && ! $this->startsWith ($dirname, ['https'])){
                        $fullPath = "$this->outputDir/$dirname";
                    
                        if (!file_exists($fullPath)){
                            if (! mkdir($fullPath, 0777, true)) { // Use true for recursive creation
                                die("Failed to create folders: $fullPath"); // Handle potential errors
                            }
                        }

                        $this->downloadFile($fullPath, $href, $filename);
                    }else{
                        $this->downloadFile($this->outputDir, $href, $filename);
                    }

                    if ($this->mutePrint) 
                    {
                        echo "$href\n";
                    }
                }
            }
        }
    }

    /**
     * Downloads embedded HTML files referenced in the HTML content and saves them to the output directory.
     * This method specifically searches for anchor tags (<a>) in downloaded HTML files and downloads HTML files
     * linked from those anchor tags. After downloading, it prints messages indicating the start and completion
     * of the download process.
     */
    private function downloadEmbededHTML(){
        if ($this->mutePrint)
        {   
            echo "---------------------------------------\n";
            echo "Downloading Embeded HTML files...\n";
            echo "---------------------------------------\n";
        }

        $files = glob ("$this->outputDir/*.html");
        $this->downloadAssetsFromDOM($files, 'a', 'href');

        if ($this->mutePrint)
        {
            echo "---------------------------------------\n";
            echo "Downloading Embeded HTML files Complete!\n";
            echo "---------------------------------------\n";
        }
    }

    /**
     * Downloads CSS and image files referenced in the HTML content and saves them to the output directory.
     * This method specifically searches for 'link' tags for CSS files and 'img' tags for image files in downloaded HTML files.
     * After downloading, it prints messages indicating the start and completion of the download process.
     */
    private function downloadCSSAndFavIcons(){
        if ($this->mutePrint)
        {
            echo "---------------------------------------\n";
            echo "Downloading CSS and Image files...\n";
            echo "---------------------------------------\n";
        }

        $files = glob ("$this->outputDir/*.html");
        $this->downloadAssetsFromDOM($files, 'link', 'href');

        if ($this->mutePrint)
        {
            echo "---------------------------------------\n";
            echo "Downloading CSS and Image files Complete!\n";
            echo "---------------------------------------\n";
        }
    }

    /**
     * Downloads JavaScript files referenced in the HTML content and saves them to the output directory.
     * This method specifically searches for 'script' tags in downloaded HTML files.
     * After downloading, it prints messages indicating the start and completion of the download process.
     */
    private function downloadJS(){
        if ($this->mutePrint)
        {
            echo "---------------------------------------\n";
            echo "Downloading JS files...\n";
            echo "---------------------------------------\n";
        }

        $files = glob ("$this->outputDir/*.html");
        $this->downloadAssetsFromDOM($files, 'script', 'src');

        if ($this->mutePrint)
        {
            echo "---------------------------------------\n";
            echo "Downloading JS files Complete!\n";
            echo "---------------------------------------\n";
        }
    }

    /**
     * Downloads image files referenced in the HTML content and saves them to the output directory.
     * This method specifically searches for 'img' tags in downloaded HTML files.
     * After downloading, it prints messages indicating the start and completion of the download process.
     */
    private function downloadImages(){
        if ($this->mutePrint)
        {
            echo "---------------------------------------\n";
            echo "Downloading Image files...\n";
            echo "---------------------------------------\n";
        }

        $files = glob ("$this->outputDir/*.html");
        $this->downloadAssetsFromDOM($files, 'img', 'src');

        if ($this->mutePrint)
        {
            echo "---------------------------------------\n";
            echo "Downloading Image files Complete!\n";
            echo "---------------------------------------\n";
        }
    }

    private function startsWith($word, $startsWith) {
        $isStartWith = false;
    
        foreach ($startsWith as $char){
            if (substr($word, 0, strlen($char)) === $char){
                $isStartWith = true;
                break;
            }
        }
        return $isStartWith;
    }

    /**
     * Downloads a file from a specified URL and saves it to the specified directory with the given filename.
     * 
     * @param string $directory The directory where the downloaded file will be saved.
     * @param string $url The URL from which the file will be downloaded.
     * @param string $filename The name of the file to be saved.
     */
    private function downloadFile($directory, $url, $filename) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);
        
        file_put_contents($directory . '/' . $filename, $content);
    }
}

?>